<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\SettingService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::where('is_active', true)
            ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END ASC')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItem $item) {
                $arr = $item->toArray();
                $arr['in_stock'] = (float) $item->stock > 0;
                $arr['formatted_price'] = SettingService::formatCurrency((float) $item->unit_price);
                return $arr;
            });

        return response()->json(['data' => $items])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

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

        return response()->json(['data' => $items])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

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
        return response()->json(['message' => 'Inventory item created successfully.', 'data' => $item], 201);
    }

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
        return response()->json(['message' => 'Inventory item updated successfully.', 'data' => $item]);
    }

    public function destroy(InventoryItem $item)
    {
        if ($item->taskMaterials()->exists()) {
            return response()->json([
                'message' => 'Cannot delete inventory item referenced in work order BOM records. Deactivate it instead.',
            ], 422);
        }

        $item->delete();
        return response()->json(['message' => 'Inventory item deleted successfully.']);
    }
}
