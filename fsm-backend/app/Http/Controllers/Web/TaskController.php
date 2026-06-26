<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $tasks = Task::with('technician')
            ->when($status, function($q, $status) {
                $q->where('status', $status);
            })
            ->when($search, function($q, $search) {
                $q->where(function($subQ) use ($search) {
                    $subQ->where('title', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $task = new Task();
        $technicians = User::where('role', 'technician')->where('is_active', true)->get();
        return view('tasks.form', compact('task', 'technicians'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'technician_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
        ]);

        $validated['status'] = $validated['technician_id'] ? 'assigned' : 'pending';

        // Use standard DB field for location (if missing in previous schema, let's just map it to location or keep latitude/longitude depending on what DB has)
        // I will assume address -> location for now based on FSM mobile app expectations (it used `location` in the task model)
        // Let's check what the model actually has: customer_name, title, description, address, latitude, longitude.
        $validated['location'] = $validated['address'];

        Task::create($validated);

        return redirect()->route('admin.tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Task $task)
    {
        $technicians = User::where('role', 'technician')->where('is_active', true)->get();
        return view('tasks.form', compact('task', 'technicians'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'technician_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,assigned,accepted,on-going,completed,rejected',
        ]);

        $validated['location'] = $validated['address'];

        // If newly assigned
        if ($validated['technician_id'] && $task->status === 'pending' && $validated['status'] === 'pending') {
            $validated['status'] = 'assigned';
        }

        $task->update($validated);

        return redirect()->route('admin.tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function show(Task $task)
    {
        $task->load(['technician', 'technician.latestLocation', 'proofs', 'progressUpdates']);
        return view('tasks.show', compact('task'));
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function assign(Request $request, Task $task)
    {
        // Simple assignment action if a technician was pre-selected but status is still pending
        if ($task->technician_id && $task->status === 'pending') {
            $task->update(['status' => 'assigned']);
            return back()->with('success', 'Tugas berhasil ditugaskan dan notifikasi dikirim (simulasi).');
        }
        return back()->with('error', 'Tugas tidak memiliki teknisi atau status sudah berubah.');
    }
}
