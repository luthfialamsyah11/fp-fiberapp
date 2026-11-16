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
        </style>
    </head>
    <body>
        <div class="glow-bg"></div>
        <div class="diag-card">
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
                    <span class="status-val val-pending">Pending `composer install`</span>
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

            <div class="actions-row">
                <a href="https://app.bentengsiber.com" class="btn btn-primary" target="_blank" rel="noopener">
                    <span>Launch Technician PWA &rarr;</span>
                </a>
                <a href="https://bentengsiber.com" class="btn btn-secondary">
                    <span>&larr; Return to Ecosystem</span>
                </a>
            </div>
        </div>
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
