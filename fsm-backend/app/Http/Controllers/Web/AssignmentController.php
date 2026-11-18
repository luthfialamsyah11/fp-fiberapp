<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        // Get active technicians and calculate active tasks count (workload)
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->withCount(['tasks' => function ($query) {
                $query->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        // Get unassigned pending/rejected tasks awaiting dispatch
        $pendingQueue = Task::whereNull('technician_id')
            ->orWhereIn('status', ['pending', 'rejected'])
            ->with('technician')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get recent assignment status updates/history
        $assignments = Task::with('technician')
            ->whereNotNull('technician_id')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        // Stats on acceptance (including tasks declined back to pending recorded in TaskStatusHistory)
        $totalAssigned = Task::whereNotNull('technician_id')->count();
        $acceptedCount = Task::where('status', 'accepted')->count();
        $ongoingCount = Task::where('status', 'on-going')->count();
        $completedCount = Task::where('status', 'completed')->count();
        $declinedHistoryCount = TaskStatusHistory::where('from_status', 'assigned')
            ->whereIn('to_status', ['pending', 'rejected'])
            ->count();
        $rejectedCount = max(Task::where('status', 'rejected')->count(), $declinedHistoryCount);
        $pendingAcceptance = Task::where('status', 'assigned')->count();

        $totalDecisions = $acceptedCount + $ongoingCount + $completedCount + $rejectedCount;
        $acceptanceRate = $totalDecisions > 0
            ? round((($acceptedCount + $ongoingCount + $completedCount) / $totalDecisions) * 100, 1)
            : 100;

        return view('assignments.index', compact(
            'technicians',
            'pendingQueue',
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
