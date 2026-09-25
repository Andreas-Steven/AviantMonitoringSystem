<?php

namespace App\Domains\Requests\Services;

use App\Domains\Master\Models\Employee;
use App\Domains\Requests\Models\EmployeeLeaveBalance;
use App\Domains\Requests\Models\EmployeeLeaveBalanceTransaction;
use App\Domains\Requests\Models\LeaveType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LeaveOpeningBalanceImportService
{
    public function import(array $payload): array
    {
        $file = $payload['file'];

        if (! $file instanceof UploadedFile) {
            throw new \InvalidArgumentException('Invalid uploaded file.');
        }

        $leaveType = LeaveType::query()->findOrFail($payload['leave_type_id']);

        $rows = $this->readRows($file);

        $result = [
            'total_rows' => 0,
            'success_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
            'skipped' => [],
        ];

        DB::transaction(function () use ($rows, $payload, $leaveType, &$result) {
            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2;

                $employeeCode = trim((string)($row['employee_code'] ?? ''));
                $remainingLeaveRaw = $row['remaining_leave'] ?? null;

                if ($employeeCode === '' && ($remainingLeaveRaw === null || $remainingLeaveRaw === '')) {
                    continue;
                }

                $result['total_rows']++;

                if ($employeeCode === '') {
                    $result['failed_rows']++;
                    $result['errors'][] = "Row {$excelRowNumber}: employee_code kosong.";
                    continue;
                }

                if (! is_numeric($remainingLeaveRaw)) {
                    $result['failed_rows']++;
                    $result['errors'][] = "Row {$excelRowNumber}: remaining_leave harus angka.";
                    continue;
                }

                $remainingLeave = round((float)$remainingLeaveRaw, 2);

                if ($remainingLeave < 0) {
                    $result['failed_rows']++;
                    $result['errors'][] = "Row {$excelRowNumber}: remaining_leave tidak boleh negatif.";
                    continue;
                }

                if ($remainingLeave == 0.0) {
                    $result['skipped_rows']++;
                    $result['skipped'][] = "Row {$excelRowNumber}: {$employeeCode} dilewati karena saldo 0.";
                    continue;
                }

                $employee = Employee::query()
                    ->where('emp_code', $employeeCode)
                    ->first();

                if (! $employee) {
                    $result['failed_rows']++;
                    $result['errors'][] = "Row {$excelRowNumber}: employee_code {$employeeCode} tidak ditemukan.";
                    continue;
                }

                $balance = EmployeeLeaveBalance::query()->firstOrCreate(
                    [
                        'emp_id' => $employee->emp_id,
                        'leave_type_id' => $leaveType->leave_type_id,
                        'period_start_date' => $payload['period_start_date'],
                        'period_end_date' => $payload['period_end_date'],
                    ],
                    [
                        'opening_balance' => 0,
                        'granted_amount' => 0,
                        'used_amount' => 0,
                        'adjustment_amount' => 0,
                        'expired_amount' => 0,
                        'closing_balance' => 0,
                        'active' => true,
                        'notes' => 'Auto-created by leave opening balance import.',
                    ]
                );

                $sourceRefId = $this->sourceRefId(
                    $payload['period_start_date'],
                    $payload['period_end_date'],
                    $leaveType->leave_type_code,
                    $employee->emp_code
                );

                $exists = EmployeeLeaveBalanceTransaction::query()
                    ->where('employee_leave_balance_id', $balance->employee_leave_balance_id)
                    ->where('transaction_type_code', 'OPENING')
                    ->where('source_type_code', 'IMPORT')
                    ->where('source_ref_id', $sourceRefId)
                    ->exists();

                if ($exists) {
                    $result['skipped_rows']++;
                    $result['skipped'][] = "Row {$excelRowNumber}: {$employeeCode} sudah pernah diimport untuk periode ini.";
                    continue;
                }

                $hasAnyOpening = EmployeeLeaveBalanceTransaction::query()
                    ->where('employee_leave_balance_id', $balance->employee_leave_balance_id)
                    ->where('transaction_type_code', 'OPENING')
                    ->exists();

                if ($hasAnyOpening) {
                    $result['skipped_rows']++;
                    $result['skipped'][] = "Row {$excelRowNumber}: {$employeeCode} sudah punya OPENING. Gunakan adjustment untuk koreksi.";
                    continue;
                }

                EmployeeLeaveBalanceTransaction::query()->create([
                    'employee_leave_balance_id' => $balance->employee_leave_balance_id,
                    'emp_id' => $employee->emp_id,
                    'leave_type_id' => $leaveType->leave_type_id,
                    'transaction_type_code' => 'OPENING',
                    'transaction_date' => $payload['transaction_date'],
                    'qty' => $remainingLeave,
                    'source_type_code' => 'IMPORT',
                    'source_ref_id' => $sourceRefId,
                    'notes' => 'Imported opening leave balance from existing data.',
                ]);

                DB::statement('SELECT recalc_employee_leave_balance(?)', [
                    $balance->employee_leave_balance_id,
                ]);

                $result['success_rows']++;
            }
        });

        return $result;
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($file);
        }

        return $this->readSpreadsheetRows($file);
    }

    private function readCsvRows(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('File tidak bisa dibaca.');
        }

        $header = null;

        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeader($data);
                continue;
            }

            $rows[] = $this->mapRow($header, $data);
        }

        fclose($handle);

        return $rows;
    }

    private function readSpreadsheetRows(UploadedFile $file): array
    {
        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException('PhpSpreadsheet belum tersedia. Install phpoffice/phpspreadsheet atau gunakan CSV.');
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, true);

        if (empty($rawRows)) {
            return [];
        }

        $firstRow = array_shift($rawRows);
        $header = $this->normalizeHeader(array_values($firstRow));

        $rows = [];

        foreach ($rawRows as $rawRow) {
            $rows[] = $this->mapRow($header, array_values($rawRow));
        }

        return $rows;
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            return Str::of((string)$value)
                ->trim()
                ->lower()
                ->replace([' ', '-', '.'], '_')
                ->toString();
        }, $header);
    }

    private function mapRow(array $header, array $data): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }

            $mapped[$key] = $data[$index] ?? null;
        }

        if (! array_key_exists('employee_code', $mapped) && array_key_exists('emp_code', $mapped)) {
            $mapped['employee_code'] = $mapped['emp_code'];
        }

        if (! array_key_exists('remaining_leave', $mapped) && array_key_exists('sisa_cuti', $mapped)) {
            $mapped['remaining_leave'] = $mapped['sisa_cuti'];
        }

        return $mapped;
    }

    private function sourceRefId(string $startDate, string $endDate, string $leaveTypeCode, string $employeeCode): string
    {
        return implode(':', [
            'LEAVE_OPENING_IMPORT',
            $startDate,
            $endDate,
            $leaveTypeCode,
            $employeeCode,
        ]);
    }
}