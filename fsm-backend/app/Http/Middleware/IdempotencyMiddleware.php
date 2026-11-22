<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Prevent duplicate mutations when clients retry or double-tap simultaneously
     * with the same Idempotency-Key header (protected by atomic Cache::lock).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = trim((string) $request->header('Idempotency-Key', ''));
        if ($key === '' || strlen($key) > 128) {
            return $next($request);
        }

        if (!Schema::hasTable('idempotency_keys')) {
            return $next($request);
        }

        $userId = auth()->id() ?? 0;
        $tenantId = TenantContext::id() ?? 0;
        $method = $request->method();
        $path = $request->path();

        $executeIdempotently = function () use ($key, $userId, $method, $path, $request, $next): Response {
            $existing = DB::table('idempotency_keys')
                ->where('idempotency_key', $key)
                ->where('user_id', $userId)
                ->where('method', $method)
                ->where('path', $path)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existing) {
                return response($existing->response_body, (int) $existing->response_code)
                    ->header('Content-Type', 'application/json')
                    ->header('X-Idempotent-Replay', 'true');
            }

            /** @var Response $response */
            $response = $next($request);

            // Cache successful 2xx responses for idempotent replay
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                try {
                    if (random_int(1, 50) === 1) {
                        DB::table('idempotency_keys')->where('created_at', '<', now()->subHours(48))->delete();
                    }

                    DB::table('idempotency_keys')->updateOrInsert(
                        [
                            'idempotency_key' => $key,
                            'user_id'         => $userId,
                            'method'          => $method,
                            'path'            => $path,
                        ],
                        [
                            'response_code' => $response->getStatusCode(),
                            'response_body' => (string) $response->getContent(),
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]
                    );
                } catch (\Throwable $e) {
                    // Ignore race condition on simultaneous identical insert
                }
            }

            return $response;
        };

        $lockKey = 'idem_lock:' . sha1("{$tenantId}:{$userId}:{$method}:{$path}:{$key}");
        try {
            return Cache::lock($lockKey, 10)->block(5, $executeIdempotently);
        } catch (\BadMethodCallException $e) {
            // Graceful fallback for cache stores without atomic lock support
            return $executeIdempotently();
        }
    }
}
