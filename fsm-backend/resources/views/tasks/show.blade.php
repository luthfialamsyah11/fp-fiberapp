@extends('layouts.admin')

@section('title', 'Task Details')
@section('header', 'Task Details')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #task-map { height: 350px; border-radius: 12px; z-index: 1; border: 1px solid #E2E8F0; }
</style>
@endpush

@section('content')
<div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <a href="{{ route('admin.tasks.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 flex items-center gap-1.5">
            <ion-icon name="arrow-back-outline"></ion-icon> Back to Task List
        </a>
    </div>
    <div>
        <a href="{{ route('admin.tasks.edit', $task->id) }}" class="bg-indigo-50 border border-indigo-200 text-indigo-700 px-4 py-2 rounded-xl text-xs font-semibold hover:bg-indigo-100 transition-all flex items-center gap-1.5">
            <ion-icon name="create-outline"></ion-icon> Edit Task Details
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Left 2 Columns: Task Metadata, Progress, and Proofs -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Task Header & Meta -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-textPrimary leading-snug">{{ $task->title }}</h2>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[10px] font-mono font-bold text-slate-500 uppercase">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-textSecondary flex items-center gap-1">
                            <ion-icon name="calendar-outline"></ion-icon> Created {{ $task->created_at->format('d M Y H:i') }}
                        </span>
                    </div>
                </div>
                <div>
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
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold border {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $task->status_label }}
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6">
                <h4 class="text-xs font-semibold text-textSecondary uppercase tracking-wider mb-1.5">Description</h4>
                <p class="text-xs text-textPrimary whitespace-pre-line leading-relaxed">{{ $task->description ?: 'No specific description provided.' }}</p>
            </div>

            <!-- Customer & Location Specs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Customer Info</h4>
                    <div class="space-y-1.5">
                        <div class="font-bold text-textPrimary text-sm flex items-center gap-1.5">
                            <ion-icon name="person-outline" class="text-slate-400"></ion-icon>
                            {{ $task->customer_name }}
                        </div>
                        @if($task->customer_phone)
                            <div class="text-xs text-textSecondary flex items-center gap-1.5">
                                <ion-icon name="call-outline" class="text-slate-400"></ion-icon>
                                <a href="tel:{{ $task->customer_phone }}" class="hover:text-primary transition-all">{{ $task->customer_phone }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Service Address</h4>
                    <div class="space-y-1.5 text-xs text-textPrimary leading-relaxed">
                        <p class="flex items-start gap-1.5">
                            <ion-icon name="location-outline" class="text-slate-400 mt-0.5 flex-shrink-0"></ion-icon>
                            <span>{{ $task->resolved_location }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Monitoring Module -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex justify-between items-center mb-6 pb-2 border-b border-fsmBorder">
                <h3 class="text-sm font-bold text-textPrimary">Progress & Timeline</h3>
                @php
                    $latestProgress = $task->progressUpdates->first() ? $task->progressUpdates->first()->progress_percent : 0;
                @endphp
                <span class="text-xs font-semibold text-primary bg-blue-50 px-2 py-0.5 rounded">
                    Status: {{ $latestProgress }}% Completed
                </span>
            </div>

            <!-- Main Progress Bar -->
            <div class="mb-8">
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full transition-all duration-500" style="width: {{ $latestProgress }}%"></div>
                </div>
            </div>

            <!-- Progress Updates Logs -->
            @if($task->progressUpdates && $task->progressUpdates->count() > 0)
                <div class="space-y-5">
                    @foreach($task->progressUpdates as $update)
                        <div class="flex gap-4 relative">
                            <!-- timeline line -->
                            @if(!$loop->last)
                                <div class="absolute left-[13px] top-7 bottom-[-20px] w-0.5 bg-slate-200"></div>
                            @endif

                            <div class="h-7 w-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0 z-10">
                                <span class="text-[9px] font-bold text-primary">{{ $update->progress_percent }}%</span>
                            </div>

                            <div class="flex-1 pb-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-xs font-semibold text-textPrimary">{{ $update->user->name ?? 'System' }}</span>
                                    <span class="text-[10px] text-textSecondary font-medium">{{ $update->created_at->format('d M Y, H:i') }}</span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 mt-1.5">
                                    <p class="text-xs text-textSecondary leading-relaxed">{{ $update->note }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-textSecondary">
                    <ion-icon name="trending-up-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">No progress logs reported yet by the technician.</p>
                </div>
            @endif
        </div>

        <!-- Proof of Work Gallery (Before vs After Photos) -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <h3 class="text-sm font-bold text-textPrimary mb-5 pb-2 border-b border-fsmBorder">Proof of Work Verification</h3>
            
            @if($task->proofs && $task->proofs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($task->proofs as $index => $proof)
                        @php
                            $label = $index == 0 ? 'Before Photo' : 'After Photo';
                            $labelBg = $index == 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-green-50 text-green-700 border-green-200';
                        @endphp
                        <div class="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
                            <!-- Image Frame -->
                            <div class="relative h-48 bg-slate-50">
                                @if(Str::startsWith($proof->image, ['http://', 'https://']))
                                    <img src="{{ $proof->image }}" class="w-full h-full object-cover" alt="{{ $label }}">
                                @else
                                    <img src="{{ asset('storage/' . $proof->image) }}" class="w-full h-full object-cover" alt="{{ $label }}" onerror="this.src='https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500'">
                                @endif
                                
                                <span class="absolute top-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $labelBg }}">
                                    {{ $label }}
                                </span>
                            </div>
                            
                            <!-- Image Metadata -->
                            <div class="p-3 bg-slate-50 border-t border-slate-100 flex-1">
                                <p class="text-xs text-textPrimary leading-snug">{{ $proof->description ?: 'No comment attached.' }}</p>
                                <span class="text-[9px] text-textSecondary mt-2 block font-medium">Uploaded at: {{ $proof->created_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-textSecondary">
                    <ion-icon name="images-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">No completion proof photos have been uploaded by the technician yet.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- Right 1 Column: Dispatch Assignee & Map Tracker -->
    <div class="space-y-6">
        
        <!-- Technician Assignment Module -->
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-fsmBorder pb-1.5">Assigned Dispatcher</h3>
            
            @if($task->technician)
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($task->technician->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-textPrimary truncate">{{ $task->technician->name }}</p>
                        <p class="text-[10px] text-textSecondary truncate">{{ $task->technician->email }}</p>
                        <p class="text-[10px] text-textSecondary font-medium truncate mt-0.5">{{ $task->technician->phone ?: 'No phone' }}</p>
                    </div>
                </div>

                @if($task->status === 'pending')
                    <form action="{{ route('admin.tasks.assign', $task->id) }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white py-2 rounded-xl text-xs font-semibold transition-all">
                            Trigger Manual Notification Dispatch
                        </button>
                    </form>
                @endif
            @else
                <div class="text-center py-4">
                    <div class="inline-flex items-center justify-center p-2.5 rounded-full bg-slate-100 text-slate-400 mb-2">
                        <ion-icon name="people-outline" class="text-xl"></ion-icon>
                    </div>
                    <p class="text-xs font-semibold text-textSecondary mb-3">No field technician assigned</p>
                    <a href="{{ route('admin.tasks.edit', $task->id) }}" class="inline-block bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-sm transition-all">
                        Assign Technician
                    </a>
                </div>
            @endif
        </div>

        <!-- Task & Technician Coordinates Map -->
        <div class="bg-white rounded-xl border border-fsmBorder overflow-hidden shadow-sm flex flex-col">
            <div class="px-5 py-3 border-b border-fsmBorder bg-slate-50/50">
                <h3 class="text-xs font-semibold text-textPrimary uppercase tracking-wider">Location Monitoring</h3>
            </div>
            
            @if($task->latitude && $task->longitude)
                <div id="task-map"></div>
                <div class="p-3 bg-slate-50 border-t border-fsmBorder text-[10px] text-textSecondary font-medium flex justify-between">
                    <span>Task Coords: {{ number_format($task->latitude, 5) }}, {{ number_format($task->longitude, 5) }}</span>
                    @if($task->technician && $task->technician->latestLocation)
                        <span class="text-green-600 flex items-center gap-0.5">
                            <span class="h-1.5 w-1.5 bg-green-500 rounded-full animate-ping"></span>
                            Tech Online
                        </span>
                    @endif
                </div>
            @else
                <div class="p-8 text-center text-textSecondary bg-slate-50/30">
                    <ion-icon name="map-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">Location coordinates are missing.</p>
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
@if($task->latitude && $task->longitude)
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var taskLat = {{ $task->latitude }};
        var taskLng = {{ $task->longitude }};
        
        // Initialize Map
        var map = L.map('task-map').setView([taskLat, taskLng], 13);

        // OSM Tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Icons
        var taskIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Add Task Pin
        L.marker([taskLat, taskLng], {icon: taskIcon}).addTo(map)
            .bindPopup(`<b>Work Order Target:</b><br>{{ $task->customer_name }}<br><small>{{ $task->address }}</small>`)
            .openPopup();

        // Add Technician Pin if coordinates are present
        @if($task->technician && $task->technician->latestLocation)
            var techLat = {{ $task->technician->latestLocation->latitude }};
            var techLng = {{ $task->technician->latestLocation->longitude }};
            
            var techIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            var techMarker = L.marker([techLat, techLng], {icon: techIcon}).addTo(map)
                .bindPopup('<b>Technician Dispatch:</b><br>{{ $task->technician->name }}<br><small>Updated: {{ $task->technician->latestLocation->created_at->diffForHumans() }}</small>');

            // Draw link line between tech and task site if active
            @if(in_array($task->status, ['assigned', 'accepted', 'on-going']))
                var routeLine = L.polyline([
                    [techLat, techLng],
                    [taskLat, taskLng]
                ], {color: '#2563EB', dashArray: '5, 8', weight: 3}).addTo(map);
                
                map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});
            @endif
        @endif
    });
</script>
@endif
@endpush
@endsection
