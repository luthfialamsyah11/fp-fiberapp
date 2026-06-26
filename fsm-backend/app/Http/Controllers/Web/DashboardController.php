<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tasks'        => Task::count(),
            'pending'            => Task::where('status', 'pending')->count(),
            'assigned'           => Task::where('status', 'assigned')->count(),
            'accepted'           => Task::where('status', 'accepted')->count(),
            'on_going'           => Task::where('status', 'on-going')->count(),
            'completed'          => Task::where('status', 'completed')->count(),
            'active_technicians' => User::where('role', 'technician')->where('is_active', true)->count(),
        ];

        $recentTasks = Task::with('technician')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $techLocations = \App\Models\TechnicianLocation::with('technician')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('technician_locations')
                    ->groupBy('technician_id');
            })
            ->get();

        return view('dashboard.index', compact('stats', 'recentTasks', 'techLocations'));
    }
}
