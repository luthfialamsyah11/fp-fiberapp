@extends('layouts.admin')

@section('title', 'Job History')
@section('header', 'Job History')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 print:hidden">
    <div>
        <h2 class="text-base font-bold text-textPrimary">Operations Archive & Job History</h2>
        <p class="text-xs text-textSecondary">Search through completed or cancelled historical field operations tickets.</p>
    </div>
    <!-- Export Actions -->
    <div class="flex items-center gap-2">
        <button onclick="exportToCSV()" class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-semibold text-xs transition-all flex items-center gap-1.5 shadow-sm">
            <ion-icon name="download-outline" class="text-base"></ion-icon>
            Export CSV
        </button>
        <button onclick="printJobHistory()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold text-xs transition-all flex items-center gap-1.5 shadow-md shadow-blue-500/10">
            <ion-icon name="print-outline" class="text-base"></ion-icon>
            Print PDF
        </button>
    </div>
</div>

<!-- Filters Panel -->
<div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm mb-6 print:hidden">
    <form action="{{ route('admin.history') }}" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            
            <!-- Customer Search -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Search Customer</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <ion-icon name="search-outline"></ion-icon>
                    </div>
                    <input type="text" name="search" value="{{ $search }}" 
                        class="pl-9 w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-textPrimary placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Name, phone, address...">
                </div>
            </div>

            <!-- Technician Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Technician</label>
                <select name="technician_id" 
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- All Technicians --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ $techFilter == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                <select name="status" 
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="assigned" {{ $statusFilter === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="accepted" {{ $statusFilter === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="on-going" {{ $statusFilter === 'on-going' ? 'selected' : '' }}>On-Going</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Date Range: Start -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary bg-white">
            </div>

            <!-- Date Range: End -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary bg-white">
            </div>

        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            @if($search || $techFilter || $statusFilter || $startDate || $endDate)
                <a href="{{ route('admin.history') }}" class="px-4 py-1.5 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 transition-all">
                    Reset Filter
                </a>
            @endif
            <button type="submit" class="px-5 py-1.5 bg-primary hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-all">
                Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
    <!-- Printable Header (Hidden in Screen) -->
    <div class="hidden print:block p-6 border-b border-slate-200 mb-6">
        <h1 class="text-xl font-bold text-slate-900">ISP FSM Job History Report</h1>
        <p class="text-xs text-slate-500 mt-1">Generated on {{ now()->format('d M Y H:i') }} | Filter: Status={{ $statusFilter ?: 'All' }}, Tech={{ $techFilter ? 'ID:'.$techFilter : 'All' }}</p>
    </div>

    <div class="overflow-x-auto">
        <table id="history-table" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Task ID</th>
                    <th class="p-4">Customer Name</th>
                    <th class="p-4">Technician</th>
                    <th class="p-4">Address / Site</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Scheduled Date</th>
                    <th class="p-4 text-center print:hidden">Details</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @forelse($tasks as $task)
                <tr class="hover:bg-slate-50/20 transition-colors">
                    <td class="p-4 font-mono font-semibold text-slate-500">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="p-4 font-medium text-textPrimary">{{ $task->customer_name }}</td>
                    <td class="p-4">
                        @if($task->technician)
                            <a href="{{ route('admin.history', ['technician_id' => $task->technician->id]) }}" class="font-medium text-primary hover:underline">
                                {{ $task->technician->name }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">Unassigned</span>
                        @endif
                    </td>
                    <td class="p-4 text-textSecondary max-w-xs truncate" title="{{ $task->resolved_location }}">{{ $task->resolved_location }}</td>
                    <td class="p-4">
                        @php
                            $statusColors = [
                                'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'on-going' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'completed' => 'bg-green-50 text-green-700 border-green-200',
                                'rejected' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ $task->status_label }}
                        </span>
                    </td>
                    <td class="p-4 text-textSecondary">
                        {{ $task->scheduled_at ? $task->scheduled_at->format('d M Y H:i') : $task->created_at->format('d M Y') }}
                    </td>
                    <td class="p-4 text-center print:hidden">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="inline-flex text-primary hover:underline font-semibold">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-12 text-center text-textSecondary">
                        <ion-icon name="archive-outline" class="text-4xl mb-2 text-slate-300"></ion-icon>
                        <p class="text-xs">No historical records match your search parameters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($tasks->hasPages())
    <div class="px-6 py-4 border-t border-fsmBorder bg-slate-50/50 print:hidden">
        {{ $tasks->links() }}
    </div>
    @endif
</div>

<!-- Print styles -->
<style>
    @media print {
        aside, header, .print\:hidden, nav { display: none !important; }
        body, main { background: white !important; color: black !important; padding: 0 !important; margin: 0 !important; }
        #history-table { width: 100% !important; border: 1px solid #ddd !important; }
        #history-table th { background-color: #f3f4f6 !important; border-bottom: 2px solid #ddd !important; }
        #history-table td, #history-table th { padding: 8px !important; border-bottom: 1px solid #eee !important; color: #000 !important; }
    }
</style>

@push('scripts')
<script>
    // Browser print functionality
    function printJobHistory() {
        window.print();
    }

    // Client-side CSV Exporter
    function exportToCSV() {
        var table = document.getElementById("history-table");
        var rows = table.querySelectorAll("tr");
        var csvContent = "";

        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var cols = row.querySelectorAll("td, th");
            var rowData = [];

            // Ignore the last column (Action / View button) on export
            var colCount = cols.length;
            if (i > 0 || colCount > 0) colCount = colCount - 1; 

            for (var j = 0; j < colCount; j++) {
                // Escape quotes and wrap with double quotes
                var text = cols[j].innerText.trim().replace(/"/g, '""');
                rowData.push('"' + text + '"');
            }
            csvContent += rowData.join(",") + "\r\n";
        }

        // Trigger Download
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "ISP_FSM_Job_History_Report.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endpush
@endsection
