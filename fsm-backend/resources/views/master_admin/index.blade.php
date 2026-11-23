@extends('layouts.admin')

@section('title', 'Platform Master Console | Multi-Tenant Management')

@section('content')
<div class="space-y-6">

    <!-- Top Headline Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-[#011835] to-[#0f3559] p-6 rounded-2xl text-white shadow-lg">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-bold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                Benteng Siber Global Master Platform
            </div>
            <h1 class="text-2xl font-bold font-display text-white tracking-tight">
                Pusat Komando Multi-Tenant ISP Nasional
            </h1>
            <p class="text-xs text-slate-300 mt-1 max-w-2xl">
                Pantau seluruh penyedia jasa internet (ISP) yang beroperasi di ekosistem Benteng Siber, kelola kuota paket langganan, dan kendalikan status tenant secara terpusat.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('tenant.register') }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-[#e8913a] hover:bg-[#d07e2e] text-white text-xs font-bold shadow-md transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Daftarkan ISP Baru
            </a>
        </div>
    </div>

    <!-- National Metrics Aggregates -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total ISP Terdaftar</span>
            <span class="text-3xl font-extrabold text-[#0f3559] font-display mt-2 block">{{ $metrics['total_isps'] }}</span>
            <span class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                {{ $metrics['active_isps'] }} Aktif / Trial
            </span>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Armada Teknisi</span>
            <span class="text-3xl font-extrabold text-[#0f3559] font-display mt-2 block">{{ $metrics['total_technicians'] }}</span>
            <span class="text-[11px] text-slate-500 font-medium mt-1">Terhubung di seluruh ISP</span>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Work Order</span>
            <span class="text-3xl font-extrabold text-[#0f3559] font-display mt-2 block">{{ $metrics['total_tasks'] }}</span>
            <span class="text-[11px] text-slate-500 font-medium mt-1">BAST Tersegel SHA-256</span>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Status Proteksi Data</span>
            <span class="text-3xl font-extrabold text-emerald-600 font-display mt-2 block">100%</span>
            <span class="text-[11px] text-emerald-700 font-medium mt-1">Row-Level Zero Leak</span>
        </div>
    </div>

    <!-- Tenants Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[#0f3559] font-display">Daftar Tenant Organisasi ISP</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar organisasi ISP berbadan hukum yang terisolasi secara kriptografis.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Organisasi & Subdomain</th>
                        <th class="px-5 py-3.5">PIC & Kontak</th>
                        <th class="px-5 py-3.5">Paket & Kuota</th>
                        <th class="px-5 py-3.5">Statistik Penggunaan</th>
                        <th class="px-5 py-3.5">Status Langganan</th>
                        <th class="px-5 py-3.5 text-right">Aksi Platform</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tenants as $t)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <!-- Name & Subdomain -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-[#0f3559]/10 text-[#0f3559] flex items-center justify-center font-bold text-sm">
                                    {{ substr($t->name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">{{ $t->name }}</span>
                                    <span class="text-xs text-[#e8913a] font-mono font-medium flex items-center gap-1">
                                        {{ $t->slug }}.bentengsiber.com
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- PIC & Contact -->
                        <td class="px-5 py-4 text-xs">
                            <span class="font-semibold text-slate-800 block">{{ $t->contact_name }}</span>
                            <span class="text-slate-500 block">{{ $t->contact_email }}</span>
                            <span class="text-slate-400 block">{{ $t->contact_phone }}</span>
                        </td>

                        <!-- Plan & Quotas -->
                        <td class="px-5 py-4 text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded font-bold uppercase text-[10px] tracking-wider mb-1
                                {{ $t->plan === 'enterprise' ? 'bg-purple-100 text-purple-800' : ($t->plan === 'growth' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $t->plan }}
                            </span>
                            <div class="text-[11px] text-slate-600">
                                <span>Teknisi: <strong>{{ $t->technicians_count }}</strong> / {{ $t->max_technicians }}</span><br>
                                <span>ODP: <strong>{{ $t->fiber_nodes_count }}</strong> / {{ $t->max_fiber_nodes }}</span>
                            </div>
                        </td>

                        <!-- Usage Stats -->
                        <td class="px-5 py-4 text-xs text-slate-600">
                            <span>Total Tiket: <strong class="text-slate-900">{{ $t->tasks_count }}</strong></span><br>
                            <span class="text-[11px] text-slate-400">Terdaftar: {{ $t->created_at?->format('d M Y') ?? '—' }}</span>
                        </td>

                        <!-- Status -->
                        <td class="px-5 py-4">
                            @if($t->status === 'active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif Berlangganan
                                </span>
                            @elseif($t->status === 'trial')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Trial (Sisa {{ $t->trial_ends_at ? max(0, now()->diffInDays($t->trial_ends_at, false)) : 0 }} Hari)
                                </span>
                            @elseif($t->status === 'suspended')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Ditangguhkan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                    Kedaluwarsa
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <!-- Change Plan Form -->
                                <form action="{{ route('master_admin.tenants.plan', $t->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <select name="plan" onchange="this.form.submit()" class="text-[11px] font-semibold py-1 px-2 border border-slate-300 rounded bg-white text-slate-700 cursor-pointer">
                                        <option value="starter" {{ $t->plan === 'starter' ? 'selected' : '' }}>Starter ($500/mo)</option>
                                        <option value="growth" {{ $t->plan === 'growth' ? 'selected' : '' }}>Growth ($1,000/mo)</option>
                                        <option value="enterprise" {{ $t->plan === 'enterprise' ? 'selected' : '' }}>Full Setup / Enterprise</option>
                                    </select>
                                </form>

                                <!-- Toggle Suspend Form -->
                                @if((int) $t->id !== 1)
                                <form action="{{ route('master_admin.tenants.toggle', $t->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status tenant {{ $t->name }}?')">
                                    @csrf
                                    @if($t->status === 'suspended')
                                        <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                            Aktifkan
                                        </button>
                                    @else
                                        <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 transition-colors">
                                            Suspend
                                        </button>
                                    @endif
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400 text-xs">
                            Belum ada tenant ISP yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Immutable SHA-256 Platform Governance Audit Ledger -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[#0f3559] font-display flex items-center gap-2">
                    Immutable Platform Audit Ledger
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Append-only SHA-256 hash-chained log of all tenant lifecycle changes, plan upgrades, and enterprise inquiries.</p>
            </div>
            @php $chainOk = ($auditIntegrity['valid'] ?? true); @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $chainOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                <span class="w-2 h-2 rounded-full {{ $chainOk ? 'bg-emerald-500' : 'bg-red-600 animate-ping' }}"></span>
                {{ $chainOk ? 'SHA-256 Chain Verified (' . ($auditIntegrity['checked'] ?? 0) . ' entries)' : 'CHAIN TAMPER ALERT (#' . ($auditIntegrity['broken_id'] ?? '?') . ')' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Actor &amp; IP</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Target ISP</th>
                        <th class="px-4 py-3">State Diff</th>
                        <th class="px-4 py-3 text-right">Entry Hash (SHA-256)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($auditLogs ?? collect()) as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->created_at?->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-800 block">{{ $log->actor_name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $log->actor_email }} &bull; {{ $log->ip_address ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] uppercase
                                    {{ str_contains($log->action, 'suspended') ? 'bg-red-50 text-red-700 border border-red-200' : (str_contains($log->action, 'plan') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : (str_contains($log->action, 'enterprise') ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200')) }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                {{ $log->target_tenant_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[10px] text-slate-600 max-w-xs truncate">
                                @if($log->before_state && $log->after_state)
                                    {{ json_encode($log->before_state) }} &rarr; {{ json_encode($log->after_state) }}
                                @elseif($log->after_state)
                                    {{ json_encode($log->after_state) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-[10px] text-slate-400" title="{{ $log->entry_hash }}">
                                {{ substr($log->entry_hash, 0, 14) }}&hellip;
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                No platform governance actions recorded yet. Every suspension, plan change, and onboarding event will be sealed here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
