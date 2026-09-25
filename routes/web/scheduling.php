<?php

use App\Http\Controllers\Web\Scheduling\AttendancePolicyController;
use App\Http\Controllers\Web\Scheduling\BranchCalendarController;
use App\Http\Controllers\Web\Scheduling\BranchPolicyAssignmentController;
use App\Http\Controllers\Web\Scheduling\EmployeeShiftAssignmentController;
use App\Http\Controllers\Web\Scheduling\EmployeeShiftRosterController;
use App\Http\Controllers\Web\Scheduling\EmployeeWorkPatternAssignmentController;
use App\Http\Controllers\Web\Scheduling\HolidayEventController;
use App\Http\Controllers\Web\Scheduling\PayrollPeriodController;
use App\Http\Controllers\Web\Scheduling\SchedulingWorkspaceController;
use App\Http\Controllers\Web\Scheduling\ShiftController;
use App\Http\Controllers\Web\Scheduling\WorkPatternController;
use App\Http\Controllers\Web\Scheduling\WorkPatternRuleController;
use App\Http\Controllers\Web\Scheduling\EmployeeSchedulingCoverageController;
use App\Http\Controllers\Web\Scheduling\HolidayImportController;
use App\Http\Controllers\Web\Scheduling\EmployeeShiftAssignmentImportController;
use App\Http\Controllers\Web\Scheduling\EmployeeWorkPatternAssignmentImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('scheduling')->name('scheduling.')->group(function () {
    Route::get('workspace', [SchedulingWorkspaceController::class, 'index'])
        ->middleware('permission:shift.view')
        ->name('workspace');

    Route::resource('shifts', ShiftController::class)
        ->middleware('permission:shift.view');

    Route::resource('attendance-policies', AttendancePolicyController::class)
        ->middleware('permission:policy.view');

    Route::post('payroll-periods/{payroll_period}/close', [PayrollPeriodController::class, 'close'])
    ->middleware('permission:payroll_period.manage')
    ->name('payroll-periods.close');

    Route::post('payroll-periods/{payroll_period}/reopen', [PayrollPeriodController::class, 'reopen'])
        ->middleware('permission:payroll_period.manage')
        ->name('payroll-periods.reopen');

    Route::post('payroll-periods/{payroll_period}/lock', [PayrollPeriodController::class, 'lock'])
        ->middleware('permission:payroll_period.manage')
        ->name('payroll-periods.lock');

    Route::post('payroll-periods/{payroll_period}/unlock', [PayrollPeriodController::class, 'unlock'])
        ->middleware('permission:payroll_period.manage')
        ->name('payroll-periods.unlock');

    Route::post('payroll-periods/{payroll_period}/create-next', [PayrollPeriodController::class, 'createNext'])
        ->middleware('permission:payroll_period.manage')
        ->name('payroll-periods.create-next');

    Route::resource('payroll-periods', PayrollPeriodController::class)
        ->middleware('permission:payroll_period.view');
    
    Route::get('employee-scheduling-coverage/setup', [EmployeeSchedulingCoverageController::class, 'createSetup'])
        ->middleware('permission:shift.manage')
        ->name('employee-scheduling-coverage.setup.create');

    Route::post('employee-scheduling-coverage/setup', [EmployeeSchedulingCoverageController::class, 'storeSetup'])
        ->middleware('permission:shift.manage')
        ->name('employee-scheduling-coverage.setup.store');

    Route::get('employee-scheduling-coverage', [EmployeeSchedulingCoverageController::class, 'index'])
        ->middleware('permission:shift.view')
        ->name('employee-scheduling-coverage.index');

    Route::get('branch-policy-assignments/coverage', [BranchPolicyAssignmentController::class, 'coverage'])
        ->middleware('permission:policy.view')
        ->name('branch-policy-assignments.coverage');

    Route::resource('branch-policy-assignments', BranchPolicyAssignmentController::class)
        ->middleware('permission:policy.view');

    /*
    |--------------------------------------------------------------------------
    | Employee Shift Assignments - static/custom routes first
    |--------------------------------------------------------------------------
    */

    Route::get('/employee-shift-assignments/import', [EmployeeShiftAssignmentImportController::class, 'create'])
        ->middleware('permission:roster.manage')
        ->name('employee-shift-assignments.import.create');

    Route::post('/employee-shift-assignments/import', [EmployeeShiftAssignmentImportController::class, 'store'])
        ->middleware('permission:roster.manage')
        ->name('employee-shift-assignments.import.store');

    Route::get('employee-shift-assignments/coverage', [EmployeeShiftAssignmentController::class, 'coverage'])
        ->middleware('permission:shift.view')
        ->name('employee-shift-assignments.coverage');

    Route::get('employee-shift-assignments/bulk-create', [EmployeeShiftAssignmentController::class, 'bulkCreate'])
        ->middleware('permission:shift.manage')
        ->name('employee-shift-assignments.bulk-create');

    Route::post('employee-shift-assignments/bulk-store', [EmployeeShiftAssignmentController::class, 'bulkStore'])
        ->middleware('permission:shift.manage')
        ->name('employee-shift-assignments.bulk-store');

    Route::resource('employee-shift-assignments', EmployeeShiftAssignmentController::class)
        ->middleware('permission:shift.view');

    /*
    |--------------------------------------------------------------------------
    | Branch Calendars
    |--------------------------------------------------------------------------
    */
    Route::post('branch-calendars/generate', [BranchCalendarController::class, 'generate'])
        ->name('branch-calendars.generate')
        ->middleware('permission:calendar.manage');

    Route::patch('branch-calendars/{branchCalendar}/day', [BranchCalendarController::class, 'updateDay'])
        ->name('branch-calendars.update-day')
        ->middleware('permission:calendar.manage');

    Route::resource('branch-calendars', BranchCalendarController::class)
        ->middleware('permission:calendar.view');

    /*
    |--------------------------------------------------------------------------
    | Holiday Events
    |--------------------------------------------------------------------------
    */

    Route::get('holiday-events/import', [HolidayImportController::class, 'create'])
        ->middleware('permission:holiday.manage')
        ->name('holiday-events.import.create');

    Route::get('holiday-events/import/preview', [HolidayImportController::class, 'preview'])
        ->middleware('permission:holiday.manage')
        ->name('holiday-events.import.preview');

    Route::post('holiday-events/import', [HolidayImportController::class, 'store'])
        ->middleware('permission:holiday.manage')
        ->name('holiday-events.import.store');

    Route::resource('holiday-events', HolidayEventController::class)
        ->middleware('permission:holiday.view');

    /*
    |--------------------------------------------------------------------------
    | Work Patterns
    |--------------------------------------------------------------------------
    */

    Route::get('/employee-work-pattern-assignments/import', [EmployeeWorkPatternAssignmentImportController::class, 'create'])
        ->middleware('permission:workpattern.manage')
        ->name('employee-work-pattern-assignments.import.create');

    Route::post('/employee-work-pattern-assignments/import', [EmployeeWorkPatternAssignmentImportController::class, 'store'])
        ->middleware('permission:workpattern.manage')
        ->name('employee-work-pattern-assignments.import.store');

    Route::resource('work-patterns', WorkPatternController::class)
        ->middleware('permission:workpattern.view');

    Route::resource('work-pattern-rules', WorkPatternRuleController::class)
        ->middleware('permission:workpattern.view');

    Route::get('employee-work-pattern-assignments/coverage', [EmployeeWorkPatternAssignmentController::class, 'coverage'])
        ->middleware('permission:workpattern.view')
        ->name('employee-work-pattern-assignments.coverage');

    Route::get('employee-work-pattern-assignments/bulk-create', [EmployeeWorkPatternAssignmentController::class, 'bulkCreate'])
        ->middleware('permission:workpattern.manage')
        ->name('employee-work-pattern-assignments.bulk-create');

    Route::post('employee-work-pattern-assignments/bulk-store', [EmployeeWorkPatternAssignmentController::class, 'bulkStore'])
        ->middleware('permission:workpattern.manage')
        ->name('employee-work-pattern-assignments.bulk-store');
    
    Route::resource('employee-work-pattern-assignments', EmployeeWorkPatternAssignmentController::class)
        ->middleware('permission:workpattern.view');

    /*
    |--------------------------------------------------------------------------
    | Shift Rosters
    |--------------------------------------------------------------------------
    */

    Route::get('employee-shift-rosters/coverage', [EmployeeShiftRosterController::class, 'coverage'])
        ->middleware('permission:roster.view')
        ->name('employee-shift-rosters.coverage');

    Route::resource('employee-shift-rosters', EmployeeShiftRosterController::class)
        ->middleware('permission:roster.view');
});