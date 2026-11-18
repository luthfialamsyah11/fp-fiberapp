<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $tasks = Task::with('technician')
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->when($search, function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
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
            'title'          => 'required|string|max:255',
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'address'        => 'required|string',
            'description'    => 'nullable|string',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'technician_id'  => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
            'scheduled_at'   => 'nullable|date',
            'priority'       => 'required|in:low,medium,high',
        ]);

        // Auto-assign nearest available technician if enabled and technician_id omitted
        if (empty($validated['technician_id']) && SettingService::get('auto_assign', '0') === '1') {
            $autoTech = $this->findRecommendedTechnician(
                isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                isset($validated['longitude']) ? (float) $validated['longitude'] : null
            );
            if ($autoTech) {
                $validated['technician_id'] = $autoTech->id;
            }
        }

        $validated['status'] = !empty($validated['technician_id']) ? 'assigned' : 'pending';
        $validated['location'] = $validated['address'];

        $task = Task::create($validated);

        if ($task->priority === 'high') {
            SettingService::dispatchWebhook('TASK_EMERGENCY_CREATED', [
                'task_id'       => $task->id,
                'title'         => $task->title,
                'customer_name' => $task->customer_name,
                'priority'      => 'high',
                'location'      => $task->location,
                'technician'    => $task->technician ? $task->technician->name : 'Unassigned',
            ]);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Work order created successfully.');
    }

    public function edit(Task $task)
    {
        $technicians = User::where('role', 'technician')->where('is_active', true)->get();
        return view('tasks.form', compact('task', 'technicians'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'customer_name'   => 'required|string|max:255',
            'customer_phone'  => 'nullable|string|max:50',
            'address'         => 'required|string',
            'description'     => 'nullable|string',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'technician_id'   => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
            ],
            'scheduled_at'    => 'nullable|date',
            'priority'        => 'required|in:low,medium,high',
            'status'          => 'required|in:pending,assigned,accepted,on-going,completed,rejected',
            'override_reason' => 'nullable|string|max:500',
        ]);

        $validated['location'] = $validated['address'];

        if (!empty($validated['technician_id']) && $task->status === 'pending' && $validated['status'] === 'pending') {
            $validated['status'] = 'assigned';
        }

        $targetStatus = $validated['status'];
        $overrideReason = $validated['override_reason'] ?? null;
        unset($validated['status'], $validated['override_reason']);

        $task->update($validated);

        if ($targetStatus !== $task->status) {
            $this->stateMachine->transition($task, $targetStatus, auth()->user(), $overrideReason);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Work order updated successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['technician', 'technician.latestLocation', 'proofs', 'progressUpdates', 'materials.inventoryItem', 'statusHistories']);
        return view('tasks.show', compact('task'));
    }

    public function destroy(Task $task)
    {
        DB::transaction(function () use ($task) {
            // Restore consumed warehouse inventory stock before deleting work order
            $materials = $task->materials()->get();
            foreach ($materials as $material) {
                $item = InventoryItem::lockForUpdate()->find($material->inventory_item_id);
                if ($item) {
                    $item->increment('stock', $material->quantity);
                }
            }
            $task->delete();
        });

        return redirect()->route('admin.tasks.index')->with('success', 'Work order deleted and any allocated materials restored to warehouse.');
    }

    public function assign(Request $request, Task $task)
    {
        if ($task->technician_id && $task->status === 'pending') {
            $this->stateMachine->transition(
                $task,
                'assigned',
                auth()->user(),
                'Work order assigned to ' . ($task->technician ? $task->technician->name : 'technician')
            );
            return back()->with('success', 'Work order dispatched and notification sent.');
        }
        return back()->with('error', 'Work order does not have a designated technician or status has already changed.');
    }

    private function findRecommendedTechnician(?float $lat, ?float $lng): ?User
    {
        $candidates = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation'])
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($lat !== null && $lng !== null) {
            $withCoords = $candidates->filter(fn ($u) => $u->latestLocation && $u->latestLocation->latitude && $u->latestLocation->longitude);
            if ($withCoords->isNotEmpty()) {
                return $withCoords->sortBy(function ($u) use ($lat, $lng) {
                    $dist = SettingService::haversineMeters($lat, $lng, (float) $u->latestLocation->latitude, (float) $u->latestLocation->longitude);
                    return $dist + ($u->active_tasks_count * 2000);
                })->first();
            }
        }

        return $candidates->sortBy('active_tasks_count')->first();
    }
}
