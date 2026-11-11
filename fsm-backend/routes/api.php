<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ProofController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\TaskMaterialController;
use App\Http\Controllers\Api\TaskSignatureController;
use App\Http\Controllers\Web\CustomerTrackingController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Api\Admin\TechnicianController;
use Illuminate\Support\Facades\Route;

// Welcome / Info
Route::get('/', function () {
    return response()->json([
        'name'    => 'FSM (Field Service Management) API',
        'version' => '1.1.0',
        'status'  => 'running',
        'docs'    => 'Import openapi.json ke Apidog/Postman untuk dokumentasi lengkap',
    ]);
});

// Public Authentication
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');

// Public Customer Live Tracking Polling API
Route::get('/public/track/{token}', [CustomerTrackingController::class, 'apiStatus']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Teknisi - Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/history', [TaskController::class, 'history']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks/{task}/accept', [TaskController::class, 'accept']);
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject']);
    Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);

    // Teknisi - Progress
    Route::post('/tasks/{task}/progress', [ProgressController::class, 'store'])->middleware('throttle:30,1');

    // Teknisi - GPS
    Route::post('/location', [LocationController::class, 'update'])->middleware('throttle:60,1');

    // Teknisi - Proof of Work
    Route::get('/tasks/{task}/proof', [ProofController::class, 'index']);
    Route::post('/tasks/{task}/proof', [ProofController::class, 'store'])->middleware('throttle:30,1');

    // Teknisi - Active Inventory Items Picker
    Route::get('/inventory', [InventoryController::class, 'index']);

    // Teknisi & Admin - Task Materials (BOM)
    Route::get('/tasks/{task}/materials', [TaskMaterialController::class, 'index']);
    Route::post('/tasks/{task}/materials', [TaskMaterialController::class, 'store']);
    Route::delete('/tasks/{task}/materials/{material}', [TaskMaterialController::class, 'destroy']);

    // Teknisi & Admin - Digital Customer Signature
    Route::post('/tasks/{task}/signature', [TaskSignatureController::class, 'store']);

    // Admin Routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Tasks
        Route::get('/tasks', [AdminTaskController::class, 'index']);
        Route::post('/tasks', [AdminTaskController::class, 'store']);
        Route::get('/tasks/{task}', [AdminTaskController::class, 'show']);
        Route::put('/tasks/{task}', [AdminTaskController::class, 'update']);
        Route::post('/tasks/{task}/assign', [AdminTaskController::class, 'assign']);

        // Technicians
        Route::get('/technicians', [TechnicianController::class, 'index']);
        Route::post('/technicians', [TechnicianController::class, 'store']);
        Route::put('/technicians/{user}', [TechnicianController::class, 'update']);
        Route::delete('/technicians/{user}', [TechnicianController::class, 'destroy']);

        // Inventory Management
        Route::get('/inventory', [InventoryController::class, 'adminIndex']);
        Route::post('/inventory', [InventoryController::class, 'store']);
        Route::put('/inventory/{item}', [InventoryController::class, 'update']);
        Route::delete('/inventory/{item}', [InventoryController::class, 'destroy']);
    });
});
