<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceImportBatch;
use App\Domains\Attendance\Models\AttendanceLogRaw;
use App\Domains\Attendance\Services\AttendanceDailyBuilderService;
use App\Domains\Attendance\Services\AttendanceNormalizationService;
use App\Domains\Attendance\Services\AttendanceOperationRunLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class AttendanceRawImportController extends Controller
{
    public function create(): View
    {
        return view('attendance.raw-import.create');
    }

    public function preview(Request $request): RedirectResponse
    {
        return redirect()->route('attendance.raw-logs.import.create');
    }

    public function store(
        Request $request,
        AttendanceNormalizationService $normalizationService,
        AttendanceDailyBuilderService $dailyBuilderService,
        AttendanceOperationRunLogger $operationRunLogger
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv'],
            'employee_prefix' => ['nullable', 'string', 'max:20'],
            'datetime_format' => ['required', 'string', 'max:50'],
            'after_import_action' => ['nullable', 'in:import_only,normalize,full_pipeline'],
        ]);

        $file = $request->file('file');
        $employeePrefix = $request->input('employee_prefix', 'EK');
        $datetimeFormat = $request->input('datetime_format', 'n/j/Y g:i A');
        $afterImportAction = $request->input('after_import_action', 'import_only');

        $importedDates = [];

        DB::beginTransaction();

        try {
            $batch = AttendanceImportBatch::create([
                'original_file_name' => $file->getClientOriginalName(),
                'stored_file_name' => $file->hashName(),
                'file_extension' => $file->getClientOriginalExtension(),
                'uploaded_by' => auth()->id(),
                'upload_status_code' => 'IMPORTED',
            ]);

            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();
            $totalRows = 0;
            $validRows = 0;
            $invalidRows = 0;

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $acNo = trim((string)($row[0] ?? ''));
                $name = trim((string)($row[2] ?? ''));
                $time = trim((string)($row[3] ?? ''));
                $state = trim((string)($row[4] ?? ''));

                if ($acNo === '' && $time === '') {
                    continue;
                }

                $totalRows++;

                if ($acNo === '' || $time === '') {
                    $invalidRows++;
                    continue;
                }

                try {
                    $dt = Carbon::createFromFormat($datetimeFormat, $time);
                } catch (Throwable $e) {
                    $invalidRows++;
                    continue;
                }

                $empCode = $employeePrefix . $acNo;$empCode = $employeePrefix . $acNo;

                // 1. Primary lookup: biometric_code
                $employee = DB::table('employees')
                    ->where('biometric_code', $acNo)
                    ->first();

                $mappingSource = null;

                if ($employee) {
                    $mappingSource = 'BIOMETRIC_CODE';
                } else {
                    // 2. Fallback: emp_code (prefix)
                    $employee = DB::table('employees')
                        ->where('emp_code', $empCode)
                        ->first();

                    if ($employee) {
                        $mappingSource = 'EMP_CODE_FALLBACK';
                    }
                }

                AttendanceLogRaw::create([
                    'attendance_import_batch_id' => $batch->attendance_import_batch_id,
                    'source_system' => strtoupper($file->getClientOriginalExtension()) . '_IMPORT',
                    'device_user_id' => $acNo,
                    'emp_id' => $employee?->emp_id,
                    'log_datetime' => $dt,
                    'log_date' => $dt->toDateString(),
                    'log_time' => $dt->format('H:i:s'),
                    'io_mode' => $state !== '' ? $state : null,
                    'raw_payload' => [
                        'original_row' => [
                            'AC-No.' => $acNo,
                            'Name' => $name,
                            'Time' => $time,
                            'State' => $state,
                            'full_row' => $row,
                        ],
                        'mapped' => [
                            'employee_identifier' => $acNo,
                            'employee_identifier_transformed' => $empCode,
                            'mapping_source' => $mappingSource,
                            'employee_name_from_device' => $name,
                            'log_datetime_raw' => $time,
                            'io_mode' => $state,
                        ],
                    ],
                    'source_row_number' => $index + 1,
                    'import_validation_status_code' => $employee
                        ? ($mappingSource === 'BIOMETRIC_CODE' ? 'VALID' : 'VALID_FALLBACK')
                        : 'UNMATCHED_EMPLOYEE',
                    'import_validation_notes' => $employee ? null : 'Employee not found by emp_code.',
                ]);

                if ($employee) {
                    $importedDates[] = $dt->toDateString();
                    $validRows++;
                } else {
                    $invalidRows++;
                }
            }

            $batch->update([
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $message = "Import selesai. Total: {$totalRows}, valid: {$validRows}, invalid: {$invalidRows}.";

        if ($afterImportAction === 'import_only') {
            return redirect()
                ->route('attendance.raw-logs.index')
                ->with('success', $message);
        }

        if ($validRows <= 0 || empty($importedDates)) {
            return redirect()
                ->route('attendance.raw-logs.index')
                ->with('warning', $message . ' Proses lanjutan tidak dijalankan karena tidak ada row valid.');
        }

        $dateFrom = min($importedDates);
        $dateTo = max($importedDates);
        $userId = auth()->user()?->user_id;

        if ($afterImportAction === 'normalize') {
            $run = $operationRunLogger->start(
                'NORMALIZE',
                $dateFrom,
                $dateTo,
                null,
                $userId
            );

            try {
                $normalizeResult = $normalizationService->normalize($dateFrom, $dateTo);

                $operationRunLogger->succeed($run, $normalizeResult);

                $message .= sprintf(
                    ' Normalize selesai. Range: %s s/d %s. Groups: %d, Processed: %d, Inserted: %d.',
                    $dateFrom,
                    $dateTo,
                    (int) ($normalizeResult['group_count'] ?? 0),
                    (int) ($normalizeResult['processed_count'] ?? 0),
                    (int) ($normalizeResult['inserted_count'] ?? 0)
                );

                return redirect()
                    ->route('attendance.normalized-logs.index')
                    ->with('success', $message);
            } catch (Throwable $e) {
                $operationRunLogger->fail($run, $e);

                return redirect()
                    ->route('attendance.raw-logs.index')
                    ->with('warning', $message . ' Tetapi normalize gagal: ' . $e->getMessage());
            }
        }

        if ($afterImportAction === 'full_pipeline') {
            $run = $operationRunLogger->start(
                'FULL_PIPELINE',
                $dateFrom,
                $dateTo,
                null,
                $userId
            );

            try {
                $normalizeResult = $normalizationService->normalize($dateFrom, $dateTo);
                $dailyResult = $dailyBuilderService->build($dateFrom, $dateTo);

                $operationRunLogger->succeed($run, [
                    'normalize' => $normalizeResult,
                    'build_daily' => $dailyResult,
                ]);

                $message .= sprintf(
                    ' Full pipeline selesai. Range: %s s/d %s. Normalize inserted: %d. Build daily upserted: %d, skipped: %d.',
                    $dateFrom,
                    $dateTo,
                    (int) ($normalizeResult['inserted_count'] ?? 0),
                    (int) ($dailyResult['upserted_count'] ?? 0),
                    (int) ($dailyResult['skipped_days'] ?? 0)
                );

                return redirect()
                    ->route('attendance.daily.index')
                    ->with('success', $message);
            } catch (Throwable $e) {
                $operationRunLogger->fail($run, $e);

                return redirect()
                    ->route('attendance.raw-logs.index')
                    ->with('warning', $message . ' Tetapi full pipeline gagal: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('attendance.raw-logs.index')
            ->with('success', $message);
    }
}