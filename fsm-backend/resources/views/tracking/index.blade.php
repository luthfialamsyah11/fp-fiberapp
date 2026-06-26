@extends('layouts.admin')

@section('title', 'Live Tracking')
@section('header', 'Live Tracking')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #tracking-map { height: calc(100vh - 12rem); border-radius: 12px; z-index: 1; border: 1px solid #E2E8F0; }
</style>
@endpush

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Sidebar: Active Agents list -->
    <div class="lg:col-span-1 flex flex-col space-y-4">
        
        <!-- Active Technicians card -->
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <h3 class="text-sm font-bold text-textPrimary mb-3 flex items-center gap-1.5">
                <ion-icon name="radio-outline" class="text-green-600 animate-pulse"></ion-icon>
                Active Technicians
            </h3>
            
            <p class="text-xs text-textSecondary mb-4">Click a technician to draw their historical GPS route trail.</p>

            <div class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                @forelse($technicians as $tech)
                    @php
                        $latestLoc = $tech->latestLocation;
                        $isSelected = $selectedTech && $selectedTech->id == $tech->id;
                    @endphp
                    <a href="{{ route('admin.tracking', ['technician_id' => $tech->id]) }}" 
                        class="flex items-center justify-between p-2.5 rounded-xl border transition-all block 
                        {{ $isSelected ? 'bg-blue-50 border-blue-200 text-primary' : 'bg-slate-50 border-slate-200/80 hover:bg-slate-100' }}">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                {{ substr($tech->name, 0, 1) }}
                            </div>
                            <div class="overflow-hidden">
                                <span class="text-xs font-bold text-textPrimary truncate block">{{ $tech->name }}</span>
                                @if($latestLoc)
                                    <span class="text-[9px] text-textSecondary truncate block">Lat: {{ number_format($latestLoc->latitude, 4) }}, Lng: {{ number_format($latestLoc->longitude, 4) }}</span>
                                @else
                                    <span class="text-[9px] text-red-500 truncate block">No Coordinates</span>
                                @endif
                            </div>
                        </div>
                        
                        @if($tech->is_online && $latestLoc)
                            <span class="h-2 w-2 rounded-full bg-green-500 flex-shrink-0" title="Online"></span>
                        @else
                            <span class="h-2 w-2 rounded-full bg-slate-300 flex-shrink-0" title="Offline"></span>
                        @endif
                    </a>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4">No active technicians.</p>
                @endforelse
            </div>
            
            @if($selectedTech)
                <div class="mt-4 pt-3 border-t border-fsmBorder flex justify-between items-center">
                    <span class="text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded font-semibold truncate max-w-[120px]">{{ $selectedTech->name }} Trail</span>
                    <a href="{{ route('admin.tracking') }}" class="text-[10px] text-red-500 font-semibold hover:underline">Clear Trail</a>
                </div>
            @endif
        </div>

        <!-- Coordinates History Log -->
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm flex-1 flex flex-col min-h-[220px]">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Live Log Feed</h3>
            
            <div class="flex-1 overflow-y-auto max-h-[250px] space-y-3 pr-1 text-[10px] font-mono text-textSecondary">
                @forelse($historyLogs as $log)
                    <div class="border-b border-slate-100 pb-2">
                        <div class="flex justify-between items-center mb-0.5 font-sans font-bold text-textPrimary">
                            <span>{{ $log->technician->name }}</span>
                            <span class="text-[9px] text-slate-400 font-medium font-sans">{{ $log->created_at->format('H:i:s') }}</span>
                        </div>
                        <p class="truncate">LAT: {{ $log->latitude }}</p>
                        <p class="truncate">LNG: {{ $log->longitude }}</p>
                    </div>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4 font-sans">No location feeds.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Map View -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl border border-fsmBorder p-4 shadow-sm h-full flex flex-col">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-bold text-textPrimary flex items-center gap-1">
                    <ion-icon name="map-outline" class="text-primary text-sm"></ion-icon>
                    FSM Technician Live Tracker Map
                </span>
                <div class="flex items-center gap-4 text-[10px] text-textSecondary">
                    <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-500"></span> Current Pin</div>
                    @if($selectedTech)
                        <div class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span> Route Trail</div>
                    @endif
                </div>
            </div>
            
            <div class="flex-1">
                <div id="tracking-map"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        var map = L.map('tracking-map').setView([-6.200000, 106.816666], 11);

        // Tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var blueMarkerIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var redMarkerIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var markers = [];

        // Seed current coordinates markers on the map
        @foreach($technicians as $tech)
            @if($tech->latestLocation)
                var marker = L.marker([{{ $tech->latestLocation->latitude }}, {{ $tech->latestLocation->longitude }}], {
                    icon: blueMarkerIcon
                }).addTo(map);

                marker.bindPopup(`
                    <div style="font-family: 'Inter', sans-serif; font-size: 11px; width: 180px;">
                        <h4 style="margin: 0 0 4px 0; font-weight: 700; color: #0F172A;">{{ $tech->name }}</h4>
                        <div style="color: #64748B; margin-bottom: 4px;">Coords: {{ number_format($tech->latestLocation->latitude, 4) }}, {{ number_format($tech->latestLocation->longitude, 4) }}</div>
                        <div style="margin-bottom: 4px; font-size: 10px;">
                            <a href="{{ route('admin.tracking', ['technician_id' => $tech->id]) }}" style="color: #2563EB; font-weight: 600; text-decoration: underline;">
                                Show Route Path
                            </a>
                        </div>
                        <div style="font-size: 9px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 4px; margin-top: 4px;">Updated: {{ $tech->latestLocation->created_at->diffForHumans() }}</div>
                    </div>
                `);

                markers.push(marker);
            @endif
        @endforeach

        // Draw Selected Technician Route Trail
        @if($selectedTech && count($selectedRoute) > 0)
            var routeCoordinates = [];
            
            @foreach($selectedRoute as $point)
                routeCoordinates.push([{{ $point->latitude }}, {{ $point->longitude }}]);
                
                // Add minor dots for route log history points
                L.circleMarker([{{ $point->latitude }}, {{ $point->longitude }}], {
                    radius: 4,
                    color: '#DC2626',
                    fillColor: '#FCA5A5',
                    fillOpacity: 1
                }).addTo(map).bindPopup('Route log point updated at: {{ $point->created_at->format("H:i:s") }}');
            @endforeach

            // Draw line
            var routeLine = L.polyline(routeCoordinates, {
                color: '#DC2626',
                weight: 4,
                opacity: 0.8,
                smoothFactor: 1
            }).addTo(map);

            // Fit map bound to focus on route trail
            map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});
        @elseif(count($technicians) > 0)
            // Zoom bounds to cover all online technicians
            var group = new L.featureGroup(markers);
            if (markers.length > 0) {
                map.fitBounds(group.getBounds(), {padding: [50, 50]});
            }
        @endif

        // Realtime simulated moving updates
        setInterval(function() {
            markers.forEach(function(m) {
                var pos = m.getLatLng();
                var latOffset = (Math.random() - 0.5) * 0.0006;
                var lngOffset = (Math.random() - 0.5) * 0.0006;
                m.setLatLng([pos.lat + latOffset, pos.lng + lngOffset]);
            });
        }, 12000);
    });
</script>
@endpush
@endsection
