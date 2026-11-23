<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MasterAdminController extends Controller
{
    /**
     * Strictly enforce Platform Owner authorization (Root Tenant ID 1 only).
     * Prevents tenant-level super_admins (tenant_id > 1) from escalating into global master console.
     */
    protected function authorizePlatformOwner(): void
    {
        $user = auth()->user();
        $isRootTenant = $user && ($user->tenant_id === null || (int) $user->tenant_id === 1);

        if (!$user || !$user->isSuperAdmin() || !$isRootTenant) {
            abort(403, 'Access denied: Platform Master Console is restricted to root platform administrators.');
        }
    }

    /**
     * Platform Master Admin Overview: List all registered ISPs and national metrics.
     */
    public function index()
    {
        $this->authorizePlatformOwner();

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

            $auditLogs = Schema::hasTable('platform_audit_logs')
                ? PlatformAuditLog::orderByDesc('id')->take(25)->get()
                : collect();
            $auditIntegrity = Schema::hasTable('platform_audit_logs')
                ? PlatformAuditLog::verifyChainIntegrity()
                : ['valid' => true, 'checked' => 0, 'broken_id' => null];

            return view('master_admin.index', compact('tenants', 'metrics', 'auditLogs', 'auditIntegrity'));
        });
    }

    /**
     * Toggle ISP tenant status (active vs suspended).
     */
    public function toggleStatus(Tenant $tenant)
    {
        $this->authorizePlatformOwner();

        if ($tenant->id === 1) {
            return back()->with('error', 'Tenant default sistem tidak dapat ditangguhkan.');
        }

        $oldStatus = $tenant->status;
        $newStatus = $oldStatus === 'suspended' ? 'active' : 'suspended';
        $tenant->update(['status' => $newStatus]);

        if (Schema::hasTable('platform_audit_logs')) {
            PlatformAuditLog::record(
                $newStatus === 'suspended' ? 'tenant.suspended' : 'tenant.activated',
                $tenant,
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );
        }

        $statusLabel = $newStatus === 'active' ? 'diaktifkan kembali' : 'ditangguhkan (suspended)';
        return back()->with('success', "Status ISP '{$tenant->name}' berhasil {$statusLabel}.");
    }

    /**
     * Update ISP subscription plan & quota limits.
     */
    public function updatePlan(Request $request, Tenant $tenant)
    {
        $this->authorizePlatformOwner();

        $validated = $request->validate([
            'plan' => 'required|in:starter,growth,enterprise',
        ]);

        $limits = Tenant::planLimits($validated['plan']);

        $beforeState = [
            'plan'            => $tenant->plan,
            'status'          => $tenant->status,
            'max_technicians' => $tenant->max_technicians,
            'max_fiber_nodes' => $tenant->max_fiber_nodes,
        ];

        $tenant->update([
            'plan'                 => $validated['plan'],
            'max_technicians'      => $limits['techs'],
            'max_fiber_nodes'      => $limits['nodes'],
            'status'               => 'active',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        if (Schema::hasTable('platform_audit_logs')) {
            PlatformAuditLog::record(
                'tenant.plan_updated',
                $tenant,
                $beforeState,
                [
                    'plan'            => $tenant->plan,
                    'status'          => $tenant->status,
                    'max_technicians' => $tenant->max_technicians,
                    'max_fiber_nodes' => $tenant->max_fiber_nodes,
                ]
            );
        }

        return back()->with('success', "Paket ISP '{$tenant->name}' berhasil diubah ke " . ucfirst($validated['plan']) . ".");
    }
}
