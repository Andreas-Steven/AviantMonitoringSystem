<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceImportBatch;
use App\Domains\Attendance\Models\AttendanceLogRaw;
use App\Domains\Attendance\Services\AttendanceNormalizationService;
use App\Domains\Attendance\Services\AttendanceDailyBuilderService;
use App\Domains\Attendance\Services\AttendanceOperationRunLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class AttendanceRawImportController extends Controller
{
    public function create(): View
    {
        return view('attendance.raw-import.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv'],
            'datetime_format' => ['required', 'string', 'max:50'],
        ]);

        $file = $request->file('file');
        $datetimeFormat = $request->input('datetime_format', 'n/j/Y g:i A');

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
                } catch (\Throwable $e) {
                    $invalidRows++;
                    continue;
                }

                $employee = DB::table('employees')
                    ->where('biometric_code', $acNo)
                    ->first();

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
                            'employee_name_from_device' => $name,
                            'log_datetime_raw' => $time,
                            'io_mode' => $state,
                        ],
                    ],
                    'source_row_number' => $index + 1,
                    'import_validation_status_code' => $employee ? 'VALID' : 'UNMATCHED_EMPLOYEE',
                    'import_validation_notes' => $employee ? null : 'Employee not found by biometric_code.',
                ]);

                $validRows++;
            }

            $batch->update([
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
            ]);

            DB::commit();

            return redirect()
                ->route('attendance.raw-logs.index')
                ->with('success', "Import selesai. Total: {$totalRows}, valid: {$validRows}, invalid: {$invalidRows}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}