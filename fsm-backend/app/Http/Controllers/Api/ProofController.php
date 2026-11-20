<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProofOfWork;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProofController extends Controller
{
    public function store(Request $request, Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        if (!in_array($task->status, ['assigned', 'accepted', 'on-going'], true)) {
            return response()->json(['message' => 'Proof of work cannot be uploaded to a completed or closed work order.'], 422);
        }

        $request->validate([
            'image'             => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description'       => 'nullable|string|max:1000',
            'slot'              => 'nullable|string|in:before,after,additional',
            'optical_power_dbm' => 'nullable|numeric|between:-50,10',
        ]);

        $path = $request->file('image')->store('proof', 'public');

        $proof = ProofOfWork::create([
            'task_id'           => $task->id,
            'image'             => $path,
            'slot'              => $request->input('slot'),
            'optical_power_dbm' => $request->filled('optical_power_dbm') ? round((float) $request->input('optical_power_dbm'), 2) : null,
            'description'       => $request->description ? strip_tags(trim($request->description)) : null,
        ]);

        // Bump parent task updated_at so both Admin & APK sync signals detect the new proof immediately
        $task->touch();

        return response()->json(['data' => $proof], 201);
    }

    public function index(Task $task)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        $proofs = $task->proofOfWork()->get();

        return response()->json(['data' => $proofs])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
