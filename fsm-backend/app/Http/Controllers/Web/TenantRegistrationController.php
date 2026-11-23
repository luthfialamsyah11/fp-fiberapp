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
            'currency'      => 'nullable|in:USD,EUR,GBP,AUD,IDR',
        ], [
            'slug.unique'          => 'This subdomain is already taken by another ISP. Please choose a different subdomain.',
            'contact_email.unique' => 'This email address is already registered on Benteng Siber.',
            'slug.alpha_dash'      => 'Subdomain may only contain letters, numbers, and dashes (-).',
            'plan.in'              => 'Full Setup Dedicated Project ($2,500) & Enterprise deployments require architect verification. Select Starter ($500/mo) or Growth ($1,000/mo) for an instant 14-day trial, or submit a Full Setup booking.',
        ]);

        $limits = Tenant::planLimits($validated['plan']);
        $currency = strtoupper((string) ($validated['currency'] ?? 'USD'));

        return DB::transaction(function () use ($validated, $limits, $currency, $request) {
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
                    'currency'        => $currency,
                    'default_sla_hrs' => 4,
                    'payment'         => [
                        'driver'        => 'simulator',
                        'is_production' => false,
                    ],
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

            // 3. Seed starter warehouse inventory catalog (USD global catalog by default, IDR if selected)
            $starterMaterials = $currency === 'IDR'
                ? [
                    ['code' => 'DC-1C', 'name' => 'Kabel Drop Core FO 1 Core G.657A', 'unit' => 'meter', 'stock' => 1000, 'unit_price' => 1200, 'category' => 'material'],
                    ['code' => 'FC-SC', 'name' => 'Fast Connector SC/UPC Biru', 'unit' => 'pcs', 'stock' => 200, 'unit_price' => 5000, 'category' => 'material'],
                    ['code' => 'PC-SC-2M', 'name' => 'Patch Cord SC/UPC-SC/UPC 2 Meter Simplex', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 18000, 'category' => 'material'],
                    ['code' => 'ROSET-1P', 'name' => 'Roset Optik 1 Port Wallmount', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 12000, 'category' => 'material'],
                    ['code' => 'ONT-XPON', 'name' => 'Modem ONT XPON Gigabit Dual Band WiFi', 'unit' => 'unit', 'stock' => 25, 'unit_price' => 285000, 'category' => 'device'],
                ]
                : [
                    ['code' => 'DC-1C', 'name' => 'FTTH Drop-Core Fiber Cable 1C G.657A', 'unit' => 'meter', 'stock' => 1000, 'unit_price' => 0.12, 'category' => 'material'],
                    ['code' => 'FC-SC', 'name' => 'SC/APC Fast Optical Connector', 'unit' => 'pcs', 'stock' => 200, 'unit_price' => 0.45, 'category' => 'material'],
                    ['code' => 'PC-SC-2M', 'name' => 'Simplex Patch Cord SC/APC 2m', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 1.50, 'category' => 'material'],
                    ['code' => 'ROSET-1P', 'name' => 'Wall-Mount Optical Termination Box (1-Port)', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 1.10, 'category' => 'material'],
                    ['code' => 'ONT-XPON', 'name' => 'XPON Gigabit Dual-Band Wi-Fi ONT', 'unit' => 'unit', 'stock' => 25, 'unit_price' => 22.00, 'category' => 'device'],
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
                        'currency'        => $currency,
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

            $planLabel = $tenant->plan === 'growth' ? 'Growth Cloud ($1,000/mo)' : 'Starter Cloud ($500/mo)';
            return redirect()->route('admin.dashboard')
                ->with('success', "Welcome to Benteng Siber FiberOps! Organization '{$tenant->name}' is live with a 14-Day Free Trial ({$planLabel}, {$currency}).");
        });
    }

    /**
     * Capture qualified $2,500 Dedicated Turnkey Project & Enterprise Carrier leads
     * instead of exposing unlimited quotas to unverified self-service trials.
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
            'router_count'           => 'nullable|integer|min:1|max:1000',
            'deployment_target'      => 'nullable|in:dedicated_cloud,on_premise,hybrid',
            'timeline'               => 'nullable|in:asap,within_30_days,evaluating',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $cleanCompany = strip_tags(trim($validated['enterprise_company']));
        $cleanName = strip_tags(trim($validated['enterprise_name']));
        $cleanNotes = isset($validated['notes']) ? strip_tags(trim($validated['notes'])) : null;
        $deploymentTarget = $validated['deployment_target'] ?? 'dedicated_cloud';
        $timeline = $validated['timeline'] ?? 'asap';
        $routerCount = (int) ($validated['router_count'] ?? 2);

        if (Schema::hasTable('platform_audit_logs')) {
            PlatformAuditLog::record(
                'enterprise.inquiry',
                null,
                null,
                [
                    'package'               => 'dedicated_full_project_2500_usd',
                    'company'               => $cleanCompany,
                    'contact_phone'         => trim($validated['enterprise_phone']),
                    'estimated_technicians' => (int) $validated['estimated_technicians'],
                    'estimated_fiber_nodes' => (int) ($validated['estimated_fiber_nodes'] ?? 0),
                    'router_count'          => $routerCount,
                    'deployment_target'     => $deploymentTarget,
                    'timeline'              => $timeline,
                    'notes'                 => $cleanNotes,
                ],
                null,
                $cleanName,
                strtolower(trim($validated['enterprise_email']))
            );
        }

        DispatchWebhookJob::dispatch(1, 'ENTERPRISE_SALES_INQUIRY', [
            'package'               => 'dedicated_full_project_2500_usd',
            'company'               => $cleanCompany,
            'contact_name'          => $cleanName,
            'contact_email'         => strtolower(trim($validated['enterprise_email'])),
            'contact_phone'         => trim($validated['enterprise_phone']),
            'estimated_technicians' => (int) $validated['estimated_technicians'],
            'estimated_fiber_nodes' => (int) ($validated['estimated_fiber_nodes'] ?? 0),
            'router_count'          => $routerCount,
            'deployment_target'     => $deploymentTarget,
            'timeline'              => $timeline,
            'notes'                 => $cleanNotes,
        ]);

        return back()->with([
            'enterprise_success' => "Booking received for {$cleanCompany}! Our Lead Architect will contact {$cleanName} shortly to kick off your 7-day \$2,500 Turnkey Deployment.",
            'enterprise_lead'    => [
                'company'    => $cleanCompany,
                'name'       => $cleanName,
                'routers'    => $routerCount,
                'techs'      => (int) $validated['estimated_technicians'],
                'deployment' => $deploymentTarget,
            ],
        ]);
    }
}
