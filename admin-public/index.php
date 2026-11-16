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

// ─── DEFENSIVE FALLBACK & DIAGNOSTIC PORTAL ──────────────────────────────────
// Prevent blank HTTP 500 when vendor packages or bootstrap are pending setup
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

    http_response_code(503);
    header('Retry-After: 300');

    if ($isApiRequest) {
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

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>FiberOps Clientzone Admin | Initializing Backend Services</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: #070a10;
                --card-bg: #0d121f;
                --primary: #00f2fe;
                --accent: #4facfe;
                --emerald: #10b981;
                --amber: #f59e0b;
                --text: #f8fafc;
                --text-muted: #94a3b8;
                --border: rgba(255, 255, 255, 0.08);
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: var(--bg);
                color: var(--text);
                font-family: 'Space Grotesk', -apple-system, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                position: relative;
                overflow-x: hidden;
            }
            .glow-bg {
                position: fixed;
                width: 600px;
                height: 600px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(0, 242, 254, 0.08) 0%, transparent 70%);
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
                z-index: 0;
            }
            .diag-card {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 40px;
                max-width: 640px;
                width: 100%;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
                position: relative;
                z-index: 1;
            }
            .diag-header {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 24px;
                padding-bottom: 20px;
                border-bottom: 1px solid var(--border);
            }
            .diag-badge {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background: rgba(245, 158, 11, 0.15);
                border: 1px solid var(--amber);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            }
            .diag-title h1 {
                font-family: 'Sora', sans-serif;
                font-size: 1.35rem;
                font-weight: 700;
                color: var(--text);
            }
            .diag-title p {
                font-size: 0.85rem;
                color: var(--text-muted);
            }
            .status-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 24px;
            }
            .status-row {
                background: rgba(255, 255, 255, 0.02);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 12px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.85rem;
            }
            .status-label { color: var(--text-muted); }
            .status-val { font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 0.8rem; }
            .val-ok { color: var(--emerald); }
            .val-pending { color: var(--amber); }
            .instructions-box {
                background: rgba(0, 0, 0, 0.4);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .instructions-box h4 {
                font-size: 0.82rem;
                color: var(--primary);
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .instructions-box code {
                display: block;
                background: #04060a;
                padding: 10px 14px;
                border-radius: 8px;
                font-family: 'JetBrains Mono', monospace;
                font-size: 0.78rem;
                color: #e2e8f0;
                overflow-x: auto;
                line-height: 1.6;
            }
            .actions-row {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }
            .btn {
                flex: 1;
                min-width: 140px;
                text-align: center;
                padding: 12px 20px;
                border-radius: 10px;
                font-size: 0.85rem;
                font-weight: 700;
                text-decoration: none;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            .btn-primary {
                background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
                color: #070a10;
            }
            .btn-secondary {
                background: rgba(255, 255, 255, 0.05);
                color: var(--text);
                border: 1px solid var(--border);
            }
            .btn:hover { transform: translateY(-2px); opacity: 0.95; }
            .btn-demo {
                background: rgba(16, 185, 129, 0.14);
                color: var(--emerald);
                border: 1px solid rgba(16, 185, 129, 0.45);
                cursor: pointer;
                width: 100%;
                margin-bottom: 12px;
            }
            .admin-demo-shell {
                display: none;
                max-width: 1120px;
                width: 100%;
                position: relative;
                z-index: 1;
                padding-bottom: 72px;
            }
            .admin-demo-shell.active { display: block; }
            .diag-card.hidden { display: none; }
            .demo-topbar {
                display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
                background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 16px 24px; margin-bottom: 20px;
            }
            .demo-kpi-grid {
                display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;
            }
            .demo-kpi-card {
                background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 18px;
            }
            .demo-kpi-card small { color: var(--text-muted); font-size: 0.78rem; }
            .demo-kpi-card strong { display: block; font-size: 1.7rem; font-family: 'Sora', sans-serif; margin-top: 4px; color: var(--primary); }
            .demo-table-card {
                background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 20px; overflow-x: auto;
            }
            .demo-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
            .demo-table th, .demo-table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); }
            .demo-table th { color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
            .pill { padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; display: inline-block; }
            .pill-ongoing { background: rgba(0,242,254,0.15); color: var(--primary); }
            .pill-assigned { background: rgba(245,158,11,0.15); color: var(--amber); }
            .pill-done { background: rgba(16,185,129,0.15); color: var(--emerald); }
        </style>
    </head>
    <body>
        <div class="glow-bg"></div>
        <div class="diag-card" id="diag-card">
            <div class="diag-header">
                <div class="diag-badge">⚙️</div>
                <div class="diag-title">
                    <h1>FiberOps Command Center</h1>
                    <p>Clientzone Admin &bull; Laravel 10 Backend Gateway</p>
                </div>
            </div>

            <div class="status-grid">
                <div class="status-row">
                    <span class="status-label">Domain Gateway</span>
                    <span class="status-val val-ok">admin.bentengsiber.com &bull; Online</span>
                </div>
                <div class="status-row">
                    <span class="status-label">Laravel 10 Core Directory</span>
                    <span class="status-val <?= $laravelCore ? 'val-ok' : 'val-pending' ?>">
                        <?= $laravelCore ? 'Detected &bull; laravel-core/' : 'Directory Missing' ?>
                    </span>
                </div>
                <div class="status-row">
                    <span class="status-label">Composer Autoload</span>
                    <span class="status-val <?= ($autoloadPath && file_exists($autoloadPath)) ? 'val-ok' : 'val-pending' ?>">
                        <?= ($autoloadPath && file_exists($autoloadPath)) ? 'Installed' : 'Pending `composer install`' ?>
                    </span>
                </div>
                <div class="status-row">
                    <span class="status-label">Environment Config</span>
                    <span class="status-val <?= ($laravelCore && file_exists($laravelCore . '/.env')) ? 'val-ok' : 'val-pending' ?>">
                        <?= ($laravelCore && file_exists($laravelCore . '/.env')) ? '.env Configured' : 'Awaiting .env Setup' ?>
                    </span>
                </div>
            </div>

            <div class="instructions-box">
                <h4>Server Provisioning Guide (cPanel Terminal / SSH)</h4>
                <code>cd ~/public_html/laravel-core<br>composer install --no-dev --optimize-autoloader<br>cp .env.example .env && php artisan key:generate</code>
            </div>

            <button type="button" class="btn btn-demo" id="btn-open-admin-demo">
                <span>👁️ Preview Admin Dashboard (Demo Testing Mode)</span>
            </button>

            <div class="actions-row">
                <a href="https://app.bentengsiber.com/?demo=1" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                    <span>Launch Tech PWA (Demo) &rarr;</span>
                </a>
                <a href="https://bentengsiber.com" class="btn btn-secondary">
                    <span>&larr; Return to Ecosystem</span>
                </a>
            </div>
        </div>

        <div class="admin-demo-shell" id="admin-demo-shell">
            <div class="demo-topbar">
                <div>
                    <h2 style="font-family:'Sora',sans-serif;font-size:1.15rem;">FiberOps Dispatcher Admin (Demo Testing Preview)</h2>
                    <p style="font-size:0.8rem;color:var(--text-muted);">Simulated Clientzone Admin view while cPanel MySQL &amp; Composer setup is pending</p>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="https://app.bentengsiber.com/?demo=1" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="flex:none;padding:9px 16px;">Tech PWA (Demo)</a>
                    <a href="https://bentengsiber.com" class="btn btn-secondary" style="flex:none;padding:9px 16px;">Ecosystem Home</a>
                    <button type="button" id="btn-close-admin-demo" class="btn btn-secondary" style="flex:none;padding:9px 16px;cursor:pointer;">Back to Server Status</button>
                </div>
            </div>

            <div class="demo-kpi-grid">
                <div class="demo-kpi-card"><small>Active Work Orders</small><strong>14</strong></div>
                <div class="demo-kpi-card"><small>Technicians Online (GPS)</small><strong style="color:var(--emerald);">6 / 8</strong></div>
                <div class="demo-kpi-card"><small>BAST Signed Today</small><strong style="color:var(--amber);">23</strong></div>
                <div class="demo-kpi-card"><small>ONT Stock Remaining</small><strong>35 units</strong></div>
            </div>

            <div class="demo-table-card">
                <h3 style="margin-bottom:14px;font-size:1rem;">Live Dispatch Queue (Demo Data)</h3>
                <table class="demo-table">
                    <thead>
                        <tr><th>Ticket</th><th>Customer</th><th>Technician</th><th>Status</th><th>Progress</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>#TSK-1042: Fiber Cut &amp; ONT Swap</td><td>Robert Vance</td><td>Alex Gunawan</td><td><span class="pill pill-ongoing">ON-GOING</span></td><td>60%</td></tr>
                        <tr><td>#TSK-1043: New FTTH Activation 100Mbps</td><td>Siti Rahmawati</td><td>Budi Santoso</td><td><span class="pill pill-assigned">ASSIGNED</span></td><td>0%</td></tr>
                        <tr><td>#TSK-1041: ODP Splice Re-Termination</td><td>PT Nusantara Cloud</td><td>Alex Gunawan</td><td><span class="pill pill-done">COMPLETED (BAST)</span></td><td>100%</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            (function() {
                var diag = document.getElementById('diag-card');
                var shell = document.getElementById('admin-demo-shell');
                var openBtn = document.getElementById('btn-open-admin-demo');
                var closeBtn = document.getElementById('btn-close-admin-demo');
                if (openBtn && diag && shell) {
                    openBtn.addEventListener('click', function() {
                        diag.classList.add('hidden');
                        shell.classList.add('active');
                    });
                }
                if (closeBtn && diag && shell) {
                    closeBtn.addEventListener('click', function() {
                        shell.classList.remove('active');
                        diag.classList.remove('hidden');
                    });
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
