<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Task;
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
        $tasks = Task::with('technician')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->technician_id, fn ($q) => $q->where('technician_id', $request->technician_id))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'customer_name'    => 'required|string|max:255',
            'description'      => 'required|string',
            'address'          => 'required|string',
            'priority'         => 'sometimes|in:low,medium,high',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'technician_id'    => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
            'scheduled_at'     => 'nullable|date',
            'customer_phone'   => 'nullable|string|max:50',
            'additional_notes' => 'nullable|string',
        ]);

        $validated['status'] = !empty($validated['technician_id']) ? 'assigned' : 'pending';
        $validated['location'] = $validated['address'];

        $task = Task::create($validated);
        return response()->json(['data' => $task], 201);
    }

    public function show(Task $task)
    {
        $task->ensureTrackingToken();
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem', 'statusHistories']);
        return response()->json(['data' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'sometimes|string',
            'customer_name'    => 'sometimes|string|max:255',
            'customer_phone'   => 'nullable|string|max:50',
            'address'          => 'sometimes|string',
            'priority'         => 'sometimes|in:low,medium,high',
            'status'           => 'sometimes|in:pending,assigned,accepted,on-going,completed,rejected',
            'override_reason'  => 'nullable|string|max:500',
            'technician_id'    => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
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
            if ($targetStatus !== $task->status) {
                $reasonToUse = $reason ?: ($techChanged ? 'Technician reassigned by dispatcher' : null);
                $task = $this->stateMachine->transition($task, $targetStatus, auth()->user(), $reasonToUse);
            }
            $task->update($validated);
            return $task->fresh(['technician', 'progressUpdates', 'proofOfWork', 'materials.inventoryItem', 'statusHistories']);
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

        $task->update(['technician_id' => $request->technician_id]);
        $task = $this->stateMachine->transition($task, 'assigned', auth()->user(), 'Assigned by dispatcher');

        return response()->json(['data' => $task]);
    }
}
