<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProgressUpdate;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $slaHours = (int) SettingService::get('sla_warning_hours', '2');
        $companyName = (string) SettingService::get('company_name', 'FiberOps Global Carrier');
        $mapStyle = (string) SettingService::get('map_style', 'openstreetmap');
        $pollInterval = (int) SettingService::get('gps_refresh_interval', '15');
        $onlineWindowMinutes = max(1, (int) round(User::ONLINE_WINDOW_SECONDS / 60));

        $taskStats = Task::selectRaw("
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN priority = 'high' AND status NOT IN ('completed', 'rejected') THEN 1 END) as urgent_active
        ")->first();

        $slaBreachedCount = Task::slaBreached($slaHours)->count();
        $completedToday = Task::where('status', 'completed')
            ->where('completed_at', '>=', now()->startOfDay())
            ->count();

        $lowStockItems = InventoryItem::where('is_active', true)
            ->where('stock', '<=', 5)
            ->count();

        $technicians = User::technicians()
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        $activeTechsCount = $technicians->where('is_active', true)->count();
        $onlineTechsCount = $technicians->filter(fn ($t) => $t->is_active && $t->isEffectivelyOnline())->count();

        $totalProofs = ProofOfWork::count();
        $totalTasksCount = (int) ($taskStats->total_tasks ?? 0);
        $completedTasksCount = (int) ($taskStats->completed ?? 0);
        $assignedCount = (int) ($taskStats->assigned ?? 0);
        $acceptedCount = (int) ($taskStats->accepted ?? 0);
        $ongoingCount = (int) ($taskStats->ongoing ?? 0);
        $pendingCount = (int) ($taskStats->pending ?? 0);

        $stats = [
            'total_tasks'        => $totalTasksCount,
            'pending'            => $pendingCount,
            'assigned'           => $assignedCount,
            'accepted'           => $acceptedCount,
            'on_going'           => $ongoingCount,
            'active_field'       => $assignedCount + $acceptedCount + $ongoingCount,
            'completed'          => $completedTasksCount,
            'completed_today'    => $completedToday,
            'rejected'           => (int) ($taskStats->rejected ?? 0),
            'urgent_active'      => (int) ($taskStats->urgent_active ?? 0),
            'sla_breached'       => $slaBreachedCount,
            'low_stock_items'    => $lowStockItems,
            'completion_rate'    => $totalTasksCount > 0 ? round(($completedTasksCount / $totalTasksCount) * 100, 1) : 0,
            'active_technicians' => $activeTechsCount,
            'online_technicians' => $onlineTechsCount,
            'fleet_utilization'  => $activeTechsCount > 0 ? round(($onlineTechsCount / $activeTechsCount) * 100) : 0,
            'total_proofs'       => $totalProofs,
        ];

        // 7-day real operational throughput (Created vs Completed per day)
        $startDate = now()->subDays(6)->startOfDay();
        $recentWeekTasks = Task::where('created_at', '>=', $startDate)
            ->orWhere(function ($q) use ($startDate) {
                $q->where('status', 'completed')->where('completed_at', '>=', $startDate);
            })
            ->get(['created_at', 'completed_at', 'status']);

        $weeklyTrend = [];
        $maxTrendValue = 1;
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dayKey = $day->format('Y-m-d');
            $createdOnDay = $recentWeekTasks->filter(fn ($t) => $t->created_at && $t->created_at->format('Y-m-d') === $dayKey)->count();
            $completedOnDay = $recentWeekTasks->filter(fn ($t) => $t->status === 'completed' && $t->completed_at && $t->completed_at->format('Y-m-d') === $dayKey)->count();
            $maxTrendValue = max($maxTrendValue, $createdOnDay, $completedOnDay);

            $weeklyTrend[] = [
                'date'      => $dayKey,
                'label'     => $day->format('D'),
                'day_num'   => $day->format('d M'),
                'is_today'  => $i === 0,
                'created'   => $createdOnDay,
                'completed' => $completedOnDay,
            ];
        }

        // Status pipeline breakdown
        $pipelineTotal = max(1, $pendingCount + $assignedCount + $acceptedCount + $ongoingCount + $completedTasksCount);
        $statusBreakdown = [
            ['key' => 'pending',   'label' => 'Pending',   'count' => $pendingCount,        'pct' => round(($pendingCount / $pipelineTotal) * 100, 1),        'bar' => 'bg-amber-500',   'dot' => 'bg-amber-500',   'url' => route('admin.dashboard', ['filter' => 'pending'])],
            ['key' => 'assigned',  'label' => 'Assigned',  'count' => $assignedCount,       'pct' => round(($assignedCount / $pipelineTotal) * 100, 1),       'bar' => 'bg-sky-500',     'dot' => 'bg-sky-500',     'url' => route('admin.tasks.index', ['status' => 'assigned'])],
            ['key' => 'accepted',  'label' => 'Accepted',  'count' => $acceptedCount,       'pct' => round(($acceptedCount / $pipelineTotal) * 100, 1),       'bar' => 'bg-indigo-500',  'dot' => 'bg-indigo-500',  'url' => route('admin.tasks.index', ['status' => 'accepted'])],
            ['key' => 'on-going',  'label' => 'On-Going',  'count' => $ongoingCount,        'pct' => round(($ongoingCount / $pipelineTotal) * 100, 1),        'bar' => 'bg-blue-600',    'dot' => 'bg-blue-600',    'url' => route('admin.dashboard', ['filter' => 'ongoing'])],
            ['key' => 'completed', 'label' => 'Completed', 'count' => $completedTasksCount, 'pct' => round(($completedTasksCount / $pipelineTotal) * 100, 1), 'bar' => 'bg-emerald-500', 'dot' => 'bg-emerald-500', 'url' => route('admin.dashboard', ['filter' => 'completed'])],
        ];

        $filter = $request->query('filter', 'all');
        $taskQuery = Task::with(['technician.latestLocation', 'latestProgressUpdate', 'proofs']);

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
            case 'sla':
                $taskQuery->slaBreached($slaHours);
                break;
            case 'completed':
                $taskQuery->where('status', 'completed');
                break;
            default:
                break;
        }

        $recentTasks = $taskQuery->orderBy('created_at', 'desc')->take(12)->get();

        $timelineLogs = ProgressUpdate::with(['task', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $fleetRoster = $technicians->map(function ($tech) {
            $ongoingTask = $tech->tasks->firstWhere('status', 'on-going')
                ?? $tech->tasks->firstWhere('status', 'accepted')
                ?? $tech->tasks->firstWhere('status', 'assigned');
            $effectiveOnline = $tech->is_active && $tech->isEffectivelyOnline();
            $loc = $tech->latestLocation;
            $capturedAt = $loc ? ($loc->captured_at ?? $loc->updated_at ?? $loc->created_at) : null;
            return [
                'id'               => $tech->id,
                'name'             => $tech->name,
                'phone'            => $tech->phone,
                'is_online'        => $effectiveOnline,
                'is_active'        => (bool) $tech->is_active,
                'active_task'      => $ongoingTask ? $ongoingTask->title : null,
                'active_task_id'   => $ongoingTask ? $ongoingTask->id : null,
                'active_task_stat' => $ongoingTask ? $ongoingTask->status : null,
                'active_tasks_qty' => $tech->tasks->count(),
                'last_ping'        => $capturedAt ? $capturedAt->diffForHumans() : 'No GPS signal',
                'accuracy_m'       => ($loc && $loc->accuracy_m !== null) ? round((float) $loc->accuracy_m) : null,
                'has_gps'          => (bool) ($loc && $loc->latitude && $loc->longitude),
                'latitude'         => $loc ? (float) $loc->latitude : null,
                'longitude'        => $loc ? (float) $loc->longitude : null,
            ];
        })->sortByDesc(function ($row) {
            return ($row['is_online'] ? 1000 : 0) + ($row['active_tasks_qty'] * 10) + ($row['has_gps'] ? 1 : 0);
        })->values();

        // Unified marker coloring matching /tracking & /guide v2.0:
        // ongoing (active task) = green, active (online standby) = blue, inactive (offline/stale) = grey
        $markerData = $technicians->map(function ($tech) {
            $loc = $tech->latestLocation;
            if (!$loc || !$loc->latitude || !$loc->longitude) {
                return null;
            }

            $isOnline = $tech->is_active && $tech->isEffectivelyOnline();
            $activeTask = $tech->tasks->firstWhere('status', 'on-going')
                ?? $tech->tasks->firstWhere('status', 'accepted')
                ?? $tech->tasks->firstWhere('status', 'assigned');
            $capturedAt = $loc->captured_at ?? $loc->updated_at ?? $loc->created_at;

            return [
                'id'          => $loc->id,
                'tech_id'     => $tech->id,
                'name'        => $tech->name,
                'email'       => $tech->email,
                'latitude'    => (float) $loc->latitude,
                'longitude'   => (float) $loc->longitude,
                'accuracy_m'  => $loc->accuracy_m !== null ? round((float) $loc->accuracy_m) : null,
                'last_ping'   => $capturedAt ? $capturedAt->diffForHumans() : 'Unknown',
                'markerColor' => !$isOnline ? 'inactive' : ($activeTask ? 'ongoing' : 'active'),
                'taskTitle'   => $activeTask ? $activeTask->title : 'Standby / Ready for Dispatch',
                'taskStatus'  => $activeTask ? $activeTask->status : null,
                'taskId'      => $activeTask ? $activeTask->id : null,
            ];
        })->filter()->values();

        $notifications = $timelineLogs->take(5)->map(function ($p) {
            return [
                'title'    => ($p->user->name ?? 'Technician') . ' updated work order ' . ($p->task ? '#TSK-' . str_pad($p->task->id, 4, '0', STR_PAD_LEFT) : ''),
                'note'     => $p->note ? \Illuminate\Support\Str::limit($p->note, 50) : 'Progress ' . $p->progress_percent . '%',
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
            'filter',
            'mapStyle',
            'pollInterval',
            'slaHours',
            'companyName',
            'onlineWindowMinutes',
            'weeklyTrend',
            'maxTrendValue',
            'statusBreakdown'
        ));
    }
}
