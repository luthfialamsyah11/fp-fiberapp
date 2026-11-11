<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskMaterial;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskMaterialController extends Controller
{
    /**
     * Get all materials used on a task
     */
    public function index(Task $task)
    {
        $materials = $task->materials()->with('inventoryItem')->get();
        $totalCost = $materials->sum(function ($mat) {
            return ($mat->inventoryItem->unit_price ?? 0) * $mat->quantity;
        });

        return response()->json([
            'data' => $materials,
            'total_cost' => $totalCost,
            'formatted_total_cost' => 'Rp ' . number_format($totalCost, 0, ',', '.')
        ]);
    }

    /**
     * Record material used on a task and deduct inventory stock
     */
    public function store(Request $request, Task $task)
    {
        // Guard authorization
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity'          => 'required|numeric|min:0.01',
            'serial_number'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $task) {
            $item = InventoryItem::lockForUpdate()->findOrFail($validated['inventory_item_id']);

            // Validate sufficient stock
            if ($item->stock < $validated['quantity']) {
                return response()->json([
                    'message' => "Stok {$item->name} tidak mencukupi (Tersisa: {$item->stock} {$item->unit})"
                ], 422);
            }

            // Deduct stock
            $item->decrement('stock', $validated['quantity']);

            // Create record
            $taskMaterial = TaskMaterial::create([
                'task_id'           => $task->id,
                'inventory_item_id' => $item->id,
                'quantity'          => $validated['quantity'],
                'serial_number'     => $validated['serial_number'] ?? null,
                'notes'             => $validated['notes'] ?? null,
            ]);

            $taskMaterial->load('inventoryItem');

            return response()->json([
                'message' => 'Material berhasil dicatat dan stok gudang terpotong',
                'data'    => $taskMaterial,
                'current_stock' => $item->fresh()->stock
            ], 201);
        });
    }

    /**
     * Remove material from task and restore inventory stock
     */
    public function destroy(Task $task, TaskMaterial $material)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($material->task_id !== $task->id) {
            return response()->json(['message' => 'Material tidak terdaftar pada tugas ini'], 422);
        }

        DB::transaction(function () use ($material) {
            $item = InventoryItem::lockForUpdate()->find($material->inventory_item_id);
            if ($item) {
                $item->increment('stock', $material->quantity);
            }
            $material->delete();
        });

        return response()->json(['message' => 'Material berhasil dihapus dan stok dikembalikan']);
    }
}
