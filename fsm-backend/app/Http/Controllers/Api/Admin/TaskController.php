<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::with('technician')
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->technician_id, function ($q) use ($request) {
                return $q->where('technician_id', $request->technician_id);
            })
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
            'technician_id'    => 'nullable|exists:users,id',
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
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork', 'materials.inventoryItem']);
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
            'technician_id'    => 'nullable|exists:users,id',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'scheduled_at'     => 'nullable|date',
            'additional_notes' => 'nullable|string',
        ]);

        if (isset($validated['address'])) {
            $validated['location'] = $validated['address'];
        }

        $task->update($validated);
        return response()->json(['data' => $task]);
    }

    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        $task->update([
            'technician_id' => $request->technician_id,
            'status'        => 'assigned',
        ]);

        return response()->json(['data' => $task]);
    }
}