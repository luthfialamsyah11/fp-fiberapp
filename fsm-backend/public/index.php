<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Self-Healing FTP Deploy Cache Sync (Pre-Bootstrap)
|--------------------------------------------------------------------------
*/
$laravelCore = dirname(__DIR__);
$deployCacheVersion = '2026-11-19-v5.9';
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
    foreach (['config.php', 'events.php'] as $cf) {
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

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
