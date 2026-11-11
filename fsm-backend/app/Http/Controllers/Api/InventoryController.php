<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Active inventory items for field technicians (Mobile App Dropdown)
     */
    public function index()
    {
        $items = InventoryItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Admin Inventory Management List
     */
    public function adminIndex(Request $request)
    {
        $items = InventoryItem::when($request->search, function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->category, function ($q, $cat) {
                return $q->where('category', $cat);
            })
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Store new inventory item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:inventory_items,code',
            'name'        => 'required|string|max:255',
            'unit'        => 'required|string|max:20',
            'stock'       => 'required|numeric|min:0',
            'unit_price'  => 'required|numeric|min:0',
            'category'    => 'required|string|in:material,device,tool',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create($validated);
        return response()->json(['message' => 'Item berhasil ditambahkan', 'data' => $item], 201);
    }

    /**
     * Update inventory item
     */
    public function update(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'unit'        => 'sometimes|string|max:20',
            'stock'       => 'sometimes|numeric|min:0',
            'unit_price'  => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|string|in:material,device,tool',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);

        $item->update($validated);
        return response()->json(['message' => 'Item berhasil diperbarui', 'data' => $item]);
    }

    /**
     * Delete / Toggle active state
     */
    public function destroy(InventoryItem $item)
    {
        $item->delete();
        return response()->json(['message' => 'Item berhasil dihapus']);
    }
}
