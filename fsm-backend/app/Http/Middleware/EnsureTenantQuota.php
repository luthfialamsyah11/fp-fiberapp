<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantQuota
{
    /**
     * Enforce technician and fiber node limits per subscription plan.
     */
    public function handle(Request $request, Closure $next, string $resource = 'technician'): Response
    {
        $tenant = TenantContext::getTenant();

        if ($tenant) {
            if ($resource === 'technician' && !$tenant->canAddTechnician()) {
                $msg = "Batas kuota teknisi paket {$tenant->plan} ({$tenant->max_technicians} teknisi) telah tercapai. Tingkatkan paket langganan Anda ke Growth atau Enterprise.";
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $msg,
                        'quota_exceeded' => true,
                    ], 422);
                }

                return back()->with('error', $msg);
            }

            if ($resource === 'fiber_node' && !$tenant->canAddFiberNode()) {
                $msg = "Batas kuota aset fiber ODP/ODC paket {$tenant->plan} ({$tenant->max_fiber_nodes} node) telah tercapai. Tingkatkan paket Anda ke Enterprise.";
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $msg,
                        'quota_exceeded' => true,
                    ], 422);
                }

                return back()->with('error', $msg);
            }
        }

        return $next($request);
    }
}
