<?php

namespace App\Domains\Master\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeImportService
{
    public function import(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        $result = [
            'total_rows' => 0,
            'created_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
            'skipped' => [],
        ];

        DB::transaction(function () use ($rows, &$result) {

            foreach ($rows as $index => $row) {

                $rowNumber = $index + 2;

                $empCode = trim((string) ($row['emp_code'] ?? ''));

                $biometricCode = array_key_exists('biometric_code', $row)
                    ? trim((string) $row['biometric_code'])
                    : null;

                $fullName = array_key_exists('full_name', $row)
                    ? trim((string) $row['full_name'])
                    : null;

                $employmentTypeCode = array_key_exists('employment_type_code', $row)
                    ? trim((string) $row['employment_type_code'])
                    : null;

                $joinDate = array_key_exists('join_date', $row)
                    ? trim((string) $row['join_date'])
                    : null;

                $resignDate = array_key_exists('resign_date', $row)
                    ? trim((string) $row['resign_date'])
                    : null;

                $activeRaw = array_key_exists('active', $row)
                    ? $row['active']
                    : null;

                $notes = array_key_exists('notes', $row)
                    ? trim((string) $row['notes'])
                    : null;

                /*
                |--------------------------------------------------------------------------
                | Empty Row
                |--------------------------------------------------------------------------
                */

                if (
                    $empCode === ''
                    && ($fullName === null || $fullName === '')
                    && ($employmentTypeCode === null || $employmentTypeCode === '')
                    && ($joinDate === null || $joinDate === '')
                    && ($resignDate === null || $resignDate === '')
                ) {
                    continue;
                }

                $result['total_rows']++;

                /*
                |--------------------------------------------------------------------------
                | emp_code wajib
                |--------------------------------------------------------------------------
                */

                if ($empCode === '') {
                    $this->fail(
                        $result,
                        $rowNumber,
                        'emp_code kosong.'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Existing employee check
                |--------------------------------------------------------------------------
                */

                $existingEmployee = DB::table('employees')
                    ->where('emp_code', $empCode)
                    ->first();

                $isCreating = ! $existingEmployee;

                /*
                |--------------------------------------------------------------------------
                | Mandatory only for CREATE
                |--------------------------------------------------------------------------
                */

                if ($isCreating && ($fullName === null || $fullName === '')) {

                    $this->fail(
                        $result,
                        $rowNumber,
                        'full_name wajib untuk employee baru.'
                    );

                    continue;
                }

                if ($isCreating && ($employmentTypeCode === null || $employmentTypeCode === '')) {

                    $this->fail(
                        $result,
                        $rowNumber,
                        'employment_type_code wajib untuk employee baru.'
                    );

                    continue;
                }

                if ($isCreating && ($joinDate === null || $joinDate === '')) {

                    $this->fail(
                        $result,
                        $rowNumber,
                        'join_date wajib untuk employee baru.'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | join_date normalize
                |--------------------------------------------------------------------------
                */

                $normalizedJoinDate = null;

                if ($joinDate !== null && $joinDate !== '') {

                    $normalizedJoinDate = $this->normalizeDate($joinDate);

                    if ($normalizedJoinDate === null) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            "join_date tidak valid: {$joinDate}."
                        );

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | resign_date normalize
                |--------------------------------------------------------------------------
                */

                $normalizedResignDate = null;

                if ($resignDate !== null && $resignDate !== '') {

                    $normalizedResignDate = $this->normalizeDate($resignDate);

                    if ($normalizedResignDate === null) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            "resign_date tidak valid: {$resignDate}."
                        );

                        continue;
                    }

                    if (
                        $normalizedJoinDate !== null
                        && $normalizedResignDate < $normalizedJoinDate
                    ) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            'resign_date tidak boleh lebih kecil dari join_date.'
                        );

                        continue;
                    }

                    if (
                        $normalizedJoinDate === null
                        && $existingEmployee
                        && $existingEmployee->join_date
                    ) {

                        $existingJoinDate = Carbon::parse(
                            $existingEmployee->join_date
                        )->toDateString();

                        if ($normalizedResignDate < $existingJoinDate) {

                            $this->fail(
                                $result,
                                $rowNumber,
                                'resign_date tidak boleh lebih kecil dari join_date existing.'
                            );

                            continue;
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | employment_type_code
                |--------------------------------------------------------------------------
                */

                $employmentType = null;

                if ($employmentTypeCode !== null && $employmentTypeCode !== '') {

                    $employmentType = DB::table('employment_types')
                        ->where('employment_type_code', $employmentTypeCode)
                        ->first();

                    if (! $employmentType) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            "employment_type_code {$employmentTypeCode} tidak ditemukan."
                        );

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | active normalize
                |--------------------------------------------------------------------------
                */

                $active = null;

                if ($activeRaw !== null && trim((string) $activeRaw) !== '') {

                    $active = $this->normalizeBoolean($activeRaw);

                    if ($active === null) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            'active tidak valid. Gunakan 1/0, true/false, yes/no, aktif/nonaktif.'
                        );

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | biometric_code conflict
                |--------------------------------------------------------------------------
                */

                if ($biometricCode !== null && $biometricCode !== '') {

                    $biometricConflict = DB::table('employees')
                        ->where('biometric_code', $biometricCode)
                        ->when(
                            $existingEmployee,
                            function ($query) use ($existingEmployee) {
                                $query->where(
                                    'emp_id',
                                    '<>',
                                    $existingEmployee->emp_id
                                );
                            }
                        )
                        ->exists();

                    if ($biometricConflict) {

                        $this->fail(
                            $result,
                            $rowNumber,
                            "biometric_code {$biometricCode} sudah dipakai employee lain."
                        );

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Build Payload
                |--------------------------------------------------------------------------
                */

                $payload = [
                    'updated_at' => now(),
                ];

                /*
                |--------------------------------------------------------------------------
                | biometric_code
                |--------------------------------------------------------------------------
                | Existing employee:
                | kosong = keep old value
                |--------------------------------------------------------------------------
                */

                if ($isCreating || ($biometricCode !== null && $biometricCode !== '')) {

                    $payload['biometric_code'] =
                        $biometricCode !== ''
                            ? $biometricCode
                            : null;
                }

                /*
                |--------------------------------------------------------------------------
                | full_name
                |--------------------------------------------------------------------------
                */

                if ($isCreating || ($fullName !== null && $fullName !== '')) {

                    $payload['full_name'] = $fullName;
                }

                /*
                |--------------------------------------------------------------------------
                | employment_type_id
                |--------------------------------------------------------------------------
                */

                if ($isCreating || $employmentType !== null) {

                    $payload['employment_type_id'] =
                        $employmentType->employment_type_id;
                }

                /*
                |--------------------------------------------------------------------------
                | join_date
                |--------------------------------------------------------------------------
                */

                if ($isCreating || $normalizedJoinDate !== null) {

                    $payload['join_date'] = $normalizedJoinDate;
                }

                /*
                |--------------------------------------------------------------------------
                | resign_date
                |--------------------------------------------------------------------------
                | Existing employee:
                | kosong = keep old value
                |--------------------------------------------------------------------------
                */

                if ($isCreating || $normalizedResignDate !== null) {

                    $payload['resign_date'] = $normalizedResignDate;
                }

                /*
                |--------------------------------------------------------------------------
                | active
                |--------------------------------------------------------------------------
                */

                if ($isCreating || $active !== null) {

                    $payload['active'] = $active ?? true;
                }

                /*
                |--------------------------------------------------------------------------
                | notes
                |--------------------------------------------------------------------------
                | Existing employee:
                | kosong = keep old value
                |--------------------------------------------------------------------------
                */

                if ($isCreating || ($notes !== null && $notes !== '')) {

                    $payload['notes'] =
                        $notes !== ''
                            ? $notes
                            : null;
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE
                |--------------------------------------------------------------------------
                */

                if ($existingEmployee) {

                    DB::table('employees')
                        ->where('emp_id', $existingEmployee->emp_id)
                        ->update($payload);

                    $result['updated_rows']++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE
                |--------------------------------------------------------------------------
                */

                DB::table('employees')->insert(
                    array_merge(
                        $payload,
                        [
                            'emp_code' => $empCode,
                            'created_at' => now(),
                        ]
                    )
                );

                $result['created_rows']++;
            }
        });

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Read Rows
    |--------------------------------------------------------------------------
    */

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

        $rawRows = $sheet->toArray(
            null,
            true,
            true,
            true
        );

        if (empty($rawRows)) {
            return [];
        }

        $firstRow = array_shift($rawRows);

        $header = $this->normalizeHeader(
            array_values($firstRow)
        );

        return array_map(
            fn ($row) => $this->mapRow(
                $header,
                array_values($row)
            ),
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

            $rows[] = $this->mapRow(
                $header,
                $data
            );
        }

        fclose($handle);

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Header
    |--------------------------------------------------------------------------
    */

    private function normalizeHeader(array $header): array
    {
        return array_map(
            function ($value) {

                return Str::of((string) $value)
                    ->trim()
                    ->lower()
                    ->replace([' ', '-', '.'], '_')
                    ->toString();
            },
            $header
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Map Row
    |--------------------------------------------------------------------------
    */

    private function mapRow(array $header, array $data): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {

            if ($key === '') {
                continue;
            }

            $mapped[$key] = $data[$index] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Aliases
        |--------------------------------------------------------------------------
        */

        if (
            ! isset($mapped['emp_code'])
            && isset($mapped['employee_code'])
        ) {
            $mapped['emp_code'] = $mapped['employee_code'];
        }

        if (
            ! isset($mapped['full_name'])
            && isset($mapped['name'])
        ) {
            $mapped['full_name'] = $mapped['name'];
        }

        return $mapped;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Date
    |--------------------------------------------------------------------------
    */

    private function normalizeDate(string $value): ?string
    {
        try {

            return Carbon::parse($value)
                ->toDateString();

        } catch (\Throwable) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Boolean
    |--------------------------------------------------------------------------
    */

    private function normalizeBoolean(mixed $value): ?bool
    {
        $normalized = Str::of((string) $value)
            ->trim()
            ->lower()
            ->toString();

        return match ($normalized) {

            '1',
            'true',
            'yes',
            'y',
            'aktif',
            'active'
                => true,

            '0',
            'false',
            'no',
            'n',
            'nonaktif',
            'inactive'
                => false,

            default
                => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Fail Helper
    |--------------------------------------------------------------------------
    */

    private function fail(
        array &$result,
        int $rowNumber,
        string $message
    ): void {

        $result['failed_rows']++;

        $result['errors'][] =
            "Row {$rowNumber}: {$message}";
    }
}