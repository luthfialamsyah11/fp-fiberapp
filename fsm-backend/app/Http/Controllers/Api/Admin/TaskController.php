<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\User;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $query = Task::with(['technician', 'latestProgressUpdate'])
            ->withCount(['proofs', 'materials'])
            ->when($request->status === 'sla_breach', fn ($q) => $q->slaBreached())
            ->when($request->status && $request->status !== 'sla_breach', fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->when($request->technician_id === 'unassigned', fn ($q) => $q->whereNull('technician_id'))
            ->when($request->technician_id && $request->technician_id !== 'unassigned', fn ($q) => $q->where('technician_id', $request->technician_id))
            ->when($request->search, function ($q, $search) {
                $trimmed = trim((string) $search);
                $numericId = null;
                if (preg_match('/^(?:#?TSK-?)?0*(\d+)$/i', $trimmed, $m)) {
                    $numericId = (int) $m[1];
                }
                $q->where(function ($sub) use ($trimmed, $numericId) {
                    if ($numericId !== null && $numericId > 0) {
                        $sub->where('id', $numericId)->orWhere('title', 'like', "%{$trimmed}%");
                    } else {
                        $sub->where('title', 'like', "%{$trimmed}%");
                    }
                    $sub->orWhere('customer_name', 'like', "%{$trimmed}%")
                        ->orWhere('customer_phone', 'like', "%{$trimmed}%")
                        ->orWhere('address', 'like', "%{$trimmed}%");
                });
            })
            ->orderByDesc('created_at');

        if ($request->filled('per_page')) {
            $perPage = min(100, max(5, (int) $request->input('per_page', 15)));
            return response()->json($query->paginate($perPage));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $tenantId = TenantContext::id();
        $techRule = Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true);
        if ($tenantId !== null) {
            $techRule->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'customer_name'    => 'required|string|max:255',
            'description'      => 'required|string',
            'address'          => 'required|string',
            'priority'         => 'sometimes|in:low,medium,high',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'technician_id'    => ['nullable', $techRule],
            'scheduled_at'     => 'nullable|date',
            'customer_phone'   => 'nullable|string|max:50',
            'additional_notes' => 'nullable|string',
        ]);

        if (empty($validated['technician_id']) && SettingService::get('auto_assign', '0') === '1') {
            $autoTech = $this->findRecommendedTechnician(
                isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                isset($validated['longitude']) ? (float) $validated['longitude'] : null
            );
            if ($autoTech) {
                $validated['technician_id'] = $autoTech->id;
            }
        }

        $validated['status'] = !empty($validated['technician_id']) ? 'assigned' : 'pending';
        $validated['location'] = $validated['address'];
        $validated['priority'] = $validated['priority'] ?? 'medium';

        $task = Task::create($validated);

        if ($task->priority === 'high') {
            SettingService::dispatchWebhook('TASK_EMERGENCY_CREATED', [
                'task_id'       => $task->id,
                'title'         => $task->title,
                'customer_name' => $task->customer_name,
                'priority'      => 'high',
                'location'      => $task->location,
                'technician'    => $task->technician ? $task->technician->name : 'Unassigned',
            ]);
        }

        return response()->json(['data' => $task->load('technician')], 201);
    }

    public function show(Task $task)
    {
        $task->ensureTrackingToken();
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem', 'statusHistories']);
        return response()->json(['data' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $tenantId = TenantContext::id();
        $techRule = Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true);
        if ($tenantId !== null) {
            $techRule->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'sometimes|string',
            'customer_name'    => 'sometimes|string|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'address'          => 'sometimes|string',
            'priority'         => 'sometimes|in:low,medium,high',
            'status'           => 'sometimes|in:pending,assigned,accepted,on-going,completed,rejected',
            'override_reason'  => 'nullable|string|max:500',
            'technician_id'    => ['nullable', $techRule],
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'scheduled_at'     => 'nullable|date',
            'additional_notes' => 'nullable|string',
        ]);

        if (isset($validated['address'])) {
            $validated['location'] = $validated['address'];
        }

        $techChanged = array_key_exists('technician_id', $validated)
            && (int) ($validated['technician_id'] ?? 0) !== (int) ($task->technician_id ?? 0);

        if ($techChanged && in_array($task->status, ['accepted', 'on-going'], true) && !isset($validated['status'])) {
            $validated['status'] = !empty($validated['technician_id']) ? 'assigned' : 'pending';
        }

        $targetStatus = $validated['status'] ?? $task->status;
        $reason = trim((string) ($validated['override_reason'] ?? ''));
        unset($validated['status'], $validated['override_reason']);

        if ($task->status === 'completed' && empty($reason)) {
            throw ValidationException::withMessages([
                'override_reason' => ['An audit override reason is required to modify a completed and BAST-sealed work order.'],
            ]);
        }

        $task = DB::transaction(function () use ($task, $validated, $targetStatus, $reason, $techChanged) {
            // Apply metadata updates BEFORE state transition so SHA-256 BAST seal hashes final fields
            $task->update($validated);

            if ($targetStatus !== $task->status) {
                $reasonToUse = $reason ?: ($techChanged ? 'Technician reassigned by dispatcher' : null);
                $task = $this->stateMachine->transition($task, $targetStatus, auth()->user(), $reasonToUse);
            } elseif ($task->status === 'completed') {
                // Re-seal SHA-256 digest after authorized admin override edit on completed work order
                $task->update([
                    'bast_hash'      => $task->computeBastHash(),
                    'bast_sealed_at' => now(),
                ]);
            }

            return $task->fresh(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem', 'statusHistories.actor']);
        });

        return response()->json(['data' => $task]);
    }

    public function destroy(Task $task)
    {
        DB::transaction(function () use ($task) {
            $materials = $task->materials()->get();
            foreach ($materials as $material) {
                $item = InventoryItem::lockForUpdate()->find($material->inventory_item_id);
                if ($item) {
                    $item->increment('stock', $material->quantity);
                }
            }
            $task->delete();
        });

        return response()->json(['message' => 'Work order deleted and allocated materials restored to warehouse.']);
    }

    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'technician_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
        ]);

        if ($task->status === 'completed') {
            return response()->json(['message' => 'Cannot reassign a completed work order without reopening it first.'], 409);
        }

        $task = DB::transaction(function () use ($request, $task) {
            $task->update(['technician_id' => $request->technician_id]);
            if ($task->status !== 'assigned') {
                return $this->stateMachine->transition($task, 'assigned', auth()->user(), 'Assigned by dispatcher');
            }
            return $task->fresh(['technician']);
        });

        return response()->json(['data' => $task]);
    }

    private function findRecommendedTechnician(?float $lat, ?float $lng): ?User
    {
        $candidates = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation'])
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($lat !== null && $lng !== null) {
            $withCoords = $candidates->filter(fn ($u) => $u->latestLocation && $u->latestLocation->latitude && $u->latestLocation->longitude);
            if ($withCoords->isNotEmpty()) {
                return $withCoords->sortBy(function ($u) use ($lat, $lng) {
                    $dist = SettingService::haversineMeters($lat, $lng, (float) $u->latestLocation->latitude, (float) $u->latestLocation->longitude);
                    return $dist + ($u->active_tasks_count * 2000);
                })->first();
            }
        }

        return $candidates->sortBy('active_tasks_count')->first();
    }
}
