<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Reserved subdomains that do not represent an ISP tenant.
     */
    protected array $reservedSubdomains = [
        'www',
        'api',
        'app',
        'admin',
        'mail',
        'cpanel',
        'webmail',
        'localhost',
    ];

    /**
     * Handle an incoming request and resolve the active tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        try {
            // 1. Check Authenticated User (Web Session or Sanctum Bearer Token) without stale tenant bias
            $user = TenantContext::withoutTenant(function () use ($request) {
                return $request->user() ?? Auth::user() ?? Auth::guard('sanctum')->user();
            });
            if ($user && !empty($user->tenant_id)) {
                $tenant = Tenant::find($user->tenant_id);
            }

            // 2. Check Explicit Request Headers (Slug allowed for active tenants; sequential integer ID restricted to non-production)
            if (!$tenant && $request->hasHeader('X-Tenant-Slug')) {
                $slug = strtolower(trim((string) $request->header('X-Tenant-Slug')));
                if ($slug !== '' && preg_match('/^[a-z0-9\-]{2,50}$/', $slug)) {
                    $tenant = Tenant::where('slug', $slug)->first();
                }
            }

            if (!$tenant && !app()->environment('production')) {
                if ($request->hasHeader('X-Tenant-ID') && is_numeric($request->header('X-Tenant-ID'))) {
                    $tenant = Tenant::find((int) $request->header('X-Tenant-ID'));
                }

                // 3. Query Parameter (Development & Testing only — blocks anonymous tenant spoofing in production)
                if (!$tenant && $request->filled('tenant')) {
                    $qTenant = (string) $request->query('tenant');
                    $tenant = Tenant::where('slug', $qTenant)
                        ->orWhere('id', is_numeric($qTenant) ? (int) $qTenant : 0)
                        ->first();
                }
            }

            // 4. Check Subdomain / Host Resolution
            if (!$tenant) {
                $host = $request->getHost();
                
                // Check custom domain first
                $tenant = Tenant::where('custom_domain', $host)->first();

                // Check subdomain
                if (!$tenant) {
                    $parts = explode('.', $host);
                    if (count($parts) >= 3) {
                        $subdomain = strtolower($parts[0]);
                        if (!in_array($subdomain, $this->reservedSubdomains, true)) {
                            $tenant = Tenant::where('slug', $subdomain)->first();
                        }
                    }
                }
            }

            // 5. Fallback to Default Tenant (for 100% backward compatibility)
            if (!$tenant) {
                $tenant = Tenant::find(1) ?? Tenant::first();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Tenant resolution fallback triggered: " . $e->getMessage());
        }

        // Bind resolved tenant to context and share with views
        TenantContext::setTenant($tenant);

        if ($tenant && function_exists('view')) {
            view()->share('currentTenant', $tenant);
        }

        return $next($request);
    }
}
