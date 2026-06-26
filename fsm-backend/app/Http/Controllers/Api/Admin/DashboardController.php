<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TechnicianLocation;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total'       => Task::count(),
            'assigned'    => Task::where('status', 'assigned')->count(),
            'accepted'    => Task::where('status', 'accepted')->count(),
            'ongoing'     => Task::where('status', 'on-going')->count(),
            'completed'   => Task::where('status', 'completed')->count(),
            'active_techs'=> User::where('role', 'technician')->where('is_active', true)->count(),
        ];

        $recentTasks = Task::with('technician')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $techLocations = TechnicianLocation::with('technician')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('technician_locations')
                    ->groupBy('technician_id');
            })
            ->get();

        return response()->json([
            'stats'          => $stats,
            'recent_tasks'   => $recentTasks,
            'tech_locations' => $techLocations,
        ]);
    }
}