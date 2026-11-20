@extends('layouts.admin')

@section('title', 'Fiber GIS Assets (OLT/ODC/ODP)')
@section('header', 'Fiber Network GIS Asset Registry')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>#fiber-gis-map { height: 340px; width: 100%; border-radius: 14px; z-index: 1; }</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Top Header -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 flex items-center justify-center flex-shrink-0">
                <ion-icon name="git-network-outline" class="text-xl"></ion-icon>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">OLT / ODC / ODP Fiber Infrastructure Map</h2>
                <p class="text-xs text-slate-500 mt-0.5">Track passive optical distribution points, splitter port utilization, and GPS coordinates synced to the Technician APK map.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('modal-add-node').classList.remove('hidden')"
            class="px-4 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
            <ion-icon name="add-circle-outline" class="text-base"></ion-icon>
            <span>Add Fiber Node</span>
        </button>
    </div>

    <!-- KPI Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-slate-400">Total Nodes</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $summary['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-violet-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-violet-600">OLT Core</span>
            <div class="text-2xl font-extrabold text-violet-700 mt-1 tabular-nums">{{ $summary['olt'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-blue-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-blue-600">ODC Cabinets</span>
            <div class="text-2xl font-extrabold text-blue-700 mt-1 tabular-nums">{{ $summary['odc'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-emerald-600">ODP Splitters</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1 tabular-nums">{{ $summary['odp'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase text-amber-600">Port Utilization</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1 tabular-nums">{{ $summary['used_ports'] }} / {{ $summary['total_ports'] }}</div>
        </div>
    </div>

    <!-- GIS Map -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <div id="fiber-gis-map"></div>
    </div>

    <!-- Asset Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Code &amp; Type</th>
                        <th class="py-3.5 px-4">Location &amp; Parent</th>
                        <th class="py-3.5 px-4">Splitter Ports</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    @forelse($nodes as $node)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-slate-900">{{ $node->code }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-blue-50 text-blue-700 border border-blue-200">{{ $node->node_type }}</span>
                                </div>
                                <div class="text-slate-600 font-medium mt-0.5">{{ $node->name }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-slate-700 truncate max-w-[240px]">{{ $node->address ?: 'No address recorded' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    @if($node->parent) Uplink: {{ $node->parent->code }} &bull; @endif
                                    @if($node->latitude !== null && $node->longitude !== null) {{ $node->latitude }}, {{ $node->longitude }} @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $node->utilization_percent >= 85 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $node->utilization_percent }}%"></div>
                                    </div>
                                    <span class="font-mono font-bold text-[11px]">{{ $node->used_ports }}/{{ $node->total_ports }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $node->status === 'operational' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ $node->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form method="POST" action="{{ route('admin.fiber_assets.destroy', $node->id) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-7 w-7 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white inline-flex items-center justify-center cursor-pointer">
                                        <ion-icon name="trash-outline" class="text-xs"></ion-icon>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center text-slate-400 text-xs">No OLT/ODC/ODP fiber nodes registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Fiber Node Modal -->
<div id="modal-add-node" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold">Register OLT / ODC / ODP Node</h3>
            <button type="button" onclick="document.getElementById('modal-add-node').classList.add('hidden')" class="text-slate-300 hover:text-white cursor-pointer"><ion-icon name="close" class="text-lg"></ion-icon></button>
        </div>
        <form method="POST" action="{{ route('admin.fiber_assets.store') }}" class="p-5 space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Node Code *</label>
                    <input type="text" name="code" required placeholder="ODP-JKT-042" class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Node Type *</label>
                    <select name="node_type" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="odp">ODP (Optical Distribution Point)</option>
                        <option value="odc">ODC (Optical Distribution Cabinet)</option>
                        <option value="olt">OLT (Central Office Core)</option>
                        <option value="closure">Joint Closure</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Display Name *</label>
                    <input type="text" name="name" required placeholder="ODP Kemang Raya Pole #12" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Total Ports *</label>
                    <input type="number" name="total_ports" value="8" min="1" max="512" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Used Ports</label>
                    <input type="number" name="used_ports" value="0" min="0" max="512" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Latitude</label>
                    <input type="text" name="latitude" placeholder="-6.2088" class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Longitude</label>
                    <input type="text" name="longitude" placeholder="106.8456" class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono">
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Uplink Parent (Optional)</label>
                    <select name="parent_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="">-- Root / None --</option>
                        @foreach($parentOptions as $po)
                            <option value="{{ $po->id }}">[{{ strtoupper($po->node_type) }}] {{ $po->code }} — {{ $po->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Address / Pole Landmark</label>
                    <input type="text" name="address" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-add-node').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white font-bold cursor-pointer">Save Node</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var nodes = @json($nodes);
    var map = L.map('fiber-gis-map').setView([-6.2088, 106.8456], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    var bounds = [];
    nodes.forEach(function(n) {
        if (n.latitude !== null && n.longitude !== null) {
            var color = n.node_type === 'olt' ? '#7c3aed' : (n.node_type === 'odc' ? '#2563eb' : '#10b981');
            L.circleMarker([Number(n.latitude), Number(n.longitude)], {
                radius: n.node_type === 'olt' ? 10 : 7,
                color: '#fff',
                weight: 2,
                fillColor: color,
                fillOpacity: 0.9
            }).addTo(map).bindPopup('<b>' + n.code + '</b> (' + String(n.node_type).toUpperCase() + ')<br>' + n.name + '<br>Ports: ' + n.used_ports + '/' + n.total_ports);
            bounds.push([Number(n.latitude), Number(n.longitude)]);
        }
    });
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
});
</script>
@endpush
