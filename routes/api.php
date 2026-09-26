<?php

use App\Http\Controllers\Api\Controller\ProductionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'production.dashboard.access'])->group(function (): void {
    Route::get('/dashboard', [ProductionController::class, 'dashboard'])->name('api.dashboard');
    Route::get('/dashboard/machine/{id}', [ProductionController::class, 'machine'])->name('api.dashboard.machine');
    Route::get('/production-orders', [ProductionController::class, 'productionOrders'])->name('api.production-orders.index');
    Route::post('/production-results', [ProductionController::class, 'storeProductionResult'])->name('api.production-results.store');
});
