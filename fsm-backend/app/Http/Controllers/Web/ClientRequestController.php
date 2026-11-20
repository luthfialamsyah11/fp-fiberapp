<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientRequestController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $statusFilter = (string) $request->get('status', '');
        $search = trim((string) $request->get('search', ''));
        $slaHours = (int) SettingService::get('sla_warning_hours', '2');

        $requests = Task::clientPortal()
            ->with(['technician.latestLocation', 'latestProgressUpdate'])
            ->withCount(['proofs', 'materials'])
            ->when($statusFilter === 'active', fn ($q) => $q->whereIn('status', ['assigned', 'accepted', 'on-going']))
            ->when(in_array($statusFilter, ['pending', 'assigned', 'accepted', 'on-going', 'completed', 'rejected'], true), fn ($q) => $q->where('status', $statusFilter))
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
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('service_category', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $countsRow = Task::clientPortal()->selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status IN ('assigned', 'accepted', 'on-going') THEN 1 END) as active,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
        ")->first();

        $summary = [
            'total'     => (int) ($countsRow->total ?? 0),
            'pending'   => (int) ($countsRow->pending ?? 0),
            'active'    => (int) ($countsRow->active ?? 0),
            'completed' => (int) ($countsRow->completed ?? 0),
            'rejected'  => (int) ($countsRow->rejected ?? 0),
        ];

        // Load all active technicians with live Online presence & GPS coordinates
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with('latestLocation')
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->orderBy('name')
            ->get();

        $technicianFleet = $technicians->map(function (User $tech) {
            $loc = $tech->latestLocation;
            $capturedAt = $loc ? ($loc->captured_at ?? $loc->created_at) : null;
            return [
                'id'                 => $tech->id,
                'name'               => $tech->name,
                'email'              => $tech->email,
                'phone'              => $tech->phone ?? '',
                'is_online'          => $tech->isEffectivelyOnline(),
                'active_tasks_count' => (int) ($tech->active_tasks_count ?? 0),
                'latitude'           => ($loc && $loc->latitude !== null) ? (float) $loc->latitude : null,
                'longitude'          => ($loc && $loc->longitude !== null) ? (float) $loc->longitude : null,
                'last_ping'          => $capturedAt ? $capturedAt->diffForHumans() : 'No GPS signal',
            ];
        })->sortByDesc(function ($t) {
            return ($t['is_online'] ? 10000 : 0) - ($t['active_tasks_count'] * 10);
        })->values();

        $onlineTechCount = $technicianFleet->where('is_online', true)->count();

        // Build safe JSON payload per request for the Eye-Icon Detail & Smart Dispatch Modal
        $requestModalData = $requests->getCollection()->mapWithKeys(function (Task $task) {
            $task->ensureTrackingToken();
            return [
                $task->id => [
                    'id'                     => $task->id,
                    'code'                   => '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    'title'                  => $task->title,
                    'service_category'       => $task->service_category,
                    'service_category_label' => $task->service_category_label,
                    'customer_name'          => $task->customer_name,
                    'customer_phone'         => $task->customer_phone ?? '',
                    'customer_email'         => $task->customer_email ?? '',
                    'address'                => $task->resolved_location,
                    'latitude'               => $task->latitude !== null ? (float) $task->latitude : null,
                    'longitude'              => $task->longitude !== null ? (float) $task->longitude : null,
                    'description'            => $task->description ?? '',
                    'priority'               => $task->priority,
                    'status'                 => $task->status,
                    'status_label'           => $task->status_label,
                    'progress_percent'       => (int) $task->progress_percent,
                    'technician_id'          => $task->technician_id,
                    'technician_name'        => $task->technician?->name,
                    'client_photo_url'       => $task->client_photo_url,
                    'tracking_url'           => $task->tracking_url,
                    'detail_url'             => route('admin.tasks.show', $task->id),
                    'assign_url'             => route('admin.client_requests.assign', $task->id),
                    'auto_assign_url'        => route('admin.client_requests.auto_assign', $task->id),
                    'reject_url'             => route('admin.client_requests.reject', $task->id),
                    'created_at_human'       => $task->created_at ? $task->created_at->diffForHumans() : '-',
                    'created_at_full'        => $task->created_at ? $task->created_at->format('d M Y, H:i') : '-',
                ],
            ];
        });

        $publicFormUrl = route('public.request.create');

        return view('client-requests.index', compact(
            'requests',
            'summary',
            'technicianFleet',
            'onlineTechCount',
            'requestModalData',
            'statusFilter',
            'search',
            'slaHours',
            'publicFormUrl'
        ));
    }

    /**
     * Assign ANY active technician (Online or Free choice) to a client-submitted work order.
     */
    public function assign(Request $request, Task $task)
    {
        $validated = $request->validate([
            'technician_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
        ]);

        $newTechId = (int) $validated['technician_id'];
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);

        $outcome = DB::transaction(function () use ($task, $newTechId) {
            /** @var Task $locked */
            $locked = Task::lockForUpdate()->findOrFail($task->id);

            if ($locked->status === 'completed') {
                return ['error' => 'Cannot assign a completed work order. Request rework from Task Details first.'];
            }

            $oldTechId = (int) ($locked->technician_id ?? 0);

            // Prevent downgrading an already-accepted or in-progress work order when re-submitting the exact same technician
            if ($oldTechId === $newTechId && in_array($locked->status, ['assigned', 'accepted', 'on-going'], true)) {
                return ['unchanged' => true, 'tech_name' => $locked->technician?->name ?? 'technician'];
            }

            $locked->update(['technician_id' => $newTechId]);
            $locked->refresh();
            $techName = $locked->technician?->name ?? 'technician';

            if ($locked->status !== 'assigned') {
                $reason = $oldTechId && $oldTechId !== $newTechId
                    ? "Client request reassigned to {$techName} by NOC dispatcher"
                    : "Client request dispatched to {$techName} by NOC dispatcher";
                $this->stateMachine->transition($locked, 'assigned', auth()->user(), $reason);
            } elseif ($oldTechId !== $newTechId) {
                ProgressUpdate::create([
                    'task_id'          => $locked->id,
                    'user_id'          => auth()->id(),
                    'note'             => "NOC dispatcher reassigned client request to {$techName}.",
                    'progress_percent' => (int) $locked->progress_percent,
                ]);
                $locked->touch();
            }

            return ['unchanged' => false, 'tech_name' => $techName];
        });

        if (!empty($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        if (!empty($outcome['unchanged'])) {
            return back()->with('success', "Client request {$code} is already assigned to {$outcome['tech_name']}.");
        }

        return back()->with('success', "Client request {$code} dispatched to {$outcome['tech_name']} (APK live sync triggered).");
    }

    /**
     * 1-Click Auto-Assign: prioritizes Online technicians nearest by GPS + lowest active workload.
     */
    public function autoAssign(Task $task)
    {
        $candidates = User::where('role', 'technician')
            ->where('is_active', true)
            ->with('latestLocation')
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        if ($candidates->isEmpty()) {
            return back()->with('error', 'No active technicians are available in the system.');
        }

        // Strongly prefer effectively-online technicians; fall back to all active if none online
        $onlinePool = $candidates->filter(fn (User $u) => $u->isEffectivelyOnline());
        $pool = $onlinePool->isNotEmpty() ? $onlinePool : $candidates;

        $bestTech = $pool->sortBy(function (User $tech) use ($task) {
            $workloadPenalty = ((int) $tech->active_tasks_count) * 2000;
            $loc = $tech->latestLocation;
            if ($task->latitude !== null && $task->longitude !== null && $loc && $loc->latitude !== null && $loc->longitude !== null) {
                $dist = SettingService::haversineMeters(
                    (float) $task->latitude,
                    (float) $task->longitude,
                    (float) $loc->latitude,
                    (float) $loc->longitude
                );
                return $dist + $workloadPenalty;
            }
            return 500000 + $workloadPenalty;
        })->first();

        if (!$bestTech) {
            return back()->with('error', 'Unable to determine an available technician.');
        }

        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $statusTag = $bestTech->isEffectivelyOnline() ? 'Online' : 'Standby';

        $outcome = DB::transaction(function () use ($task, $bestTech) {
            /** @var Task $locked */
            $locked = Task::lockForUpdate()->findOrFail($task->id);

            if ($locked->status === 'completed') {
                return ['error' => 'Cannot auto-assign a completed work order.'];
            }

            $oldTechId = (int) ($locked->technician_id ?? 0);
            if ($oldTechId === (int) $bestTech->id && in_array($locked->status, ['assigned', 'accepted', 'on-going'], true)) {
                return ['unchanged' => true];
            }

            $locked->update(['technician_id' => $bestTech->id]);
            $locked->refresh();
            $modeLabel = $bestTech->isEffectivelyOnline() ? 'Online Nearest GPS' : 'Lowest Workload Fallback';

            if ($locked->status !== 'assigned') {
                $this->stateMachine->transition(
                    $locked,
                    'assigned',
                    auth()->user(),
                    "Auto-assigned ({$modeLabel}) to {$bestTech->name}"
                );
            } elseif ($oldTechId !== (int) $bestTech->id) {
                ProgressUpdate::create([
                    'task_id'          => $locked->id,
                    'user_id'          => auth()->id(),
                    'note'             => "Auto-reassigned ({$modeLabel}) to {$bestTech->name}.",
                    'progress_percent' => (int) $locked->progress_percent,
                ]);
                $locked->touch();
            }

            return ['unchanged' => false];
        });

        if (!empty($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        if (!empty($outcome['unchanged'])) {
            return back()->with('success', "Client request {$code} is already assigned to {$bestTech->name} ({$statusTag}).");
        }

        return back()->with('success', "Auto-assigned {$code} to {$bestTech->name} ({$statusTag}) — live APK sync triggered.");
    }

    /**
     * Decline / cancel an invalid or duplicate client request with audit reason.
     */
    public function reject(Request $request, Task $task)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $cleanReason = strip_tags(trim($validated['reason']));
        if ($cleanReason === '') {
            return back()->with('error', 'Please provide a clear decline reason.');
        }

        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);

        $outcome = DB::transaction(function () use ($task, $cleanReason) {
            /** @var Task $locked */
            $locked = Task::lockForUpdate()->findOrFail($task->id);

            if ($locked->status === 'completed') {
                return ['error' => 'Cannot reject an already completed work order.'];
            }

            if ($locked->status === 'rejected') {
                return ['already_rejected' => true];
            }

            $this->stateMachine->transition(
                $locked,
                'rejected',
                auth()->user(),
                'Client request declined by NOC: ' . $cleanReason
            );

            return ['already_rejected' => false];
        });

        if (!empty($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        return back()->with('success', "Client request {$code} marked as rejected.");
    }
}
