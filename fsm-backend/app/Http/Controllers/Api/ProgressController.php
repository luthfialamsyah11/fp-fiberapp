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
        $request->validate([
            'note'             => 'required|string',
            'progress_percent' => 'required|integer|min:0|max:100',
        ]);

        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = ProgressUpdate::create([
            'task_id'          => $task->id,
            'user_id'          => auth()->id(),
            'note'             => $request->note,
            'progress_percent' => $request->progress_percent,
        ]);

        return response()->json(['data' => $progress], 201);
    }
}