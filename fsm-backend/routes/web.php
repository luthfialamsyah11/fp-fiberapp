<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TechnicianController;
use App\Http\Controllers\Web\CustomerTrackingController;
use App\Http\Controllers\Web\BastController;
use App\Http\Controllers\Web\MediaController;

// Public Media Streaming Route (Fixes shared hosting symlink 404s & cross-origin canvas CORS)
Route::get('/storage/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.serve');

// Public Customer Live Tracking Page (Uber-Style, Rate-Limited)
Route::get('/track/{token}', [CustomerTrackingController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('customer.tracking');

// BAST Official Work Order Printout & SHA-256 Verification
Route::get('tasks/{task}/bast', [BastController::class, 'show'])->name('admin.tasks.bast');
Route::get('tasks/{task}/bast/verify', [BastController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('admin.tasks.bast.verify');

// Public Admin Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('admin.login.post');
});

// Protected Admin Routes (Strict RBAC - Active Admin Role Only)
Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/live-pulse', [DashboardController::class, 'livePulse'])->name('admin.live_pulse');

    // Technicians
    Route::resource('technicians', TechnicianController::class, [
        'as' => 'admin'
    ])->except(['show']);

    // Tasks
    Route::resource('tasks', TaskController::class, [
        'as' => 'admin'
    ]);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('admin.tasks.assign');
    Route::post('tasks/{task}/request-rework', [TaskController::class, 'requestRework'])->name('admin.tasks.rework');
    Route::post('tasks/{task}/notes', [TaskController::class, 'addProgressNote'])->name('admin.tasks.notes.store');
    Route::post('tasks/{task}/materials', [TaskController::class, 'addMaterial'])->name('admin.tasks.materials.store');
    Route::delete('tasks/{task}/materials/{material}', [TaskController::class, 'removeMaterial'])->name('admin.tasks.materials.destroy');
    Route::get('tasks/{task}/live-status', [TaskController::class, 'liveStatus'])->name('admin.tasks.live_status');

    // Inventory & Material Management
    Route::get('inventory/export', [\App\Http\Controllers\Web\InventoryController::class, 'export'])->name('admin.inventory.export');
    Route::resource('inventory', \App\Http\Controllers\Web\InventoryController::class, [
        'as' => 'admin'
    ])->only(['index', 'store', 'update', 'destroy']);
    Route::post('inventory/{inventory}/adjust-stock', [\App\Http\Controllers\Web\InventoryController::class, 'adjustStock'])->name('admin.inventory.adjust_stock');
    Route::post('inventory/{inventory}/toggle-status', [\App\Http\Controllers\Web\InventoryController::class, 'toggleStatus'])->name('admin.inventory.toggle_status');

    // FSM Admin Console Routes
    Route::get('/assignments', [\App\Http\Controllers\Web\AssignmentController::class, 'index'])->name('admin.assignments');
    Route::post('/assignments/{task}/auto-dispatch', [\App\Http\Controllers\Web\AssignmentController::class, 'autoDispatch'])->name('admin.assignments.auto_dispatch');
    Route::get('/tracking', [\App\Http\Controllers\Web\TrackingController::class, 'index'])->name('admin.tracking');
    Route::get('/tracking/live-data', [\App\Http\Controllers\Web\TrackingController::class, 'liveData'])->name('admin.tracking.live');
    Route::get('/proofs', [\App\Http\Controllers\Web\ProofController::class, 'index'])->name('admin.proofs');
    Route::get('/history', [\App\Http\Controllers\Web\JobHistoryController::class, 'index'])->name('admin.history');
    Route::get('/reports', [\App\Http\Controllers\Web\ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export', [\App\Http\Controllers\Web\ReportController::class, 'export'])->name('admin.reports.export');
    Route::get('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'update'])->name('admin.settings.update');
    Route::post('/settings/test-webhook', [\App\Http\Controllers\Web\SettingsController::class, 'testWebhook'])->name('admin.settings.test_webhook');
    Route::post('/settings/purge-cache', [\App\Http\Controllers\Web\SettingsController::class, 'purgeCache'])->name('admin.settings.purge_cache');

    // User Guide / Documentation
    Route::get('/guide', [\App\Http\Controllers\Web\GuideController::class, 'index'])->name('admin.guide');
});
