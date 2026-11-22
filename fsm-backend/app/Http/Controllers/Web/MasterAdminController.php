<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class MasterAdminController extends Controller
{
    /**
     * Platform Master Admin Overview: List all registered ISPs and national metrics.
     */
    public function index()
    {
        return TenantContext::withoutTenant(function () {
            $tenants = Tenant::withCount([
                'users as technicians_count' => function ($query) {
                    $query->where('role', 'technician');
                },
                'tasks',
                'fiberNodes',
            ])->latest()->get();

            $metrics = [
                'total_isps'        => $tenants->count(),
                'active_isps'       => $tenants->whereIn('status', ['active', 'trial'])->count(),
                'total_technicians' => User::where('role', 'technician')->count(),
                'total_tasks'       => Task::count(),
            ];

            return view('master_admin.index', compact('tenants', 'metrics'));
        });
    }

    /**
     * Toggle ISP tenant status (active vs suspended).
     */
    public function toggleStatus(Tenant $tenant)
    {
        if ($tenant->id === 1) {
            return back()->with('error', 'Tenant default sistem tidak dapat ditangguhkan.');
        }

        $newStatus = $tenant->status === 'suspended' ? 'active' : 'suspended';
        $tenant->update(['status' => $newStatus]);

        $statusLabel = $newStatus === 'active' ? 'diaktifkan kembali' : 'ditangguhkan (suspended)';
        return back()->with('success', "Status ISP '{$tenant->name}' berhasil {$statusLabel}.");
    }

    /**
     * Update ISP subscription plan & quota limits.
     */
    public function updatePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan' => 'required|in:starter,growth,enterprise',
        ]);

        $planLimits = [
            'starter'    => ['techs' => 5, 'nodes' => 50],
            'growth'     => ['techs' => 20, 'nodes' => 250],
            'enterprise' => ['techs' => 9999, 'nodes' => 9999],
        ];

        $limits = $planLimits[$validated['plan']] ?? $planLimits['starter'];

        $tenant->update([
            'plan'                 => $validated['plan'],
            'max_technicians'      => $limits['techs'],
            'max_fiber_nodes'      => $limits['nodes'],
            'status'               => 'active',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        return back()->with('success', "Paket ISP '{$tenant->name}' berhasil diubah ke " . ucfirst($validated['plan']) . ".");
    }
}
