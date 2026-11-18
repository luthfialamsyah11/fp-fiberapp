<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $taskStats = Task::selectRaw("
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing,
            COUNT(CASE WHEN priority = 'high' AND status NOT IN ('completed', 'rejected') THEN 1 END) as urgent_active
        ")->first();

        $technicians = User::technicians()
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        $activeTechsCount = $technicians->where('is_active', true)->count();
        $onlineTechsCount = $technicians->filter(fn ($t) => $t->is_active && $t->isEffectivelyOnline(10))->count();

        $totalProofs = ProofOfWork::count();
        $totalTasksCount = (int) ($taskStats->total_tasks ?? 0);
        $completedTasksCount = (int) ($taskStats->completed ?? 0);

        $stats = [
            'total_tasks'        => $totalTasksCount,
            'pending'            => (int) ($taskStats->pending ?? 0),
            'assigned'           => (int) ($taskStats->assigned ?? 0),
            'accepted'           => (int) ($taskStats->accepted ?? 0),
            'on_going'           => (int) ($taskStats->ongoing ?? 0),
            'completed'          => $completedTasksCount,
            'urgent_active'      => (int) ($taskStats->urgent_active ?? 0),
            'completion_rate'    => $totalTasksCount > 0 ? round(($completedTasksCount / $totalTasksCount) * 100, 1) : 0,
            'active_technicians' => $activeTechsCount,
            'online_technicians' => $onlineTechsCount,
            'total_proofs'       => $totalProofs,
        ];

        $filter = $request->query('filter', 'all');
        $taskQuery = Task::with(['technician', 'latestProgressUpdate', 'proofs']);

        switch ($filter) {
            case 'pending':
                $taskQuery->where('status', 'pending');
                break;
            case 'ongoing':
                $taskQuery->whereIn('status', ['accepted', 'on-going']);
                break;
            case 'urgent':
                $taskQuery->where('priority', 'high')->whereNotIn('status', ['completed', 'rejected']);
                break;
            case 'completed':
                $taskQuery->where('status', 'completed');
                break;
            default:
                break;
        }

        $recentTasks = $taskQuery->orderBy('created_at', 'desc')->take(10)->get();

        $timelineLogs = ProgressUpdate::with(['task', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $fleetRoster = $technicians->map(function ($tech) {
            $ongoingTask = $tech->tasks->firstWhere('status', 'on-going') ?? $tech->tasks->firstWhere('status', 'accepted');
            $effectiveOnline = $tech->isEffectivelyOnline(10);
            return [
                'id'               => $tech->id,
                'name'             => $tech->name,
                'phone'            => $tech->phone,
                'is_online'        => $effectiveOnline,
                'is_active'        => (bool) $tech->is_active,
                'active_task'      => $ongoingTask ? $ongoingTask->title : null,
                'active_task_id'   => $ongoingTask ? $ongoingTask->id : null,
                'active_tasks_qty' => $tech->tasks->count(),
                'last_ping'        => $tech->latestLocation ? $tech->latestLocation->updated_at->diffForHumans() : 'No signal yet',
                'latitude'         => $tech->latestLocation ? (float) $tech->latestLocation->latitude : null,
                'longitude'        => $tech->latestLocation ? (float) $tech->latestLocation->longitude : null,
            ];
        });

        $markerData = $technicians->map(function ($tech) {
            $loc = $tech->latestLocation;
            if (!$loc || !$loc->latitude || !$loc->longitude) {
                return null;
            }

            $isOnline = $tech->isEffectivelyOnline(10);
            $ongoingTask = $tech->tasks->firstWhere('status', 'on-going');
            $hasOngoing = !is_null($ongoingTask);
            $activeTask = $ongoingTask ?? $tech->tasks->firstWhere('status', 'accepted');

            return [
                'id'          => $loc->id,
                'tech_id'     => $tech->id,
                'name'        => $tech->name,
                'email'       => $tech->email,
                'latitude'    => (float) $loc->latitude,
                'longitude'   => (float) $loc->longitude,
                'markerColor' => !$isOnline ? 'inactive' : ($hasOngoing ? 'ongoing' : 'active'),
                'taskTitle'   => $activeTask ? $activeTask->title : 'Standby / Idle',
                'taskId'      => $activeTask ? $activeTask->id : null,
            ];
        })->filter()->values();

        $notifications = $timelineLogs->take(4)->map(function ($p) {
            return [
                'title'    => ($p->user->name ?? 'Technician') . ' updated work order ' . ($p->task ? '#TSK-' . str_pad($p->task->id, 4, '0', STR_PAD_LEFT) : ''),
                'note'     => $p->note ? \Illuminate\Support\Str::limit($p->note, 45) : 'Progress ' . $p->progress_percent . '%',
                'time_ago' => $p->created_at ? $p->created_at->diffForHumans() : 'just now',
                'url'      => $p->task ? route('admin.tasks.show', $p->task->id) : route('admin.tasks.index'),
            ];
        });

        return view('dashboard.index', compact(
            'stats',
            'recentTasks',
            'timelineLogs',
            'markerData',
            'notifications',
            'fleetRoster',
            'filter'
        ));
    }
}
