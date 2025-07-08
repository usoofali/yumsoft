<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ShopController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SyncController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are protected by Sanctum middleware
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    
    // Shop-specific data
    Route::prefix('shops/{shop}')->group(function () {

        Route::get('/stock', [ShopController::class, 'getStock']);
        Route::get('/customers', [ShopController::class, 'getCustomers']);
        // Sync endpoints
        Route::get('/sync/updates', [SyncController::class, 'getUpdates']);

        Route::post('/customers', [SyncController::class, 'pushCustomers']);
        // Payment creation endpoint (matches create_payment)
        Route::post('/payments', [SyncController::class, 'pushPayments']);
    });
    
    // Sales operations
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/{sale}/receipt', [SaleController::class, 'uploadReceipt']);
    Route::post('/payments', [SaleController::class, 'recordPayment']);
    
    // Bulk sync operations
    Route::prefix('sync')->group(function () {
        Route::post('/sales', [SyncController::class, 'pushSales']);
        Route::post('/customers', [SyncController::class, 'pushCustomers']);
        Route::post('/payments', [SyncController::class, 'pushPayments']);
    });
    
    // Token refresh
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

