<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        // Get all technicians and calculate active tasks count (workload)
        $technicians = User::where('role', 'technician')
            ->withCount(['tasks' => function ($query) {
                $query->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        // Get recent assignment status updates/history
        $assignments = Task::with('technician')
            ->whereNotNull('technician_id')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Stats on acceptance
        $totalAssigned = Task::whereNotNull('technician_id')->count();
        $acceptedCount = Task::where('status', 'accepted')->count();
        $ongoingCount = Task::where('status', 'on-going')->count();
        $completedCount = Task::where('status', 'completed')->count();
        $rejectedCount = Task::where('status', 'rejected')->count();
        $pendingAcceptance = Task::where('status', 'assigned')->count();

        $acceptanceRate = $totalAssigned > 0 
            ? round((($acceptedCount + $ongoingCount + $completedCount) / $totalAssigned) * 100, 1) 
            : 100;

        return view('assignments.index', compact(
            'technicians', 
            'assignments', 
            'totalAssigned', 
            'acceptedCount', 
            'ongoingCount', 
            'completedCount', 
            'rejectedCount', 
            'pendingAcceptance',
            'acceptanceRate'
        ));
    }
}
