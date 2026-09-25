<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Web\Requests\LeaveRequestController;
use App\Http\Controllers\Web\Requests\OvertimeRequestController;
use App\Http\Controllers\Web\Requests\LeaveBalanceController;
use App\Http\Controllers\Web\Requests\LeaveBalanceOpeningImportController;

Route::middleware([
    'auth',
    'user.active',
])->prefix('requests')->name('requests.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Leave Requests
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leave-requests',
        [LeaveRequestController::class, 'index']
    )
        ->middleware('permission:leave.view')
        ->name('leave-requests.index');

    Route::get(
        '/leave-requests/create',
        [LeaveRequestController::class, 'create']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-requests.create');

    Route::post(
        '/leave-requests',
        [LeaveRequestController::class, 'store']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-requests.store');

    Route::get(
        '/leave-requests/{leaveRequest}',
        [LeaveRequestController::class, 'show']
    )
        ->middleware('permission:leave.view')
        ->name('leave-requests.show');

    Route::get(
        '/leave-requests/{leaveRequest}/edit',
        [LeaveRequestController::class, 'edit']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-requests.edit');

    Route::put(
        '/leave-requests/{leaveRequest}',
        [LeaveRequestController::class, 'update']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-requests.update');

    Route::post(
        '/leave-requests/{leaveRequest}/approve',
        [LeaveRequestController::class, 'approve']
    )
        ->middleware('permission:leave.approve')
        ->name('leave-requests.approve');

    Route::post(
        '/leave-requests/{leaveRequest}/reject',
        [LeaveRequestController::class, 'reject']
    )
        ->middleware('permission:leave.approve')
        ->name('leave-requests.reject');

    Route::post(
        '/leave-requests/{leaveRequest}/cancel',
        [LeaveRequestController::class, 'cancel']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-requests.cancel');

    /*
    |--------------------------------------------------------------------------
    | Overtime Requests
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/overtime-requests',
        [OvertimeRequestController::class, 'index']
    )
        ->middleware('permission:overtime.view')
        ->name('overtime-requests.index');

    Route::get(
        '/overtime-requests/create',
        [OvertimeRequestController::class, 'create']
    )
        ->middleware('permission:overtime.manage')
        ->name('overtime-requests.create');

    Route::post(
        '/overtime-requests',
        [OvertimeRequestController::class, 'store']
    )
        ->middleware('permission:overtime.manage')
        ->name('overtime-requests.store');

    Route::get(
        '/overtime-requests/{overtimeRequest}',
        [OvertimeRequestController::class, 'show']
    )
        ->middleware('permission:overtime.view')
        ->name('overtime-requests.show');

    Route::post(
        '/overtime-requests/{overtimeRequest}/approve',
        [OvertimeRequestController::class, 'approve']
    )
        ->middleware('permission:overtime.approve')
        ->name('overtime-requests.approve');

    Route::post(
        '/overtime-requests/{overtimeRequest}/reject',
        [OvertimeRequestController::class, 'reject']
    )
        ->middleware('permission:overtime.approve')
        ->name('overtime-requests.reject');

    /*
    |--------------------------------------------------------------------------
    | Leave Balances
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leave-balances',
        [LeaveBalanceController::class, 'index']
    )
        ->middleware('permission:leave.view')
        ->name('leave-balances.index');

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Opening Import
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leave-balances/import-opening',
        [LeaveBalanceOpeningImportController::class, 'create']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-balances.import-opening.create');

    Route::post(
        '/leave-balances/import-opening',
        [LeaveBalanceOpeningImportController::class, 'store']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-balances.import-opening.store');

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Bulk Grant
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/leave-balances/bulk-grant',
        [LeaveBalanceController::class, 'bulkGrant']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-balances.bulk-grant');

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Detail
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/leave-balances/{employee_leave_balance}',
        [LeaveBalanceController::class, 'show']
    )
        ->middleware('permission:leave.view')
        ->name('leave-balances.show');

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Adjustment
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/leave-balances/{employee_leave_balance}/adjust',
        [LeaveBalanceController::class, 'adjust']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-balances.adjust');

    /*
    |--------------------------------------------------------------------------
    | Leave Balance Manual Grant
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/leave-balances/{employee_leave_balance}/grant',
        [LeaveBalanceController::class, 'grant']
    )
        ->middleware('permission:leave.manage')
        ->name('leave-balances.grant');
});