<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Technician Live Tracking | {{ $task->customer_name }}</title>
    
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

        /* Post-Completion Customer CSAT Rating Card */
        .csat-box {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 16px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .csat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .csat-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #fde68a;
        }
        .csat-stars {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .csat-star-btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .csat-star-btn.active {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.16);
            border-color: rgba(251, 191, 36, 0.5);
            transform: scale(1.06);
        }
        .csat-input {
            width: 100%;
            background: rgba(7, 10, 16, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 10px;
            color: #f8fafc;
            padding: 9px 11px;
            font-size: 0.78rem;
            outline: none;
        }
        .csat-input:focus {
            border-color: #fbbf24;
        }
        .csat-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .btn-csat-submit {
            flex: 1;
            min-height: 38px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #070a10;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            padding: 0 14px;
        }
        .btn-csat-submit:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .btn-bast-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
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
    <div class="bottom-sheet" style="max-height: 62vh; overflow-y: auto;">
        <div class="sheet-handle"></div>

        <div class="mission-status-headline">
            <div>
                <div class="mission-title" id="mission-title-text">{{ $task->title }}</div>
                <div class="mission-sub" id="mission-eta-text">
                    <ion-icon name="navigate-outline"></ion-icon>
                    <span>Technician is processing your work order</span>
                </div>
            </div>
        </div>

        <!-- Progress Slider -->
        <div class="progress-track">
            <div class="progress-fill" id="progress-bar-fill" style="width: {{ max(15, $task->progress_percent) }}%;"></div>
        </div>

        <!-- Technician Info Card (Field-Level Privacy Redacted) -->
        <div class="technician-card">
            <div class="tech-info">
                <div class="tech-avatar">
                    <ion-icon name="construct"></ion-icon>
                </div>
                <div class="tech-text">
                    <h4 id="tech-name-text">{{ $task->technician ? \Illuminate\Support\Str::before(trim($task->technician->name), ' ') : 'Awaiting NOC Dispatch' }}</h4>
                    <span><ion-icon name="shield-checkmark"></ion-icon> Certified Field Technician</span>
                </div>
            </div>

            <div class="tech-actions">
                <span style="font-size: 11px; color: #38bdf8; font-weight: 600; background: rgba(56, 189, 248, 0.12); padding: 6px 10px; border-radius: 999px; border: 1px solid rgba(56, 189, 248, 0.3);">
                    ID #TECH-{{ str_pad((string) ($task->technician_id ?? 0), 3, '0', STR_PAD_LEFT) }}
                </span>
            </div>
        </div>

        <!-- Post-Completion CSAT Service Rating & Official BAST PDF -->
        @php
            $bastLink = route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->tracking_token]);
            $isCompletedInitial = $task->status === 'completed';
            $hasRatingInitial = $task->customer_rating !== null;
        @endphp
        <div class="csat-box" id="csat-section" style="{{ $isCompletedInitial ? '' : 'display:none;' }}">
            <div class="csat-header">
                <span class="csat-title" id="csat-title-text">
                    {{ $hasRatingInitial ? 'Thank You for Rating Our Field Service!' : 'Rate Your Fiber Technician Experience' }}
                </span>
                <a href="{{ $bastLink }}" target="_blank" rel="noopener noreferrer" class="btn-bast-pill" id="bast-download-link">
                    <ion-icon name="document-text-outline"></ion-icon>
                    <span>Official BAST PDF</span>
                </a>
            </div>

            <div id="csat-form-wrap" style="{{ $hasRatingInitial ? 'display:none;' : '' }}">
                <div class="csat-stars" id="csat-star-row" role="radiogroup" aria-label="Service Rating">
                    @for($s = 1; $s <= 5; $s++)
                        <button type="button" class="csat-star-btn" data-star="{{ $s }}" onclick="selectCsatStar({{ $s }})" aria-label="{{ $s }} stars">
                            <ion-icon name="star"></ion-icon>
                        </button>
                    @endfor
                </div>
                <input type="text" id="csat-feedback-input" class="csat-input" maxlength="500" style="margin-top:8px;" placeholder="Optional comment (e.g. Fast splicing, clean cable work)..." />
                <div class="csat-actions" style="margin-top:8px;">
                    <button type="button" id="csat-submit-btn" class="btn-csat-submit" onclick="submitCsatRating()" disabled>
                        Submit Rating
                    </button>
                </div>
                <div id="csat-error-msg" style="display:none; color:#fca5a5; font-size:0.72rem; margin-top:4px;"></div>
            </div>

            <div id="csat-done-wrap" style="{{ $hasRatingInitial ? '' : 'display:none;' }} font-size:0.78rem; color:#cbd5e1;">
                <span id="csat-done-stars" style="color:#fbbf24; font-weight:700;">
                    @if($hasRatingInitial)
                        {{ str_repeat('★', (int) $task->customer_rating) }}{{ str_repeat('☆', 5 - (int) $task->customer_rating) }} ({{ $task->customer_rating }}/5)
                    @endif
                </span>
                <span id="csat-done-comment" style="display:block; margin-top:4px; color:#94a3b8;">
                    {{ $task->customer_feedback ?: 'Your feedback has been recorded for our NOC Quality Team.' }}
                </span>
            </div>
        </div>

        <!-- Customer Destination Row -->
        <div class="destination-row">
            <ion-icon name="location-sharp"></ion-icon>
            <span>Destination: {{ $task->customer_name }} &bull; {{ $task->address }}</span>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const TOKEN = @json($task->tracking_token);
        const CUSTOMER_NAME = @json($task->customer_name);
        const IS_EXPIRED = {{ ($isExpired ?? false) ? 'true' : 'false' }};
        const HAS_INITIAL_TECH_LOC = {{ $latestLocation ? 'true' : 'false' }};
        const HAS_DEST_COORDS = {{ ($task->latitude !== null && $task->longitude !== null) ? 'true' : 'false' }};
        const destLat = {{ $task->latitude !== null ? (float) $task->latitude : 'null' }};
        const destLng = {{ $task->longitude !== null ? (float) $task->longitude : 'null' }};

        let techLat = {{ $latestLocation ? (float) $latestLocation->latitude : 'null' }};
        let techLng = {{ $latestLocation ? (float) $latestLocation->longitude : 'null' }};
        let selectedCsatRating = 0;

        function selectCsatStar(star) {
            selectedCsatRating = star;
            document.querySelectorAll('#csat-star-row .csat-star-btn').forEach(btn => {
                btn.classList.toggle('active', Number(btn.dataset.star) <= star);
            });
            const submitBtn = document.getElementById('csat-submit-btn');
            if (submitBtn) submitBtn.disabled = star < 1;
        }

        async function submitCsatRating() {
            if (selectedCsatRating < 1 || selectedCsatRating > 5) return;
            const btn = document.getElementById('csat-submit-btn');
            const errEl = document.getElementById('csat-error-msg');
            const feedback = (document.getElementById('csat-feedback-input')?.value || '').trim();
            if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }
            if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }

            try {
                const res = await fetch(`/api/public/track/${TOKEN}/rating`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ rating: selectedCsatRating, feedback: feedback || null })
                });
                const payload = await res.json();
                if (!res.ok) {
                    if (errEl) {
                        errEl.textContent = payload.message || 'Unable to submit rating.';
                        errEl.style.display = 'block';
                    }
                    if (btn) { btn.disabled = false; btn.textContent = 'Submit Rating'; }
                    return;
                }
                const r = payload.data?.customer_rating || selectedCsatRating;
                document.getElementById('csat-title-text').textContent = 'Thank You for Rating Our Field Service!';
                document.getElementById('csat-form-wrap').style.display = 'none';
                document.getElementById('csat-done-stars').textContent = '★'.repeat(r) + '☆'.repeat(5 - r) + ` (${r}/5)`;
                document.getElementById('csat-done-comment').textContent = payload.data?.customer_feedback || 'Your feedback has been recorded for our NOC Quality Team.';
                document.getElementById('csat-done-wrap').style.display = 'block';
            } catch (e) {
                if (errEl) {
                    errEl.textContent = 'Network error. Please try again.';
                    errEl.style.display = 'block';
                }
                if (btn) { btn.disabled = false; btn.textContent = 'Submit Rating'; }
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        const initialCenter = HAS_DEST_COORDS
            ? [destLat, destLng]
            : (HAS_INITIAL_TECH_LOC ? [techLat, techLng] : [-6.2088, 106.8456]);

        // Initialize Map
        const map = L.map('map-viewport', { zoomControl: false }).setView(initialCenter, 14);

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

        // Add Customer Marker only when premise coordinates exist
        if (HAS_DEST_COORDS) {
            L.marker([destLat, destLng], { icon: customerIcon })
                .addTo(map)
                .bindPopup(`<b>Subscriber Location:</b><br>${escapeHtml(CUSTOMER_NAME)}`);
        }

        let techMarker = null;
        let routeLine = null;

        function ensureTechMarker(lat, lng) {
            if (!techMarker) {
                techMarker = L.marker([lat, lng], { icon: techIcon })
                    .addTo(map)
                    .bindPopup("<b>Field Technician Location</b>");
                if (HAS_DEST_COORDS) {
                    routeLine = L.polyline([[lat, lng], [destLat, destLng]], {
                        color: '#00f2fe',
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '6, 8'
                    }).addTo(map);
                    const bounds = L.latLngBounds([[lat, lng], [destLat, destLng]]);
                    map.fitBounds(bounds, { padding: [60, 60] });
                } else {
                    map.setView([lat, lng], 15);
                }
            } else {
                techMarker.setLatLng([lat, lng]);
                if (HAS_DEST_COORDS && routeLine) {
                    routeLine.setLatLngs([[lat, lng], [destLat, destLng]]);
                }
            }
        }

        if (HAS_INITIAL_TECH_LOC && techLat !== null && techLng !== null) {
            ensureTechMarker(techLat, techLng);
        }

        let pollingTimer = null;

        async function fetchLiveStatus() {
            if (document.hidden || IS_EXPIRED) return;
            try {
                const res = await fetch(`/api/public/track/${TOKEN}`);
                if (res.status === 410) {
                    const etaText = document.getElementById('mission-eta-text');
                    if (etaText) {
                        etaText.innerHTML = '<ion-icon name="lock-closed-outline"></ion-icon><span>Tracking session expired (24h post-completion)</span>';
                    }
                    if (pollingTimer) clearInterval(pollingTimer);
                    return;
                }
                if (!res.ok) return;
                const data = await res.json();

                const statusBadge = document.getElementById('badge-status-text');
                if (statusBadge) statusBadge.textContent = data.task.status_label;

                const techNameEl = document.getElementById('tech-name-text');
                if (techNameEl && data.technician && data.technician.name) {
                    techNameEl.textContent = data.technician.name;
                }

                const progressFill = document.getElementById('progress-bar-fill');
                if (progressFill) progressFill.style.width = Math.max(15, data.task.progress_percent) + '%';

                const etaText = document.getElementById('mission-eta-text');
                if (etaText) {
                    if (data.task.status === 'completed') {
                        etaText.innerHTML = '<ion-icon name="checkmark-circle" style="color: #10b981;"></ion-icon><span style="color: #10b981;">Work order completed</span>';
                        const csatBox = document.getElementById('csat-section');
                        if (csatBox) csatBox.style.display = 'flex';
                        if (data.task.bast_url) {
                            const bastLink = document.getElementById('bast-download-link');
                            if (bastLink) bastLink.href = data.task.bast_url;
                        }
                        if (pollingTimer) {
                            clearInterval(pollingTimer);
                            pollingTimer = null;
                        }
                    } else if (data.live_location && data.live_location.eta_minutes) {
                        etaText.innerHTML = `<ion-icon name="navigate-outline"></ion-icon><span>Technician en route &bull; ETA ~${data.live_location.eta_minutes} min (${data.live_location.distance_km} km)</span>`;
                    } else if (data.state === 'waiting') {
                        etaText.innerHTML = '<ion-icon name="time-outline"></ion-icon><span>Waiting for field technician GPS signal...</span>';
                    } else if (data.task.status === 'on-going') {
                        etaText.innerHTML = '<ion-icon name="navigate-outline"></ion-icon><span>Technician is executing your work order</span>';
                    }
                }

                if (data.live_location && data.live_location.latitude !== null && data.live_location.longitude !== null) {
                    ensureTechMarker(data.live_location.latitude, data.live_location.longitude);
                }
            } catch (err) {
                console.warn('Polling tracking failed:', err);
            }
        }

        if (IS_EXPIRED) {
            const etaText = document.getElementById('mission-eta-text');
            if (etaText) {
                etaText.innerHTML = '<ion-icon name="lock-closed-outline"></ion-icon><span>Tracking session expired (24h post-completion)</span>';
            }
        } else {
            pollingTimer = setInterval(fetchLiveStatus, 5000);
        }
    </script>
</body>
</html>
