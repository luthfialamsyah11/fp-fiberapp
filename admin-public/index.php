<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
| Laravel core is placed outside the public HTML webroot: /home/benp5534/laravel-core
|
*/

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
