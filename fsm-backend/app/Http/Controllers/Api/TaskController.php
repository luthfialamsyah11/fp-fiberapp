<?php

namespace App\Http\Controllers\Api;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $tasks = Task::where('technician_id', auth()->id())
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->status),
                fn ($q) => $q->whereIn('status', ['assigned', 'accepted', 'on-going'])
            )
            ->with(['latestProgressUpdate'])
            ->withCount(['proofOfWork', 'materials'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    public function show(Task $task)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }
        $task->ensureTrackingToken();
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem', 'statusHistories']);
        return response()->json(['data' => $task]);
    }

    public function accept(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }
        if ($task->status !== 'assigned') {
            return response()->json(['message' => 'Work order is not in assigned status.'], 422);
        }

        try {
            $updated = $this->stateMachine->transition($task, 'accepted', auth()->user());
            return response()->json(['data' => $updated]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }

    public function reject(Request $request, Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }
        if ($task->status !== 'assigned') {
            return response()->json(['message' => 'Work order cannot be declined in its current status.'], 422);
        }

        $reason = $request->input('reason', 'Declined by field technician');

        try {
            $this->stateMachine->transition($task, 'pending', auth()->user(), $reason, ['unassign' => true]);

            SettingService::dispatchWebhook('TASK_DECLINED', [
                'task_id'         => $task->id,
                'title'           => $task->title,
                'technician_name' => auth()->user()->name,
                'reason'          => $reason,
            ]);

            return response()->json(['message' => 'Work order declined and returned to dispatch queue.']);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }

    public function start(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }
        if (!in_array($task->status, ['assigned', 'accepted'], true)) {
            return response()->json(['message' => 'Work order cannot be started from its current status.'], 422);
        }

        try {
            $updated = $this->stateMachine->transition($task, 'on-going', auth()->user());
            return response()->json(['data' => $updated]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }

    public function complete(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }
        if ($task->status !== 'on-going') {
            return response()->json(['message' => 'Work order execution has not been started yet.'], 422);
        }

        try {
            $updated = $this->stateMachine->transition($task, 'completed', auth()->user());

            SettingService::dispatchWebhook('TASK_COMPLETED', [
                'task_id'         => $updated->id,
                'title'           => $updated->title,
                'customer_name'   => $updated->customer_name,
                'technician_name' => auth()->user()->name,
                'status'          => 'completed',
                'bast_hash'       => $updated->bast_hash,
            ]);

            return response()->json(['data' => $updated]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }

    public function history(Request $request)
    {
        $tasks = Task::where('technician_id', auth()->id())
            ->where('status', 'completed')
            ->with(['progressUpdates', 'proofOfWork'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }
}
