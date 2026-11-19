@extends('layouts.admin')

@section('title', 'Fiber Inventory & Materials')
@section('header', 'Inventory & Materials')

@section('content')
<div class="space-y-6">

    <!-- Validation Error Banner (for Add/Edit/Adjust Modals) -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs shadow-sm">
            <div class="flex items-center gap-2 font-bold mb-1">
                <ion-icon name="alert-circle" class="text-base text-red-600 flex-shrink-0"></ion-icon>
                <span>Please fix the following inventory validation error(s):</span>
            </div>
            <ul class="list-disc pl-6 space-y-0.5 text-red-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Clickable KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.inventory.index') }}" class="bg-white rounded-xl border {{ !request('stock_status') && !request('active_status') ? 'border-blue-400 ring-1 ring-blue-100' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Catalog SKUs</p>
                    <h3 class="text-2xl font-bold text-textPrimary mt-1">
                        {{ $activeItemsCount }} <span class="text-sm font-medium text-slate-400">/ {{ $totalItems }}</span>
                    </h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <ion-icon name="cube-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">Active SKUs synced to Technician APK</p>
        </a>

        <a href="{{ route('admin.inventory.index', ['stock_status' => 'in_stock']) }}" class="bg-white rounded-xl border {{ request('stock_status') === 'in_stock' ? 'border-emerald-400 ring-1 ring-emerald-100' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Warehouse Stock</p>
                    <h3 class="text-2xl font-bold text-textPrimary mt-1">{{ rtrim(rtrim(number_format($totalStockUnits, 2, '.', ','), '0'), '.') }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <ion-icon name="layers-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">{{ rtrim(rtrim(number_format($totalDeployedUnits, 2, '.', ','), '0'), '.') }} total units consumed in BOM</p>
        </a>

        <a href="{{ route('admin.inventory.index', ['stock_status' => 'low']) }}" class="bg-white rounded-xl border {{ request('stock_status') === 'low' || request('stock_status') === 'out_of_stock' ? 'border-amber-400 ring-1 ring-amber-100' : ($lowStockCount > 0 ? 'border-amber-200 bg-amber-50/20' : 'border-fsmBorder') }} p-5 shadow-sm hover:shadow-md transition-all block">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Low / Depleted</p>
                    <h3 class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1">{{ $lowStockCount }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">
                <span class="{{ $outOfStockCount > 0 ? 'text-red-600 font-bold' : '' }}">{{ $outOfStockCount }} out of stock</span> &bull; &le; 10 threshold
            </p>
        </a>

        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Stock Valuation</p>
                    <h3 class="text-xl font-bold text-emerald-700 mt-1">{{ \App\Services\SettingService::formatCurrency((float) $totalValuation) }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <ion-icon name="cash-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">Warehouse asset value ({{ $currencyCode }})</p>
        </div>
    </div>

    <!-- Main Content Box -->
    <div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">

        <!-- Header & Action Row -->
        <div class="p-5 border-b border-fsmBorder flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-base font-bold text-textPrimary">Network Materials &amp; Hardware Catalog</h3>
                <p class="text-xs text-textSecondary">Real-time warehouse stock synced with Field Technician APK Bill of Materials (BOM) &amp; BAST certificates.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.inventory.export', request()->query()) }}"
                   class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-semibold shadow-sm transition-all inline-flex items-center gap-1.5">
                    <ion-icon name="download-outline" class="text-sm pointer-events-none"></ion-icon> Export CSV
                </a>
                <button type="button" onclick="openAddInventoryModal()"
                        class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition-all inline-flex items-center gap-1.5">
                    <ion-icon name="add-circle-outline" class="text-base pointer-events-none"></ion-icon> Add New Item
                </button>
            </div>
        </div>

        <!-- Multi-Facet Filter & Search Bar -->
        <div class="p-4 bg-slate-50 border-b border-fsmBorder">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="flex flex-col md:flex-row gap-2.5">
                <div class="relative flex-1">
                    <ion-icon name="search-outline" class="absolute left-3 top-2.5 text-slate-400 text-base pointer-events-none"></ion-icon>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by item name, SKU code, or specs..."
                           class="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>

                <select name="category" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none">
                    <option value="">All Categories</option>
                    <option value="material" {{ request('category') === 'material' ? 'selected' : '' }}>Material</option>
                    <option value="device" {{ request('category') === 'device' ? 'selected' : '' }}>Device / ONT</option>
                    <option value="tool" {{ request('category') === 'tool' ? 'selected' : '' }}>Tools</option>
                </select>

                <select name="stock_status" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none">
                    <option value="">All Stock Levels</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>Healthy (&gt; 10)</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock (&le; 10)</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (0)</option>
                </select>

                <select name="active_status" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-xl px-3 py-2 bg-white focus:outline-none">
                    <option value="">All Status</option>
                    <option value="active" {{ request('active_status') === 'active' ? 'selected' : '' }}>Active in APK</option>
                    <option value="inactive" {{ request('active_status') === 'inactive' ? 'selected' : '' }}>Inactive / Archived</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold inline-flex items-center justify-center gap-1">
                    <ion-icon name="funnel-outline" class="pointer-events-none"></ion-icon> Filter
                </button>

                @if(request()->hasAny(['search', 'category', 'stock_status', 'active_status']))
                    <a href="{{ route('admin.inventory.index') }}" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-semibold inline-flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Catalog Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-fsmBorder bg-slate-50/50 text-slate-500 font-semibold uppercase text-[10px]">
                        <th class="p-4">SKU &amp; APK Status</th>
                        <th class="p-4">Item &amp; BOM Usage</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Warehouse Stock</th>
                        <th class="p-4">Unit Price &amp; Value</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsmBorder">
                    @forelse($items as $item)
                        @php
                            $catBadges = [
                                'material' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'device'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                'tool'     => 'bg-amber-50 text-amber-700 border-amber-200',
                            ];
                            $stockFloat = (float) $item->stock;
                            $stockDisplay = rtrim(rtrim(number_format($stockFloat, 2, '.', ','), '0'), '.');
                            $bomCount = (int) ($item->task_materials_count ?? 0);
                            $bomQty = (float) ($item->task_materials_sum_quantity ?? 0);
                            $bomQtyDisplay = rtrim(rtrim(number_format($bomQty, 2, '.', ','), '0'), '.');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors {{ !$item->is_active ? 'bg-slate-50/60 opacity-75' : '' }}">
                            <td class="p-4 align-top">
                                <div class="font-mono font-bold text-slate-700 text-xs">{{ $item->code }}</div>
                                <div class="mt-1">
                                    @if($item->is_active)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active (APK)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 align-top">
                                <div class="font-bold text-textPrimary text-sm">{{ $item->name }}</div>
                                @if($item->description)
                                    <div class="text-[11px] text-textSecondary mt-0.5 max-w-xs truncate" title="{{ $item->description }}">{{ $item->description }}</div>
                                @endif
                                <div class="mt-1.5 flex items-center gap-1.5 text-[10px]">
                                    @if($bomCount > 0)
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-semibold inline-flex items-center gap-1">
                                            <ion-icon name="receipt-outline" class="pointer-events-none"></ion-icon>
                                            Used in {{ $bomCount }} {{ \Illuminate\Support\Str::plural('task', $bomCount) }} &bull; {{ $bomQtyDisplay }} {{ $item->unit }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Not yet consumed in work orders</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 align-top">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $catBadges[$item->category] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $item->category }}
                                </span>
                            </td>
                            <td class="p-4 align-top">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-sm {{ $stockFloat <= 0 ? 'text-red-600' : ($stockFloat <= 10 ? 'text-amber-600' : 'text-slate-800') }}">
                                        {{ $stockDisplay }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">{{ $item->unit }}</span>
                                    @if($stockFloat <= 0)
                                        <span class="text-[9px] bg-red-100 text-red-700 border border-red-200 font-bold px-1.5 py-0.5 rounded uppercase">Out of Stock</span>
                                    @elseif($stockFloat <= 10)
                                        <span class="text-[9px] bg-amber-100 text-amber-800 border border-amber-200 font-bold px-1.5 py-0.5 rounded uppercase">Low</span>
                                    @endif
                                </div>
                                <button type="button"
                                    onclick="openAdjustStockModal(this)"
                                    data-adjust-url="{{ route('admin.inventory.adjust_stock', $item->id) }}"
                                    data-code="{{ $item->code }}"
                                    data-name="{{ $item->name }}"
                                    data-stock="{{ $item->stock }}"
                                    data-unit="{{ $item->unit }}"
                                    class="mt-1.5 text-[10px] font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-0.5">
                                    <ion-icon name="swap-vertical-outline" class="pointer-events-none"></ion-icon> Quick Restock / Adjust
                                </button>
                            </td>
                            <td class="p-4 align-top">
                                <div class="font-semibold text-slate-700">
                                    {{ \App\Services\SettingService::formatCurrency((float) $item->unit_price) }} <span class="text-[10px] text-slate-400 font-normal">/ {{ $item->unit }}</span>
                                </div>
                                <div class="text-[11px] font-bold text-emerald-700 mt-0.5">
                                    Val: {{ \App\Services\SettingService::formatCurrency((float) ($stockFloat * (float) $item->unit_price)) }}
                                </div>
                            </td>
                            <td class="p-4 align-top text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                        onclick="openAdjustStockModal(this)"
                                        data-adjust-url="{{ route('admin.inventory.adjust_stock', $item->id) }}"
                                        data-code="{{ $item->code }}"
                                        data-name="{{ $item->name }}"
                                        data-stock="{{ $item->stock }}"
                                        data-unit="{{ $item->unit }}"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-all"
                                        title="Quick Restock / Adjust Stock" aria-label="Restock {{ $item->code }}">
                                        <ion-icon name="swap-vertical-outline" class="text-sm pointer-events-none"></ion-icon>
                                    </button>

                                    <form action="{{ route('admin.inventory.toggle_status', $item->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg border transition-all {{ $item->is_active ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' }}"
                                            title="{{ $item->is_active ? 'Deactivate (Hide from Technician APK)' : 'Reactivate (Show in Technician APK)' }}"
                                            aria-label="Toggle Status {{ $item->code }}">
                                            <ion-icon name="power-outline" class="text-sm pointer-events-none"></ion-icon>
                                        </button>
                                    </form>

                                    <button type="button"
                                        onclick="openEditInventoryModal(this)"
                                        data-update-url="{{ route('admin.inventory.update', $item->id) }}"
                                        data-code="{{ $item->code }}"
                                        data-name="{{ $item->name }}"
                                        data-category="{{ $item->category }}"
                                        data-stock="{{ $item->stock }}"
                                        data-unit="{{ $item->unit }}"
                                        data-price="{{ $item->unit_price }}"
                                        data-desc="{{ $item->description ?? '' }}"
                                        data-active="{{ $item->is_active ? '1' : '0' }}"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100 hover:text-blue-600 transition-all"
                                        title="Edit Item Details" aria-label="Edit {{ $item->code }}">
                                        <ion-icon name="create-outline" class="text-sm pointer-events-none"></ion-icon>
                                    </button>

                                    <button type="button"
                                        onclick="openDeleteInventoryModal(this)"
                                        data-delete-url="{{ route('admin.inventory.destroy', $item->id) }}"
                                        data-toggle-url="{{ route('admin.inventory.toggle_status', $item->id) }}"
                                        data-code="{{ $item->code }}"
                                        data-name="{{ $item->name }}"
                                        data-bom-count="{{ $bomCount }}"
                                        data-active="{{ $item->is_active ? '1' : '0' }}"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-slate-50 text-slate-600 border border-slate-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all"
                                        title="{{ $bomCount > 0 ? 'Referenced in BOM — Click to Deactivate or Inspect' : 'Delete Item' }}"
                                        aria-label="Delete {{ $item->code }}">
                                        <ion-icon name="trash-outline" class="text-sm pointer-events-none"></ion-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-textSecondary">
                                <ion-icon name="cube-outline" class="text-4xl text-slate-300 mb-2"></ion-icon>
                                <p class="text-sm font-bold text-textPrimary">No inventory items match your filter</p>
                                <p class="text-xs mt-1">Reset filters or add a new warehouse SKU.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-fsmBorder bg-slate-50/40">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Add Item -->
<div id="modal-add-item" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Add New Hardware / Material</h3>
                <p class="text-[11px] text-textSecondary">New active items become immediately selectable in the Technician APK BOM picker.</p>
            </div>
            <button type="button" onclick="closeInventoryModal('modal-add-item')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>
        <form action="{{ route('admin.inventory.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">SKU Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. MAT-DC-1C" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Category *</label>
                    <select name="category" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none">
                        <option value="material" {{ old('category') === 'material' ? 'selected' : '' }}>Material (Cable, Dropcore, Connectors)</option>
                        <option value="device" {{ old('category') === 'device' ? 'selected' : '' }}>Device (ONT Modem, Router)</option>
                        <option value="tool" {{ old('category') === 'tool' ? 'selected' : '' }}>Tools (Splicer, Cleaver, OPM)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Item Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Dropcore Fiber Cable 1-Core" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Initial Stock *</label>
                    <input type="number" step="any" min="0" name="stock" value="{{ old('stock', 10) }}" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Unit *</label>
                    <input type="text" name="unit" value="{{ old('unit', 'pcs') }}" placeholder="meter/pcs/unit" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Unit Price ({{ $currencyCode }}) *</label>
                    <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', 0) }}" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Specifications / Notes</label>
                <textarea name="description" rows="2" placeholder="Specifications, brand, or batch notes..." class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('description') }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-700 font-medium cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-primary focus:ring-primary">
                <span>Active &amp; visible in Technician APK BOM catalog</span>
            </label>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeInventoryModal('modal-add-item')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-primary hover:bg-blue-700 text-white rounded-xl shadow-sm">Save to Inventory</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Item -->
<div id="modal-edit-item" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-textPrimary">Edit Inventory Item</h3>
            <button type="button" onclick="closeInventoryModal('modal-edit-item')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>
        <form id="form-edit-item" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active_present" value="1">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">SKU Code *</label>
                    <input type="text" name="code" id="edit-code" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white uppercase font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Category *</label>
                    <select name="category" id="edit-category" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl bg-white focus:outline-none">
                        <option value="material">Material</option>
                        <option value="device">Device</option>
                        <option value="tool">Tools</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Item Name *</label>
                <input type="text" name="name" id="edit-name" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Stock *</label>
                    <input type="number" step="any" min="0" name="stock" id="edit-stock" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Unit *</label>
                    <input type="text" name="unit" id="edit-unit" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Unit Price ({{ $currencyCode }}) *</label>
                    <input type="number" step="0.01" min="0" name="unit_price" id="edit-price" required class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Specifications / Notes</label>
                <textarea name="description" id="edit-desc" rows="2" class="w-full p-2.5 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-700 font-medium cursor-pointer">
                <input type="checkbox" name="is_active" id="edit-active" value="1" class="rounded border-slate-300 text-primary focus:ring-primary">
                <span>Active &amp; visible in Technician APK BOM catalog</span>
            </label>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeInventoryModal('modal-edit-item')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-primary hover:bg-blue-700 text-white rounded-xl shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Atomic Quick Restock / Adjust Stock -->
<div id="modal-adjust-stock" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-start mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Atomic Stock Adjustment</h3>
                <p id="adj-item-label" class="text-[11px] text-textSecondary mt-0.5">SKU</p>
            </div>
            <button type="button" onclick="closeInventoryModal('modal-adjust-stock')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>

        <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Current Warehouse Stock</span>
            <span id="adj-current-stock" class="font-bold text-slate-800 text-sm">0</span>
        </div>

        <form id="form-adjust-stock" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="add" checked class="peer sr-only">
                    <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 text-xs font-bold transition-all">
                        + Restock
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="subtract" class="peer sr-only">
                    <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 text-xs font-bold transition-all">
                        - Deduct
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="set" class="peer sr-only">
                    <div class="p-2.5 text-center rounded-xl border border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 text-xs font-bold transition-all">
                        = Set Exact
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Quantity (<span id="adj-unit-label">units</span>) *</label>
                <input type="number" step="any" min="0" name="quantity" id="adj-quantity" value="10" required
                       class="w-full p-2.5 text-sm font-bold border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/25">
                <p class="text-[10px] text-slate-400 mt-1">Executed inside a row-locked DB transaction to stay safe against concurrent APK deductions.</p>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeInventoryModal('modal-adjust-stock')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-sm">Apply Stock Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete or Deactivate Inventory Item -->
<div id="modal-delete-inventory" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-start gap-3 pb-3 mb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center flex-shrink-0">
                <ion-icon name="trash-outline" class="text-xl pointer-events-none"></ion-icon>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="del-inv-title" class="text-sm font-bold text-textPrimary">Delete Inventory Item</h3>
                <p id="del-inv-label" class="text-[11px] text-textSecondary truncate mt-0.5">SKU</p>
            </div>
            <button type="button" onclick="closeInventoryModal('modal-delete-inventory')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>

        <div id="del-inv-bom-guard" class="hidden space-y-3">
            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                <span class="font-bold block mb-1">Protected by BAST Certificate Integrity</span>
                <span id="del-inv-bom-msg">This SKU is already referenced in work order BOM records and cannot be permanently deleted. You can deactivate it instead to hide it from the Technician APK while keeping historical BAST certificates intact.</span>
            </div>
            <form id="form-deactivate-instead" method="POST" action="" class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                @csrf
                <button type="button" onclick="closeInventoryModal('modal-delete-inventory')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">Close</button>
                <button id="btn-deactivate-instead" type="submit" class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-sm inline-flex items-center gap-1.5">
                    <ion-icon name="power-outline" class="pointer-events-none"></ion-icon> Deactivate Instead
                </button>
            </form>
        </div>

        <div id="del-inv-confirm-wrap" class="space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                This SKU has not been consumed in any work orders yet. Deleting it will permanently remove it from the warehouse catalog and Technician APK.
            </p>
            <form id="form-delete-inventory" method="POST" action="" class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeInventoryModal('modal-delete-inventory')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded-xl shadow-sm inline-flex items-center gap-1.5">
                    <ion-icon name="trash-outline" class="pointer-events-none"></ion-icon> Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAddInventoryModal() {
        document.getElementById('modal-add-item').classList.remove('hidden');
    }

    function closeInventoryModal(id) {
        document.getElementById(id)?.classList.add('hidden');
    }

    function openEditInventoryModal(btn) {
        document.getElementById('form-edit-item').action = btn.getAttribute('data-update-url');
        document.getElementById('edit-code').value = btn.getAttribute('data-code') || '';
        document.getElementById('edit-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-category').value = btn.getAttribute('data-category') || 'material';
        document.getElementById('edit-stock').value = btn.getAttribute('data-stock') || '0';
        document.getElementById('edit-unit').value = btn.getAttribute('data-unit') || 'pcs';
        document.getElementById('edit-price').value = btn.getAttribute('data-price') || '0';
        document.getElementById('edit-desc').value = btn.getAttribute('data-desc') || '';
        document.getElementById('edit-active').checked = btn.getAttribute('data-active') === '1';
        document.getElementById('modal-edit-item').classList.remove('hidden');
    }

    function openAdjustStockModal(btn) {
        const code = btn.getAttribute('data-code') || '';
        const name = btn.getAttribute('data-name') || '';
        const stock = btn.getAttribute('data-stock') || '0';
        const unit = btn.getAttribute('data-unit') || 'units';

        document.getElementById('form-adjust-stock').action = btn.getAttribute('data-adjust-url');
        document.getElementById('adj-item-label').textContent = `[${code}] ${name}`;
        document.getElementById('adj-current-stock').textContent = `${stock} ${unit}`;
        document.getElementById('adj-unit-label').textContent = unit;
        document.getElementById('adj-quantity').value = '10';
        document.getElementById('modal-adjust-stock').classList.remove('hidden');
    }

    function openDeleteInventoryModal(btn) {
        const code = btn.getAttribute('data-code') || '';
        const name = btn.getAttribute('data-name') || '';
        const bomCount = parseInt(btn.getAttribute('data-bom-count') || '0', 10);
        const isActive = btn.getAttribute('data-active') === '1';

        document.getElementById('del-inv-label').textContent = `[${code}] ${name}`;

        const guardWrap = document.getElementById('del-inv-bom-guard');
        const confirmWrap = document.getElementById('del-inv-confirm-wrap');
        const titleEl = document.getElementById('del-inv-title');

        if (bomCount > 0) {
            titleEl.textContent = 'Cannot Delete Referenced SKU';
            document.getElementById('del-inv-bom-msg').textContent =
                `This SKU is referenced in ${bomCount} work order BOM record(s) and cannot be permanently deleted. ` +
                (isActive
                    ? 'Deactivate it instead to hide it from the Technician APK while preserving historical BAST certificates.'
                    : 'This SKU is already deactivated and safely hidden from the Technician APK.');
            document.getElementById('form-deactivate-instead').action = btn.getAttribute('data-toggle-url');
            const deactivateBtn = document.getElementById('btn-deactivate-instead');
            if (isActive) {
                deactivateBtn.classList.remove('hidden');
            } else {
                deactivateBtn.classList.add('hidden');
            }
            guardWrap.classList.remove('hidden');
            confirmWrap.classList.add('hidden');
        } else {
            titleEl.textContent = 'Delete Inventory Item';
            document.getElementById('form-delete-inventory').action = btn.getAttribute('data-delete-url');
            guardWrap.classList.add('hidden');
            confirmWrap.classList.remove('hidden');
        }

        document.getElementById('modal-delete-inventory').classList.remove('hidden');
    }

    ['modal-add-item', 'modal-edit-item', 'modal-adjust-stock', 'modal-delete-inventory'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e) {
            if (e.target === this) closeInventoryModal(id);
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['modal-add-item', 'modal-edit-item', 'modal-adjust-stock', 'modal-delete-inventory'].forEach(closeInventoryModal);
        }
    });
</script>
@endpush
@endsection
