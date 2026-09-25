<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Attendance\AttendanceRawImportController;
use App\Http\Controllers\Web\Attendance\AttendanceRawLogController;
use App\Http\Controllers\Web\Attendance\AttendanceExceptionController;
use App\Http\Controllers\Web\Attendance\AttendanceDailyController;
use App\Http\Controllers\Web\Attendance\AttendanceNormalizedLogController;
use App\Http\Controllers\Web\Attendance\AttendanceOperationController;
use App\Http\Controllers\Web\Attendance\AttendanceWorkspaceController;

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('workspace', [AttendanceWorkspaceController::class, 'index'])
        ->middleware('permission:attendance_daily.view')
        ->name('workspace');

    Route::get('raw-logs', [AttendanceRawLogController::class, 'index'])
        ->middleware('permission:attendance_raw.view')
        ->name('raw-logs.index');

    Route::get('raw-logs/import', [AttendanceRawImportController::class, 'create'])
        ->middleware('permission:attendance_raw.import')
        ->name('raw-logs.import.create');

    Route::post('raw-logs/import/preview', [AttendanceRawImportController::class, 'preview'])
        ->middleware('permission:attendance_raw.import')
        ->name('raw-logs.import.preview');

    Route::post('raw-logs/import/store', [AttendanceRawImportController::class, 'store'])
        ->middleware('permission:attendance_raw.import')
        ->name('raw-logs.import.store');

    Route::get('raw-logs/{rawLog}', [AttendanceRawLogController::class, 'show'])
        ->middleware('permission:attendance_raw.view')
        ->name('raw-logs.show');

    Route::get('operations', [AttendanceOperationController::class, 'index'])
        ->middleware('permission:attendance_daily.recalculate')
        ->name('operations.index');

    Route::post('operations/normalize', [AttendanceOperationController::class, 'normalize'])
        ->middleware('permission:attendance_daily.recalculate')
        ->name('operations.normalize');

    Route::post('operations/build-daily', [AttendanceOperationController::class, 'buildDaily'])
        ->middleware('permission:attendance_daily.recalculate')
        ->name('operations.build-daily');

    Route::post('operations/run-pipeline', [AttendanceOperationController::class, 'runPipeline'])
        ->middleware('permission:attendance_daily.recalculate')
        ->name('operations.run-pipeline');

    Route::get('daily', [AttendanceDailyController::class, 'index'])
        ->middleware('permission:attendance_daily.view')
        ->name('daily.index');

    Route::get('daily/{attendanceDaily}', [AttendanceDailyController::class, 'show'])
        ->middleware('permission:attendance_daily.view')
        ->name('daily.show');

    Route::post('daily/{attendanceDaily}/recalculate', [AttendanceDailyController::class, 'recalculate'])
        ->middleware('permission:attendance_daily.recalculate')
        ->name('daily.recalculate');

    Route::get('attendance-exceptions', [AttendanceExceptionController::class, 'index'])
        ->middleware('permission:attendance_exception.view')
        ->name('attendance-exceptions.index');

    Route::get('attendance-exceptions/create', [AttendanceExceptionController::class, 'create'])
        ->middleware('permission:attendance_exception.manage')
        ->name('attendance-exceptions.create');

    Route::post('attendance-exceptions', [AttendanceExceptionController::class, 'store'])
        ->middleware('permission:attendance_exception.manage')
        ->name('attendance-exceptions.store');

    Route::get('attendance-exceptions/{attendance_exception}', [AttendanceExceptionController::class, 'show'])
        ->middleware('permission:attendance_exception.view')
        ->name('attendance-exceptions.show');

    Route::get('attendance-exceptions/{attendance_exception}/edit', [AttendanceExceptionController::class, 'edit'])
        ->middleware('permission:attendance_exception.manage')
        ->name('attendance-exceptions.edit');

    Route::put('attendance-exceptions/{attendance_exception}', [AttendanceExceptionController::class, 'update'])
        ->middleware('permission:attendance_exception.manage')
        ->name('attendance-exceptions.update');

    Route::get('normalized-logs', [AttendanceNormalizedLogController::class, 'index'])
        ->middleware('permission:attendance_normalized.view')
        ->name('normalized-logs.index');

    Route::get('normalized-logs/{normalizedLog}', [AttendanceNormalizedLogController::class, 'show'])
        ->middleware('permission:attendance_normalized.view')
        ->name('normalized-logs.show');
    
});