<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\DashboardController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    require __DIR__.'/web/admin-tools.php';
    require __DIR__.'/web/master.php';
    require __DIR__.'/web/scheduling.php';
    require __DIR__.'/web/requests.php';
    require __DIR__.'/web/attendance.php';
    require __DIR__.'/web/review.php';
    require __DIR__.'/web/summary.php';
    require __DIR__.'/web/audit.php';
    require __DIR__.'/web/access.php';
    require __DIR__.'/web/payroll.php';
});