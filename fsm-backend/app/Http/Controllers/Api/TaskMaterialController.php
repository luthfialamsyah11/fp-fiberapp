<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\TaskMaterial;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskMaterialController extends Controller
{
    public function index(Task $task)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        $materials = $task->materials()->with('inventoryItem')->get();
        $totalCost = (float) $materials->sum(function ($mat) {
            return ($mat->inventoryItem->unit_price ?? 0) * $mat->quantity;
        });

        return response()->json([
            'data'                 => $materials,
            'total_cost'           => $totalCost,
            'formatted_total_cost' => SettingService::formatCurrency($totalCost),
        ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request, Task $task)
    {
        $user = auth()->user();
        if ($task->technician_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        // Prevent post-completion BOM tampering for all roles to preserve SHA-256 BAST integrity
        if ($task->status === 'completed') {
            return response()->json([
                'message' => 'Materials cannot be modified on a completed and BAST-sealed work order. Request rework to reopen the task first.',
            ], 409);
        }

        if (!in_array($task->status, ['assigned', 'accepted', 'on-going'], true) && $user->role !== 'admin') {
            return response()->json(['message' => 'Materials cannot be modified after a work order is completed or closed.'], 422);
        }

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|numeric|min:0.01',
            'serial_number'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $task) {
            $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);

            if ($item->stock < $validated['quantity']) {
                return response()->json([
                    'message' => "Insufficient stock for {$item->name} (Available: {$item->stock} {$item->unit})."
                ], 422);
            }

            $item->decrement('stock', $validated['quantity']);

            $taskMaterial = TaskMaterial::create([
                'task_id'           => $task->id,
                'inventory_item_id' => $item->id,
                'quantity'          => $validated['quantity'],
                'serial_number'     => isset($validated['serial_number']) ? strip_tags(trim($validated['serial_number'])) : null,
                'notes'             => isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
            ]);

            $taskMaterial->load('inventoryItem');
            $task->touch();

            return response()->json([
                'message'       => 'Material consumption recorded and warehouse inventory deducted.',
                'data'          => $taskMaterial,
                'current_stock' => $item->fresh()->stock,
            ], 201);
        });
    }

    public function destroy(Task $task, TaskMaterial $material)
    {
        $user = auth()->user();
        if ($task->technician_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json([
                'message' => 'Materials cannot be removed from a completed and BAST-sealed work order. Request rework to reopen the task first.',
            ], 409);
        }

        if (!in_array($task->status, ['assigned', 'accepted', 'on-going'], true) && $user->role !== 'admin') {
            return response()->json(['message' => 'Materials cannot be removed after a work order is completed or closed.'], 422);
        }

        if ($material->task_id !== $task->id) {
            return response()->json(['message' => 'Material record does not belong to this work order.'], 422);
        }

        DB::transaction(function () use ($task, $material) {
            $item = InventoryItem::lockForUpdate()->find($material->inventory_item_id);
            if ($item) {
                $item->increment('stock', $material->quantity);
            }
            $material->delete();
            $task->touch();
        });

        return response()->json(['message' => 'Material entry removed and warehouse stock restored.']);
    }
}
