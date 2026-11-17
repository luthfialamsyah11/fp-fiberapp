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
        $taskStats = Task::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
        ")->first();

        $stats = [
            'total'        => (int) ($taskStats->total ?? 0),
            'assigned'     => (int) ($taskStats->assigned ?? 0),
            'accepted'     => (int) ($taskStats->accepted ?? 0),
            'ongoing'      => (int) ($taskStats->ongoing ?? 0),
            'completed'    => (int) ($taskStats->completed ?? 0),
            'active_techs' => User::where('role', 'technician')->where('is_active', true)->count(),
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