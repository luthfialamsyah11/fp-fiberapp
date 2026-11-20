<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Service Request — {{ $settings['company_name'] ?? 'FiberOps' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
        #client-map { height: 320px; width: 100%; border-radius: 14px; border: 1px solid #CBD5E1; z-index: 1; cursor: crosshair; }
        .cat-card { transition: all 0.16s ease; }
        .cat-card.selected { border-color: #2563EB; background-color: #EFF6FF; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2); }
        .prio-pill.selected-low { border-color: #64748B; background: #F1F5F9; color: #0F172A; }
        .prio-pill.selected-medium { border-color: #D97706; background: #FFFBEB; color: #92400E; }
        .prio-pill.selected-high { border-color: #DC2626; background: #FEF2F2; color: #991B1B; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">
@php
    $companyName  = $settings['company_name'] ?? 'FiberOps Global Carrier';
    $supportPhone = $settings['support_phone'] ?? '+1-800-555-0199';
    $supportEmail = $settings['support_email'] ?? 'noc@bentengsiber.com';
    $selectedCat  = old('service_category', 'outage_los');
    $selectedPrio = old('priority', 'high');
    $jsMapStyle   = $mapStyle ?? 'openstreetmap';
@endphp

<!-- Top Navigation Header -->
<header class="sticky top-0 z-30 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-md shadow-blue-950/50 flex-shrink-0">
                <ion-icon name="wifi" class="text-white text-lg"></ion-icon>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-bold tracking-tight truncate">{{ $companyName }}</div>
                <div class="text-[10px] text-slate-400 uppercase tracking-wider">Direct Client Service Portal</div>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-[10px] font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                No Login Required
            </span>
            <a href="tel:{{ $supportPhone }}" class="hidden md:inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white transition-colors">
                <ion-icon name="call-outline" class="text-blue-400"></ion-icon>
                <span>NOC: {{ $supportPhone }}</span>
            </a>
            <a href="https://bentengsiber.com/" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition-colors">
                Home
            </a>
        </div>
    </div>
</header>

<main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">

    <!-- Page Intro Strip -->
    <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-3">
        <div>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-blue-600">
                <ion-icon name="flash-outline"></ion-icon> Instant NOC Dispatch Intake
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Submit Fiber Service or Repair Request
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl">
                Report an outage, request new FTTH installation, or schedule relocation directly without creating an account. You will receive a live tracking link immediately after submission.
            </p>
        </div>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs space-y-1.5 shadow-sm" role="alert">
            <div class="font-bold flex items-center gap-2 text-red-900">
                <ion-icon name="alert-circle" class="text-lg text-red-600"></ion-icon>
                <span>Please check the following fields before submitting:</span>
            </div>
            <ul class="list-disc list-inside pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('public.request.store') }}" method="POST" enctype="multipart/form-data" id="public-request-form">
        @csrf
        <!-- Anti-Spam Honeypot (Hidden from humans) -->
        <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
            <label for="website_url">Leave empty</label>
            <input type="text" name="website_url" id="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- LEFT COLUMN (7 cols): Service Category, Contact Details, Issue Description & Photo -->
            <div class="lg:col-span-7 space-y-5">

                <!-- Step 1: Service Category -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">1</span>
                        <h2 class="text-sm font-bold text-slate-900">Select Service Type</h2>
                    </div>

                    <input type="hidden" name="service_category" id="service_category_input" value="{{ $selectedCat }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" data-cat="outage_los" data-prio="high" data-title="Fiber Outage / Red LOS Emergency Repair"
                            class="cat-card text-left p-3.5 rounded-xl border border-slate-200 hover:border-blue-300 cursor-pointer {{ $selectedCat === 'outage_los' ? 'selected' : '' }}">
                            <div class="flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="warning-outline" class="text-lg"></ion-icon>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Total Outage / LOS Red</div>
                                    <div class="text-[11px] text-slate-500">No internet or blinking red LOS</div>
                                </div>
                            </div>
                        </button>

                        <button type="button" data-cat="new_install" data-prio="medium" data-title="FTTH New Fiber Installation Request"
                            class="cat-card text-left p-3.5 rounded-xl border border-slate-200 hover:border-blue-300 cursor-pointer {{ $selectedCat === 'new_install' ? 'selected' : '' }}">
                            <div class="flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="flash-outline" class="text-lg"></ion-icon>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">New Fiber Installation</div>
                                    <div class="text-[11px] text-slate-500">New FTTH drop &amp; ONT setup</div>
                                </div>
                            </div>
                        </button>

                        <button type="button" data-cat="slow_speed" data-prio="medium" data-title="Degraded Speed & Optical Attenuation Check"
                            class="cat-card text-left p-3.5 rounded-xl border border-slate-200 hover:border-blue-300 cursor-pointer {{ $selectedCat === 'slow_speed' ? 'selected' : '' }}">
                            <div class="flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="speedometer-outline" class="text-lg"></ion-icon>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Slow Speed / High Loss</div>
                                    <div class="text-[11px] text-slate-500">Intermittent ping or high attenuation</div>
                                </div>
                            </div>
                        </button>

                        <button type="button" data-cat="relocation" data-prio="low" data-title="ONT Relocation & Cable Maintenance"
                            class="cat-card text-left p-3.5 rounded-xl border border-slate-200 hover:border-blue-300 cursor-pointer {{ $selectedCat === 'relocation' ? 'selected' : '' }}">
                            <div class="flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <ion-icon name="construct-outline" class="text-lg"></ion-icon>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">Relocation / Maintenance</div>
                                    <div class="text-[11px] text-slate-500">Move router/ONT or tidy drop cable</div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Contact & Request Details -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">2</span>
                        <h2 class="text-sm font-bold text-slate-900">Your Contact &amp; Issue Details</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name / Company <span class="text-red-500">*</span></label>
                            <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" required
                                placeholder="e.g. Budi Santoso / PT Maju Jaya"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="customer_phone" class="block text-xs font-semibold text-slate-700 mb-1.5">WhatsApp / Phone Number <span class="text-red-500">*</span></label>
                            <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                                placeholder="e.g. 081234567890 or +62812..."
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="customer_email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}"
                                placeholder="you@company.com"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Urgency Level <span class="text-red-500">*</span></label>
                            <input type="hidden" name="priority" id="priority_input" value="{{ $selectedPrio }}">
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" data-prio-btn="low" class="prio-pill py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 cursor-pointer {{ $selectedPrio === 'low' ? 'selected-low' : '' }}">Low</button>
                                <button type="button" data-prio-btn="medium" class="prio-pill py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 cursor-pointer {{ $selectedPrio === 'medium' ? 'selected-medium' : '' }}">Medium</button>
                                <button type="button" data-prio-btn="high" class="prio-pill py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 cursor-pointer {{ $selectedPrio === 'high' ? 'selected-high' : '' }}">Urgent</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="request_title" class="block text-xs font-semibold text-slate-700 mb-1.5">Request Subject</label>
                        <input id="request_title" type="text" name="title" value="{{ old('title', 'Fiber Outage / Red LOS Emergency Repair') }}"
                            placeholder="Brief summary of the request"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold text-slate-700 mb-1.5">Describe the Issue or Installation Notes <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="3" required
                            placeholder="Explain symptoms (e.g., red LOS light blinking since 08:00, cable snagged outside gate, preferred technician arrival time)..."
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    </div>

                    <!-- Optional Problem Photo Upload -->
                    <div>
                        <label for="client_photo" class="block text-xs font-semibold text-slate-700 mb-1.5">Attach Photo of Issue <span class="text-slate-400 font-normal">(Optional — Modem LED / Cable / Location, Max 5MB)</span></label>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/70">
                            <input id="client_photo" type="file" name="client_photo" accept="image/jpeg,image/png,image/webp"
                                class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer w-full sm:w-auto">
                            <div id="photo-preview-wrap" class="hidden flex items-center gap-2">
                                <img id="photo-preview-img" src="" alt="Preview" class="h-12 w-12 object-cover rounded-lg border border-slate-200">
                                <button type="button" id="btn-clear-photo" class="text-[11px] font-bold text-red-600 hover:underline cursor-pointer">Remove</button>
                            </div>
                        </div>
                        <p id="photo-client-error" class="hidden mt-1.5 text-[11px] font-semibold text-red-600"></p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (5 cols): Service Location & Interactive Map Pin -->
            <div class="lg:col-span-5 lg:sticky lg:top-20 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3.5">
                    <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">3</span>
                            <h2 class="text-sm font-bold text-slate-900">Service Location &amp; GPS Pin</h2>
                        </div>
                        <button type="button" id="btn-use-gps"
                            class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-bold inline-flex items-center gap-1 transition-colors cursor-pointer">
                            <ion-icon name="locate-outline" class="text-sm"></ion-icon>
                            <span>Use My GPS</span>
                        </button>
                    </div>

                    <!-- Address Search -->
                    <div class="flex items-center gap-1.5">
                        <input type="text" id="map-search-input" placeholder="Search street, building, or area..."
                            class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" id="btn-map-search"
                            class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold cursor-pointer">
                            Find
                        </button>
                    </div>
                    <div id="map-search-status" class="hidden text-[11px] text-slate-500"></div>

                    <!-- Interactive Leaflet Map -->
                    <div id="client-map"></div>

                    <!-- Hidden / Readout Coordinates -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label for="lat-input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Latitude</label>
                            <input id="lat-input" type="text" name="latitude" value="{{ old('latitude') }}" readonly placeholder="Tap map or Use GPS"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-mono text-slate-700">
                        </div>
                        <div>
                            <label for="lng-input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Longitude</label>
                            <input id="lng-input" type="text" name="longitude" value="{{ old('longitude') }}" readonly placeholder="Optional exact pin"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-mono text-slate-700">
                        </div>
                    </div>

                    <!-- Full Address -->
                    <div>
                        <label for="address-input" class="block text-xs font-semibold text-slate-700 mb-1.5">Complete Address &amp; Landmark <span class="text-red-500">*</span></label>
                        <textarea id="address-input" name="address" rows="2" required
                            placeholder="Street name, house/unit number, floor, and nearest landmark..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                    </div>

                    <button type="submit" id="btn-submit-request"
                        class="w-full min-h-[44px] py-3 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <ion-icon name="paper-plane-outline" class="text-base"></ion-icon>
                        <span>Submit Service Request</span>
                    </button>

                    <p class="text-[11px] text-center text-slate-400">
                        Instant dispatch queue &bull; Live technician tracking link generated automatically.
                    </p>
                </div>
            </div>
        </div>
    </form>
</main>

<footer class="py-5 border-t border-slate-200 bg-white mt-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
        <span>&copy; {{ date('Y') }} {{ $companyName }} &bull; FiberOps FSM</span>
        <span>NOC Support: <strong class="text-slate-700">{{ $supportPhone }}</strong> &bull; {{ $supportEmail }}</span>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Category & Priority Interactive Cards
        var catInput = document.getElementById('service_category_input');
        var prioInput = document.getElementById('priority_input');
        var titleInput = document.getElementById('request_title');
        var defaultCategoryTitles = Array.from(document.querySelectorAll('.cat-card'))
            .map(function(c) { return (c.dataset.title || '').trim(); });
        var titleManuallyEdited = Boolean(
            titleInput && titleInput.value.trim() !== '' && !defaultCategoryTitles.includes(titleInput.value.trim())
        );

        titleInput?.addEventListener('input', function() { titleManuallyEdited = true; });

        function applyPriorityUI(val) {
            prioInput.value = val;
            document.querySelectorAll('[data-prio-btn]').forEach(function(btn) {
                btn.className = 'prio-pill py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 cursor-pointer';
                if (btn.dataset.prioBtn === val) {
                    btn.classList.add('selected-' + val);
                }
            });
        }

        document.querySelectorAll('[data-prio-btn]').forEach(function(btn) {
            btn.addEventListener('click', function() { applyPriorityUI(btn.dataset.prioBtn); });
        });

        document.querySelectorAll('.cat-card').forEach(function(card) {
            card.addEventListener('click', function() {
                document.querySelectorAll('.cat-card').forEach(function(c) { c.classList.remove('selected'); });
                card.classList.add('selected');
                catInput.value = card.dataset.cat;
                applyPriorityUI(card.dataset.prio);
                if (!titleManuallyEdited && card.dataset.title) {
                    titleInput.value = card.dataset.title;
                }
            });
        });

        // 2. Photo Preview + Client-Side 5MB / Image MIME Validation
        var photoInput = document.getElementById('client_photo');
        var previewWrap = document.getElementById('photo-preview-wrap');
        var previewImg = document.getElementById('photo-preview-img');
        var clearBtn = document.getElementById('btn-clear-photo');
        var photoErrEl = document.getElementById('photo-client-error');
        var MAX_PHOTO_BYTES = 5 * 1024 * 1024;
        var ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

        function clearPhotoSelection(errorMessage) {
            if (photoInput) photoInput.value = '';
            if (previewImg) previewImg.src = '';
            previewWrap?.classList.add('hidden');
            if (photoErrEl) {
                if (errorMessage) {
                    photoErrEl.textContent = errorMessage;
                    photoErrEl.classList.remove('hidden');
                } else {
                    photoErrEl.textContent = '';
                    photoErrEl.classList.add('hidden');
                }
            }
        }

        photoInput?.addEventListener('change', function() {
            var file = photoInput.files && photoInput.files[0];
            if (!file) {
                clearPhotoSelection('');
                return;
            }
            if (file.type && !ALLOWED_PHOTO_TYPES.includes(file.type.toLowerCase())) {
                clearPhotoSelection('Unsupported file type. Please attach a JPEG, PNG, or WebP photo.');
                return;
            }
            if (file.size > MAX_PHOTO_BYTES) {
                clearPhotoSelection('Photo is too large (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB). Maximum allowed size is 5 MB.');
                return;
            }
            if (photoErrEl) {
                photoErrEl.textContent = '';
                photoErrEl.classList.add('hidden');
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewWrap.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
        clearBtn?.addEventListener('click', function() {
            clearPhotoSelection('');
        });

        // 3. Interactive Leaflet Map Picker + GPS + Nominatim Search (Equator 0.0 Safe)
        var mapStyle = @json($jsMapStyle);
        var latInput = document.getElementById('lat-input');
        var lngInput = document.getElementById('lng-input');
        var addrInput = document.getElementById('address-input');
        var searchInput = document.getElementById('map-search-input');
        var searchStatus = document.getElementById('map-search-status');

        var parsedLat = latInput.value !== '' ? parseFloat(latInput.value) : NaN;
        var parsedLng = lngInput.value !== '' ? parseFloat(lngInput.value) : NaN;
        var hasSavedPin = Number.isFinite(parsedLat) && Number.isFinite(parsedLng);
        var startLat = hasSavedPin ? parsedLat : -6.2088;
        var startLng = hasSavedPin ? parsedLng : 106.8456;
        var map = L.map('client-map').setView([startLat, startLng], hasSavedPin ? 15 : 12);

        var tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        var tileAttr = '&copy; OpenStreetMap contributors';
        if (mapStyle === 'carto') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            tileAttr = '&copy; OpenStreetMap &copy; CARTO';
        } else if (mapStyle === 'mapbox') {
            tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
            tileAttr = 'Tiles &copy; Esri';
        }
        L.tileLayer(tileUrl, { maxZoom: 19, attribution: tileAttr }).addTo(map);

        var marker = null;
        function setPin(lat, lng, fetchAddress) {
            latInput.value = Number(lat).toFixed(6);
            lngInput.value = Number(lng).toFixed(6);
            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function(ev) {
                    var pos = ev.target.getLatLng();
                    setPin(pos.lat, pos.lng, !addrInput.value.trim());
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            if (fetchAddress) {
                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d && d.display_name && !addrInput.value.trim()) {
                            addrInput.value = d.display_name;
                        }
                    }).catch(function() {});
            }
        }

        if (hasSavedPin) {
            setPin(parsedLat, parsedLng, false);
        }

        map.on('click', function(e) {
            setPin(e.latlng.lat, e.latlng.lng, !addrInput.value.trim());
        });

        document.getElementById('btn-use-gps')?.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('GPS geolocation is not supported in this browser.');
                return;
            }
            searchStatus.classList.remove('hidden');
            searchStatus.textContent = 'Locating your GPS position...';
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    map.setView([lat, lng], 16);
                    setPin(lat, lng, true);
                    searchStatus.textContent = 'GPS location locked (±' + Math.round(pos.coords.accuracy) + 'm).';
                },
                function() {
                    searchStatus.textContent = 'Unable to get GPS fix. Tap directly on the map instead.';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });

        async function runAddressSearch() {
            var q = (searchInput.value || '').trim();
            if (!q) return;
            searchStatus.classList.remove('hidden');
            searchStatus.textContent = 'Searching location...';
            try {
                var resp = await fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(q));
                var rows = await resp.json();
                if (rows && rows.length > 0) {
                    var lat = parseFloat(rows[0].lat);
                    var lng = parseFloat(rows[0].lon);
                    map.setView([lat, lng], 16);
                    setPin(lat, lng, false);
                    if (!addrInput.value.trim()) {
                        addrInput.value = rows[0].display_name;
                    }
                    searchStatus.textContent = 'Pinned: ' + rows[0].display_name;
                } else {
                    searchStatus.textContent = 'Location not found. Try a broader street or city name.';
                }
            } catch (e) {
                searchStatus.textContent = 'Search unavailable right now. Tap on the map to place your pin.';
            }
        }

        document.getElementById('btn-map-search')?.addEventListener('click', runAddressSearch);
        searchInput?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runAddressSearch();
            }
        });

        // Prevent double-submit & handle BFCache restore cleanly
        var form = document.getElementById('public-request-form');
        var submitBtn = document.getElementById('btn-submit-request');
        var defaultSubmitHtml = submitBtn ? submitBtn.innerHTML : '';
        var isSubmitting = false;

        function resetSubmitState() {
            isSubmitting = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = defaultSubmitHtml;
            }
        }

        window.addEventListener('pageshow', function(ev) {
            if (ev.persisted || isSubmitting) {
                resetSubmitState();
            }
        });

        form?.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            var requiredIds = ['customer_name', 'customer_phone', 'description', 'address-input'];
            for (var i = 0; i < requiredIds.length; i++) {
                var field = document.getElementById(requiredIds[i]);
                if (field && !field.value.trim()) {
                    field.value = '';
                    field.focus();
                    e.preventDefault();
                    return;
                }
            }
            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<ion-icon name="sync-outline" class="animate-spin text-base"></ion-icon><span>Submitting Request...</span>';
            }
        });
    });
</script>
</body>
</html>
