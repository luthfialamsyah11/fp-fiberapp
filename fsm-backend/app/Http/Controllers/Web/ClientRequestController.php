<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tasks\DispatchService;
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
    public function __construct(
        private readonly TaskStateMachine $stateMachine,
        private readonly DispatchService $dispatchService
    ) {
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
        $tenantId = \App\Services\TenantContext::id();
        $techRule = Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true);
        if ($tenantId !== null) {
            $techRule->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'technician_id' => ['required', $techRule],
        ]);

        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $outcome = $this->dispatchService->assignTechnician($task, (int) $validated['technician_id'], auth()->user());

        if (!($outcome['ok'] ?? false)) {
            return back()->with('error', $outcome['error'] ?? 'Unable to assign technician.');
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
        $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $outcome = $this->dispatchService->autoAssign($task, auth()->user());

        if (!($outcome['ok'] ?? false)) {
            return back()->with('error', $outcome['error'] ?? 'Unable to auto-assign technician.');
        }

        $statusTag = $outcome['status_tag'] ?? 'Online';
        if (!empty($outcome['unchanged'])) {
            return back()->with('success', "Client request {$code} is already assigned to {$outcome['tech_name']} ({$statusTag}).");
        }

        return back()->with('success', "Auto-assigned {$code} to {$outcome['tech_name']} ({$statusTag}) — live APK sync triggered.");
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
