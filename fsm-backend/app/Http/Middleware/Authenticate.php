<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        if ($request->user() && !$request->user()->is_active) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Akun tidak aktif'], 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan.',
            ]);
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }
        return route('admin.login');
    }
}
