<?php

use App\Http\Controllers\Api\MobilePosController;
use App\Http\Controllers\Api\MobileStockController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\ProfitLossController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Health Check API
 * 
 * Mengecek status koneksi ke server Warung.
 * @unauthenticated
 */
Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'Warung API is up and running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // POS & Scanner
    Route::get('/pos/products', [MobilePosController::class, 'products']);
    Route::post('/pos/checkout', [MobilePosController::class, 'checkout']);
    
    // Inventory & Stock Opname
    Route::get('/stock/lookup', [MobileStockController::class, 'lookup']);
    Route::post('/stock/adjustment', [MobileStockController::class, 'adjustment']);
    Route::post('/stock/opname', [MobileStockController::class, 'opname']);
    
    // Owner Dashboard
    Route::get('/dashboard/summary', [MobileDashboardController::class, 'summary']);

    // Reports & Accounting (Superadmin Only)
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/journal', [JournalController::class, 'index']);
        Route::get('/profit-loss', [ProfitLossController::class, 'index']);
    });
});
