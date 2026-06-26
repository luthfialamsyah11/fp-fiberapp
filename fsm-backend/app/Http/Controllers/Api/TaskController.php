<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
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
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork']);
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
        if ($task->status !== 'accepted') {
            return response()->json(['message' => 'Tugas belum diterima'], 422);
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

        $task->update(['status' => 'completed']);
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