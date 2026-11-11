<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\SettingService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('technician_id', auth()->id())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with(['progressUpdates', 'proofOfWork'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    public function show(Task $task)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $task->ensureTrackingToken();
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem']);
        return response()->json(['data' => $task]);
    }

    public function accept(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($task->status !== 'assigned') {
            return response()->json(['message' => 'Tugas tidak dalam status assigned'], 422);
        }

        $task->update(['status' => 'accepted']);
        return response()->json(['data' => $task]);
    }

    public function reject(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($task->status !== 'assigned') {
            return response()->json(['message' => 'Tugas tidak bisa ditolak'], 422);
        }

        $task->update(['technician_id' => null, 'status' => 'pending']);
        return response()->json(['message' => 'Tugas ditolak']);
    }

    public function start(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (!in_array($task->status, ['assigned', 'accepted'])) {
            return response()->json(['message' => 'Tugas tidak dalam status yang dapat dimulai'], 422);
        }

        $task->update(['status' => 'on-going']);
        return response()->json(['data' => $task]);
    }

    public function complete(Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($task->status !== 'on-going') {
            return response()->json(['message' => 'Pekerjaan belum dimulai'], 422);
        }

        // Enforce Proof of Work guardrail if configured in settings
        $requireProof = SettingService::get('require_proof_photo', '1');
        if ($requireProof == '1' && $task->proofOfWork()->count() === 0) {
            return response()->json(['message' => 'Wajib mengunggah minimal 1 foto bukti pekerjaan sebelum menyelesaikan tugas'], 422);
        }

        $task->update(['status' => 'completed']);

        // Dispatch real-time webhook alert if configured
        SettingService::dispatchWebhook('TASK_COMPLETED', [
            'task_id'         => $task->id,
            'title'           => $task->title,
            'customer_name'   => $task->customer_name,
            'technician_name' => auth()->user()->name,
            'status'          => 'completed',
        ]);

        return response()->json(['data' => $task]);
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