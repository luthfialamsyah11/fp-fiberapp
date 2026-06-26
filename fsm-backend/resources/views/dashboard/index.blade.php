@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Monitoring Control Center')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #dashboard-map { height: 480px; border-radius: 12px; z-index: 1; border: 1px solid #E2E8F0; }
    .sparkline-svg { width: 100px; height: 30px; }
</style>
@endpush

@section('content')
<!-- KPI Cards Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-6">
    
    <!-- KPI 1: Total Tasks -->
    <a href="{{ route('admin.tasks.index') }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Total Tasks</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-slate-50 to-slate-200 text-slate-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="folder-open-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['total_tasks'] }}</h3>
                <span class="text-[10px] font-semibold text-green-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-up-outline"></ion-icon> +12.3%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,25 Q15,10 30,22 T60,5 T90,12 T100,8" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,25 Q15,10 30,22 T60,5 T90,12 T100,8 L100,30 L0,30 Z" fill="rgba(37, 99, 235, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 2: Assigned Tasks -->
    <a href="{{ route('admin.tasks.index', ['status' => 'assigned']) }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Assigned</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="send-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['assigned'] }}</h3>
                <span class="text-[10px] font-semibold text-red-500 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-down-outline"></ion-icon> -2.1%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,5 Q20,25 40,15 T80,25 T100,20" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,5 Q20,25 40,15 T80,25 T100,20 L100,30 L0,30 Z" fill="rgba(220, 38, 38, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 3: Accepted Tasks -->
    <a href="{{ route('admin.tasks.index', ['status' => 'accepted']) }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Accepted</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="mail-open-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['accepted'] }}</h3>
                <span class="text-[10px] font-semibold text-green-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-up-outline"></ion-icon> +8.4%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,25 Q15,18 30,12 T60,18 T90,5 T100,10" fill="none" stroke="#4F46E5" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,25 Q15,18 30,12 T60,18 T90,5 T100,10 L100,30 L0,30 Z" fill="rgba(79, 70, 229, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 4: On Going Tasks -->
    <a href="{{ route('admin.tasks.index', ['status' => 'on-going']) }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">On Going</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 text-amber-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="construct-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['on_going'] }}</h3>
                <span class="text-[10px] font-semibold text-green-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-up-outline"></ion-icon> +15.2%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,28 L20,20 L40,24 L60,12 L80,15 L100,2" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,28 L20,20 L40,24 L60,12 L80,15 L100,2 L100,30 L0,30 Z" fill="rgba(217, 119, 6, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 5: Completed Tasks -->
    <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Completed</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-green-50 to-green-100 text-green-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="checkmark-done-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['completed'] }}</h3>
                <span class="text-[10px] font-semibold text-green-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-up-outline"></ion-icon> +22.8%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,20 Q20,10 40,25 T80,5 T100,2" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,20 Q20,10 40,25 T80,5 T100,2 L100,30 L0,30 Z" fill="rgba(22, 163, 74, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 6: Active Technicians -->
    <a href="{{ route('admin.technicians.index') }}" class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-blue-300 transition-all duration-300 flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider">Active Techs</span>
            <div class="p-1.5 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600 group-hover:scale-110 group-hover:shadow-sm transition-all duration-300">
                <ion-icon name="people-outline" class="text-base"></ion-icon>
            </div>
        </div>
        <div class="mt-4 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['active_technicians'] }}</h3>
                <span class="text-[10px] font-semibold text-green-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="trending-up-outline"></ion-icon> +4.7%
                </span>
            </div>
            <!-- Sparkline Chart (SVG) -->
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,15 L25,12 L50,15 L75,10 L100,5" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,15 L25,12 L50,15 L75,10 L100,5 L100,30 L0,30 Z" fill="rgba(124, 58, 237, 0.08)"/>
            </svg>
        </div>
    </a>
</div>

<!-- Grid Layout: Interactive Map (Section 1) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Map (Section 1: takes 2 columns on large screen) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm flex flex-col hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow duration-300">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <ion-icon name="map-outline" class="text-xl text-primary"></ion-icon>
                <h2 class="text-base font-bold text-textPrimary">Live Technician Tracking</h2>
            </div>
            <div class="flex items-center gap-2 text-xs font-medium text-textSecondary">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                <span>Active Location Markers (Leaflet Map)</span>
            </div>
        </div>
        <div class="flex-1 min-h-[300px] sm:min-h-[400px]">
            <div id="dashboard-map"></div>
        </div>
    </div>

    <!-- Recent Activities Timeline (Section 3: takes 1 column) -->
    <div class="bg-white rounded-2xl border border-slate-200/60 p-4 sm:p-5 shadow-sm flex flex-col hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow duration-300">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <ion-icon name="time-outline" class="text-xl text-primary"></ion-icon>
                <h2 class="text-base font-bold text-textPrimary">Recent Activities Timeline</h2>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto max-h-[440px] pr-2 space-y-5">
            @php
                // Fetch progress updates for timeline representation
                $timelineLogs = \App\Models\ProgressUpdate::with(['task', 'user'])->orderBy('created_at', 'desc')->take(6)->get();
            @endphp
            
            @forelse($timelineLogs as $log)
                <div class="flex gap-3 relative">
                    <!-- Timeline connector line -->
                    @if(!$loop->last)
                        <div class="absolute left-3.5 top-6 bottom-[-20px] w-0.5 bg-slate-200"></div>
                    @endif
                    
                    <div class="h-7 w-7 rounded-full flex items-center justify-center flex-shrink-0 z-10 
                        @if($log->progress_percent == 100) bg-green-100 text-green-700
                        @elseif($log->progress_percent >= 50) bg-amber-100 text-amber-700
                        @else bg-blue-100 text-blue-700 @endif">
                        <ion-icon name="
                            @if($log->progress_percent == 100) checkmark-circle
                            @elseif($log->progress_percent >= 50) construct
                            @else radio-button-on @endif
                        " class="text-sm"></ion-icon>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-semibold text-textPrimary block">{{ $log->user->name ?? 'System' }}</span>
                            <span class="text-[10px] text-textSecondary">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-textSecondary mt-0.5">
                            {{ $log->note }}
                        </p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <a href="{{ $log->task ? route('admin.tasks.show', $log->task->id) : '#' }}" class="text-[9px] font-medium px-2 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 hover:text-primary text-slate-700 border border-slate-200 truncate max-w-[150px] transition-all" title="{{ $log->task->title ?? 'Tugas' }}">
                                {{ $log->task->title ?? 'N/A' }}
                            </a>
                            <span class="text-[9px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                                {{ $log->progress_percent }}%
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="h-full flex flex-col items-center justify-center text-center p-8 text-textSecondary">
                    <ion-icon name="chatbox-ellipses-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">Belum ada aktivitas terekam.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Active Task Monitoring Table (Section 2) -->
<div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden mb-6 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-shadow duration-300">
    <div class="px-4 sm:px-6 py-4 border-b border-fsmBorder flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex items-center gap-2">
            <ion-icon name="list-circle-outline" class="text-xl text-primary"></ion-icon>
            <h2 class="text-base font-bold text-textPrimary">Active Task Monitoring</h2>
        </div>
        <a href="{{ route('admin.tasks.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 transition-all flex items-center gap-1">
            Lihat Semua Tasks <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Task ID</th>
                    <th class="p-4">Customer Name</th>
                    <th class="p-4">Technician</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Priority</th>
                    <th class="p-4">Location</th>
                    <th class="p-4">Last Update</th>
                    <th class="p-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @forelse($recentTasks as $task)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="p-4 font-mono font-semibold text-slate-500">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="p-4">
                        <div class="font-medium text-textPrimary">{{ $task->customer_name }}</div>
                        <div class="text-[10px] text-textSecondary">{{ $task->customer_phone ?? '-' }}</div>
                    </td>
                    <td class="p-4">
                        @if($task->technician)
                            <a href="{{ route('admin.tracking', ['technician_id' => $task->technician->id]) }}" class="flex items-center gap-2 hover:text-primary transition-all hover:underline">
                                <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] text-slate-700">
                                    {{ substr($task->technician->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-textPrimary hover:text-primary">{{ $task->technician->name }}</span>
                            </a>
                        @else
                            <span class="text-slate-400 italic bg-slate-50 px-2 py-0.5 rounded border border-slate-100">Belum ditugaskan</span>
                        @endif
                    </td>
                    <td class="p-4">
                        @php
                            $badgeColors = [
                                'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'on-going' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'completed' => 'bg-green-50 text-green-700 border-green-200',
                                'rejected' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $colorClass = $badgeColors[$task->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $colorClass }}">
                            {{ $task->status_label }}
                        </span>
                    </td>
                    <td class="p-4">
                        @php
                            $priorityColors = [
                                'low' => 'text-slate-500 bg-slate-100',
                                'medium' => 'text-amber-700 bg-amber-50',
                                'high' => 'text-red-700 bg-red-50',
                            ];
                            $pColor = $priorityColors[$task->priority] ?? 'text-slate-500 bg-slate-100';
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $pColor }} capitalize">
                            {{ $task->priority }}
                        </span>
                    </td>
                    <td class="p-4 text-textSecondary truncate max-w-[200px]" title="{{ $task->resolved_location }}">
                        {{ $task->resolved_location }}
                    </td>
                    <td class="p-4 text-textSecondary">{{ $task->updated_at->format('d M Y H:i') }}</td>
                    <td class="p-4 text-center">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-primary transition-all border border-fsmBorder" title="Lihat detail">
                            <ion-icon name="eye-outline" class="text-sm"></ion-icon>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-textSecondary">
                        <ion-icon name="file-tray-outline" class="text-4xl mb-2 text-slate-300"></ion-icon>
                        <p class="text-xs">Belum ada data tugas.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        var map = L.map('dashboard-map').setView([-6.200000, 106.816666], 11);

        // OSM Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Marker Colors mapped by Status
        // Standard Leaflet Icon
        var createMarkerIcon = function(color) {
            return L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-' + color + '.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
        };

        var icons = {
            active: createMarkerIcon('blue'),
            inactive: createMarkerIcon('grey'),
            ongoing: createMarkerIcon('gold')
        };

        var markers = [];

        // Loop through locations passed from controller
        @foreach($techLocations as $loc)
            @if($loc->latitude && $loc->longitude)
                @php
                    // Determine status of technician for marker color
                    // If technician is offline, set to grey (inactive).
                    // If online and has on-going task, set to gold, else blue
                    $isOnline = $loc->technician->is_online;
                    $hasOngoing = $loc->technician->tasks->where('status', 'on-going')->count() > 0;
                    $markerColor = !$isOnline ? 'inactive' : ($hasOngoing ? 'ongoing' : 'active');
                    $ongoingTask = $loc->technician->tasks->whereIn('status', ['accepted', 'on-going'])->first();
                    $taskTitle = $ongoingTask ? $ongoingTask->title : 'Idle';
                @endphp
                
                var marker = L.marker([{{ $loc->latitude }}, {{ $loc->longitude }}], {
                    icon: icons['{{ $markerColor }}']
                }).addTo(map);

                marker.bindPopup(`
                    <div style="font-family: 'Inter', sans-serif; font-size: 12px; width: 200px;">
                        <h4 style="margin: 0 0 4px 0; font-weight: 700; color: #0F172A;">{{ $loc->technician->name }}</h4>
                        <div style="color: #64748B; margin-bottom: 6px;">Email: {{ $loc->technician->email }}</div>
                        <div style="margin-bottom: 4px;">
                            <span style="font-weight: 600; color: #0F172A;">Task:</span> 
                            <span style="color: #2563EB;">{{ $taskTitle }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 10px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 4px; margin-top: 4px;">
                            <span>Lat: {{ number_format($loc->latitude, 6) }}</span>
                            <span>Lng: {{ number_format($loc->longitude, 6) }}</span>
                        </div>
                    </div>
                `);

                markers.push(marker);
            @endif
        @endforeach

        // Auto zoom and fit bounds of markers
        if (markers.length > 0) {
            var group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds(), {padding: [50, 50]});
        }

        // Simulating Realtime updates in UI for coordinates
        setInterval(function() {
            // Randomly offset markers slightly to simulate motion
            markers.forEach(function(m) {
                var currentLatLng = m.getLatLng();
                var latOffset = (Math.random() - 0.5) * 0.0008;
                var lngOffset = (Math.random() - 0.5) * 0.0008;
                m.setLatLng([currentLatLng.lat + latOffset, currentLatLng.lng + lngOffset]);
            });
        }, 15000); // every 15 seconds
    });
</script>
@endpush
@endsection
