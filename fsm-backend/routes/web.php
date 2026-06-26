<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TechnicianController;

// Public Admin Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });
    
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.post');
});

// Protected Admin Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Technicians
    Route::resource('technicians', TechnicianController::class, [
        'as' => 'admin'
    ]);
    
    // Tasks
    Route::resource('tasks', TaskController::class, [
        'as' => 'admin'
    ]);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('admin.tasks.assign');

    // New FSM Admin Redesign Routes
    Route::get('/assignments', [\App\Http\Controllers\Web\AssignmentController::class, 'index'])->name('admin.assignments');
    Route::get('/tracking', [\App\Http\Controllers\Web\TrackingController::class, 'index'])->name('admin.tracking');
    Route::get('/proofs', [\App\Http\Controllers\Web\ProofController::class, 'index'])->name('admin.proofs');
    Route::get('/history', [\App\Http\Controllers\Web\JobHistoryController::class, 'index'])->name('admin.history');
    Route::get('/reports', [\App\Http\Controllers\Web\ReportController::class, 'index'])->name('admin.reports');
    Route::get('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'update'])->name('admin.settings.update');
});
