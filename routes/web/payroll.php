<?php

use App\Http\Controllers\Web\Payroll\EmployeeDebtController;
use App\Http\Controllers\Web\Payroll\PayrollDeductionController;
use App\Http\Controllers\Web\Payroll\PayrollDeductionTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::get('deduction-types', [PayrollDeductionTypeController::class, 'index'])
        ->middleware('permission:payroll_deduction.view')
        ->name('deduction-types.index');

    Route::get('deduction-types/create', [PayrollDeductionTypeController::class, 'create'])
        ->middleware('permission:payroll_deduction.manage')
        ->name('deduction-types.create');

    Route::post('deduction-types', [PayrollDeductionTypeController::class, 'store'])
        ->middleware('permission:payroll_deduction.manage')
        ->name('deduction-types.store');

    Route::get('deductions', [PayrollDeductionController::class, 'index'])
        ->middleware('permission:payroll_deduction.view')
        ->name('deductions.index');

    Route::get('deductions/create', [PayrollDeductionController::class, 'create'])
        ->middleware('permission:payroll_deduction.manage')
        ->name('deductions.create');

    Route::post('deductions', [PayrollDeductionController::class, 'store'])
        ->middleware('permission:payroll_deduction.manage')
        ->name('deductions.store');

    Route::get('deductions/{deduction}', [PayrollDeductionController::class, 'show'])
        ->middleware('permission:payroll_deduction.view')
        ->name('deductions.show');

    Route::post('deductions/{deduction}/cancel', [PayrollDeductionController::class, 'cancel'])
        ->middleware('permission:payroll_deduction.manage')
        ->name('deductions.cancel');

    Route::get('debts', [EmployeeDebtController::class, 'index'])
        ->middleware('permission:employee_debt.view')
        ->name('debts.index');

    Route::get('debts/create', [EmployeeDebtController::class, 'create'])
        ->middleware('permission:employee_debt.manage')
        ->name('debts.create');

    Route::post('debts', [EmployeeDebtController::class, 'store'])
        ->middleware('permission:employee_debt.manage')
        ->name('debts.store');

    Route::get('debts/{debt}', [EmployeeDebtController::class, 'show'])
        ->middleware('permission:employee_debt.view')
        ->name('debts.show');

    Route::post('debts/{debt}/transactions', [EmployeeDebtController::class, 'addTransaction'])
        ->middleware('permission:employee_debt.manage')
        ->name('debts.transactions.store');

    Route::post('debts/{debt}/transactions/{tx}/reverse', [EmployeeDebtController::class, 'reverseTransaction'])
        ->middleware('permission:employee_debt.manage')
        ->name('debts.transactions.reverse');
});