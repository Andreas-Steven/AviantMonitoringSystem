<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Summary\AttendanceMonthlySummaryController;
use App\Http\Controllers\Web\Summary\AttendancePeriodSummaryController;
use App\Http\Controllers\Web\Summary\EmployeePeriodObligationController;
use App\Http\Controllers\Web\Summary\SummaryRebuildController;
use App\Http\Controllers\Web\Summary\PayrollAttendanceAmountController;
use App\Http\Controllers\Web\Summary\PayrollAttendanceDashboardController;
use App\Http\Controllers\Web\Summary\PayrollAttendanceResultController;
use App\Http\Controllers\Web\Summary\PayrollPeriodReportController;
use App\Http\Controllers\Web\Summary\SummaryWorkspaceController;

Route::prefix('summary')->name('summary.')->group(function () {
    Route::get('workspace', [SummaryWorkspaceController::class, 'index'])
        ->middleware('permission:summary.view')
        ->name('workspace');

    Route::get('monthly', [AttendanceMonthlySummaryController::class, 'index'])
        ->middleware('permission:summary.view')
        ->name('monthly.index');

    Route::get('monthly/{summary}', [AttendanceMonthlySummaryController::class, 'show'])
        ->middleware('permission:summary.view')
        ->name('monthly.show');

    Route::get('period', [AttendancePeriodSummaryController::class, 'index'])
        ->middleware('permission:summary.view')
        ->name('period.index');

    Route::get('period/{summary}', [AttendancePeriodSummaryController::class, 'show'])
        ->middleware('permission:summary.view')
        ->name('period.show');

    Route::get('obligations', [EmployeePeriodObligationController::class, 'index'])
        ->middleware('permission:summary.view')
        ->name('obligations.index');

    Route::get('obligations/{obligation}', [EmployeePeriodObligationController::class, 'show'])
        ->middleware('permission:summary.view')
        ->name('obligations.show');

    Route::get('rebuild', [SummaryRebuildController::class, 'index'])
        ->middleware('permission:summary.recalculate')
        ->name('rebuild.index');

    Route::post('rebuild/monthly', [SummaryRebuildController::class, 'rebuildMonthly'])
        ->middleware('permission:summary.recalculate')
        ->name('rebuild.monthly');

    Route::post('rebuild/period', [SummaryRebuildController::class, 'rebuildPeriod'])
        ->middleware('permission:summary.recalculate')
        ->name('rebuild.period');

    Route::post('rebuild/obligation', [SummaryRebuildController::class, 'rebuildObligation'])
        ->middleware('permission:summary.recalculate')
        ->name('rebuild.obligation');

    Route::post('rebuild/payroll', [SummaryRebuildController::class, 'rebuildPayroll'])
        ->middleware('permission:summary.recalculate')
        ->name('rebuild.payroll');

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('dashboard', [PayrollAttendanceDashboardController::class, 'index'])
            ->middleware('permission:summary.view')
            ->name('dashboard');

        Route::post('calculate-results', [PayrollAttendanceDashboardController::class, 'calculateResults'])
            ->middleware('permission:summary.recalculate')
            ->name('calculate-results');

        Route::post('calculate-amounts', [PayrollAttendanceDashboardController::class, 'calculateAmounts'])
            ->middleware('permission:summary.recalculate')
            ->name('calculate-amounts');

        Route::get('results', [PayrollAttendanceResultController::class, 'index'])
            ->middleware('permission:summary.view')
            ->name('results.index');

        Route::get('results/{result}', [PayrollAttendanceResultController::class, 'show'])
            ->middleware('permission:summary.view')
            ->name('results.show');

        Route::get('amounts', [PayrollAttendanceAmountController::class, 'index'])
            ->middleware('permission:summary.view')
            ->name('amounts.index');

        Route::get('amounts/{amount}', [PayrollAttendanceAmountController::class, 'show'])
            ->middleware('permission:summary.view')
            ->name('amounts.show');

        Route::get('reports', [PayrollPeriodReportController::class, 'index'])
            ->middleware('permission:summary.view')
            ->name('reports.index');

        Route::get('reports/{report}', [PayrollPeriodReportController::class, 'show'])
            ->middleware('permission:summary.view')
            ->name('reports.show');
    });
});