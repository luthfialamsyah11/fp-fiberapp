<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Tasks by status count
        $statusCounts = Task::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Tasks by priority count
        $priorityCounts = Task::select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->get()
            ->pluck('total', 'priority')
            ->toArray();

        // Technician performance (completed tasks count)
        $technicianCompletions = User::where('role', 'technician')
            ->withCount(['tasks' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->withCount(['tasks as total_assigned' => function ($q) {
                $q->whereNotNull('technician_id');
            }])
            ->get();

        // Average completion rate calculation
        $totalTasks = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        return view('reports.index', compact(
            'statusCounts',
            'priorityCounts',
            'technicianCompletions',
            'completionRate',
            'totalTasks',
            'completedTasks'
        ));
    }
}
