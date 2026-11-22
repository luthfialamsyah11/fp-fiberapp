<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhookJob;
use App\Models\InventoryItem;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class TenantRegistrationController extends Controller
{
    /**
     * Show self-service ISP onboarding registration form.
     */
    public function showRegistrationForm()
    {
        return view('tenant.register');
    }

    /**
     * Process ISP registration and initialize organization instance.
     * Self-service instant 14-day trials are strictly scoped to Starter & Growth;
     * Enterprise requires Sales verification & Master Admin activation.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'company_name'  => 'required|string|max:255',
            'slug'          => 'required|alpha_dash|max:50|unique:tenants,slug',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|max:255|unique:tenants,contact_email|unique:users,email',
            'contact_phone' => 'required|string|max:30',
            'password'      => ['required', 'confirmed', Password::min(8)],
            'plan'          => 'required|in:starter,growth',
        ], [
            'slug.unique'          => 'Subdomain ini sudah digunakan oleh ISP lain. Silakan pilih subdomain lain.',
            'contact_email.unique' => 'Email ini sudah terdaftar di sistem Benteng Siber.',
            'slug.alpha_dash'      => 'Subdomain hanya boleh berupa huruf, angka, dan strip (-).',
            'plan.in'              => 'Paket Enterprise memerlukan verifikasi tim sales Benteng Siber. Silakan pilih Starter/Growth untuk uji coba instan atau kirim Enterprise Inquiry.',
        ]);

        $planLimits = [
            'starter' => ['techs' => 5, 'nodes' => 50],
            'growth'  => ['techs' => 20, 'nodes' => 250],
        ];

        $limits = $planLimits[$validated['plan']] ?? $planLimits['starter'];

        return DB::transaction(function () use ($validated, $limits, $request) {
            // 1. Create Tenant Record
            $tenant = Tenant::create([
                'name'                 => $validated['company_name'],
                'slug'                 => strtolower($validated['slug']),
                'contact_name'         => $validated['contact_name'],
                'contact_email'        => strtolower($validated['contact_email']),
                'contact_phone'        => $validated['contact_phone'],
                'plan'                 => $validated['plan'],
                'status'               => 'trial',
                'max_technicians'      => $limits['techs'],
                'max_fiber_nodes'      => $limits['nodes'],
                'trial_ends_at'        => now()->addDays(14),
                'subscription_ends_at' => null,
                'brand_color'          => '#0f3559',
                'settings'             => [
                    'currency'        => 'IDR',
                    'default_sla_hrs' => 4,
                ],
            ]);

            // Set tenant context for model creation
            TenantContext::setTenant($tenant);

            // 2. Create Super Admin User for this ISP
            $adminUser = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $validated['contact_name'],
                'email'     => strtolower($validated['contact_email']),
                'password'  => Hash::make($validated['password']),
                'role'      => 'super_admin',
                'phone'     => $validated['contact_phone'],
                'is_active' => true,
            ]);

            // 3. Seed starter warehouse inventory catalog with valid platform categories (material/device/tool)
            $starterMaterials = [
                ['code' => 'DC-1C', 'name' => 'Kabel Drop Core FO 1 Core G.657A', 'unit' => 'meter', 'stock' => 1000, 'unit_price' => 1200, 'category' => 'material'],
                ['code' => 'FC-SC', 'name' => 'Fast Connector SC/UPC Biru', 'unit' => 'pcs', 'stock' => 200, 'unit_price' => 5000, 'category' => 'material'],
                ['code' => 'PC-SC-2M', 'name' => 'Patch Cord SC/UPC-SC/UPC 2 Meter Simplex', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 18000, 'category' => 'material'],
                ['code' => 'ROSET-1P', 'name' => 'Roset Optik 1 Port Wallmount', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 12000, 'category' => 'material'],
                ['code' => 'ONT-XPON', 'name' => 'Modem ONT XPON Gigabit Dual Band WiFi', 'unit' => 'unit', 'stock' => 25, 'unit_price' => 285000, 'category' => 'device'],
            ];

            foreach ($starterMaterials as $mat) {
                InventoryItem::create(array_merge($mat, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]));
            }

            // 4. Record immutable platform audit entry for new tenant onboarding
            if (Schema::hasTable('platform_audit_logs')) {
                PlatformAuditLog::record(
                    'tenant.registered',
                    $tenant,
                    null,
                    [
                        'slug'            => $tenant->slug,
                        'plan'            => $tenant->plan,
                        'status'          => $tenant->status,
                        'max_technicians' => $tenant->max_technicians,
                        'max_fiber_nodes' => $tenant->max_fiber_nodes,
                    ],
                    $adminUser
                );
            }

            // 5. Authenticate the newly created ISP Admin and bind session tenant securely
            Auth::login($adminUser);
            $request->session()->regenerate();
            $request->session()->put('active_tenant_id', $tenant->id);

            return redirect()->route('admin.dashboard')
                ->with('success', "Selamat datang di Benteng Siber FiberOps! Akun ISP '{$tenant->name}' berhasil dibuat dengan akses Trial 14 Hari (Paket " . ucfirst($tenant->plan) . ").");
        });
    }

    /**
     * Capture qualified Enterprise Carrier / Multi-Branch ISP sales leads
     * instead of exposing unlimited 9999-node quotas to unverified self-service trials.
     */
    public function enterpriseInquiry(Request $request)
    {
        if ($request->filled('website_hp')) {
            return back()->withErrors(['enterprise' => 'Automated submission rejected.']);
        }

        $validated = $request->validate([
            'enterprise_company'     => 'required|string|max:200',
            'enterprise_name'        => 'required|string|max:160',
            'enterprise_email'       => 'required|email|max:190',
            'enterprise_phone'       => 'required|string|max:40',
            'estimated_technicians'  => 'required|integer|min:1|max:50000',
            'estimated_fiber_nodes'  => 'nullable|integer|min:0|max:500000',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $cleanCompany = strip_tags(trim($validated['enterprise_company']));
        $cleanName = strip_tags(trim($validated['enterprise_name']));
        $cleanNotes = isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null;

        if (Schema::hasTable('platform_audit_logs')) {
            PlatformAuditLog::record(
                'enterprise.inquiry',
                null,
                null,
                [
                    'company'               => $cleanCompany,
                    'contact_phone'         => trim($validated['enterprise_phone']),
                    'estimated_technicians' => (int) $validated['estimated_technicians'],
                    'estimated_fiber_nodes' => (int) ($validated['estimated_fiber_nodes'] ?? 0),
                    'notes'                 => $cleanNotes,
                ],
                null,
                $cleanName,
                strtolower(trim($validated['enterprise_email']))
            );
        }

        DispatchWebhookJob::dispatch(1, 'ENTERPRISE_SALES_INQUIRY', [
            'company'               => $cleanCompany,
            'contact_name'          => $cleanName,
            'contact_email'         => strtolower(trim($validated['enterprise_email'])),
            'contact_phone'         => trim($validated['enterprise_phone']),
            'estimated_technicians' => (int) $validated['estimated_technicians'],
            'estimated_fiber_nodes' => (int) ($validated['estimated_fiber_nodes'] ?? 0),
            'notes'                 => $cleanNotes,
        ]);

        return back()->with('enterprise_success', "Terima kasih {$cleanName}! Permintaan aktivasi paket Enterprise untuk {$cleanCompany} telah dicatat. Tim Solutions Engineering kami akan segera menghubungi Anda via WhatsApp/Email.");
    }
}
