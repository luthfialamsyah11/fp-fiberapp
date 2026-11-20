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

if (!$laravelCore) {
    http_response_code(500);
    die('Critical Error: Laravel core bootstrap not found. Please verify laravel-core deployment path.');
}

/*
|--------------------------------------------------------------------------
| Self-Healing FTP Deploy Cache Sync (Pre-Bootstrap)
|--------------------------------------------------------------------------
|
| Shared hosting FTP deployments update routes/*.php and resources/views/*
| without shell access to run `php artisan route:clear` or `view:clear`.
| Clearing stale bootstrap route/config/view caches BEFORE Laravel boots
| guarantees every new Admin/API route registers immediately (zero 404/500).
|
*/
$deployCacheVersion = '2026-11-20-v7.0-bright-auth-supervisor';
$stampFile = $laravelCore . '/storage/framework/deploy_cache.version';
$cacheDir = $laravelCore . '/bootstrap/cache';
$routeCacheFiles = glob($cacheDir . '/routes-*.php') ?: [];

$routesStale = false;
$webRoutesMtime = @filemtime($laravelCore . '/routes/web.php') ?: 0;
$apiRoutesMtime = @filemtime($laravelCore . '/routes/api.php') ?: 0;
$latestRouteMtime = max($webRoutesMtime, $apiRoutesMtime);

foreach ($routeCacheFiles as $rf) {
    if ((@filemtime($rf) ?: 0) <= $latestRouteMtime) {
        $routesStale = true;
        break;
    }
}

$versionStale = !file_exists($stampFile) || trim((string) @file_get_contents($stampFile)) !== $deployCacheVersion;

if ($versionStale || $routesStale) {
    foreach ($routeCacheFiles as $rf) {
        @unlink($rf);
    }
    foreach (['config.php', 'events.php', 'packages.php', 'services.php'] as $cf) {
        if (file_exists($cacheDir . '/' . $cf)) {
            @unlink($cacheDir . '/' . $cf);
        }
    }
    $compiledViews = glob($laravelCore . '/storage/framework/views/*.php') ?: [];
    foreach ($compiledViews as $vf) {
        @unlink($vf);
    }
    @file_put_contents($stampFile, $deployCacheVersion);
}

if (file_exists($maintenance = $laravelCore . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelCore . '/vendor/autoload.php';

$app = require_once $laravelCore . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
