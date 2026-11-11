@extends('layouts.admin')

@section('title', 'Live Tracking Teknisi')
@section('header', 'Live Tracking Lapangan')

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
                    Armada Teknisi
                </h3>
                <span id="badge-tech-count" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-100">
                    {{ $technicians->where('is_online', true)->count() }} / {{ $technicians->count() }} Aktif
                </span>
            </div>
            
            <p class="text-xs text-textSecondary mb-3">
                Klik teknisi untuk menampilkan riwayat lintasan rute GPS lengkap.
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
                                    <span class="text-[9px] text-slate-400 truncate block">Belum ada koordinat</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if($tech->is_online || ($latestLoc && $latestLoc->created_at->gt(now()->subMinutes(30))))
                                <span class="h-2 w-2 rounded-full bg-emerald-500" title="Online Aktif"></span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-slate-300" title="Offline"></span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4">Belum ada teknisi terdaftar.</p>
                @endforelse
            </div>
            
            @if($selectedTech)
                <div class="mt-3 pt-3 border-t border-fsmBorder flex justify-between items-center">
                    <span class="text-[10px] text-blue-700 bg-blue-100/70 border border-blue-200 px-2 py-0.5 rounded font-semibold truncate max-w-[150px]">
                        Rute: {{ $selectedTech->name }}
                    </span>
                    <a href="{{ route('admin.tracking') }}" class="text-[10px] text-red-500 font-semibold hover:underline">
                        Hapus Filter Rute
                    </a>
                </div>
            @endif
        </div>

        <!-- Coordinates Real-time Log Feed -->
        <div class="bg-white rounded-xl border border-fsmBorder p-4 sm:p-5 shadow-sm flex-1 flex flex-col min-h-[260px]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <ion-icon name="pulse-outline" class="text-blue-500"></ion-icon>
                    Live Telemetry Log
                </h3>
                <span id="log-status-dot" class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Streaming Telemetri"></span>
            </div>
            
            <div id="live-logs-container" class="flex-1 overflow-y-auto max-h-[260px] space-y-2 pr-1 text-[10px] font-mono text-textSecondary">
                @forelse($historyLogs as $log)
                    <div class="border-b border-slate-100 pb-2 transition-all">
                        <div class="flex justify-between items-center mb-0.5 font-sans font-bold text-textPrimary">
                            <span class="truncate">{{ $log->technician ? $log->technician->name : 'Teknisi' }}</span>
                            <span class="text-[9px] text-slate-400 font-medium font-sans flex-shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
                        </div>
                        <p class="truncate text-slate-500">LAT: {{ $log->latitude }}</p>
                        <p class="truncate text-slate-500">LNG: {{ $log->longitude }}</p>
                    </div>
                @empty
                    <p class="text-xs text-textSecondary text-center py-4 font-sans">Belum ada data telemetri.</p>
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
                            Terhubung secara real-time ke aplikasi teknisi mobile
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <span id="last-sync-time" class="text-[11px] font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                        Sync: {{ now()->format('H:i:s') }}
                    </span>
                    <button type="button" id="btn-recenter" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 transition-all flex items-center gap-1">
                        <ion-icon name="locate-outline"></ion-icon>
                        Pusatkan
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
                        Lokasi Teknisi Terkini
                    </span>
                    @if($selectedTech)
                        <span class="flex items-center gap-1.5 text-red-600 font-semibold">
                            <span class="w-3 h-3 rounded-full bg-red-500 border-2 border-white shadow-sm inline-block"></span>
                            Jejak Lintasan Rute ({{ $selectedTech->name }})
                        </span>
                    @endif
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        Status Tugas On-Going
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

        // Dictionary to track active Leaflet markers by technician ID
        const techMarkers = {};
        let routePolyline = null;
        let routeMarkersGroup = L.layerGroup().addTo(map);

        function buildPopupContent(tech) {
            let activeTaskHtml = '';
            if (tech.active_task) {
                activeTaskHtml = `
                    <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 6px 8px; margin: 6px 0;">
                        <div style="color: #1E40AF; font-weight: 700; font-size: 11px;">🛠️ ${escapeHtml(tech.active_task.title)}</div>
                        <div style="color: #3B82F6; font-size: 10px;">Pelanggan: ${escapeHtml(tech.active_task.customer_name || '-')}</div>
                        <div style="color: #64748B; font-size: 9px;">Status: <b>${escapeHtml(tech.active_task.status)}</b></div>
                    </div>
                `;
            } else {
                activeTaskHtml = `
                    <div style="color: #64748B; font-size: 10px; margin: 4px 0;">
                        <i>Tidak ada tugas aktif</i>
                    </div>
                `;
            }

            return `
                <div style="font-family: 'Inter', sans-serif; padding: 12px; width: 220px;">
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
                    <div style="font-size: 9px; font-mono; color: #64748B; background: #F8FAFC; padding: 4px 6px; border-radius: 4px; border: 1px solid #E2E8F0; margin-top: 4px;">
                        Koordinat: ${tech.latitude ? tech.latitude.toFixed(5) : '-'}, ${tech.longitude ? tech.longitude.toFixed(5) : '-'}
                    </div>
                    <div style="margin-top: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 9px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 6px;">
                        <span>Update: ${tech.last_seen || '-'}</span>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${tech.latitude},${tech.longitude}" target="_blank" rel="noopener noreferrer" style="color: #2563EB; font-weight: 700; text-decoration: none;" title="Buka Google Maps">Maps</a>
                            <span style="color: #CBD5E1;">&bull;</span>
                            <a href="https://waze.com/ul?ll=${tech.latitude},${tech.longitude}&navigate=yes" target="_blank" rel="noopener noreferrer" style="color: #0284C7; font-weight: 700; text-decoration: none;" title="Buka Waze">Waze</a>
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

        // Live Poll Function to fetch real data from backend
        let isFetching = false;
        async function fetchLiveTrackingData(shouldFitBounds = false) {
            if (isFetching) return;
            isFetching = true;

            const refreshBtn = document.getElementById('btn-manual-refresh');
            const refreshIcon = document.getElementById('refresh-icon');
            if (refreshIcon) refreshIcon.classList.add('animate-spin');

            try {
                const queryParam = selectedTechId ? `?technician_id=${selectedTechId}` : '';
                const response = await fetch(`${liveDataUrl}${queryParam}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('Live telemetry fetch failed');
                const result = await response.json();

                // 1. Update Sync Time and Counts
                const lastSyncEl = document.getElementById('last-sync-time');
                if (lastSyncEl) lastSyncEl.textContent = `Sync: ${result.server_time}`;

                const badgeCountEl = document.getElementById('badge-tech-count');
                if (badgeCountEl) {
                    badgeCountEl.textContent = `${result.online_count} / ${result.total_count} Aktif`;
                }

                // 2. Update Map Markers with Real GPS coordinates
                const activeCoordinates = [];
                const incomingIds = new Set();

                result.technicians.forEach(tech => {
                    incomingIds.add(tech.id);
                    if (tech.latitude !== null && tech.longitude !== null) {
                        activeCoordinates.push([tech.latitude, tech.longitude]);
                        const iconToUse = tech.active_task ? greenIcon : blueIcon;

                        if (techMarkers[tech.id]) {
                            // Smoothly update marker position
                            const existingMarker = techMarkers[tech.id];
                            const currentPos = existingMarker.getLatLng();
                            if (currentPos.lat !== tech.latitude || currentPos.lng !== tech.longitude) {
                                existingMarker.setLatLng([tech.latitude, tech.longitude]);
                            }
                            existingMarker.setIcon(iconToUse);
                            existingMarker.setPopupContent(buildPopupContent(tech));
                        } else {
                            // Create new marker
                            const marker = L.marker([tech.latitude, tech.longitude], {
                                icon: iconToUse
                            }).addTo(map);
                            marker.bindPopup(buildPopupContent(tech), { className: 'custom-tech-popup' });
                            techMarkers[tech.id] = marker;
                        }
                    }
                });

                // Prune stale markers for removed technicians
                Object.keys(techMarkers).forEach(id => {
                    if (!incomingIds.has(Number(id))) {
                        map.removeLayer(techMarkers[id]);
                        delete techMarkers[id];
                    }
                });

                // 3. Update Route Trail if a technician is selected
                if (result.selected_route && result.selected_route.length > 0) {
                    routeMarkersGroup.clearLayers();
                    const latLngs = [];

                    result.selected_route.forEach((pt, idx) => {
                        latLngs.push([pt.latitude, pt.longitude]);

                        // Add small circle dots along the path
                        L.circleMarker([pt.latitude, pt.longitude], {
                            radius: idx === result.selected_route.length - 1 ? 6 : 4,
                            color: '#DC2626',
                            fillColor: '#FCA5A5',
                            fillOpacity: 0.9,
                            weight: 2
                        }).addTo(routeMarkersGroup).bindPopup(`Titik GPS pukul: ${pt.time}`);
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

                    if (shouldFitBounds && latLngs.length > 0) {
                        map.fitBounds(L.latLngBounds(latLngs), { padding: [40, 40] });
                    }
                } else if (shouldFitBounds && activeCoordinates.length > 0) {
                    map.fitBounds(L.latLngBounds(activeCoordinates), { padding: [50, 50] });
                }

                // 4. Update Live Log Feed in Sidebar
                const logsContainer = document.getElementById('live-logs-container');
                if (logsContainer && result.history_logs && result.history_logs.length > 0) {
                    let logsHtml = '';
                    result.history_logs.slice(0, 25).forEach(log => {
                        logsHtml += `
                            <div class="border-b border-slate-100 pb-2">
                                <div class="flex justify-between items-center mb-0.5 font-sans font-bold text-textPrimary">
                                    <span class="truncate">${escapeHtml(log.technician_name)}</span>
                                    <span class="text-[9px] text-slate-400 font-medium font-sans flex-shrink-0">${log.time}</span>
                                </div>
                                <p class="truncate text-slate-500">LAT: ${log.latitude}</p>
                                <p class="truncate text-slate-500">LNG: ${log.longitude}</p>
                            </div>
                        `;
                    });
                    logsContainer.innerHTML = logsHtml;
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
            fetchLiveTrackingData(false);
        }, pollMs);

        // Interactive Sidebar Technician Click: Focus on map without full page reload
        document.querySelectorAll('[id^="tech-item-"]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const techId = this.getAttribute('id').replace('tech-item-', '');
                selectedTechId = Number(techId);

                // Update active sidebar styling
                document.querySelectorAll('[id^="tech-item-"]').forEach(el => {
                    el.classList.remove('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-sm');
                    el.classList.add('bg-slate-50/80', 'border-slate-200/80');
                });
                this.classList.remove('bg-slate-50/80', 'border-slate-200/80');
                this.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700', 'shadow-sm');

                // If marker exists, pan smoothly and open popup
                if (techMarkers[techId]) {
                    const marker = techMarkers[techId];
                    map.flyTo(marker.getLatLng(), 15, { duration: 1.2 });
                    marker.openPopup();
                }

                // Immediately fetch route trail for selected technician
                fetchLiveTrackingData(false);
            });
        });

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
