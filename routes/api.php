<?php

use App\Http\Controllers\Api\ProductionApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'production.dashboard.access'])->group(function (): void {
    Route::get('/dashboard', [ProductionApiController::class, 'dashboard'])->name('api.dashboard');
    Route::get('/dashboard/machine/{id}', [ProductionApiController::class, 'machine'])->name('api.dashboard.machine');
    Route::get('/production-orders', [ProductionApiController::class, 'productionOrders'])->name('api.production-orders.index');
    Route::post('/production-results', [ProductionApiController::class, 'storeProductionResult'])->name('api.production-results.store');
});
