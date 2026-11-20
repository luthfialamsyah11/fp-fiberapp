@extends('layouts.admin')

@section('title', 'Field Technician Live Tracking')
@section('header', 'Field Technician Live Tracking')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #tracking-map {
        height: calc(100vh - 12.5rem);
        min-height: 520px;
        border-radius: 12px;
        z-index: 1;
        border: 1px solid #E2E8F0;
    }
    .custom-tech-popup .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        padding: 0;
        overflow: hidden;
    }
    .custom-tech-popup .leaflet-popup-content {
        margin: 0;
        line-height: 1.4;
    }
    .pulse-online {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: onlinePulse 2s infinite;
    }
    @keyframes onlinePulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .feed-item-flash {
        animation: flashBg 1.5s ease-out;
    }
    @keyframes flashBg {
        0% { background-color: rgba(37, 99, 235, 0.15); }
        100% { background-color: transparent; }
    }
</style>
@endpush

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Sidebar: Active Agents list & Live Log Feed -->
    <div class="lg:col-span-1 flex flex-col space-y-4">
        
        <!-- Active Technicians Card -->
        <div class="bg-white rounded-xl border border-fsmBorder p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-textPrimary flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 pulse-online"></span>
                    Technician Fleet
                </h3>
                <span id="badge-tech-count" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-100">
                    {{ $technicians->filter(fn($t) => $t->isEffectivelyOnline())->count() }} / {{ $technicians->count() }} Active
                </span>
            </div>
            
            <p class="text-xs text-textSecondary mb-3">
                Click any technician to view complete GPS breadcrumb route history.
            </p>

            <!-- Technicians List Container -->
            <div id="tech-list-container" class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                @forelse($technicians as $tech)
                    @php
                        $latestLoc = $tech->latestLocation;
                        $isSelected = $selectedTech && $selectedTech->id == $tech->id;
                        $activeTask = $tech->tasks->first();
                    @endphp
                    <a href="{{ route('admin.tracking', ['technician_id' => $tech->id]) }}" 
                        id="tech-item-{{ $tech->id }}"
                        class="flex items-center justify-between p-2.5 rounded-xl border transition-all block 
                        {{ $isSelected ? 'bg-blue-50 border-blue-300 text-blue-700 shadow-sm' : 'bg-slate-50/80 border-slate-200/80 hover:bg-slate-100' }}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-8 w-8 rounded-lg {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700' }} flex items-center justify-center font-bold text-xs flex-shrink-0">
                                {{ substr($tech->name, 0, 1) }}
                            </div>
                            <div class="overflow-hidden min-w-0">
                                <span class="text-xs font-bold text-textPrimary truncate block">{{ $tech->name }}</span>
                                @if($activeTask)
                                    <span class="text-[10px] text-emerald-600 font-medium truncate block flex items-center gap-1">
                                        <ion-icon name="construct-outline"></ion-icon>
                                        {{ $activeTask->title }}
                                    </span>
                                @elseif($latestLoc)
                                    <span class="text-[9px] text-textSecondary truncate block">
                                        {{ number_format($latestLoc->latitude, 4) }}, {{ number_format($latestLoc->longitude, 4) }}
                                    </span>
                                @else
                                    <span class="text-[9px] text-slate-400 truncate block">No coordinates yet</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if($tech->isEffectivelyOnline())
                                <span class="h-2 w-2 rounded-full bg-emerald-500" title="Active Online"></span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-slate-300" title="Offline"></span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4">No technicians registered.</p>
                @endforelse
            </div>
            
            <div id="route-filter-bar" class="mt-3 pt-3 border-t border-fsmBorder flex justify-between items-center {{ $selectedTech ? '' : 'hidden' }}">
                <span id="route-filter-name" class="text-[10px] text-blue-700 bg-blue-100/70 border border-blue-200 px-2 py-0.5 rounded font-semibold truncate max-w-[150px]">
                    Route: {{ $selectedTech ? $selectedTech->name : '' }}
                </span>
                <button type="button" id="btn-clear-route-filter" class="text-[10px] text-red-500 font-semibold hover:underline">
                    Clear Route Filter
                </button>
            </div>
        </div>

        <!-- Coordinates Real-time Log Feed -->
        <div class="bg-white rounded-xl border border-fsmBorder p-4 sm:p-5 shadow-sm flex-1 flex flex-col min-h-[260px]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <ion-icon name="pulse-outline" class="text-blue-500"></ion-icon>
                    Live Telemetry Log
                </h3>
                <span id="log-status-dot" class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Telemetry Streaming"></span>
            </div>
            
            <div id="live-logs-container" class="flex-1 overflow-y-auto max-h-[260px] space-y-2 pr-1 text-[10px] font-mono text-textSecondary">
                @forelse($historyLogs as $log)
                    <div class="border-b border-slate-100 pb-2 transition-all">
                        <div class="flex justify-between items-center mb-0.5 font-sans font-bold text-textPrimary">
                            <span class="truncate">{{ $log->technician ? $log->technician->name : 'Technician' }}</span>
                            <span class="text-[9px] text-slate-400 font-medium font-sans flex-shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
                        </div>
                        <p class="truncate text-slate-500">LAT: {{ $log->latitude }}</p>
                        <p class="truncate text-slate-500">LNG: {{ $log->longitude }}</p>
                    </div>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4 font-sans">No telemetry records yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Map View -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl border border-fsmBorder p-4 shadow-sm h-full flex flex-col">
            <!-- Map Header Toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                        <ion-icon name="navigate" class="text-base"></ion-icon>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-textPrimary leading-none">
                            Live GPS Dispatcher Tracking
                        </h2>
                        <span class="text-[10px] text-textSecondary flex items-center gap-1.5 mt-1">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            Real-time telemetry link to field technician mobile PWA
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <label class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 cursor-pointer select-none">
                        <input type="checkbox" id="toggle-odp-layer" checked class="w-3.5 h-3.5 rounded text-blue-600 border-slate-300">
                        <ion-icon name="git-network-outline" class="text-cyan-600"></ion-icon>
                        <span>ODPs ({{ isset($fiberNodes) ? $fiberNodes->count() : 0 }})</span>
                    </label>
                    <span id="last-sync-time" class="text-[11px] font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                        Sync: {{ now()->format('H:i:s') }}
                    </span>
                    <button type="button" id="btn-recenter" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 transition-all flex items-center gap-1">
                        <ion-icon name="locate-outline"></ion-icon>
                        Recenter
                    </button>
                    <button type="button" id="btn-manual-refresh" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg shadow-sm transition-all flex items-center gap-1">
                        <ion-icon id="refresh-icon" name="refresh-outline" class="text-sm"></ion-icon>
                        Refresh
                    </button>
                </div>
            </div>
            
            <!-- Map Element -->
            <div class="flex-1 relative">
                <div id="tracking-map"></div>
            </div>
            
            <!-- Map Legend Footer -->
            <div class="mt-3 pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-[11px] text-slate-500">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-blue-500 border-2 border-white shadow-sm inline-block"></span>
                        Current Technician Location
                    </span>
                    @if($selectedTech)
                        <span class="flex items-center gap-1.5 text-red-600 font-semibold">
                            <span class="w-3 h-3 rounded-full bg-red-500 border-2 border-white shadow-sm inline-block"></span>
                            GPS Route Breadcrumbs ({{ $selectedTech->name }})
                        </span>
                    @endif
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        On-Going Work Order Status
                    </span>
                </div>
                <div class="text-[10px] text-slate-400">
                    Engine: Leaflet 1.9.4 & OpenStreetMap • Real GPS Telemetry
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedTechId = @json($selectedTech ? $selectedTech->id : null);
        let lastLogId = @json($historyLogs->first() ? $historyLogs->first()->id : 0);
        const liveDataUrl = @json(route('admin.tracking.live'));
        const mapStyle = @json($mapStyle ?? 'openstreetmap');
        const pollIntervalSec = @json($pollInterval ?? 15);

        // Initialize Map
        const map = L.map('tracking-map', {
            zoomControl: true,
            scrollWheelZoom: true
        }).setView([-6.2088, 106.8456], 12);

        // Dynamic Tile Layer according to Platform Settings
        let tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        let tileAttr = '&copy; OpenStreetMap contributors';
        if (mapStyle === 'carto') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            tileAttr = '&copy; OpenStreetMap &copy; CARTO';
        } else if (mapStyle === 'mapbox') {
            tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
            tileAttr = 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics';
        }

        L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: tileAttr
        }).addTo(map);

        // Marker Icons
        const blueIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const greenIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const redDestinationIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Dictionary to track active Leaflet markers & GPS accuracy circles by technician ID
        const techMarkers = {};
        const techCircles = {};
        const greyIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        let routePolyline = null;
        let destinationMarker = null;
        let destinationLine = null;
        let routeMarkersGroup = L.layerGroup().addTo(map);

        // Fiber GIS Assets Layer (ODP / ODC / OLT)
        const initialFiberNodes = @json($fiberNodes ?? []);
        const fiberNodesLayer = L.layerGroup().addTo(map);

        function renderFiberNodesLayer() {
            fiberNodesLayer.clearLayers();
            if (!initialFiberNodes || initialFiberNodes.length === 0) return;
            initialFiberNodes.forEach(node => {
                if (!node.latitude || !node.longitude) return;
                const used = Number(node.used_ports || 0);
                const total = Number(node.total_ports || 8);
                const isFull = used >= total;
                const badgeColor = isFull ? '#EF4444' : '#06B6D4';

                const odpIcon = L.divIcon({
                    className: 'leaflet-odp-pin',
                    html: `<div style="position:relative;display:flex;align-items:center;justify-content:center;width:24px;height:24px;background:#0F172A;border:2px solid ${badgeColor};border-radius:6px;box-shadow:0 2px 5px rgba(0,0,0,0.35);">
                             <span style="font-size:8px;font-weight:800;color:#38BDF8;letter-spacing:-0.5px;">ODP</span>
                             <span style="position:absolute;top:-5px;right:-7px;background:${badgeColor};color:#FFFFFF;font-size:7px;font-weight:700;padding:1px 2px;border-radius:3px;">${used}/${total}</span>
                           </div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                const m = L.marker([node.latitude, node.longitude], { icon: odpIcon }).addTo(fiberNodesLayer);
                m.bindPopup(`
                    <div style="font-family:Inter,system-ui,sans-serif;font-size:12px;padding:4px;min-width:160px;">
                        <b style="color:#0F172A;display:block;">[${escapeHtml(node.code)}] ${escapeHtml(node.name)}</b>
                        <span style="color:#64748B;font-size:10px;">${escapeHtml(node.node_type ? node.node_type.toUpperCase() : 'ODP')} Node</span>
                        <div style="background:#F1F5F9;padding:4px 6px;border-radius:4px;margin-top:4px;font-size:10px;">
                            Port Usage: <b>${used}/${total} ports (${total - used} available)</b>
                        </div>
                        ${node.address ? `<div style="font-size:9px;color:#94A3B8;margin-top:4px;">${escapeHtml(node.address)}</div>` : ''}
                    </div>
                `);
            });
        }
        renderFiberNodesLayer();

        const toggleOdpEl = document.getElementById('toggle-odp-layer');
        if (toggleOdpEl) {
            toggleOdpEl.addEventListener('change', function(e) {
                if (e.target.checked) {
                    map.addLayer(fiberNodesLayer);
                } else {
                    map.removeLayer(fiberNodesLayer);
                }
            });
        }

        function buildPopupContent(tech) {
            let activeTaskHtml = '';
            if (tech.active_task) {
                activeTaskHtml = `
                    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 6px 8px; margin: 6px 0;">
                        <div style="color: #1E40AF; font-weight: 700; font-size: 11px;">🛠️ ${escapeHtml(tech.active_task.title)}</div>
                        <div style="color: #3B82F6; font-size: 10px;">Customer: ${escapeHtml(tech.active_task.customer_name || '-')}</div>
                        <div style="color: #64748B; font-size: 9px;">Status: <b>${escapeHtml(tech.active_task.status)}</b></div>
                    </div>
                `;
            } else {
                activeTaskHtml = `
                    <div style="color: #64748B; font-size: 10px; margin: 4px 0;">
                        <i>No active task</i>
                    </div>
                `;
            }

            const accuracyBadge = tech.accuracy_m
                ? `<div style="font-size: 9px; margin-top: 3px; color: ${tech.low_accuracy ? '#B45309' : '#64748B'}; font-weight: ${tech.low_accuracy ? '700' : '500'};">
                    Accuracy: &plusmn;${Math.round(tech.accuracy_m)}m ${tech.low_accuracy ? '(Low Accuracy)' : ''}
                    ${tech.is_position_stale ? ' &bull; <span style="color:#DC2626;font-weight:700;">Stale GPS</span>' : ''}
                   </div>`
                : '';

            return `
                <div style="font-family: 'Inter', sans-serif; padding: 12px; width: 230px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="margin: 0; font-weight: 700; font-size: 13px; color: #0F172A;">${escapeHtml(tech.name)}</h4>
                        <span style="font-size: 9px; padding: 2px 6px; border-radius: 9999px; font-weight: 600; background: ${tech.is_online ? '#DCFCE7; color: #166534;' : '#F1F5F9; color: #64748B;'}">
                            ${tech.is_online ? '● Online' : 'Offline'}
                        </span>
                    </div>
                    <div style="font-size: 10px; color: #64748B; margin-bottom: 2px;">
                        📞 ${escapeHtml(tech.phone || '-')}
                    </div>
                    ${activeTaskHtml}
                    <div style="font-size: 9px; font-family: monospace; color: #64748B; background: #F8FAFC; padding: 4px 6px; border-radius: 4px; border: 1px solid #E2E8F0; margin-top: 4px;">
                        Coordinates: ${tech.latitude ? tech.latitude.toFixed(5) : '-'}, ${tech.longitude ? tech.longitude.toFixed(5) : '-'}
                        ${accuracyBadge}
                    </div>
                    <div style="margin-top: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 9px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 6px;">
                        <span>Fix: ${tech.last_seen || '-'}</span>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${tech.latitude},${tech.longitude}" target="_blank" rel="noopener noreferrer" style="color: #2563EB; font-weight: 700; text-decoration: none;" title="Open Google Maps">Maps</a>
                            <span style="color: #CBD5E1;">&bull;</span>
                            <a href="https://waze.com/ul?ll=${tech.latitude},${tech.longitude}&navigate=yes" target="_blank" rel="noopener noreferrer" style="color: #0284C7; font-weight: 700; text-decoration: none;" title="Open Waze">Waze</a>
                        </div>
                    </div>
                </div>
            `;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function updateSidebarRosterItem(tech) {
            const item = document.getElementById(`tech-item-${tech.id}`);
            if (!item) return;
            const statusDot = item.querySelector('span[title="Active Online"], span[title="Offline"]');
            if (statusDot) {
                statusDot.className = tech.is_online ? 'h-2 w-2 rounded-full bg-emerald-500' : 'h-2 w-2 rounded-full bg-slate-300';
                statusDot.setAttribute('title', tech.is_online ? 'Active Online' : 'Offline');
            }
            const infoContainer = item.querySelector('.overflow-hidden.min-w-0');
            if (infoContainer) {
                let subLineHtml = '';
                if (tech.active_task) {
                    subLineHtml = `<span class="text-[10px] text-emerald-600 font-medium truncate block flex items-center gap-1"><ion-icon name="construct-outline"></ion-icon>${escapeHtml(tech.active_task.title)}</span>`;
                } else if (tech.latitude !== null && tech.longitude !== null) {
                    subLineHtml = `<span class="text-[9px] text-textSecondary truncate block">${tech.latitude.toFixed(4)}, ${tech.longitude.toFixed(4)}</span>`;
                } else {
                    subLineHtml = `<span class="text-[9px] text-slate-400 truncate block">No coordinates yet</span>`;
                }
                infoContainer.innerHTML = `<span class="text-xs font-bold text-textPrimary truncate block">${escapeHtml(tech.name)}</span>${subLineHtml}`;
            }
        }

        function clearRouteVisuals() {
            routeMarkersGroup.clearLayers();
            if (routePolyline) {
                map.removeLayer(routePolyline);
                routePolyline = null;
            }
            if (destinationMarker) {
                map.removeLayer(destinationMarker);
                destinationMarker = null;
            }
            if (destinationLine) {
                map.removeLayer(destinationLine);
                destinationLine = null;
            }
        }

        // Live Poll Function to fetch real data from backend
        let isFetching = false;
        async function fetchLiveTrackingData(shouldFitBounds = false) {
            if (isFetching) return;
            isFetching = true;

            const refreshIcon = document.getElementById('refresh-icon');
            if (refreshIcon) refreshIcon.classList.add('animate-spin');

            try {
                const params = new URLSearchParams();
                if (selectedTechId) params.set('technician_id', String(selectedTechId));
                if (lastLogId > 0) params.set('since_id', String(lastLogId));
                const queryString = params.toString() ? `?${params.toString()}` : '';

                const response = await fetch(`${liveDataUrl}${queryString}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('Live telemetry fetch failed');
                const result = await response.json();

                // 1. Update Sync Time and Counts
                const lastSyncEl = document.getElementById('last-sync-time');
                if (lastSyncEl) lastSyncEl.textContent = `Sync: ${result.server_time}`;

                const badgeCountEl = document.getElementById('badge-tech-count');
                if (badgeCountEl) {
                    badgeCountEl.textContent = `${result.online_count} / ${result.total_count} Active`;
                }

                // 2. Update Map Markers & Sidebar Roster with Real GPS coordinates
                const activeCoordinates = [];
                const incomingIds = new Set();
                let selectedTechObj = null;

                result.technicians.forEach(tech => {
                    incomingIds.add(tech.id);
                    updateSidebarRosterItem(tech);
                    if (selectedTechId && Number(selectedTechId) === Number(tech.id)) {
                        selectedTechObj = tech;
                    }

                    if (tech.latitude !== null && tech.longitude !== null) {
                        activeCoordinates.push([tech.latitude, tech.longitude]);
                        const iconToUse = (!tech.is_online || tech.is_position_stale)
                            ? greyIcon
                            : (tech.active_task ? greenIcon : blueIcon);

                        if (techMarkers[tech.id]) {
                            const existingMarker = techMarkers[tech.id];
                            const currentPos = existingMarker.getLatLng();
                            if (currentPos.lat !== tech.latitude || currentPos.lng !== tech.longitude) {
                                existingMarker.setLatLng([tech.latitude, tech.longitude]);
                            }
                            existingMarker.setIcon(iconToUse);
                            existingMarker.setPopupContent(buildPopupContent(tech));
                        } else {
                            const marker = L.marker([tech.latitude, tech.longitude], {
                                icon: iconToUse
                            }).addTo(map);
                            marker.bindPopup(buildPopupContent(tech), { className: 'custom-tech-popup' });
                            techMarkers[tech.id] = marker;
                        }

                        // GPS Accuracy Radius Circle
                        if (tech.accuracy_m && tech.accuracy_m > 10) {
                            const circleColor = tech.low_accuracy ? '#D97706' : (tech.is_online ? '#2563EB' : '#94A3B8');
                            if (techCircles[tech.id]) {
                                techCircles[tech.id].setLatLng([tech.latitude, tech.longitude]);
                                techCircles[tech.id].setRadius(Math.min(tech.accuracy_m, 1000));
                                techCircles[tech.id].setStyle({ color: circleColor, fillColor: circleColor });
                            } else {
                                techCircles[tech.id] = L.circle([tech.latitude, tech.longitude], {
                                    radius: Math.min(tech.accuracy_m, 1000),
                                    color: circleColor,
                                    fillColor: circleColor,
                                    fillOpacity: tech.low_accuracy ? 0.14 : 0.08,
                                    weight: 1,
                                    dashArray: tech.low_accuracy ? '4, 4' : null
                                }).addTo(map);
                            }
                        } else if (techCircles[tech.id]) {
                            map.removeLayer(techCircles[tech.id]);
                            delete techCircles[tech.id];
                        }
                    }
                });

                // Prune stale markers & circles for removed technicians
                Object.keys(techMarkers).forEach(id => {
                    if (!incomingIds.has(Number(id))) {
                        map.removeLayer(techMarkers[id]);
                        delete techMarkers[id];
                    }
                });
                Object.keys(techCircles).forEach(id => {
                    if (!incomingIds.has(Number(id))) {
                        map.removeLayer(techCircles[id]);
                        delete techCircles[id];
                    }
                });

                // 3. Update Route Trail & Active Work Order Destination Pin if a technician is selected
                if (!selectedTechId) {
                    clearRouteVisuals();
                } else {
                    if (result.selected_route && result.selected_route.length > 0) {
                        routeMarkersGroup.clearLayers();
                        const latLngs = [];

                        result.selected_route.forEach((pt, idx) => {
                            latLngs.push([pt.latitude, pt.longitude]);

                            L.circleMarker([pt.latitude, pt.longitude], {
                                radius: idx === result.selected_route.length - 1 ? 6 : 4,
                                color: '#DC2626',
                                fillColor: '#FCA5A5',
                                fillOpacity: 0.9,
                                weight: 2
                            }).addTo(routeMarkersGroup).bindPopup(`GPS point at: ${pt.time}`);
                        });

                        if (routePolyline) {
                            routePolyline.setLatLngs(latLngs);
                        } else {
                            routePolyline = L.polyline(latLngs, {
                                color: '#DC2626',
                                weight: 4,
                                opacity: 0.85,
                                smoothFactor: 1
                            }).addTo(map);
                        }
                    }

                    // Draw active work order destination marker + dashed route line
                    if (destinationMarker) { map.removeLayer(destinationMarker); destinationMarker = null; }
                    if (destinationLine) { map.removeLayer(destinationLine); destinationLine = null; }

                    if (selectedTechObj && selectedTechObj.active_task && selectedTechObj.active_task.latitude && selectedTechObj.active_task.longitude) {
                        const destLat = selectedTechObj.active_task.latitude;
                        const destLng = selectedTechObj.active_task.longitude;
                        destinationMarker = L.marker([destLat, destLng], { icon: redDestinationIcon })
                            .addTo(map)
                            .bindPopup(`<b>Target Work Order:</b><br>${escapeHtml(selectedTechObj.active_task.title)}<br><small>Customer: ${escapeHtml(selectedTechObj.active_task.customer_name || '-')}</small>`);

                        if (selectedTechObj.latitude !== null && selectedTechObj.longitude !== null) {
                            destinationLine = L.polyline([
                                [selectedTechObj.latitude, selectedTechObj.longitude],
                                [destLat, destLng]
                            ], { color: '#2563EB', dashArray: '6, 8', weight: 3 }).addTo(map);
                        }
                    }
                }

                if (shouldFitBounds) {
                    if (selectedTechId && routePolyline) {
                        map.fitBounds(routePolyline.getBounds(), { padding: [40, 40] });
                    } else if (activeCoordinates.length > 0) {
                        map.fitBounds(L.latLngBounds(activeCoordinates), { padding: [50, 50] });
                    }
                }

                // 4. Delta Update Live Log Feed in Sidebar
                const logsContainer = document.getElementById('live-logs-container');
                if (logsContainer && result.history_logs && result.history_logs.length > 0) {
                    let newLogsHtml = '';
                    result.history_logs.forEach(log => {
                        if (log.id > lastLogId) lastLogId = log.id;
                        newLogsHtml += `
                            <div class="border-b border-slate-100 pb-2 feed-item-flash">
                                <div class="flex justify-between items-center mb-0.5 font-sans font-bold text-textPrimary">
                                    <span class="truncate">${escapeHtml(log.technician_name)}</span>
                                    <span class="text-[9px] text-slate-400 font-medium font-sans flex-shrink-0">${log.time}</span>
                                </div>
                                <p class="truncate text-slate-500">LAT: ${log.latitude}</p>
                                <p class="truncate text-slate-500">LNG: ${log.longitude}</p>
                            </div>
                        `;
                    });
                    if (logsContainer.querySelector('p.text-center')) {
                        logsContainer.innerHTML = newLogsHtml;
                    } else {
                        logsContainer.insertAdjacentHTML('afterbegin', newLogsHtml);
                        while (logsContainer.children.length > 35) {
                            logsContainer.removeChild(logsContainer.lastElementChild);
                        }
                    }
                } else if (result.latest_log_id && result.latest_log_id > lastLogId) {
                    lastLogId = result.latest_log_id;
                }

            } catch (err) {
                console.warn('[LiveTracking] Telemetry sync error:', err);
            } finally {
                isFetching = false;
                if (refreshIcon) refreshIcon.classList.remove('animate-spin');
            }
        }

        // Initial Load and Bound Fitting
        fetchLiveTrackingData(true);

        // Real-Time Polling Loop: Configured via settings (minimum 4000ms)
        const pollMs = Math.max(4000, Number(pollIntervalSec) * 1000);
        const livePollingInterval = setInterval(() => {
            if (document.hidden) return;
            fetchLiveTrackingData(false);
        }, pollMs);

        // Interactive Sidebar Technician Click: Focus on map without full page reload
        const routeFilterBar = document.getElementById('route-filter-bar');
        const routeFilterName = document.getElementById('route-filter-name');
        const btnClearRoute = document.getElementById('btn-clear-route-filter');

        document.querySelectorAll('[id^="tech-item-"]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const techId = this.getAttribute('id').replace('tech-item-', '');
                selectedTechId = Number(techId);

                document.querySelectorAll('[id^="tech-item-"]').forEach(el => {
                    el.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-sm');
                    el.classList.add('bg-slate-50/80', 'border-slate-200/80');
                });
                this.classList.remove('bg-slate-50/80', 'border-slate-200/80');
                this.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-sm');

                const techNameEl = this.querySelector('.text-xs.font-bold');
                if (routeFilterBar && routeFilterName) {
                    routeFilterName.textContent = 'Route: ' + (techNameEl ? techNameEl.textContent.trim() : '#' + techId);
                    routeFilterBar.classList.remove('hidden');
                }

                if (techMarkers[techId]) {
                    const marker = techMarkers[techId];
                    map.flyTo(marker.getLatLng(), 15, { duration: 1.2 });
                    marker.openPopup();
                }

                fetchLiveTrackingData(false);
            });
        });

        if (btnClearRoute) {
            btnClearRoute.addEventListener('click', function() {
                selectedTechId = null;
                clearRouteVisuals();
                if (routeFilterBar) routeFilterBar.classList.add('hidden');
                document.querySelectorAll('[id^="tech-item-"]').forEach(el => {
                    el.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-sm');
                    el.classList.add('bg-slate-50/80', 'border-slate-200/80');
                });
                fetchLiveTrackingData(true);
            });
        }

        // Manual Refresh Button
        const btnManualRefresh = document.getElementById('btn-manual-refresh');
        if (btnManualRefresh) {
            btnManualRefresh.addEventListener('click', () => fetchLiveTrackingData(false));
        }

        // Recenter Map Button
        const btnRecenter = document.getElementById('btn-recenter');
        if (btnRecenter) {
            btnRecenter.addEventListener('click', () => fetchLiveTrackingData(true));
        }

        // Clean up interval on navigation away
        window.addEventListener('beforeunload', () => {
            clearInterval(livePollingInterval);
        });
    });
</script>
@endpush
@endsection
