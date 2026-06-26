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
        $request->validate([
            'title'         => 'required|string',
            'customer_name' => 'required|string',
            'description'   => 'required|string',
            'address'       => 'required|string',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'technician_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();
        $data['status'] = !empty($data['technician_id']) ? 'assigned' : 'pending';
        $data['location'] = $data['address'];

        $task = Task::create($data);
        return response()->json(['data' => $task], 201);
    }

    public function show(Task $task)
    {
        $task->load(['technician', 'progressUpdates.user', 'proofOfWork']);
        return response()->json(['data' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $task->update($request->all());
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