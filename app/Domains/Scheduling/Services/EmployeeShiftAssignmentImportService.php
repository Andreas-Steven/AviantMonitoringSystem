<?php

namespace App\Domains\Scheduling\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeShiftAssignmentImportService
{
    public function import(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        $result = [
            'total_rows' => 0,
            'created_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
            'skipped' => [],
        ];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $empCode = trim((string) ($row['emp_code'] ?? ''));
                $shiftCode = trim((string) ($row['shift_code'] ?? ''));
                $effectiveStartDate = trim((string) ($row['effective_start_date'] ?? ''));

                $effectiveEndDate = array_key_exists('effective_end_date', $row)
                    ? trim((string) $row['effective_end_date'])
                    : null;

                $notes = array_key_exists('notes', $row)
                    ? trim((string) $row['notes'])
                    : null;

                if (
                    $empCode === ''
                    && $shiftCode === ''
                    && $effectiveStartDate === ''
                    && ($effectiveEndDate === null || $effectiveEndDate === '')
                ) {
                    continue;
                }

                $result['total_rows']++;

                if ($empCode === '') {
                    $this->fail($result, $rowNumber, 'emp_code kosong.');
                    continue;
                }

                if ($shiftCode === '') {
                    $this->fail($result, $rowNumber, 'shift_code kosong.');
                    continue;
                }

                if ($effectiveStartDate === '') {
                    $this->fail($result, $rowNumber, 'effective_start_date kosong.');
                    continue;
                }

                $startDate = $this->normalizeDate($effectiveStartDate);

                if ($startDate === null) {
                    $this->fail($result, $rowNumber, "effective_start_date tidak valid: {$effectiveStartDate}.");
                    continue;
                }

                $endDate = null;

                if ($effectiveEndDate !== null && $effectiveEndDate !== '') {
                    $endDate = $this->normalizeDate($effectiveEndDate);

                    if ($endDate === null) {
                        $this->fail($result, $rowNumber, "effective_end_date tidak valid: {$effectiveEndDate}.");
                        continue;
                    }

                    if ($endDate < $startDate) {
                        $this->fail($result, $rowNumber, 'effective_end_date tidak boleh lebih kecil dari effective_start_date.');
                        continue;
                    }
                }

                $employee = DB::table('employees')
                    ->where('emp_code', $empCode)
                    ->first();

                if (! $employee) {
                    $this->fail($result, $rowNumber, "emp_code {$empCode} tidak ditemukan.");
                    continue;
                }

                $shift = DB::table('shifts')
                    ->where('shift_code', $shiftCode)
                    ->first();

                if (! $shift) {
                    $this->fail($result, $rowNumber, "shift_code {$shiftCode} tidak ditemukan.");
                    continue;
                }

                $duplicate = DB::table('employee_shift_assignments')
                    ->where('emp_id', $employee->emp_id)
                    ->where('shift_id', $shift->shift_id)
                    ->where('effective_start_date', $startDate)
                    ->where(function ($query) use ($endDate) {
                        if ($endDate === null) {
                            $query->whereNull('effective_end_date');
                        } else {
                            $query->where('effective_end_date', $endDate);
                        }
                    })
                    ->exists();

                if ($duplicate) {
                    $result['skipped_rows']++;
                    $result['skipped'][] = "Row {$rowNumber}: {$empCode} shift assignment persis sama sudah ada.";
                    continue;
                }

                $hasOverlap = DB::table('employee_shift_assignments')
                    ->where('emp_id', $employee->emp_id)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $queryEndDate = $endDate ?? '9999-12-31';

                        $query
                            ->where('effective_start_date', '<=', $queryEndDate)
                            ->where(function ($subQuery) use ($startDate) {
                                $subQuery
                                    ->whereNull('effective_end_date')
                                    ->orWhere('effective_end_date', '>=', $startDate);
                            });
                    })
                    ->exists();

                if ($hasOverlap) {
                    $this->fail(
                        $result,
                        $rowNumber,
                        "{$empCode} memiliki shift assignment overlap. Import tahap ini tidak auto replace."
                    );

                    continue;
                }

                DB::table('employee_shift_assignments')->insert([
                    'emp_id' => $employee->emp_id,
                    'shift_id' => $shift->shift_id,
                    'assignment_type_code' => 'DEFAULT',
                    'effective_start_date' => $startDate,
                    'effective_end_date' => $endDate,
                    'notes' => $notes !== '' ? $notes : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $result['created_rows']++;
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

        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException(
                'PhpSpreadsheet belum tersedia. Install phpoffice/phpspreadsheet atau gunakan CSV.'
            );
        }

        $spreadsheet = IOFactory::load($file->getRealPath());

        $sheet = $spreadsheet->getActiveSheet();

        $rawRows = $sheet->toArray(null, true, true, true);

        if (empty($rawRows)) {
            return [];
        }

        $firstRow = array_shift($rawRows);

        $header = $this->normalizeHeader(array_values($firstRow));

        return array_map(
            fn ($row) => $this->mapRow($header, array_values($row)),
            $rawRows
        );
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

    private function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            return Str::of((string) $value)
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

        if (! isset($mapped['emp_code']) && isset($mapped['employee_code'])) {
            $mapped['emp_code'] = $mapped['employee_code'];
        }

        return $mapped;
    }

    private function normalizeDate(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fail(array &$result, int $rowNumber, string $message): void
    {
        $result['failed_rows']++;
        $result['errors'][] = "Row {$rowNumber}: {$message}";
    }
}