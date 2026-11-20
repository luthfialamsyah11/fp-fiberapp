<?php

namespace App\Http\Controllers\Web;

use App\Domain\Tasks\TaskStateMachine;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\TaskMaterial;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(private readonly TaskStateMachine $stateMachine)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $priority = $request->get('priority');
        $technicianId = $request->get('technician_id');
        $search = $request->get('search');
        $slaHours = (int) SettingService::get('sla_warning_hours', '2');

        $tasks = Task::with(['technician', 'latestProgressUpdate'])
            ->withCount(['proofs', 'materials'])
            ->when($status === 'sla_breach', fn ($q) => $q->slaBreached($slaHours))
            ->when($status && $status !== 'sla_breach', fn ($q) => $q->where('status', $status))
            ->when($priority && in_array($priority, ['low', 'medium', 'high'], true), fn ($q) => $q->where('priority', $priority))
            ->when($technicianId === 'unassigned', fn ($q) => $q->whereNull('technician_id'))
            ->when($technicianId && $technicianId !== 'unassigned', fn ($q) => $q->where('technician_id', $technicianId))
            ->when($search, function ($q, $search) {
                $trimmed = trim((string) $search);
                $numericId = null;
                if (preg_match('/^(?:#?TSK-?)?0*(\d+)$/i', $trimmed, $m)) {
                    $numericId = (int) $m[1];
                }

                $q->where(function ($subQ) use ($trimmed, $numericId) {
                    if ($numericId !== null && $numericId > 0) {
                        $subQ->where('id', $numericId)
                             ->orWhere('title', 'like', "%{$trimmed}%");
                    } else {
                        $subQ->where('title', 'like', "%{$trimmed}%");
                    }
                    $subQ->orWhere('customer_name', 'like', "%{$trimmed}%")
                         ->orWhere('customer_phone', 'like', "%{$trimmed}%")
                         ->orWhere('address', 'like', "%{$trimmed}%")
                         ->orWhere('location', 'like', "%{$trimmed}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Ensure every displayed work order has a valid tracking token for BAST PDF & Customer Tracking actions
        $tasks->getCollection()->each(function (Task $task) {
            if (empty($task->tracking_token)) {
                $task->ensureTrackingToken();
            }
        });

        $countsRow = Task::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN priority = 'high' AND status NOT IN ('completed', 'rejected') THEN 1 END) as high_active
        ")->first();

        $summary = [
            'total'       => (int) ($countsRow->total ?? 0),
            'pending'     => (int) ($countsRow->pending ?? 0),
            'assigned'    => (int) ($countsRow->assigned ?? 0),
            'accepted'    => (int) ($countsRow->accepted ?? 0),
            'on_going'    => (int) ($countsRow->ongoing ?? 0),
            'completed'   => (int) ($countsRow->completed ?? 0),
            'rejected'    => (int) ($countsRow->rejected ?? 0),
            'high_active' => (int) ($countsRow->high_active ?? 0),
            'sla_breach'  => Task::slaBreached($slaHours)->count(),
        ];

        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->orderBy('name')
            ->get();

        return view('tasks.index', compact('tasks', 'summary', 'technicians', 'slaHours'));
    }

    public function create()
    {
        $task = new Task();
        return view('tasks.form', $this->buildFormViewData($task));
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

        $taskCode = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $dispatchMsg = $task->technician
            ? "Work order {$taskCode} created and dispatched to {$task->technician->name}."
            : "Work order {$taskCode} created and queued in Pending Dispatch.";

        return redirect()->route('admin.tasks.index')->with('success', $dispatchMsg);
    }

    public function edit(Task $task)
    {
        return view('tasks.form', $this->buildFormViewData($task));
    }

    /**
     * Assemble enriched technician fleet telemetry, recent customer directory,
     * and platform map/geofence settings for the Create/Edit Task Geo-Dispatcher Workbench.
     */
    private function buildFormViewData(Task $task): array
    {
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going'])->latest();
            }])
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->orderBy('name')
            ->get();

        $technicianFleet = $technicians->map(function ($tech) {
            $loc = $tech->latestLocation;
            $capturedAt = $loc ? ($loc->captured_at ?? $loc->created_at) : null;
            $activeTask = $tech->tasks->first();
            return [
                'id'                 => $tech->id,
                'name'               => $tech->name,
                'email'              => $tech->email,
                'phone'              => $tech->phone ?? '',
                'is_online'          => $tech->isEffectivelyOnline(),
                'active_tasks_count' => (int) ($tech->active_tasks_count ?? 0),
                'active_task_title'  => $activeTask ? $activeTask->title : null,
                'latitude'           => ($loc && $loc->latitude) ? (float) $loc->latitude : null,
                'longitude'          => ($loc && $loc->longitude) ? (float) $loc->longitude : null,
                'last_seen'          => $capturedAt ? $capturedAt->diffForHumans() : 'No GPS signal yet',
            ];
        })->values();

        // Recent unique customers for 1-click returning subscriber auto-fill
        $recentCustomers = Task::select('customer_name', 'customer_phone', 'address', 'location', 'latitude', 'longitude', 'created_at')
            ->whereNotNull('customer_name')
            ->orderByDesc('id')
            ->take(100)
            ->get()
            ->unique(function ($t) {
                return strtolower(trim((string) $t->customer_name)) . '|' . preg_replace('/[^0-9]/', '', (string) $t->customer_phone);
            })
            ->take(30)
            ->map(function ($t) {
                return [
                    'name'      => $t->customer_name,
                    'phone'     => $t->customer_phone ?? '',
                    'address'   => $t->address ?: ($t->location ?? ''),
                    'latitude'  => $t->latitude ? (float) $t->latitude : null,
                    'longitude' => $t->longitude ? (float) $t->longitude : null,
                ];
            })
            ->values();

        $mapStyle = SettingService::get('map_style', 'openstreetmap');
        $geofenceRadius = (int) SettingService::get('geofence_check_meters', '100');
        $slaHours = (int) SettingService::get('sla_warning_hours', '2');
        $autoAssignEnabled = SettingService::get('auto_assign', '0') === '1';

        return compact(
            'task',
            'technicians',
            'technicianFleet',
            'recentCustomers',
            'mapStyle',
            'geofenceRadius',
            'slaHours',
            'autoAssignEnabled'
        );
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

        // If technician changes on an accepted or on-going task without explicit status change, reset to assigned
        $techChanged = array_key_exists('technician_id', $validated)
            && (int) ($validated['technician_id'] ?? 0) !== (int) ($task->technician_id ?? 0);

        if ($techChanged && in_array($task->status, ['accepted', 'on-going'], true) && $validated['status'] === $task->status) {
            $validated['status'] = !empty($validated['technician_id']) ? 'assigned' : 'pending';
        }

        $targetStatus = $validated['status'];
        $overrideReason = trim((string) ($validated['override_reason'] ?? ''));
        unset($validated['status'], $validated['override_reason']);

        if ($task->status === 'completed' && empty($overrideReason)) {
            throw ValidationException::withMessages([
                'override_reason' => ['An audit override reason is required to modify a completed and BAST-sealed work order.'],
            ]);
        }

        DB::transaction(function () use ($task, $validated, $targetStatus, $overrideReason, $techChanged) {
            // Persist metadata BEFORE state transition so SHA-256 BAST seal hashes final customer/tech fields
            $task->update($validated);

            if ($targetStatus !== $task->status) {
                $reasonToUse = $overrideReason ?: ($techChanged ? 'Technician reassigned by dispatcher' : null);
                $this->stateMachine->transition($task, $targetStatus, auth()->user(), $reasonToUse);
            } elseif ($task->status === 'completed') {
                // Recompute tamper-evident BAST hash after authorized override update on completed task
                $task->update([
                    'bast_hash'      => $task->computeBastHash(),
                    'bast_sealed_at' => now(),
                ]);
            }
        });

        return redirect()->route('admin.tasks.show', $task->id)->with('success', 'Work order updated successfully.');
    }

    public function show(Task $task)
    {
        $task->ensureTrackingToken();
        $task->load([
            'technician',
            'technician.latestLocation',
            'proofs',
            'progressUpdates.user',
            'materials.inventoryItem',
            'statusHistories.actor',
        ]);

        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->orderBy('name')
            ->get();

        $inventoryItems = InventoryItem::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $mapStyle = SettingService::get('map_style', 'openstreetmap');
        $geofenceRadius = (int) SettingService::get('geofence_check_meters', '100');
        $pollInterval = (int) SettingService::get('gps_refresh_interval', '15');

        return view('tasks.show', compact(
            'task',
            'technicians',
            'inventoryItems',
            'mapStyle',
            'geofenceRadius',
            'pollInterval'
        ));
    }

    public function destroy(Task $task)
    {
        $clientPhotoToDelete = $task->client_photo;

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

        if (!empty($clientPhotoToDelete)) {
            Storage::disk('public')->delete($clientPhotoToDelete);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Work order deleted and any allocated materials restored to warehouse.');
    }

    public function assign(Request $request, Task $task)
    {
        if ($request->filled('technician_id')) {
            $request->validate([
                'technician_id' => [
                    'required',
                    Rule::exists('users', 'id')->where('role', 'technician')->where('is_active', true),
                ],
            ]);
            $newTechId = (int) $request->technician_id;

            $outcome = DB::transaction(function () use ($task, $newTechId) {
                /** @var Task $locked */
                $locked = Task::lockForUpdate()->findOrFail($task->id);
                if ($locked->status === 'completed') {
                    return ['error' => 'Cannot assign or reassign a completed work order. Request rework first.'];
                }

                $oldTechId = (int) ($locked->technician_id ?? 0);
                if ($oldTechId === $newTechId && in_array($locked->status, ['assigned', 'accepted', 'on-going'], true)) {
                    return ['unchanged' => true, 'tech_name' => $locked->technician?->name ?? 'technician'];
                }

                $locked->update(['technician_id' => $newTechId]);
                $locked->refresh();
                $techName = $locked->technician?->name ?? 'technician';

                if ($locked->status !== 'assigned') {
                    $reason = $oldTechId && $oldTechId !== $newTechId
                        ? "Reassigned to {$techName} by dispatcher"
                        : "Work order assigned to {$techName}";
                    $this->stateMachine->transition($locked, 'assigned', auth()->user(), $reason);
                } elseif ($oldTechId !== $newTechId) {
                    ProgressUpdate::create([
                        'task_id'          => $locked->id,
                        'user_id'          => auth()->id(),
                        'note'             => "Dispatcher reassigned work order to {$techName}.",
                        'progress_percent' => (int) $locked->progress_percent,
                    ]);
                    $locked->touch();
                }

                return ['unchanged' => false, 'tech_name' => $techName];
            });

            if (!empty($outcome['error'])) {
                return back()->with('error', $outcome['error']);
            }
            if (!empty($outcome['unchanged'])) {
                return back()->with('success', 'Work order is already assigned to ' . $outcome['tech_name'] . '.');
            }

            return back()->with('success', 'Work order dispatched to ' . $outcome['tech_name'] . '.');
        }

        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot assign or reassign a completed work order. Request rework first.');
        }

        if ($task->technician_id && in_array($task->status, ['pending', 'rejected'], true)) {
            $this->stateMachine->transition(
                $task,
                'assigned',
                auth()->user(),
                'Work order assigned to ' . ($task->technician ? $task->technician->name : 'technician')
            );
            return back()->with('success', 'Work order dispatched and notification sent.');
        }
        return back()->with('error', 'Please select an active field technician to dispatch.');
    }

    public function addProgressNote(Request $request, Task $task)
    {
        $validated = $request->validate([
            'note'             => 'required|string|max:1000',
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $percent = isset($validated['progress_percent']) && $validated['progress_percent'] !== null
            ? (int) $validated['progress_percent']
            : (int) $task->progress_percent;

        ProgressUpdate::create([
            'task_id'          => $task->id,
            'user_id'          => auth()->id(),
            'note'             => '[NOC Dispatcher] ' . strip_tags(trim($validated['note'])),
            'progress_percent' => $percent,
        ]);
        $task->touch();

        return back()->with('success', 'Dispatcher note posted to work order timeline.');
    }

    public function addMaterial(Request $request, Task $task)
    {
        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot add materials to a completed and BAST-sealed work order. Request rework first.');
        }

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|numeric|min:0.01',
            'serial_number'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        $errorMsg = null;
        DB::transaction(function () use ($validated, $task, &$errorMsg) {
            $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);
            if ($item->stock < $validated['quantity']) {
                $errorMsg = "Insufficient warehouse stock for {$item->name} (Available: {$item->stock} {$item->unit}).";
                return;
            }
            $item->decrement('stock', $validated['quantity']);

            TaskMaterial::create([
                'task_id'           => $task->id,
                'inventory_item_id' => $item->id,
                'quantity'          => $validated['quantity'],
                'serial_number'     => isset($validated['serial_number']) ? strip_tags(trim($validated['serial_number'])) : null,
                'notes'             => isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
            ]);
            $task->touch();
        });

        if ($errorMsg) {
            return back()->with('error', $errorMsg);
        }

        return back()->with('success', 'Material added to BOM ledger and deducted from warehouse stock.');
    }

    public function removeMaterial(Task $task, TaskMaterial $material)
    {
        if ($task->status === 'completed') {
            return back()->with('error', 'Cannot remove materials from a completed and BAST-sealed work order. Request rework first.');
        }

        if ($material->task_id !== $task->id) {
            abort(404);
        }

        DB::transaction(function () use ($task, $material) {
            $item = InventoryItem::lockForUpdate()->find($material->inventory_item_id);
            if ($item) {
                $item->increment('stock', $material->quantity);
            }
            $material->delete();
            $task->touch();
        });

        return back()->with('success', 'Material removed and stock restored to warehouse.');
    }

    public function liveStatus(Task $task)
    {
        $task->load(['technician.latestLocation', 'latestProgressUpdate'])
            ->loadCount(['proofs', 'materials', 'progressUpdates']);
        $tech = $task->technician;
        $loc = $tech?->latestLocation;

        $distanceMeters = null;
        $etaMinutes = null;
        if ($loc && $loc->latitude && $loc->longitude && $task->latitude && $task->longitude) {
            $distanceMeters = SettingService::haversineMeters(
                (float) $loc->latitude,
                (float) $loc->longitude,
                (float) $task->latitude,
                (float) $task->longitude
            );
            $speedMps = ($loc->speed_mps && $loc->speed_mps > 1.5) ? (float) $loc->speed_mps : 8.33;
            $etaMinutes = max(1, (int) ceil(($distanceMeters / $speedMps) / 60));
        }

        $capturedAt = $loc ? ($loc->captured_at ?? $loc->created_at) : null;
        $proofsCount = (int) ($task->proofs_count ?? 0);
        $materialsCount = (int) ($task->materials_count ?? 0);
        $progressUpdatesCount = (int) ($task->progress_updates_count ?? 0);
        $hasSignature = !empty($task->customer_signature);
        $bastHash = (string) ($task->bast_hash ?? '');
        $stateSignature = sha1(implode('|', [
            $task->status,
            (int) $task->progress_percent,
            $proofsCount,
            $materialsCount,
            $progressUpdatesCount,
            $hasSignature ? '1' : '0',
            $bastHash,
            (int) ($task->technician_id ?? 0),
            (string) ($task->updated_at?->toIso8601String() ?? ''),
        ]));

        return response()->json([
            'task_id'                => $task->id,
            'status'                 => $task->status,
            'status_label'           => $task->status_label,
            'progress_percent'       => (int) $task->progress_percent,
            'proofs_count'           => $proofsCount,
            'materials_count'        => $materialsCount,
            'progress_updates_count' => $progressUpdatesCount,
            'has_signature'          => $hasSignature,
            'bast_hash'              => $bastHash ?: null,
            'state_signature'        => $stateSignature,
            'updated_at'             => $task->updated_at?->toIso8601String(),
            'server_time'            => now()->format('H:i:s'),
            'technician'             => $tech ? [
                'id'              => $tech->id,
                'name'            => $tech->name,
                'is_online'       => $tech->isEffectivelyOnline(),
                'latitude'        => ($loc && $loc->latitude) ? (float) $loc->latitude : null,
                'longitude'       => ($loc && $loc->longitude) ? (float) $loc->longitude : null,
                'last_seen'       => $capturedAt ? $capturedAt->diffForHumans() : 'No GPS signal',
                'captured_at'     => $capturedAt ? $capturedAt->toIso8601String() : null,
                'distance_meters' => $distanceMeters !== null ? round($distanceMeters) : null,
                'distance_km'     => $distanceMeters !== null ? round($distanceMeters / 1000, 2) : null,
                'eta_minutes'     => $etaMinutes,
            ] : null,
        ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function requestRework(Request $request, Task $task)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!in_array($task->status, ['completed', 'on-going'], true)) {
            return back()->with('error', 'Rework can only be requested on in-progress or completed work orders.');
        }

        if ($task->status === 'completed') {
            $this->stateMachine->transition(
                $task,
                'on-going',
                auth()->user(),
                'Proof Rework Requested: ' . $validated['reason']
            );
        } else {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => auth()->id(),
                'note'             => 'Dispatcher requested proof rework: ' . strip_tags(trim($validated['reason'])),
                'progress_percent' => min(75, (int) $task->progress_percent),
            ]);
        }

        return back()->with('success', 'Work order #' . str_pad($task->id, 4, '0', STR_PAD_LEFT) . ' reopened for field rework and technician notified.');
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
