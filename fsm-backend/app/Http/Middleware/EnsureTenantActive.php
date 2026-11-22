<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Ensure the tenant's subscription or trial is currently active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::getTenant();

        if ($tenant) {
            if ($tenant->status === 'suspended') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Akun organisasi ISP Anda sedang ditangguhkan. Silakan hubungi dukungan Benteng Siber.',
                        'status'  => 'suspended',
                    ], 403);
                }

                abort(403, 'Akun organisasi ISP Anda sedang ditangguhkan. Silakan hubungi administrator platform.');
            }

            if (!$tenant->isActive()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Masa uji coba / langganan paket ISP Anda telah berakhir. Silakan lakukan perpanjangan.',
                        'status'  => 'expired',
                    ], 403);
                }

                abort(403, 'Masa uji coba gratis 14 hari atau langganan ISP Anda telah berakhir. Silakan hubungi tim sales kami untuk aktivasi paket.');
            }
        }

        return $next($request);
    }
}
