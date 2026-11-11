<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        $totalItems = InventoryItem::count();
        $totalStockUnits = InventoryItem::sum('stock');
        $lowStockCount = InventoryItem::where('stock', '<=', 10)->count();
        $totalValuation = InventoryItem::all()->sum(function ($i) {
            return $i->stock * $i->unit_price;
        });

        return view('inventory.index', compact('items', 'totalItems', 'totalStockUnits', 'lowStockCount', 'totalValuation'));
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

        InventoryItem::create($validated);
        return redirect()->route('admin.inventory.index')->with('success', 'Material/Perangkat berhasil ditambahkan ke inventaris.');
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'unit'        => 'required|string|max:20',
            'stock'       => 'required|numeric|min:0',
            'unit_price'  => 'required|numeric|min:0',
            'category'    => 'required|string|in:material,device,tool',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);

        $inventory->update($validated);
        return redirect()->route('admin.inventory.index')->with('success', 'Data inventaris berhasil diperbarui.');
    }

    public function destroy(InventoryItem $inventory)
    {
        $inventory->delete();
        return redirect()->route('admin.inventory.index')->with('success', 'Item inventaris berhasil dihapus.');
    }
}
