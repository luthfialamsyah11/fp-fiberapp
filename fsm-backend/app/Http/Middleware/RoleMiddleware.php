<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $allowed = [];
        foreach ($roles as $chunk) {
            foreach (explode(',', $chunk) as $r) {
                $r = trim($r);
                if ($r !== '') {
                    $allowed[] = $r;
                }
            }
        }

        $user = $request->user();
        if (!$user || !$user->hasAnyRole($allowed)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Access denied.'], 403);
            }
            abort(403, 'Access denied: You are not authorized to access this page.');
        }

        return $next($request);
    }
}