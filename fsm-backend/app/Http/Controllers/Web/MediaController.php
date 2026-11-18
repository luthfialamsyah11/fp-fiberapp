<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];

    /**
     * Serve uploaded proof-of-work photos & customer e-signatures safely on shared hosting
     * even when symbolic links (public/storage) are unavailable on FTP deployments.
     */
    public function show(string $path): BinaryFileResponse
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

        foreach ($candidateRoots as $root) {
            $candidate = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath));
            if ($candidate !== false && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) && is_file($candidate)) {
                return response()->file($candidate, [
                    'Content-Type'                => self::ALLOWED_EXTENSIONS[$ext],
                    'Cache-Control'               => 'public, max-age=604800',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }
        }

        abort(404, 'Media file not found.');
    }
}
