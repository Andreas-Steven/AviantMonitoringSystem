<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Audit\SystemChangeLogController;
use App\Http\Controllers\Web\Audit\AppLoginAuditController;

Route::prefix('audit')->name('audit.')->group(function () {
    Route::get('system-change-logs', [SystemChangeLogController::class, 'index'])
        ->middleware('permission:audit.view')
        ->name('system-change-logs.index');

    Route::get('system-change-logs/{changeLog}', [SystemChangeLogController::class, 'show'])
        ->middleware('permission:audit.view')
        ->name('system-change-logs.show');

    Route::get('login-audits', [AppLoginAuditController::class, 'index'])
        ->middleware('permission:audit.view')
        ->name('login-audits.index');

    Route::get('login-audits/{loginAudit}', [AppLoginAuditController::class, 'show'])
        ->middleware('permission:audit.view')
        ->name('login-audits.show');
});