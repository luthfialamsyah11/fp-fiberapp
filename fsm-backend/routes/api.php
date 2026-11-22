<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
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
        'version' => '1.2.0',
        'status'  => 'running',
        'docs'    => 'Import openapi.json into Apidog or Postman for full API specification',
    ]);
});

// Public Authentication (Strict brute-force protection: 5 attempts per minute)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Public Customer Live Tracking Polling API & Post-Completion CSAT Rating (Rate-limited)
Route::get('/public/track/{token}', [CustomerTrackingController::class, 'apiStatus'])->middleware('throttle:60,1');
Route::post('/public/track/{token}/rating', [CustomerTrackingController::class, 'submitRating'])->middleware('throttle:10,1');

// Public Payment Gateway Webhooks (Midtrans / Xendit / Simulator)
Route::post('/webhooks/payment', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handleWebhook']);

// Authenticated Routes
Route::middleware(['auth:sanctum', 'active', 'tenant.active'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('throttle:10,1');

    // Push Notification Device Tokens (FCM / Capacitor)
    Route::post('/devices/token', [DeviceTokenController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/devices/token', [DeviceTokenController::class, 'destroy'])->middleware('throttle:30,1');

    // Technician - Tasks & Deterministic State Machine
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/sync-signal', [TaskController::class, 'syncSignal'])->middleware('throttle:120,1');
    Route::get('/tasks/history', [TaskController::class, 'history']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks/{task}/accept', [TaskController::class, 'accept'])->middleware('idempotent');
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject'])->middleware('idempotent');
    Route::post('/tasks/{task}/start', [TaskController::class, 'start'])->middleware('idempotent');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('idempotent');

    // Technician - Progress
    Route::post('/tasks/{task}/progress', [ProgressController::class, 'store'])->middleware(['throttle:30,1', 'idempotent']);

    // Technician - GPS (Single & Offline Batch Queue Flush)
    Route::post('/location', [LocationController::class, 'update'])->middleware('throttle:120,1');
    Route::post('/location/batch', [LocationController::class, 'batchUpdate'])->middleware('throttle:60,1');

    // Technician - Proof of Work
    Route::get('/tasks/{task}/proof', [ProofController::class, 'index']);
    Route::get('/tasks/{task}/proofs', [ProofController::class, 'index']);
    Route::post('/tasks/{task}/proof', [ProofController::class, 'store'])->middleware(['throttle:30,1', 'idempotent']);

    // Technician - Active Inventory Items Picker & Nearby Fiber GIS Nodes (OLT/ODC/ODP)
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/fiber-nodes/nearby', [\App\Http\Controllers\Web\FiberNodeController::class, 'apiNearby']);

    // Technician & Admin - Task Materials (BOM) with Idempotency Guard
    Route::get('/tasks/{task}/materials', [TaskMaterialController::class, 'index']);
    Route::post('/tasks/{task}/materials', [TaskMaterialController::class, 'store'])->middleware('idempotent');
    Route::delete('/tasks/{task}/materials/{material}', [TaskMaterialController::class, 'destroy']);

    // Technician & Admin - Digital Customer Signature
    Route::post('/tasks/{task}/signature', [TaskSignatureController::class, 'store'])->middleware('idempotent');

    // Technician & Admin - Dynamic QRIS On-Site Billing Collection
    Route::post('/tasks/{task}/qris', [\App\Http\Controllers\Api\TaskPaymentController::class, 'generateQris'])->middleware(['throttle:30,1', 'idempotent']);
    Route::get('/tasks/{task}/payment-status', [\App\Http\Controllers\Api\TaskPaymentController::class, 'paymentStatus']);

    // Admin Routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Tasks
        Route::get('/tasks', [AdminTaskController::class, 'index']);
        Route::post('/tasks', [AdminTaskController::class, 'store']);
        Route::get('/tasks/{task}', [AdminTaskController::class, 'show']);
        Route::put('/tasks/{task}', [AdminTaskController::class, 'update']);
        Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy']);
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
