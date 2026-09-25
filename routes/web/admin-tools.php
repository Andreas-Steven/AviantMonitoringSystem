<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminTools\AdminToolsController;

Route::prefix('admin-tools')
    ->name('admin-tools.')
    ->group(function () {
        Route::get('/scheduling', [AdminToolsController::class, 'scheduling'])
            ->name('scheduling');

        Route::get('/attendance', [AdminToolsController::class, 'attendance'])
            ->name('attendance');

        Route::get('/payroll', [AdminToolsController::class, 'payroll'])
            ->name('payroll');
    });