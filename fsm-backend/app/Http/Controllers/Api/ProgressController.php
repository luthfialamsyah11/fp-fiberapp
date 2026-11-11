<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\Task;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function store(Request $request, Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($task->status !== 'on-going') {
            return response()->json(['message' => 'Catatan progres hanya dapat ditambahkan saat tugas berstatus dikerjakan (on-going)'], 422);
        }

        $request->validate([
            'note'             => 'required|string',
            'progress_percent' => 'required|integer|min:0|max:100',
        ]);

        $progress = ProgressUpdate::create([
            'task_id'          => $task->id,
            'user_id'          => auth()->id(),
            'note'             => strip_tags(trim($request->note)),
            'progress_percent' => $request->progress_percent,
        ]);

        return response()->json(['data' => $progress], 201);
    }
}