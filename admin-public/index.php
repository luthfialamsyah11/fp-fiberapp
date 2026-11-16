<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Locate Laravel Core Bootstrap
|--------------------------------------------------------------------------
|
| Dynamically detect whether Laravel core is placed inside public_html
| (/public_html/laravel-core) or above it (/home/username/laravel-core).
|
*/

$coreCandidates = [
    __DIR__ . '/../laravel-core',
    __DIR__ . '/../../laravel-core',
    dirname(__DIR__) . '/laravel-core',
    dirname(__DIR__, 2) . '/laravel-core',
];

$laravelCore = null;
foreach ($coreCandidates as $candidate) {
    if (file_exists($candidate . '/bootstrap/app.php')) {
        $laravelCore = $candidate;
        break;
    }
}

// ─── DEFENSIVE FALLBACK & INTERACTIVE ADMIN DEMO PORTAL ──────────────────────
// Prevent blank HTTP 500 when vendor packages or bootstrap are pending setup,
// and provide a full interactive Admin Login & Dashboard Demo for testing.
$autoloadPath = $laravelCore ? $laravelCore . '/vendor/autoload.php' : null;
$envMissing = $laravelCore && !file_exists($laravelCore . '/.env');

if (!$laravelCore || !file_exists($autoloadPath) || $envMissing) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [
        'https://app.bentengsiber.com',
        'https://admin.bentengsiber.com',
        'https://bentengsiber.com',
        'http://localhost:8100',
        'http://127.0.0.1:8100',
        'http://localhost:4200',
    ];
    if (in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, ngrok-skip-browser-warning');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isApiRequest = str_starts_with($requestUri, '/api') || str_contains($acceptHeader, 'application/json');

    if ($isApiRequest) {
        http_response_code(503);
        header('Retry-After: 300');
        header('Content-Type: application/json; charset=utf-8');
        $reason = !$laravelCore
            ? 'Direktori laravel-core tidak ditemukan.'
            : (!file_exists($autoloadPath)
                ? 'Dependensi Laravel (vendor/autoload.php) belum terpasang di cPanel.'
                : 'Konfigurasi .env & database Laravel belum dibuat di cPanel.');
        echo json_encode([
            'message' => 'Backend API sedang dalam provisioning: ' . $reason,
            'code' => 'BACKEND_NOT_PROVISIONED',
        ]);
        exit;
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FiberOps Clientzone Admin | Login &amp; Command Center</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: #050507;
                --card: #0c0e14;
                --panel: #12151f;
                --primary: #22d3ee;
                --emerald: #10b981;
                --amber: #f59e0b;
                --danger: #ef4444;
                --text: #f8fafc;
                --muted: #94a3b8;
                --border: rgba(255, 255, 255, 0.08);
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: var(--bg);
                color: var(--text);
                font-family: 'Space Grotesk', -apple-system, sans-serif;
                min-height: 100vh;
            }
            .hidden { display: none !important; }
            .mono { font-family: 'JetBrains Mono', monospace; }

            /* ─── LOGIN VIEW ─── */
            .login-wrap {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }
            .login-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 36px;
                max-width: 440px;
                width: 100%;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.75);
            }
            .brand-row {
                display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
            }
            .brand-icon {
                width: 42px; height: 42px; border-radius: 10px;
                background: rgba(34, 211, 238, 0.12); border: 1px solid rgba(34, 211, 238, 0.35);
                display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--primary);
            }
            .brand-row h1 { font-family: 'Sora', sans-serif; font-size: 1.2rem; }
            .brand-row p { font-size: 0.78rem; color: var(--muted); }
            .status-banner {
                background: rgba(245, 158, 11, 0.1);
                border: 1px solid rgba(245, 158, 11, 0.3);
                border-radius: 10px;
                padding: 10px 12px;
                font-size: 0.76rem;
                color: #fde68a;
                margin-bottom: 20px;
                line-height: 1.45;
            }
            .form-group { margin-bottom: 14px; }
            .form-group label {
                display: block; font-size: 0.78rem; color: var(--muted); margin-bottom: 6px; font-weight: 600;
            }
            .form-input {
                width: 100%; padding: 11px 14px; border-radius: 10px;
                background: #07090d; border: 1px solid var(--border); color: var(--text);
                font-size: 0.9rem; outline: none;
            }
            .form-input:focus { border-color: var(--primary); }
            .btn {
                display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                padding: 11px 16px; border-radius: 10px; font-size: 0.85rem; font-weight: 700;
                border: none; cursor: pointer; text-decoration: none; transition: all 0.15s ease;
            }
            .btn:hover { opacity: 0.92; transform: translateY(-1px); }
            .btn-white { background: #ffffff; color: #050507; width: 100%; }
            .btn-cyan {
                background: rgba(34, 211, 238, 0.12); color: var(--primary);
                border: 1px dashed rgba(34, 211, 238, 0.45); width: 100%; margin-top: 10px;
            }
            .btn-outline {
                background: rgba(255,255,255,0.04); color: var(--text); border: 1px solid var(--border);
            }
            .login-error {
                background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger);
                color: #fca5a5; font-size: 0.78rem; padding: 8px 12px; border-radius: 8px; margin-bottom: 14px;
            }
            .login-links {
                margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border);
                display: flex; justify-content: space-between; font-size: 0.78rem;
            }
            .login-links a { color: var(--muted); text-decoration: none; }
            .login-links a:hover { color: var(--primary); }

            /* ─── ADMIN DASHBOARD SHELL ─── */
            .admin-shell { display: flex; min-height: 100vh; }
            .sidebar {
                width: 250px; background: var(--card); border-right: 1px solid var(--border);
                padding: 20px 14px; display: flex; flex-direction: column; flex-shrink: 0;
            }
            .nav-item {
                display: flex; align-items: center; gap: 10px; width: 100%;
                padding: 10px 12px; border-radius: 8px; background: transparent; border: none;
                color: var(--muted); font-size: 0.85rem; font-weight: 600; cursor: pointer;
                text-align: left; margin-bottom: 4px; transition: all 0.15s ease;
            }
            .nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
            .nav-item.active { background: rgba(34, 211, 238, 0.12); color: var(--primary); border: 1px solid rgba(34, 211, 238, 0.25); }
            .main-area { flex: 1; display: flex; flex-direction: column; min-width: 0; }
            .topbar {
                background: var(--card); border-bottom: 1px solid var(--border);
                padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            }
            .demo-pill {
                background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.4);
                color: var(--emerald); padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700;
            }
            .content-pad { padding: 24px; max-width: 1200px; width: 100%; }
            .kpi-grid {
                display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;
            }
            .kpi-card {
                background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px;
            }
            .kpi-card small { color: var(--muted); font-size: 0.76rem; display: block; }
            .kpi-card strong { font-size: 1.7rem; font-family: 'Sora', sans-serif; display: block; margin-top: 6px; }
            .panel-card {
                background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 20px; overflow-x: auto;
            }
            .panel-header {
                display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;
            }
            table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
            th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); }
            th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; }
            .badge { padding: 3px 9px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; display: inline-block; }
            .b-cyan { background: rgba(34,211,238,0.15); color: var(--primary); }
            .b-amber { background: rgba(245,158,11,0.15); color: var(--amber); }
            .b-green { background: rgba(16,185,129,0.15); color: var(--emerald); }
            .code-box {
                background: #04060a; border: 1px solid var(--border); border-radius: 10px; padding: 14px;
                font-size: 0.78rem; color: #e2e8f0; line-height: 1.7; overflow-x: auto;
            }
            @media (max-width: 820px) {
                .admin-shell { flex-direction: column; }
                .sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); flex-direction: row; overflow-x: auto; padding: 10px; gap: 6px; }
                .sidebar-brand, .sidebar-footer { display: none; }
                .nav-item { width: auto; white-space: nowrap; margin-bottom: 0; }
            }
        </style>
    </head>
    <body>

        <!-- ═══ 1. ADMIN LOGIN VIEW ═══ -->
        <div class="login-wrap" id="admin-login-view">
            <div class="login-card">
                <div class="brand-row">
                    <div class="brand-icon">🛡️</div>
                    <div>
                        <h1>FiberOps Admin Portal</h1>
                        <p>Clientzone NOC &amp; Dispatcher Command</p>
                    </div>
                </div>

                <div class="status-banner">
                    <strong>Demo Testing Mode:</strong> Backend MySQL/Composer di cPanel belum diaktifkan. Anda tetap bisa login menggunakan akun demo di bawah untuk menguji seluruh UI Admin Dashboard.
                </div>

                <div class="login-error hidden" id="login-error">Email atau password demo tidak cocok. Gunakan <strong>admin@fsm.com</strong> / <strong>password123</strong>.</div>

                <form id="admin-login-form">
                    <div class="form-group">
                        <label for="admin-email">Email Administrator</label>
                        <input type="email" id="admin-email" class="form-input" value="admin@fsm.com" required>
                    </div>
                    <div class="form-group">
                        <label for="admin-pass">Password</label>
                        <input type="password" id="admin-pass" class="form-input" value="password123" required>
                    </div>
                    <button type="submit" class="btn btn-white">Masuk ke Admin Dashboard &rarr;</button>
                </form>

                <button type="button" class="btn btn-cyan" id="btn-direct-admin-demo">
                    ⚡ 1-Click Direct Demo Admin (Testing)
                </button>

                <div class="login-links">
                    <a href="https://bentengsiber.com">&larr; Landing Page</a>
                    <a href="https://app.bentengsiber.com/?demo=1" target="_blank" rel="noopener noreferrer">Tech PWA (Demo) &rarr;</a>
                </div>
            </div>
        </div>

        <!-- ═══ 2. ADMIN COMMAND CENTER DASHBOARD (DEMO MODE) ═══ -->
        <div class="admin-shell hidden" id="admin-dashboard-view">
            <aside class="sidebar">
                <div class="sidebar-brand" style="padding: 6px 10px 18px; border-bottom: 1px solid var(--border); margin-bottom: 14px;">
                    <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1rem;color:var(--primary);">FiberOps NOC</div>
                    <div class="mono" style="font-size:0.68rem;color:var(--muted);">ADMIN COMMAND v4.2</div>
                </div>

                <button type="button" class="nav-item active" data-admin-tab="overview">📊 Dashboard Overview</button>
                <button type="button" class="nav-item" data-admin-tab="tasks">📋 Work Orders (Tickets)</button>
                <button type="button" class="nav-item" data-admin-tab="technicians">👷 Technicians &amp; GPS</button>
                <button type="button" class="nav-item" data-admin-tab="inventory">📦 BOM &amp; Inventory</button>
                <button type="button" class="nav-item" data-admin-tab="server">⚙️ Server Setup (cPanel)</button>

                <div class="sidebar-footer" style="margin-top:auto;padding-top:14px;border-top:1px solid var(--border);">
                    <button type="button" id="btn-admin-logout" class="nav-item" style="color:#fca5a5;">🚪 Log Out Demo</button>
                </div>
            </aside>

            <main class="main-area">
                <header class="topbar">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span class="demo-pill">● DEMO TESTING SESSION: admin@fsm.com</span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="https://app.bentengsiber.com/?demo=1" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding:7px 12px;font-size:0.78rem;">Open Tech PWA (Demo)</a>
                        <a href="https://bentengsiber.com" class="btn btn-outline" style="padding:7px 12px;font-size:0.78rem;">Landing Page</a>
                        <button type="button" id="btn-mobile-logout" class="btn btn-outline" style="padding:7px 12px;font-size:0.78rem;color:#fca5a5;">Logout</button>
                    </div>
                </header>

                <div class="content-pad">
                    <!-- TAB: OVERVIEW -->
                    <section class="admin-tab-panel" id="tab-overview">
                        <div class="kpi-grid">
                            <div class="kpi-card"><small>Active Work Orders</small><strong style="color:var(--primary);" id="kpi-active-tasks">2</strong></div>
                            <div class="kpi-card"><small>Technicians Online (GPS)</small><strong style="color:var(--emerald);">3 / 4</strong></div>
                            <div class="kpi-card"><small>BAST Signed Today</small><strong style="color:var(--amber);">14</strong></div>
                            <div class="kpi-card"><small>ONT Stock Available</small><strong>35 units</strong></div>
                        </div>

                        <div class="panel-card">
                            <div class="panel-header">
                                <h3>Recent Live Dispatch Queue</h3>
                                <button type="button" class="btn btn-white" style="width:auto;padding:8px 14px;font-size:0.78rem;" onclick="switchAdminTab('tasks')">Manage All Tickets &rarr;</button>
                            </div>
                            <table>
                                <thead><tr><th>Work Order</th><th>Customer</th><th>Technician</th><th>Status</th><th>Progress</th></tr></thead>
                                <tbody id="overview-tasks-body"></tbody>
                            </table>
                        </div>
                    </section>

                    <!-- TAB: WORK ORDERS -->
                    <section class="admin-tab-panel hidden" id="tab-tasks">
                        <div class="panel-card">
                            <div class="panel-header">
                                <h3>Work Orders Management</h3>
                                <button type="button" class="btn btn-white" style="width:auto;padding:8px 14px;font-size:0.78rem;" id="btn-add-demo-ticket">+ Create New Ticket</button>
                            </div>
                            <table>
                                <thead><tr><th>Ticket</th><th>Customer &amp; Address</th><th>Assigned Tech</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody id="full-tasks-body"></tbody>
                            </table>
                        </div>
                    </section>

                    <!-- TAB: TECHNICIANS -->
                    <section class="admin-tab-panel hidden" id="tab-technicians">
                        <div class="panel-card">
                            <div class="panel-header"><h3>Field Technicians &amp; Live GPS Telemetry</h3></div>
                            <table>
                                <thead><tr><th>Name</th><th>Email</th><th>Specialty</th><th>GPS Coordinates</th><th>Status</th></tr></thead>
                                <tbody>
                                    <tr><td>Alex Gunawan</td><td class="mono">alex@fsm.com</td><td>Fiber Cut &amp; ONT</td><td class="mono">-6.2615, 106.8106</td><td><span class="badge b-green">ONLINE • EN ROUTE</span></td></tr>
                                    <tr><td>Budi Santoso</td><td class="mono">budi@fsm.com</td><td>FTTH Activation</td><td class="mono">-6.2542, 106.8195</td><td><span class="badge b-green">ONLINE • STANDBY</span></td></tr>
                                    <tr><td>Citra Lestari</td><td class="mono">citra@fsm.com</td><td>Backbone ODP</td><td class="mono">-6.2321, 106.8315</td><td><span class="badge b-cyan">ONLINE • ON-GOING</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- TAB: INVENTORY -->
                    <section class="admin-tab-panel hidden" id="tab-inventory">
                        <div class="panel-card">
                            <div class="panel-header"><h3>Warehouse BOM &amp; Stock Ledger</h3></div>
                            <table>
                                <thead><tr><th>SKU Code</th><th>Item Name</th><th>Category</th><th>Remaining Stock</th><th>Action</th></tr></thead>
                                <tbody id="inventory-body"></tbody>
                            </table>
                        </div>
                    </section>

                    <!-- TAB: SERVER SETUP -->
                    <section class="admin-tab-panel hidden" id="tab-server">
                        <div class="panel-card">
                            <div class="panel-header"><h3>cPanel Backend Activation Status</h3></div>
                            <div style="display:grid;gap:10px;margin-bottom:18px;font-size:0.84rem;">
                                <div>Laravel Core Path: <span class="mono" style="color:<?= $laravelCore ? 'var(--emerald)' : 'var(--amber)' ?>"><?= $laravelCore ? 'Detected (laravel-core/)' : 'Missing' ?></span></div>
                                <div>Composer Vendor: <span class="mono" style="color:<?= ($autoloadPath && file_exists($autoloadPath)) ? 'var(--emerald)' : 'var(--amber)' ?>"><?= ($autoloadPath && file_exists($autoloadPath)) ? 'Installed' : 'Pending composer install' ?></span></div>
                                <div>Database .env: <span class="mono" style="color:<?= ($laravelCore && file_exists($laravelCore . '/.env')) ? 'var(--emerald)' : 'var(--amber)' ?>"><?= ($laravelCore && file_exists($laravelCore . '/.env')) ? 'Configured' : 'Awaiting .env & MySQL DB' ?></span></div>
                            </div>
                            <p style="font-size:0.82rem;color:var(--muted);margin-bottom:10px;">Jalankan perintah berikut di <strong>cPanel Terminal</strong> setelah membuat database MySQL untuk mengaktifkan mode produksi penuh:</p>
                            <pre class="code-box mono">cd ~/public_html/laravel-core
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
php artisan migrate --seed --force</pre>
                        </div>
                    </section>
                </div>
            </main>
        </div>

        <script>
            (function() {
                const loginView = document.getElementById('admin-login-view');
                const dashView = document.getElementById('admin-dashboard-view');
                const loginForm = document.getElementById('admin-login-form');
                const loginErr = document.getElementById('login-error');
                const directBtn = document.getElementById('btn-direct-admin-demo');

                let demoTasks = [
                    { id: 1042, title: '#TSK-1042: Fiber Cut & ONT Swap', customer: 'Robert Vance • Kemang Selatan', tech: 'Alex Gunawan', status: 'on-going', progress: 60 },
                    { id: 1043, title: '#TSK-1043: New FTTH Activation 100Mbps', customer: 'Siti Rahmawati • Jl. Bangka', tech: 'Budi Santoso', status: 'assigned', progress: 0 },
                    { id: 1041, title: '#TSK-1041: ODP Splice Re-Termination', customer: 'PT Nusantara Cloud • Kuningan', tech: 'Citra Lestari', status: 'completed', progress: 100 }
                ];

                let demoStock = [
                    { code: 'ONT-HUAWEI-HG8245H5', name: 'Huawei EchoLife HG8245H5 ONT', cat: 'Device', qty: 35, unit: 'units' },
                    { code: 'CBL-DROP-1C', name: 'Fiber Drop Core 1C Fig-8', cat: 'Material', qty: 1240, unit: 'meters' },
                    { code: 'CONN-SC-UPC', name: 'Fast Connector SC/UPC', cat: 'Material', qty: 250, unit: 'pcs' }
                ];

                function statusBadge(st) {
                    if (st === 'completed') return '<span class="badge b-green">COMPLETED</span>';
                    if (st === 'on-going') return '<span class="badge b-cyan">ON-GOING</span>';
                    return '<span class="badge b-amber">ASSIGNED</span>';
                }

                function renderTables() {
                    const ov = document.getElementById('overview-tasks-body');
                    const full = document.getElementById('full-tasks-body');
                    const inv = document.getElementById('inventory-body');
                    const activeCount = document.getElementById('kpi-active-tasks');

                    if (activeCount) activeCount.textContent = demoTasks.filter(t => t.status !== 'completed').length;

                    if (ov) {
                        ov.innerHTML = demoTasks.map(t => `<tr><td><strong>${t.title}</strong></td><td>${t.customer}</td><td>${t.tech}</td><td>${statusBadge(t.status)}</td><td>${t.progress}%</td></tr>`).join('');
                    }
                    if (full) {
                        full.innerHTML = demoTasks.map((t, idx) => `<tr>
                            <td><strong>${t.title}</strong></td>
                            <td>${t.customer}</td>
                            <td>${t.tech}</td>
                            <td>${statusBadge(t.status)}</td>
                            <td><button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:0.72rem;" onclick="advanceDemoTask(${idx})">${t.status === 'completed' ? 'Done ✓' : 'Advance Stage →'}</button></td>
                        </tr>`).join('');
                    }
                    if (inv) {
                        inv.innerHTML = demoStock.map((s, idx) => `<tr>
                            <td class="mono">${s.code}</td>
                            <td>${s.name}</td>
                            <td>${s.cat}</td>
                            <td><strong style="color:var(--primary);">${s.qty}</strong> ${s.unit}</td>
                            <td><button type="button" class="btn btn-outline" style="padding:4px 10px;font-size:0.72rem;" onclick="restockDemo(${idx})">+10 Restock</button></td>
                        </tr>`).join('');
                    }
                }

                window.advanceDemoTask = function(idx) {
                    const t = demoTasks[idx];
                    if (!t) return;
                    if (t.status === 'assigned') { t.status = 'on-going'; t.progress = 50; }
                    else if (t.status === 'on-going') { t.status = 'completed'; t.progress = 100; }
                    renderTables();
                };

                window.restockDemo = function(idx) {
                    demoStock[idx].qty += 10;
                    renderTables();
                };

                window.switchAdminTab = function(tabName) {
                    document.querySelectorAll('.admin-tab-panel').forEach(p => p.classList.add('hidden'));
                    document.querySelectorAll('[data-admin-tab]').forEach(b => b.classList.remove('active'));
                    const panel = document.getElementById('tab-' + tabName);
                    const btn = document.querySelector('[data-admin-tab="' + tabName + '"]');
                    if (panel) panel.classList.remove('hidden');
                    if (btn) btn.classList.add('active');
                };

                function enterAdminDemo() {
                    sessionStorage.setItem('admin_demo_auth', '1');
                    loginView.classList.add('hidden');
                    dashView.classList.remove('hidden');
                    renderTables();
                }

                function logoutAdminDemo() {
                    sessionStorage.removeItem('admin_demo_auth');
                    dashView.classList.add('hidden');
                    loginView.classList.remove('hidden');
                }

                if (loginForm) {
                    loginForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const em = (document.getElementById('admin-email').value || '').trim().toLowerCase();
                        const pw = document.getElementById('admin-pass').value || '';
                        if (em === 'admin@fsm.com' && pw === 'password123') {
                            loginErr.classList.add('hidden');
                            enterAdminDemo();
                        } else {
                            loginErr.classList.remove('hidden');
                        }
                    });
                }

                if (directBtn) directBtn.addEventListener('click', enterAdminDemo);
                document.getElementById('btn-admin-logout')?.addEventListener('click', logoutAdminDemo);
                document.getElementById('btn-mobile-logout')?.addEventListener('click', logoutAdminDemo);

                document.querySelectorAll('[data-admin-tab]').forEach(btn => {
                    btn.addEventListener('click', () => window.switchAdminTab(btn.getAttribute('data-admin-tab')));
                });

                document.getElementById('btn-add-demo-ticket')?.addEventListener('click', () => {
                    const num = 1044 + demoTasks.length;
                    demoTasks.unshift({
                        id: num,
                        title: '#TSK-' + num + ': Urgent ODP Signal Loss Check',
                        customer: 'New Fiber Subscriber • Jakarta',
                        tech: 'Alex Gunawan',
                        status: 'assigned',
                        progress: 0
                    });
                    renderTables();
                });

                if (sessionStorage.getItem('admin_demo_auth') === '1' || new URLSearchParams(window.location.search).get('demo') === '1') {
                    enterAdminDemo();
                }
            })();
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ─── NORMAL LARAVEL 10 EXECUTION ─────────────────────────────────────────────
if (file_exists($maintenance = $laravelCore . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $autoloadPath;

$app = require_once $laravelCore . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
