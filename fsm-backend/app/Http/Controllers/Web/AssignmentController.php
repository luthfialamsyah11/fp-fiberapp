<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $slaHours = (int) SettingService::get('sla_warning_hours', '2');

        // Active technicians with live GPS telemetry, active workload, and completed count
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with('latestLocation')
            ->withCount([
                'tasks' => function ($query) {
                    $query->whereIn('status', ['assigned', 'accepted', 'on-going']);
                },
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->orderBy('name')
            ->get();

        foreach ($technicians as $tech) {
            $tech->effectively_online = $tech->isEffectivelyOnline();
        }

        // Pending / Rejected queue strictly excluding completed tasks, ordered High -> Medium -> Low
        $pendingQueue = Task::whereIn('status', ['pending', 'rejected'])
            ->with('technician')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END ASC")
            ->orderBy('created_at', 'asc')
            ->take(15)
            ->get();

        foreach ($pendingQueue as $pendingTask) {
            $best = $this->findOptimalTechnician($pendingTask, $technicians);
            $pendingTask->recommended_tech = $best['technician'];
            $pendingTask->recommended_distance_km = $best['distance_km'];
        }

        // Filterable Recent Dispatches & Timelines
        $statusFilter = $request->get('status');
        $techFilter = $request->get('technician_id');
        $search = trim((string) $request->get('search', ''));

        $assignments = Task::with(['technician', 'latestProgressUpdate'])
            ->whereNotNull('technician_id')
            ->when($statusFilter && in_array($statusFilter, ['pending', 'assigned', 'accepted', 'on-going', 'completed', 'rejected'], true), function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            })
            ->when($techFilter, function ($q) use ($techFilter) {
                $q->where('technician_id', $techFilter);
            })
            ->when($search !== '', function ($q) use ($search) {
                $numericId = null;
                if (preg_match('/^(?:#?TSK-?)?0*(\d+)$/i', $search, $m)) {
                    $numericId = (int) $m[1];
                }
                $q->where(function ($sub) use ($search, $numericId) {
                    if ($numericId !== null && $numericId > 0) {
                        $sub->where('id', $numericId)->orWhere('title', 'like', "%{$search}%");
                    } else {
                        $sub->where('title', 'like', "%{$search}%");
                    }
                    $sub->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Recent Technician Decline Audit Feed (from APK Decline action)
        $recentDeclines = TaskStatusHistory::with(['task', 'actor'])
            ->where('from_status', 'assigned')
            ->whereIn('to_status', ['pending', 'rejected'])
            ->latest('id')
            ->take(8)
            ->get();

        // KPI Stats
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
            'recentDeclines',
            'totalAssigned',
            'acceptedCount',
            'ongoingCount',
            'completedCount',
            'rejectedCount',
            'pendingAcceptance',
            'acceptanceRate',
            'slaHours'
        ));
    }

    /**
     * 1-Click Smart Auto-Dispatch Nearest Available Technician (GPS + Workload + Online Presence).
     */
    public function autoDispatch(Request $request, Task $task)
    {
        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot auto-dispatch a completed work order. Request rework first.');
        }

        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with('latestLocation')
            ->withCount(['tasks' => function ($query) {
                $query->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        if ($technicians->isEmpty()) {
            return back()->with('error', 'No active field technicians available for auto-dispatch.');
        }

        foreach ($technicians as $tech) {
            $tech->effectively_online = $tech->isEffectivelyOnline();
        }

        $best = $this->findOptimalTechnician($task, $technicians);
        /** @var User|null $bestTech */
        $bestTech = $best['technician'];
        $distanceKm = $best['distance_km'];

        if (!$bestTech) {
            return back()->with('error', 'Could not resolve an eligible technician for auto-dispatch.');
        }

        $oldTechId = (int) ($task->technician_id ?? 0);
        $distanceLabel = $distanceKm !== null ? " ({$distanceKm} km away)" : '';
        $reason = "Auto-dispatched to nearest optimal technician {$bestTech->name}{$distanceLabel}";

        DB::transaction(function () use ($task, $bestTech, $oldTechId, $reason, $distanceKm) {
            $task->update(['technician_id' => $bestTech->id]);
            $task->refresh();

            if ($task->status !== 'assigned') {
                $this->stateMachine->transition(
                    $task,
                    'assigned',
                    auth()->user(),
                    $reason,
                    [
                        'auto_dispatch' => true,
                        'technician_id' => $bestTech->id,
                        'distance_km'   => $distanceKm,
                    ]
                );
            } elseif ($oldTechId !== $bestTech->id) {
                ProgressUpdate::create([
                    'task_id'          => $task->id,
                    'user_id'          => auth()->id(),
                    'note'             => $reason . '.',
                    'progress_percent' => (int) $task->progress_percent,
                ]);
            }
        });

        $taskCode = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        return back()->with('success', "Auto-dispatched {$taskCode} to {$bestTech->name}{$distanceLabel}.");
    }

    /**
     * Pick the optimal technician balancing Haversine GPS distance, active workload, and online status.
     *
     * @return array{technician: ?User, distance_km: ?float}
     */
    private function findOptimalTechnician(Task $task, Collection $technicians): array
    {
        if ($technicians->isEmpty()) {
            return ['technician' => null, 'distance_km' => null];
        }

        $hasTaskGps = $task->latitude !== null && $task->longitude !== null;
        $bestTech = null;
        $bestScore = PHP_FLOAT_MAX;
        $bestDistanceKm = null;

        foreach ($technicians as $tech) {
            $distMeters = null;
            if ($hasTaskGps && $tech->latestLocation && $tech->latestLocation->latitude !== null && $tech->latestLocation->longitude !== null) {
                $distMeters = SettingService::haversineMeters(
                    (float) $task->latitude,
                    (float) $task->longitude,
                    (float) $tech->latestLocation->latitude,
                    (float) $tech->latestLocation->longitude
                );
            }

            $baseMeters = $distMeters !== null ? $distMeters : 500000.0;
            $workloadPenalty = ((int) ($tech->tasks_count ?? 0)) * 2000.0;
            $onlinePenalty = !empty($tech->effectively_online) ? 0.0 : 15000.0;
            $score = $baseMeters + $workloadPenalty + $onlinePenalty;

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestTech = $tech;
                $bestDistanceKm = $distMeters !== null ? round($distMeters / 1000, 1) : null;
            }
        }

        return [
            'technician'  => $bestTech,
            'distance_km' => $bestDistanceKm,
        ];
    }
}
