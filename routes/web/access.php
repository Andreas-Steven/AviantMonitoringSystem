<?php

use App\Http\Controllers\Web\Access\AppPermissionController;
use App\Http\Controllers\Web\Access\AppRoleController;
use App\Http\Controllers\Web\Access\AppUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('access')->name('access.')->group(function (): void {
    Route::get('users', [AppUserController::class, 'index'])
        ->middleware('permission:user.view')
        ->name('users.index');

    Route::get('users/create', [AppUserController::class, 'create'])
        ->middleware('permission:user.manage')
        ->name('users.create');

    Route::post('users', [AppUserController::class, 'store'])
        ->middleware('permission:user.manage')
        ->name('users.store');

    Route::get('users/{app_user}', [AppUserController::class, 'show'])
        ->middleware('permission:user.view')
        ->name('users.show');

    Route::get('users/{app_user}/edit', [AppUserController::class, 'edit'])
        ->middleware('permission:user.manage')
        ->name('users.edit');

    Route::put('users/{app_user}', [AppUserController::class, 'update'])
        ->middleware('permission:user.manage')
        ->name('users.update');

    Route::get('roles', [AppRoleController::class, 'index'])
        ->middleware('permission:role.view')
        ->name('roles.index');

    Route::get('roles/{app_role}', [AppRoleController::class, 'show'])
        ->middleware('permission:role.view')
        ->name('roles.show');

    Route::get('roles/{app_role}/edit', [AppRoleController::class, 'edit'])
        ->middleware('permission:role.manage')
        ->name('roles.edit');

    Route::put('roles/{app_role}', [AppRoleController::class, 'update'])
        ->middleware('permission:role.manage')
        ->name('roles.update');

    Route::get('permissions', [AppPermissionController::class, 'index'])
        ->middleware('permission:role.view')
        ->name('permissions.index');
});