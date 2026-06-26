@extends('layouts.admin')

@section('title', 'Reports & Analytics')
@section('header', 'Reports & Analytics')

@section('content')
<!-- Top stats banner -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Operational Tickets</span>
        <h3 class="text-2xl font-bold text-textPrimary leading-none mt-2">{{ $totalTasks }} Tasks</h3>
        <p class="text-[10px] text-textSecondary mt-1.5">All tickets registered in FSM database</p>
    </a>
    
    <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Completed Jobs</span>
        <h3 class="text-2xl font-bold text-green-600 leading-none mt-2">{{ $completedTasks }} Tasks</h3>
        <p class="text-[10px] text-textSecondary mt-1.5">Successfully finished by field agents</p>
    </a>

    <a href="{{ route('admin.history', ['status' => 'completed']) }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Completion Rate</span>
        <h3 class="text-2xl font-bold text-primary leading-none mt-2">{{ $completionRate }}%</h3>
        <p class="text-[10px] text-textSecondary mt-1.5">Ratio of completed tasks to total tasks</p>
    </a>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- Doughnut Chart: Task Statuses -->
    <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm flex flex-col items-center">
        <div class="w-full mb-4">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Task Status Distribution</h3>
        </div>
        <div class="w-full max-w-[280px]">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- Bar Chart: Tasks by Priority -->
    <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm flex flex-col items-center">
        <div class="w-full mb-4">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tickets by Priority</h3>
        </div>
        <div class="w-full max-w-[320px] flex-1 flex items-center">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>

    <!-- Full Width Bar Chart: Technician Performance -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
        <div class="mb-4">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Technician Completions Comparison</h3>
        </div>
        <div class="w-full h-72">
            <canvas id="technicianChart"></canvas>
        </div>
    </div>

</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Status Doughnut Chart ---
        var statusData = {
            labels: ['Pending', 'Assigned', 'Accepted', 'On-Going', 'Completed', 'Rejected'],
            datasets: [{
                data: [
                    {{ $statusCounts['pending'] ?? 0 }},
                    {{ $statusCounts['assigned'] ?? 0 }},
                    {{ $statusCounts['accepted'] ?? 0 }},
                    {{ $statusCounts['on-going'] ?? 0 }},
                    {{ $statusCounts['completed'] ?? 0 }},
                    {{ $statusCounts['rejected'] ?? 0 }},
                ],
                backgroundColor: [
                    '#F1F5F9', // pending (slate-100)
                    '#DBEAFE', // assigned (blue-100)
                    '#E0E7FF', // accepted (indigo-100)
                    '#FEF3C7', // ongoing (amber-100)
                    '#D1FAE5', // completed (green-100)
                    '#FEE2E2'  // rejected (red-100)
                ],
                borderColor: [
                    '#CBD5E1', // slate-300
                    '#93C5FD', // blue-300
                    '#A5B4FC', // indigo-300
                    '#FCD34D', // amber-300
                    '#6EE7B7', // green-300
                    '#FCA5A5'  // red-300
                ],
                borderWidth: 1
            }]
        };
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 10 }
                        }
                    }
                }
            }
        });

        // --- 2. Priority Horizontal Bar Chart ---
        var priorityData = {
            labels: ['Low', 'Medium', 'High'],
            datasets: [{
                label: 'Task Count',
                data: [
                    {{ $priorityCounts['low'] ?? 0 }},
                    {{ $priorityCounts['medium'] ?? 0 }},
                    {{ $priorityCounts['high'] ?? 0 }}
                ],
                backgroundColor: ['#E2E8F0', '#FEF3C7', '#FEE2E2'],
                borderColor: ['#94A3B8', '#F59E0B', '#DC2626'],
                borderWidth: 1,
                borderRadius: 6
            }]
        };
        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: priorityData,
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { ticks: { stepSize: 1 } }
                }
            }
        });

        // --- 3. Technician Completions Bar Chart ---
        var technicianData = {
            labels: [
                @foreach($technicianCompletions as $tech)
                    '{{ $tech->name }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'Jobs Completed',
                    data: [
                        @foreach($technicianCompletions as $tech)
                            {{ $tech->tasks_count }},
                        @endforeach
                    ],
                    backgroundColor: '#10B981',
                    borderRadius: 6
                },
                {
                    label: 'Total Assigned',
                    data: [
                        @foreach($technicianCompletions as $tech)
                            {{ $tech->total_assigned }},
                        @endforeach
                    ],
                    backgroundColor: '#3B82F6',
                    borderRadius: 6
                }
            ]
        };
        new Chart(document.getElementById('technicianChart'), {
            type: 'bar',
            data: technicianData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Inter', size: 11 } }
                    }
                },
                scales: {
                    y: {
                        ticks: { stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
