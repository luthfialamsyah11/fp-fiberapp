@extends('layouts.admin')

@section('title', 'Subscribers CRM')
@section('header', 'Subscriber CRM & ONT Registry')

@section('content')
<div class="space-y-6">
    <!-- Header Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center flex-shrink-0">
                <ion-icon name="business-outline" class="text-xl"></ion-icon>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">FTTH Subscriber Directory</h2>
                <p class="text-xs text-slate-500 mt-0.5">Manage active ISP subscribers, PPPoE credentials, ONT serial numbers, linked ODP ports, and repeat-outage risk.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="document.getElementById('modal-sync-mikrotik').classList.remove('hidden')"
                class="px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 text-xs font-bold inline-flex items-center gap-1.5 transition-all cursor-pointer">
                <ion-icon name="sync-outline" class="text-base"></ion-icon>
                <span>1-Click MikroTik Sync</span>
            </button>
            <button type="button" onclick="document.getElementById('modal-import-csv').classList.remove('hidden')"
                class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 text-xs font-bold inline-flex items-center gap-1.5 transition-all cursor-pointer">
                <ion-icon name="cloud-upload-outline" class="text-base"></ion-icon>
                <span>Import CSV</span>
            </button>
            <button type="button" onclick="document.getElementById('modal-add-customer').classList.remove('hidden')"
                class="px-4 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                <ion-icon name="add-circle-outline" class="text-base"></ion-icon>
                <span>Register Subscriber</span>
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Subscribers</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $summary['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Active Lines</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1 tabular-nums">{{ $summary['active'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Suspended</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1 tabular-nums">{{ $summary['suspended'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200 p-4 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Repeat Outages (30d)</span>
            <div class="text-2xl font-extrabold text-rose-700 mt-1 tabular-nums">{{ $summary['repeat_outages'] }}</div>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('admin.customers.index') }}" class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search CUST code, name, phone, PPPoE, ONT SN..."
            class="flex-1 min-w-[200px] rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary">
        <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700">
            <option value="">All Statuses</option>
            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ $statusFilter === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="terminated" {{ $statusFilter === 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold cursor-pointer">Filter</button>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Subscriber</th>
                        <th class="py-3.5 px-4">Plan &amp; PPPoE / ONT</th>
                        <th class="py-3.5 px-4">Linked ODP Node</th>
                        <th class="py-3.5 px-4">Outage Health (30d)</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-mono text-[10px] font-bold text-indigo-600">{{ $c->customer_code }}</div>
                                <div class="font-bold text-slate-900">{{ $c->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $c->phone }} @if($c->email) &bull; {{ $c->email }} @endif</div>
                                <div class="text-[10px] text-slate-400 truncate max-w-[240px]">{{ $c->address }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $c->service_plan ?: 'Standard FTTH' }}</div>
                                @if($c->pppoe_username)
                                    <div class="text-[10px] font-mono text-slate-500">PPPoE: {{ $c->pppoe_username }}</div>
                                @endif
                                @if($c->router)
                                    <div class="text-[10px] font-mono text-indigo-600 font-semibold">Router: {{ $c->router->code }}</div>
                                @endif
                                @if($c->ont_serial_number)
                                    <div class="text-[10px] font-mono text-slate-500">ONT: {{ $c->ont_serial_number }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($c->fiberNode)
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono font-bold text-[10px]">
                                        {{ $c->fiberNode->code }}
                                    </span>
                                    @if($c->odp_port_number)
                                        <span class="text-[10px] text-slate-500 ml-1">Port #{{ $c->odp_port_number }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-[10px]">Unlinked</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($c->recent_outages_count >= 2)
                                    <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[10px]">
                                        ⚠ {{ $c->recent_outages_count }} tickets / 30d
                                    </span>
                                @else
                                    <span class="text-slate-600 font-medium">{{ $c->tasks_count }} total tickets</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $c->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($c->status === 'suspended' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $c->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5 flex-wrap justify-end">
                                    @if($c->status === 'isolated')
                                        <form method="POST" action="{{ route('admin.customers.unisolate', $c->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 text-[10px] font-bold transition-all cursor-pointer" title="Un-isolate PPPoE on MikroTik">
                                                Un-Isolate
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.customers.provision_pppoe', $c->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 text-[10px] font-bold transition-all cursor-pointer" title="Provision PPPoE Secret on MikroTik">
                                                Sync PPPoE
                                            </button>
                                        </form>
                                        @if(!empty($c->pppoe_username))
                                            <form method="POST" action="{{ route('admin.customers.isolate', $c->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-600 text-amber-800 hover:text-white border border-amber-200 text-[10px] font-bold transition-all cursor-pointer" title="Isolate Subscriber on MikroTik (ISOLIR)">
                                                    Isolate
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    <a href="{{ route('admin.tasks.create') }}?customer_name={{ urlencode($c->name) }}&customer_phone={{ urlencode($c->phone) }}&address={{ urlencode($c->address) }}"
                                        class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 text-[11px] font-bold transition-all">
                                        + Repair Ticket
                                    </a>
                                    <form method="POST" action="{{ route('admin.customers.destroy', $c->id) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="h-7 w-7 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white inline-flex items-center justify-center transition-all cursor-pointer" title="Delete Subscriber">
                                            <ion-icon name="trash-outline" class="text-xs"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 text-xs">No subscribers registered yet. Click "Register Subscriber" to add your first FTTH line.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $customers->links() }}</div>
        @endif
    </div>
</div>

<!-- Add Subscriber Modal -->
<div id="modal-add-customer" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
        <div class="bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold">Register FTTH Subscriber</h3>
            <button type="button" onclick="document.getElementById('modal-add-customer').classList.add('hidden')" class="text-slate-300 hover:text-white cursor-pointer"><ion-icon name="close" class="text-lg"></ion-icon></button>
        </div>
        <form method="POST" action="{{ route('admin.customers.store') }}" class="p-5 space-y-3.5 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name / Company *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Phone / WhatsApp *</label>
                    <input type="text" name="phone" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email (Optional)</label>
                    <input type="email" name="email" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Service Plan</label>
                    <input type="text" name="service_plan" placeholder="e.g. FTTH 100Mbps Dedicated" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">PPPoE Username</label>
                    <input type="text" name="pppoe_username" placeholder="cust_001@fiberops" class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">ONT Serial Number</label>
                    <input type="text" name="ont_serial_number" placeholder="ZTEGC81A22B1" class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Linked Fiber Node (ODP)</label>
                    <select name="fiber_node_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="">-- Optional ODP --</option>
                        @foreach($fiberNodes as $fn)
                            <option value="{{ $fn->id }}">[{{ strtoupper($fn->node_type) }}] {{ $fn->code }} — {{ $fn->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">ODP Port #</label>
                    <input type="number" name="odp_port_number" min="1" max="256" placeholder="1..16" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
                @if(isset($routers) && $routers->count() > 0)
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Assigned MikroTik POP Router (Optional)</label>
                    <select name="router_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="">-- Use Default Tenant Router --</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }} ({{ strtoupper($r->driver) }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Installation Address *</label>
                <textarea name="address" rows="2" required class="w-full rounded-xl border border-slate-200 px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-add-customer').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white font-bold cursor-pointer">Save Subscriber</button>
            </div>
        </form>
    </div>
</div>

<!-- 1-Click Sync from MikroTik Modal -->
<div id="modal-sync-mikrotik" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-indigo-900 text-white px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold">1-Click MikroTik PPPoE Sync</h3>
            <button type="button" onclick="document.getElementById('modal-sync-mikrotik').classList.add('hidden')" class="text-slate-300 hover:text-white cursor-pointer"><ion-icon name="close" class="text-lg"></ion-icon></button>
        </div>
        <form method="POST" action="{{ route('admin.customers.sync_mikrotik') }}" class="p-5 space-y-4 text-xs">
            @csrf
            <p class="text-slate-600 leading-relaxed">
                Pull existing PPPoE secrets straight from your MikroTik RouterOS v7 node and upsert them into Subscriber CRM automatically (mapping active &amp; ISOLIR profiles in seconds).
            </p>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Select Source POP Router</label>
                <select name="router_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                    <option value="">Default Tenant Router (from Settings)</option>
                    @if(isset($routers))
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }} ({{ strtoupper($r->driver) }})</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-sync-mikrotik').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold cursor-pointer">Sync Subscribers Now &rarr;</button>
            </div>
        </form>
    </div>
</div>

<!-- 1-Click Bulk CSV Import Modal -->
<div id="modal-import-csv" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-emerald-900 text-white px-5 py-4 flex items-center justify-between">
            <h3 class="text-sm font-bold">Bulk Import Subscribers (CSV)</h3>
            <button type="button" onclick="document.getElementById('modal-import-csv').classList.add('hidden')" class="text-slate-300 hover:text-white cursor-pointer"><ion-icon name="close" class="text-lg"></ion-icon></button>
        </div>
        <form method="POST" action="{{ route('admin.customers.import_csv') }}" enctype="multipart/form-data" class="p-5 space-y-4 text-xs">
            @csrf
            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between gap-2">
                <span class="text-emerald-900 font-semibold">Need the exact CSV column layout?</span>
                <a href="{{ route('admin.customers.import_template') }}" class="px-2.5 py-1 rounded-lg bg-white text-emerald-800 border border-emerald-300 font-bold hover:bg-emerald-100">Download Template</a>
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Upload CSV File (Max 2,000 rows, 5 MB) *</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required class="w-full rounded-xl border border-slate-200 px-3 py-2 bg-slate-50">
            </div>
            @if(isset($routers) && $routers->count() > 0)
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Assign Imported Subscribers to Router (Optional)</label>
                <select name="router_id" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                    <option value="">-- Default Router --</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">[{{ $r->code }}] {{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-import-csv').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold cursor-pointer">Upload &amp; Import CSV &rarr;</button>
            </div>
        </form>
    </div>
</div>
@endsection
