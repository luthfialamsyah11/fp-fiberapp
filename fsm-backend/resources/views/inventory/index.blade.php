@extends('layouts.admin')

@section('title', 'Inventaris & Material Fiber')
@section('header', 'Inventaris & Material')

@section('content')
<div class="space-y-6">

    <!-- Top Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Katalog Barang</p>
                    <h3 class="text-2xl font-bold text-textPrimary mt-1">{{ $totalItems }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <ion-icon name="cube-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">Total SKU terdaftar di sistem</p>
        </div>

        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kuantitas</p>
                    <h3 class="text-2xl font-bold text-textPrimary mt-1">{{ number_format($totalStockUnits, 0, ',', '.') }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    <ion-icon name="layers-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">Akumulasi stok fisik di gudang</p>
        </div>

        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stok Menipis</p>
                    <h3 class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-1">{{ $lowStockCount }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">&le; 10 unit/meter tersisa</p>
        </div>

        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Valuasi Stok</p>
                    <h3 class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($totalValuation, 0, ',', '.') }}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <ion-icon name="cash-outline"></ion-icon>
                </div>
            </div>
            <p class="text-[11px] text-textSecondary mt-2">Estimasi nilai aset gudang</p>
        </div>
    </div>

    <!-- Main Content Box -->
    <div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
        
        <!-- Header & Action Row -->
        <div class="p-5 border-b border-fsmBorder flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-base font-bold text-textPrimary">Daftar Suku Cadang &amp; Material Jaringan</h3>
                <p class="text-xs text-textSecondary">Pantau pergerakan stok kabel, modem ONT, dan konektor fiber</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('modal-add-item').classList.remove('hidden')" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                    <ion-icon name="add-circle-outline" class="text-base"></ion-icon> Tambah Barang Baru
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="p-4 bg-slate-50 border-b border-fsmBorder">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <ion-icon name="search-outline" class="absolute left-3 top-3 text-slate-400 text-base"></ion-icon>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode barang..." class="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <select name="category" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-3 py-2 bg-white focus:outline-none">
                    <option value="">Semua Kategori</option>
                    <option value="material" {{ request('category') == 'material' ? 'selected' : '' }}>Material</option>
                    <option value="device" {{ request('category') == 'device' ? 'selected' : '' }}>Device / ONT</option>
                    <option value="tool" {{ request('category') == 'tool' ? 'selected' : '' }}>Tools</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-700">Filter</button>
                @if(request('search') || request('category'))
                    <a href="{{ route('admin.inventory.index') }}" class="px-3 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center">Reset</a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-fsmBorder bg-slate-50/50 text-slate-500 font-semibold uppercase text-[10px]">
                        <th class="p-4">Kode SKU</th>
                        <th class="p-4">Nama Barang</th>
                        <th class="p-4">Kategori</th>
                        <th class="p-4">Sisa Stok</th>
                        <th class="p-4">Harga Satuan</th>
                        <th class="p-4">Total Nilai</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsmBorder">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-mono font-bold text-slate-600">
                                <code>{{ $item->code }}</code>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-textPrimary text-sm">{{ $item->name }}</div>
                                <div class="text-[11px] text-textSecondary">{{ $item->description ?: '-' }}</div>
                            </td>
                            <td class="p-4">
                                @php
                                    $catBadges = [
                                        'material' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'device'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'tool'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $catBadges[$item->category] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $item->category }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-sm {{ $item->stock <= 10 ? 'text-amber-600' : 'text-slate-800' }}">
                                        {{ number_format($item->stock, 0, ',', '.') }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">{{ $item->unit }}</span>
                                    @if($item->stock <= 10)
                                        <span class="text-[9px] bg-red-100 text-red-700 font-bold px-1.5 py-0.5 rounded">Low</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 font-semibold text-slate-600">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="p-4 font-bold text-emerald-700">
                                Rp {{ number_format($item->stock * $item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" onclick="openEditModal({{ json_encode($item) }})" class="p-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="Edit Barang">
                                        <ion-icon name="create-outline"></ion-icon>
                                    </button>
                                    <form action="{{ route('admin.inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus item ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" title="Hapus Barang">
                                            <ion-icon name="trash-outline"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-textSecondary">
                                <ion-icon name="cube-outline" class="text-4xl text-slate-300 mb-1"></ion-icon>
                                <p class="text-sm font-semibold">Tidak ada barang inventaris yang ditemukan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-fsmBorder">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Barang -->
<div id="modal-add-item" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-base font-bold text-textPrimary">Tambah Suku Cadang / Material Baru</h3>
            <button onclick="document.getElementById('modal-add-item').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form action="{{ route('admin.inventory.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kode SKU *</label>
                    <input type="text" name="code" placeholder="Misal: MAT-DC-1C" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori *</label>
                    <select name="category" required class="w-full p-2 text-xs border rounded-lg focus:outline-none">
                        <option value="material">Material (Kabel, Klem, Konektor)</option>
                        <option value="device">Device (ONT Modem, Router)</option>
                        <option value="tool">Tools (Splicer, Cleaver, OPM)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Barang *</label>
                <input type="text" name="name" placeholder="Misal: Kabel Dropcore 1 Core" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Stok Awal *</label>
                    <input type="number" step="any" name="stock" value="10" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Satuan *</label>
                    <input type="text" name="unit" placeholder="meter/pcs/unit" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Harga Satuan (Rp) *</label>
                    <input type="number" name="unit_price" value="5000" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Deskripsi Tambahan</label>
                <textarea name="description" rows="2" placeholder="Catatan spesifikasi atau nomor seri..." class="w-full p-2 text-xs border rounded-lg focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('modal-add-item').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-primary hover:bg-blue-700 text-white rounded-lg">Simpan ke Inventaris</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Barang -->
<div id="modal-edit-item" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-base font-bold text-textPrimary">Edit Data Inventaris</h3>
            <button onclick="document.getElementById('modal-edit-item').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form id="form-edit-item" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kode SKU</label>
                    <input type="text" id="edit-code" disabled class="w-full p-2 text-xs border rounded-lg bg-slate-100 uppercase font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori *</label>
                    <select name="category" id="edit-category" required class="w-full p-2 text-xs border rounded-lg focus:outline-none">
                        <option value="material">Material</option>
                        <option value="device">Device</option>
                        <option value="tool">Tools</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Barang *</label>
                <input type="text" name="name" id="edit-name" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Update Stok *</label>
                    <input type="number" step="any" name="stock" id="edit-stock" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Satuan *</label>
                    <input type="text" name="unit" id="edit-unit" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Harga Satuan (Rp) *</label>
                    <input type="number" name="unit_price" id="edit-price" required class="w-full p-2 text-xs border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Deskripsi Tambahan</label>
                <textarea name="description" id="edit-desc" rows="2" class="w-full p-2 text-xs border rounded-lg focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('modal-edit-item').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold bg-primary hover:bg-blue-700 text-white rounded-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(item) {
        document.getElementById('form-edit-item').action = `/inventory/${item.id}`;
        document.getElementById('edit-code').value = item.code;
        document.getElementById('edit-name').value = item.name;
        document.getElementById('edit-category').value = item.category;
        document.getElementById('edit-stock').value = item.stock;
        document.getElementById('edit-unit').value = item.unit;
        document.getElementById('edit-price').value = item.unit_price;
        document.getElementById('edit-desc').value = item.description || '';
        document.getElementById('modal-edit-item').classList.remove('hidden');
    }
</script>
@endsection
