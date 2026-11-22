<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    /**
     * Serve uploaded proof-of-work photos & customer e-signatures safely on shared hosting
     * with hardened MIME, privacy caching, noindex, and origin-restricted CORS headers.
     */
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        if ($cleanPath === '' || str_contains($cleanPath, '..') || str_contains($cleanPath, "\0")) {
            abort(404);
        }

        $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED_EXTENSIONS[$ext])) {
            abort(404);
        }

        $candidateRoots = array_filter([
            realpath(storage_path('app/public')),
            realpath(dirname(base_path()) . '/admin/storage'),
            realpath(base_path('../public_html/admin/storage')),
        ]);

        $origin = (string) $request->header('Origin', '');
        $allowedOrigins = array_filter([
            rtrim((string) config('app.url'), '/'),
            'https://admin.bentengsiber.com',
            'https://app.bentengsiber.com',
            'https://bentengsiber.com',
            'http://localhost:8100',
            'http://localhost:4200',
            'capacitor://localhost',
            'http://localhost',
        ]);
        $corsOrigin = in_array($origin, $allowedOrigins, true) ? $origin : 'https://app.bentengsiber.com';

        foreach ($candidateRoots as $root) {
            $candidate = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath));
            if ($candidate !== false && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) && is_file($candidate)) {
                $response = response()->file($candidate, [
                    'Content-Type'                => self::ALLOWED_EXTENSIONS[$ext],
                    'X-Content-Type-Options'      => 'nosniff',
                    'X-Robots-Tag'                => 'noindex, nofollow',
                    'Access-Control-Allow-Origin' => $corsOrigin,
                    'Vary'                        => 'Origin',
                ]);
                $response->setPrivate();
                $response->setMaxAge(86400);
                return $response;
            }
        }

        abort(404, 'Media file not found.');
    }
}
