<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\TaskMaterial;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    private function buildFilteredQuery(Request $request)
    {
        $query = InventoryItem::query()
            ->withCount('taskMaterials')
            ->withSum('taskMaterials', 'quantity');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && in_array($request->category, ['material', 'device', 'tool'], true)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'out_of_stock' => $query->where('stock', '<=', 0),
                'low'          => $query->where('stock', '<=', 10),
                'in_stock'     => $query->where('stock', '>', 10),
                default        => null,
            };
        }

        if ($request->filled('active_status')) {
            if ($request->active_status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->active_status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $items = $this->buildFilteredQuery($request)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalItems = InventoryItem::count();
        $activeItemsCount = InventoryItem::where('is_active', true)->count();
        $totalStockUnits = (float) InventoryItem::sum('stock');
        $lowStockCount = InventoryItem::where('stock', '<=', 10)->count();
        $outOfStockCount = InventoryItem::where('stock', '<=', 0)->count();
        $totalValuation = (float) InventoryItem::selectRaw('COALESCE(SUM(stock * unit_price), 0) as total')->value('total');
        $totalDeployedUnits = (float) TaskMaterial::sum('quantity');
        $currencyCode = strtoupper((string) SettingService::get('currency', 'USD'));

        return view('inventory.index', compact(
            'items',
            'totalItems',
            'activeItemsCount',
            'totalStockUnits',
            'lowStockCount',
            'outOfStockCount',
            'totalValuation',
            'totalDeployedUnits',
            'currencyCode'
        ));
    }

    public function export(Request $request)
    {
        $items = $this->buildFilteredQuery($request)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $filename = 'fiberops_inventory_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'SKU Code',
                'Item Name',
                'Category',
                'Status',
                'Stock',
                'Unit',
                'Unit Price',
                'Valuation',
                'BOM Tasks Count',
                'Total Units Deployed',
                'Description',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->code,
                    $item->name,
                    $item->category,
                    $item->is_active ? 'Active' : 'Inactive',
                    $item->stock,
                    $item->unit,
                    $item->unit_price,
                    round((float) $item->stock * (float) $item->unit_price, 2),
                    (int) ($item->task_materials_count ?? 0),
                    (float) ($item->task_materials_sum_quantity ?? 0),
                    $item->description ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function store(Request $request)
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
        ]);

        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:inventory_items,code',
            'name'        => 'required|string|max:255',
            'unit'        => 'required|string|max:20',
            'stock'       => 'required|numeric|min:0',
            'unit_price'  => 'required|numeric|min:0',
            'category'    => 'required|string|in:material,device,tool',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        InventoryItem::create($validated);

        return redirect()->route('admin.inventory.index')
            ->with('success', "Item [{$validated['code']}] {$validated['name']} added to warehouse inventory.");
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        if ($request->filled('code')) {
            $request->merge([
                'code' => strtoupper(trim((string) $request->input('code'))),
            ]);
        }

        $validated = $request->validate([
            'code'        => ['sometimes', 'required', 'string', 'max:50', Rule::unique('inventory_items', 'code')->ignore($inventory->id)],
            'name'        => 'required|string|max:255',
            'unit'        => 'required|string|max:20',
            'stock'       => 'required|numeric|min:0',
            'unit_price'  => 'required|numeric|min:0',
            'category'    => 'required|string|in:material,device,tool',
            'description' => 'nullable|string',
        ]);

        if ($request->has('is_active_present') || $request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $inventory->update($validated);

        return redirect()->route('admin.inventory.index')
            ->with('success', "Inventory item [{$inventory->code}] updated successfully.");
    }

    /**
     * Atomic warehouse stock adjustment (add / subtract / set) with row-level locking
     * to prevent lost-update race conditions with concurrent Technician APK BOM deductions.
     */
    public function adjustStock(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'mode'     => 'required|in:add,subtract,set',
            'quantity' => 'required|numeric|min:0',
        ]);

        $mode = $validated['mode'];
        $qty = (float) $validated['quantity'];

        $result = DB::transaction(function () use ($inventory, $mode, $qty) {
            /** @var InventoryItem $locked */
            $locked = InventoryItem::lockForUpdate()->findOrFail($inventory->id);
            $before = (float) $locked->stock;

            if ($mode === 'add') {
                $after = $before + $qty;
            } elseif ($mode === 'subtract') {
                if ($qty > $before) {
                    return [
                        'ok'      => false,
                        'message' => "Cannot deduct {$qty} {$locked->unit} from [{$locked->code}]. Only {$before} {$locked->unit} available in warehouse.",
                    ];
                }
                $after = $before - $qty;
            } else {
                $after = $qty;
            }

            $locked->update(['stock' => $after]);

            return [
                'ok'     => true,
                'code'   => $locked->code,
                'name'   => $locked->name,
                'unit'   => $locked->unit,
                'before' => $before,
                'after'  => $after,
            ];
        });

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        $verb = match ($mode) {
            'add'      => "Restocked +{$qty}",
            'subtract' => "Deducted -{$qty}",
            default    => "Set exact stock to {$qty}",
        };

        return back()->with(
            'success',
            "{$verb} {$result['unit']} for [{$result['code']}] {$result['name']} ({$result['before']} → {$result['after']} {$result['unit']})."
        );
    }

    /**
     * Toggle active/inactive visibility for Technician APK BOM picker without breaking historical BAST records.
     */
    public function toggleStatus(InventoryItem $inventory)
    {
        $nextState = !$inventory->is_active;
        $inventory->update(['is_active' => $nextState]);

        $label = $nextState ? 'activated (visible in Technician APK)' : 'deactivated (hidden from Technician APK, historical BAST preserved)';

        return back()->with('success', "Item [{$inventory->code}] {$inventory->name} {$label}.");
    }

    public function destroy(InventoryItem $inventory)
    {
        if ($inventory->taskMaterials()->exists()) {
            return back()->with('error', 'Cannot delete inventory item because it is referenced in work order BOM records. Deactivate it instead to preserve BAST certificate integrity.');
        }

        $code = $inventory->code;
        $inventory->delete();

        return redirect()->route('admin.inventory.index')
            ->with('success', "Inventory item [{$code}] deleted successfully.");
    }
}
