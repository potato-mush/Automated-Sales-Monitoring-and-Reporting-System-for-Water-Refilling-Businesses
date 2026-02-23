<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\GallonController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\LogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test endpoint for connection checking
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Server is reachable',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes - accepts both web session and JWT token authentication
Route::middleware('auth:web,api')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Dashboard routes (Admin only recommended, but employees can view limited data)
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('/transaction-type-breakdown', [DashboardController::class, 'transactionTypeBreakdown']);
        Route::get('/daily-report', [DashboardController::class, 'dailyReport']);
        Route::get('/weekly-report', [DashboardController::class, 'weeklyReport']);
        Route::get('/monthly-report', [DashboardController::class, 'monthlyReport']);
        Route::get('/yearly-report', [DashboardController::class, 'yearlyReport']);
    });

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/today-summary', [TransactionController::class, 'todaySummary']);
        Route::get('/statistics', [TransactionController::class, 'statistics']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::post('/', [TransactionController::class, 'store']);
    });

    // Gallon routes
    Route::prefix('gallons')->group(function () {
        Route::get('/', [GallonController::class, 'index']);
        Route::get('/status-summary', [GallonController::class, 'statusSummary']);
        Route::get('/overdue', [GallonController::class, 'overdue']);
        Route::get('/missing', [GallonController::class, 'missing']);
        Route::post('/scan', [GallonController::class, 'scan']);
        Route::post('/return', [GallonController::class, 'returnGallon']);
        Route::post('/bulk-create', [GallonController::class, 'bulkCreate']);
        Route::get('/{code}', [GallonController::class, 'show']);
        Route::get('/{code}/history', [GallonController::class, 'history']);
        
        // Cron job endpoint (should be protected by IP or special token in production)
        Route::post('/update-overdue', [GallonController::class, 'updateOverdue']);
    });

    // Inventory routes
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/statistics', [InventoryController::class, 'statistics']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::put('/{id}', [InventoryController::class, 'update']);
        Route::delete('/{id}', [InventoryController::class, 'destroy']);
        Route::post('/{id}/adjust', [InventoryController::class, 'adjustQuantity']);
    });

    // Employee routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/statistics', [EmployeeController::class, 'statistics']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
    });

    // Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::get('/{key}', [SettingsController::class, 'show']);
        Route::put('/{key}', [SettingsController::class, 'updateSingle']);
        Route::post('/clear-cache', [SettingsController::class, 'clearCache']);
    });

    // System Logs routes
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogController::class, 'index']);
        Route::get('/statistics', [LogController::class, 'statistics']);
        Route::get('/export', [LogController::class, 'export']);
        Route::post('/clear', [LogController::class, 'clear']);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'Water Refilling System API'
    ]);
});
