<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TechnicianController;
use App\Http\Controllers\Web\CustomerTrackingController;
use App\Http\Controllers\Web\BastController;
use App\Http\Controllers\Web\MediaController;
use App\Http\Controllers\Web\PublicRequestController;
use App\Http\Controllers\Web\ClientRequestController;

// Public Media Streaming Route (Fixes shared hosting symlink 404s & cross-origin canvas CORS)
Route::get('/storage/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.serve');

// Public Client Service Request Portal (Direct Access — No Login / Register Required)
Route::get('/request', [PublicRequestController::class, 'create'])->name('public.request.create');
Route::post('/request', [PublicRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.request.store');
Route::get('/request/receipt/{token}', [PublicRequestController::class, 'receipt'])
    ->middleware('throttle:60,1')
    ->name('public.request.receipt');

// Public Self-Service ISP Onboarding Registration
Route::get('/register-isp', [\App\Http\Controllers\Web\TenantRegistrationController::class, 'showRegistrationForm'])->name('tenant.register');
Route::post('/register-isp', [\App\Http\Controllers\Web\TenantRegistrationController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('tenant.register.post');

// Public Customer Live Tracking Page (Uber-Style, Rate-Limited)
Route::get('/track', [CustomerTrackingController::class, 'lookupForm'])->name('tracking.lookup');
Route::post('/track/lookup', [CustomerTrackingController::class, 'processLookup'])
    ->middleware('throttle:20,1')
    ->name('tracking.process_lookup');
Route::get('/track/{token}', [CustomerTrackingController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('customer.tracking');

// BAST Official Work Order Printout & SHA-256 Verification
Route::get('tasks/{task}/bast', [BastController::class, 'show'])->name('admin.tasks.bast');
Route::get('tasks/{task}/bast/verify', [BastController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('admin.tasks.bast.verify');
Route::get('bast/verify/{hash}', [BastController::class, 'publicVerify'])
    ->middleware('throttle:60,1')
    ->name('bast.public_verify');

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

// Protected Portal Staff Routes (Granular Multi-Role RBAC)
Route::middleware(['auth', 'active', 'tenant.active', 'role:admin,super_admin,noc_dispatcher,warehouse_admin,field_supervisor'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/live-pulse', [DashboardController::class, 'livePulse'])->name('admin.live_pulse');
    Route::get('/guide', [\App\Http\Controllers\Web\GuideController::class, 'index'])->name('admin.guide');

    // Tasks Read & BOM / Notes (shared across portal staff, write ops guarded below)
    Route::middleware('role:admin,super_admin,noc_dispatcher,field_supervisor,warehouse_admin')->group(function () {
        Route::get('tasks', [TaskController::class, 'index'])->name('admin.tasks.index');
        Route::get('tasks/{task}/live-status', [TaskController::class, 'liveStatus'])->name('admin.tasks.live_status');
    });

    // NOC Dispatcher, Field Supervisor & Super Admin Operations
    Route::middleware('role:admin,super_admin,noc_dispatcher,field_supervisor')->group(function () {
        Route::get('/tracking', [\App\Http\Controllers\Web\TrackingController::class, 'index'])->name('admin.tracking');
        Route::get('/tracking/live-data', [\App\Http\Controllers\Web\TrackingController::class, 'liveData'])->name('admin.tracking.live');
        Route::get('/proofs', [\App\Http\Controllers\Web\ProofController::class, 'index'])->name('admin.proofs');
        Route::get('/history', [\App\Http\Controllers\Web\JobHistoryController::class, 'index'])->name('admin.history');
        Route::get('/reports', [\App\Http\Controllers\Web\ReportController::class, 'index'])->name('admin.reports');
        Route::get('/reports/export', [\App\Http\Controllers\Web\ReportController::class, 'export'])->name('admin.reports.export');
        Route::post('tasks/{task}/request-rework', [TaskController::class, 'requestRework'])->name('admin.tasks.rework');
        Route::post('tasks/{task}/notes', [TaskController::class, 'addProgressNote'])->name('admin.tasks.notes.store');
    });

    // NOC Dispatcher & Super Admin Dispatch / CRM / GIS Write Operations
    Route::middleware('role:admin,super_admin,noc_dispatcher')->group(function () {
        Route::get('tasks/create', [TaskController::class, 'create'])->name('admin.tasks.create');
        Route::post('tasks', [TaskController::class, 'store'])->name('admin.tasks.store');
        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('admin.tasks.edit');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('admin.tasks.update');
        Route::patch('tasks/{task}', [TaskController::class, 'update']);
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('admin.tasks.destroy');
        Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('admin.tasks.assign');

        // Client Requests Inbox (Public Client Portal Submissions)
        Route::get('/client-requests', [ClientRequestController::class, 'index'])->name('admin.client_requests.index');
        Route::post('/client-requests/{task}/assign', [ClientRequestController::class, 'assign'])->name('admin.client_requests.assign');
        Route::post('/client-requests/{task}/auto-assign', [ClientRequestController::class, 'autoAssign'])->name('admin.client_requests.auto_assign');
        Route::post('/client-requests/{task}/reject', [ClientRequestController::class, 'reject'])->name('admin.client_requests.reject');

        // Assignment Monitoring
        Route::get('/assignments', [\App\Http\Controllers\Web\AssignmentController::class, 'index'])->name('admin.assignments');
        Route::post('/assignments/{task}/auto-dispatch', [\App\Http\Controllers\Web\AssignmentController::class, 'autoDispatch'])->name('admin.assignments.auto_dispatch');

        // Subscriber CRM & Fiber GIS Asset Registry (OLT / ODC / ODP)
        Route::get('/customers', [\App\Http\Controllers\Web\CustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/customers', [\App\Http\Controllers\Web\CustomerController::class, 'store'])->name('admin.customers.store');
        Route::put('/customers/{customer}', [\App\Http\Controllers\Web\CustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/customers/{customer}', [\App\Http\Controllers\Web\CustomerController::class, 'destroy'])->name('admin.customers.destroy');

        Route::get('/fiber-assets', [\App\Http\Controllers\Web\FiberNodeController::class, 'index'])->name('admin.fiber_assets.index');
        Route::post('/fiber-assets', [\App\Http\Controllers\Web\FiberNodeController::class, 'store'])
            ->middleware('tenant.quota:fiber_node')
            ->name('admin.fiber_assets.store');
        Route::put('/fiber-assets/{fiberNode}', [\App\Http\Controllers\Web\FiberNodeController::class, 'update'])->name('admin.fiber_assets.update');
        Route::delete('/fiber-assets/{fiberNode}', [\App\Http\Controllers\Web\FiberNodeController::class, 'destroy'])->name('admin.fiber_assets.destroy');
    });

    // Task detail show (registered after tasks/create to avoid route collision)
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('admin.tasks.show');

    // Warehouse Admin, NOC Dispatcher & Super Admin Inventory / BOM
    Route::middleware('role:admin,super_admin,warehouse_admin,noc_dispatcher')->group(function () {
        Route::get('inventory/export', [\App\Http\Controllers\Web\InventoryController::class, 'export'])->name('admin.inventory.export');
        Route::resource('inventory', \App\Http\Controllers\Web\InventoryController::class, [
            'as' => 'admin'
        ])->only(['index', 'store', 'update', 'destroy']);
        Route::post('inventory/{inventory}/adjust-stock', [\App\Http\Controllers\Web\InventoryController::class, 'adjustStock'])->name('admin.inventory.adjust_stock');
        Route::post('inventory/{inventory}/toggle-status', [\App\Http\Controllers\Web\InventoryController::class, 'toggleStatus'])->name('admin.inventory.toggle_status');
        Route::post('tasks/{task}/materials', [TaskController::class, 'addMaterial'])->name('admin.tasks.materials.store');
        Route::delete('tasks/{task}/materials/{material}', [TaskController::class, 'removeMaterial'])->name('admin.tasks.materials.destroy');
    });

    // Super Admin Only: Staff & Technicians User Management & Platform Settings
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::resource('staff', \App\Http\Controllers\Web\StaffController::class, [
            'as' => 'admin'
        ])->except(['show']);
        Route::post('staff/{staff}/toggle-status', [\App\Http\Controllers\Web\StaffController::class, 'toggleStatus'])->name('admin.staff.toggle_status');

        Route::post('technicians', [TechnicianController::class, 'store'])
            ->middleware('tenant.quota:technician')
            ->name('admin.technicians.store');
        Route::resource('technicians', TechnicianController::class, [
            'as' => 'admin'
        ])->except(['show', 'store']);

        // Platform Master Console: Multi-Tenant Oversight
        Route::get('/master-admin', [\App\Http\Controllers\Web\MasterAdminController::class, 'index'])->name('master_admin.index');
        Route::post('/master-admin/tenants/{tenant}/toggle', [\App\Http\Controllers\Web\MasterAdminController::class, 'toggleStatus'])->name('master_admin.tenants.toggle');
        Route::post('/master-admin/tenants/{tenant}/plan', [\App\Http\Controllers\Web\MasterAdminController::class, 'updatePlan'])->name('master_admin.tenants.plan');

        Route::get('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'index'])->name('admin.settings');
        Route::post('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('/settings/test-webhook', [\App\Http\Controllers\Web\SettingsController::class, 'testWebhook'])->name('admin.settings.test_webhook');
        Route::post('/settings/purge-cache', [\App\Http\Controllers\Web\SettingsController::class, 'purgeCache'])->name('admin.settings.purge_cache');
        Route::post('/settings/escalate-sla', [\App\Http\Controllers\Web\SettingsController::class, 'escalateSlaNow'])->name('admin.settings.escalate_sla');
    });
});
