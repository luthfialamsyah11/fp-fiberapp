<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\InventoryItem;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'plan'          => 'required|in:starter,growth,enterprise',
        ], [
            'slug.unique'          => 'Subdomain ini sudah digunakan oleh ISP lain. Silakan pilih subdomain lain.',
            'contact_email.unique' => 'Email ini sudah terdaftar di sistem Benteng Siber.',
            'slug.alpha_dash'      => 'Subdomain hanya boleh berupa huruf, angka, dan strip (-).',
        ]);

        $planLimits = [
            'starter'    => ['techs' => 5, 'nodes' => 50],
            'growth'     => ['techs' => 20, 'nodes' => 250],
            'enterprise' => ['techs' => 9999, 'nodes' => 9999],
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

            // 3. Seed starter warehouse inventory catalog for rapid onboarding
            $starterMaterials = [
                ['code' => 'DC-1C', 'name' => 'Kabel Drop Core FO 1 Core G.657A', 'unit' => 'meter', 'stock' => 1000, 'unit_price' => 1200, 'category' => 'Fiber Drop Cable'],
                ['code' => 'FC-SC', 'name' => 'Fast Connector SC/UPC Biru', 'unit' => 'pcs', 'stock' => 200, 'unit_price' => 5000, 'category' => 'Passive Accessories'],
                ['code' => 'PC-SC-2M', 'name' => 'Patch Cord SC/UPC-SC/UPC 2 Meter Simplex', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 18000, 'category' => 'Passive Accessories'],
                ['code' => 'ROSET-1P', 'name' => 'Roset Optik 1 Port Wallmount', 'unit' => 'pcs', 'stock' => 50, 'unit_price' => 12000, 'category' => 'Passive Accessories'],
                ['code' => 'ONT-XPON', 'name' => 'Modem ONT XPON Gigabit Dual Band WiFi', 'unit' => 'unit', 'stock' => 25, 'unit_price' => 285000, 'category' => 'Active Device ONT'],
            ];

            foreach ($starterMaterials as $mat) {
                InventoryItem::create(array_merge($mat, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]));
            }

            // 4. Authenticate the newly created ISP Admin
            Auth::login($adminUser);

            return redirect()->route('admin.dashboard')
                ->with('success', "Selamat datang di Benteng Siber FiberOps! Akun ISP '{$tenant->name}' berhasil dibuat dengan akses Trial 14 Hari (Paket " . ucfirst($tenant->plan) . ").");
        });
    }
}
