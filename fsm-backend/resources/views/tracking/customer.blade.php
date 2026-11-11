<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Live Tracking Teknisi | {{ $task->customer_name }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        :root {
            --bg-dark: #070a10;
            --card-bg: rgba(14, 20, 32, 0.94);
            --primary-cyan: #00f2fe;
            --accent-purple: #8b5cf6;
            --emerald: #10b981;
            --amber: #f59e0b;
            --border-glow: rgba(0, 242, 254, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Space Grotesk', -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Bar */
        .tracking-top-bar {
            background: rgba(7, 10, 16, 0.9);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glow);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }

        .brand-pill {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(0, 242, 254, 0.2), rgba(139, 92, 246, 0.2));
            border: 1px solid var(--border-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-cyan);
            font-size: 18px;
        }

        .brand-text h1 {
            font-size: 0.92rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.2px;
        }

        .brand-text span {
            font-size: 0.68rem;
            color: var(--text-muted);
            display: block;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--emerald);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: var(--emerald);
            box-shadow: 0 0 8px var(--emerald);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Map Container */
        #map-viewport {
            flex: 1;
            width: 100%;
            z-index: 1;
        }

        /* Bottom Floating Card (Uber Style) */
        .bottom-sheet {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            border-top: 1px solid var(--border-glow);
            border-radius: 24px 24px 0 0;
            padding: 18px 20px calc(18px + env(safe-area-inset-bottom));
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.75);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .sheet-handle {
            width: 40px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 0 auto -6px;
        }

        .mission-status-headline {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .mission-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }

        .mission-sub {
            font-size: 0.8rem;
            color: var(--primary-cyan);
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .progress-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-cyan), var(--emerald));
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        /* Technician Profile Card */
        .technician-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tech-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tech-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-cyan), var(--accent-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #070a10;
            font-weight: 700;
        }

        .tech-text h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
        }

        .tech-text span {
            font-size: 0.72rem;
            color: var(--emerald);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .tech-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-call, .btn-wa {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.2s;
        }

        .btn-wa {
            background: #25d366;
            color: #fff;
        }

        .btn-call {
            background: rgba(0, 242, 254, 0.15);
            border: 1px solid var(--border-glow);
            color: var(--primary-cyan);
        }

        .btn-call:active, .btn-wa:active {
            transform: scale(0.92);
        }

        .destination-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: var(--text-muted);
            padding: 2px 4px;
        }
        .destination-row ion-icon {
            font-size: 16px;
            color: #f43f5e;
            flex-shrink: 0;
        }
        .destination-row span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>

    <!-- Top Sticky Bar -->
    <header class="tracking-top-bar">
        <div class="brand-pill">
            <div class="brand-icon">
                <ion-icon name="shield-checkmark"></ion-icon>
            </div>
            <div class="brand-text">
                <h1>FiberOps Live Tracking</h1>
                <span>Benteng Siber Fleet Service</span>
            </div>
        </div>

        <div class="status-badge" id="badge-status-container">
            <span class="status-dot"></span>
            <span id="badge-status-text">{{ $task->status_label }}</span>
        </div>
    </header>

    <!-- Map Viewport -->
    <div id="map-viewport"></div>

    <!-- Bottom Sheet (Uber-Style Live Mission Card) -->
    <div class="bottom-sheet">
        <div class="sheet-handle"></div>

        <div class="mission-status-headline">
            <div>
                <div class="mission-title" id="mission-title-text">{{ $task->title }}</div>
                <div class="mission-sub" id="mission-eta-text">
                    <ion-icon name="navigate-outline"></ion-icon>
                    <span>Teknisi sedang memproses tugas Anda</span>
                </div>
            </div>
        </div>

        <!-- Progress Slider -->
        <div class="progress-track">
            <div class="progress-fill" id="progress-bar-fill" style="width: {{ max(15, $task->progress_percent) }}%;"></div>
        </div>

        <!-- Technician Info Card -->
        <div class="technician-card">
            <div class="tech-info">
                <div class="tech-avatar">
                    <ion-icon name="construct"></ion-icon>
                </div>
                <div class="tech-text">
                    <h4 id="tech-name-text">{{ $task->technician?->name ?? 'Teknisi Ditugaskan' }}</h4>
                    <span><ion-icon name="shield-checkmark"></ion-icon> Teknisi Resmi ISP</span>
                </div>
            </div>

            <div class="tech-actions">
                @if($task->technician?->phone)
                    <a href="tel:{{ $task->technician->phone }}" class="btn-call" title="Telepon Langsung">
                        <ion-icon name="call-outline"></ion-icon>
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $task->technician->phone)) }}?text=Halo%20{{ urlencode($task->technician->name) }}%2C%20saya%20pelanggan%20tiket%20{{ urlencode($task->title) }}" target="_blank" class="btn-wa" title="Chat WhatsApp">
                        <ion-icon name="logo-whatsapp"></ion-icon>
                    </a>
                @endif
            </div>
        </div>

        <!-- Customer Destination Row -->
        <div class="destination-row">
            <ion-icon name="location-sharp"></ion-icon>
            <span>Tujuan: {{ $task->customer_name }} &bull; {{ $task->address }}</span>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const TOKEN = "{{ $task->tracking_token }}";
        const destLat = {{ (float) ($task->latitude ?? -6.2088) }};
        const destLng = {{ (float) ($task->longitude ?? 106.8456) }};
        
        // Initial tech coordinates
        let techLat = {{ (float) ($latestLocation?->latitude ?? $task->latitude ?? -6.2100) }};
        let techLng = {{ (float) ($latestLocation?->longitude ?? $task->longitude ?? 106.8400) }};

        // Initialize Map
        const map = L.map('map-viewport', { zoomControl: false }).setView([destLat, destLng], 14);

        // Tile layer (Dark Cyber Theme by CartoDB)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; CartoDB &copy; OpenStreetMap'
        }).addTo(map);

        // Custom HTML Marker Icons
        const customerIcon = L.divIcon({
            className: 'custom-customer-marker',
            html: `
                <div style="background: #f43f5e; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 15px rgba(244,63,94,0.6); border: 2px solid #fff; font-size: 18px;">
                    🏠
                </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        const techIcon = L.divIcon({
            className: 'custom-tech-marker',
            html: `
                <div style="background: #00f2fe; color: #070a10; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px rgba(0,242,254,0.8); border: 2px solid #fff; font-size: 20px;">
                    🛵
                </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Add Customer Marker
        const destMarker = L.marker([destLat, destLng], { icon: customerIcon })
            .addTo(map)
            .bindPopup("<b>Alamat Anda:</b><br>{{ $task->customer_name }}");

        // Add Technician Marker
        const techMarker = L.marker([techLat, techLng], { icon: techIcon })
            .addTo(map)
            .bindPopup("<b>Posisi Teknisi Lapangan</b>");

        // Route Polyline
        let routeLine = L.polyline([[techLat, techLng], [destLat, destLng]], {
            color: '#00f2fe',
            weight: 4,
            opacity: 0.7,
            dashArray: '6, 8'
        }).addTo(map);

        // Fit map bounds
        const bounds = L.latLngBounds([[techLat, techLng], [destLat, destLng]]);
        map.fitBounds(bounds, { padding: [60, 60] });

        // Real-time Polling Engine (Every 5 seconds)
        async function fetchLiveStatus() {
            try {
                const res = await fetch(`/api/public/track/${TOKEN}`);
                if (!res.ok) return;
                const data = await res.json();

                // Update status text & badge
                const statusBadge = document.getElementById('badge-status-text');
                if (statusBadge) statusBadge.textContent = data.task.status_label;

                // Update progress bar
                const progressFill = document.getElementById('progress-bar-fill');
                if (progressFill) progressFill.style.width = Math.max(15, data.task.progress_percent) + '%';

                // Update ETA / subtitle
                const etaText = document.getElementById('mission-eta-text');
                if (etaText) {
                    if (data.task.status === 'completed') {
                        etaText.innerHTML = '<ion-icon name="checkmark-circle" style="color: #10b981;"></ion-icon><span style="color: #10b981;">Pekerjaan telah selesai</span>';
                    } else if (data.task.status === 'on-going') {
                        etaText.innerHTML = '<ion-icon name="navigate-outline"></ion-icon><span>Teknisi sedang dalam perjalanan / pengerjaan</span>';
                    }
                }

                // Smoothly update technician coordinates
                if (data.live_location && data.live_location.latitude && data.live_location.longitude) {
                    const newLat = data.live_location.latitude;
                    const newLng = data.live_location.longitude;

                    techMarker.setLatLng([newLat, newLng]);
                    routeLine.setLatLngs([[newLat, newLng], [destLat, destLng]]);
                }
            } catch (err) {
                console.warn('Polling tracking failed:', err);
            }
        }

        setInterval(fetchLiveStatus, 5000);
    </script>
</body>
</html>
