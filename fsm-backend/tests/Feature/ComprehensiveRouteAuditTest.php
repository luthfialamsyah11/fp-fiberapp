<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FiberNode;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveRouteAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $supervisor;
    protected User $technician;
    protected Task $task;
    protected Customer $customer;
    protected FiberNode $fiberNode;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'             => 'Audit Carrier Telco',
            'slug'             => 'audit-telco',
            'contact_name'     => 'Audit Head NOC',
            'contact_email'    => 'audit@telco.net.id',
            'contact_phone'    => '08123456789',
            'plan'             => 'enterprise',
            'status'           => 'active',
            'network_settings' => [
                'mikrotik_driver' => 'simulator',
                'olt_driver'      => 'simulator',
            ],
        ]);

        TenantContext::setTenant($this->tenant);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Auditor',
            'email'     => 'admin@audit.telco',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->supervisor = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Supervisor Auditor',
            'email'     => 'spv@audit.telco',
            'password'  => bcrypt('password'),
            'role'      => 'field_supervisor',
            'is_active' => true,
        ]);

        $this->technician = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Technician Field',
            'email'     => 'tech@audit.telco',
            'password'  => bcrypt('password'),
            'role'      => 'technician',
            'is_online' => true,
            'is_active' => true,
        ]);

        $this->fiberNode = FiberNode::create([
            'tenant_id'   => $this->tenant->id,
            'code'        => 'ODP-AUDIT-01',
            'name'        => 'ODP Audit 01',
            'node_type'   => 'odp',
            'total_ports' => 8,
            'used_ports'  => 1,
        ]);

        $this->customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-AUDIT-01',
            'name'              => 'Customer Audit',
            'phone'             => '08123456780',
            'address'           => 'Jl. Audit Cyber 1',
            'service_plan'      => 'SPEED-50M',
            'pppoe_username'    => 'audit_pppoe',
            'ont_serial_number' => 'ZTEG00998877',
            'status'            => 'active',
        ]);

        $this->item = InventoryItem::create([
            'code'       => 'AUDIT-DROP-01',
            'name'       => 'Dropcore 1 Core 100M',
            'unit'       => 'meter',
            'stock'      => 500,
            'unit_price' => 1.25,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $this->task = Task::create([
            'tenant_id'            => $this->tenant->id,
            'technician_id'        => $this->technician->id,
            'customer_id'          => $this->customer->id,
            'customer_name'        => $this->customer->name,
            'customer_phone'       => $this->customer->phone,
            'title'                => 'Audit Work Order',
            'service_category'     => 'new_install',
            'status'               => 'completed',
            'optical_power_rx_dbm' => -21.50,
            'optical_power_tx_dbm' => 2.45,
            'hardware_verified_at' => now(),
            'payment_status'       => 'paid',
            'completed_at'         => now(),
        ]);

        $this->task->update([
            'bast_hash'      => $this->task->computeBastHash(),
            'bast_sealed_at' => now(),
        ]);
    }

    /**
     * Audit: Public & Unauthenticated Guest Routes
     */
    public function test_all_public_guest_routes_accessible_without_server_error()
    {
        // 1. Root redirect
        $this->get('/')->assertRedirect(route('admin.login'));

        // 2. Login Page
        $this->get('/login')->assertStatus(200)->assertSee('Sign In');

        // 3. Public Customer Self-Service Request Portal
        $this->get('/request')->assertStatus(200)->assertSee('Submit Fiber Service or Repair Request');

        // 4. Public Self-Service ISP Onboarding Registration (Global English USD)
        $this->get('/register-isp')->assertStatus(200)->assertSee('Provision Your Zero-Trust ISP Command Center');

        // 5. Public Live Tracking Lookup Form
        $this->get('/track')->assertStatus(200);

        // 6. Public Live Tracking View for valid token
        $this->get("/track/{$this->task->tracking_token}")->assertStatus(200);

        // 7. Public BAST Cryptographic Verification
        $this->get("/bast/verify/{$this->task->bast_hash}")->assertStatus(200)->assertSee('Sertifikat BAST Asli');

        // 8. Public API Info Endpoint
        $this->getJson('/api')->assertStatus(200)->assertJson(['status' => 'running']);
    }

    /**
     * Audit: Admin & Dispatcher Authenticated Web Routes
     */
    public function test_all_admin_portal_routes_render_200_ok()
    {
        $routes = [
            '/dashboard',
            '/live-pulse',
            '/guide',
            '/tasks',
            '/tasks/create',
            "/tasks/{$this->task->id}",
            "/tasks/{$this->task->id}/edit",
            "/tasks/{$this->task->id}/bast",
            '/inventory',
            '/assignments',
            '/technicians',
            '/technicians/create',
            "/technicians/{$this->technician->id}/edit",
            '/tracking',
            '/proofs',
            '/history',
            '/reports',
            '/settings',
            '/customers',
            '/fiber-assets',
            '/staff',
            '/staff/create',
            "/staff/{$this->supervisor->id}/edit",
            '/client-requests',
        ];

        foreach ($routes as $route) {
            $res = $this->actingAs($this->admin)->get($route);
            $this->assertEquals(200, $res->status(), "Route {$route} failed with status {$res->status()}");
        }
    }

    /**
     * Audit: Field Supervisor Authenticated Access & Role Boundaries
     */
    public function test_supervisor_can_access_all_permitted_pages()
    {
        $spvRoutes = [
            '/dashboard',
            '/tasks',
            "/tasks/{$this->task->id}",
            '/tracking',
            '/proofs',
            '/history',
            '/reports',
            '/guide',
        ];

        foreach ($spvRoutes as $route) {
            $res = $this->actingAs($this->supervisor)->get($route);
            $this->assertEquals(200, $res->status(), "Supervisor Route {$route} failed with status {$res->status()}");
        }

        // Settings and Staff pages are restricted to Admin/SuperAdmin
        $this->actingAs($this->supervisor)->get('/settings')->assertStatus(403);
        $this->actingAs($this->supervisor)->get('/staff')->assertStatus(403);
    }

    /**
     * Audit: Master Platform Admin Route Access (Root Tenant 1 only; Secondary Tenants receive 403)
     */
    public function test_master_platform_admin_console_access()
    {
        // Secondary tenant super_admin must be denied (403)
        $tenantAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Secondary Tenant Admin',
            'email'     => 'tenantadmin@auditcarrier.net',
            'password'  => bcrypt('password'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
        $this->actingAs($tenantAdmin)->get('/master-admin')->assertStatus(403);

        // Root Platform Owner (tenant_id = 1) has full Master Console access
        $rootTenantId = \App\Models\Tenant::find(1)?->id ?? 1;
        $masterAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $rootTenantId,
            'name'      => 'Master Super Administrator',
            'email'     => 'master@bentengsiber.com',
            'password'  => bcrypt('password'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $res = $this->actingAs($masterAdmin)->get('/master-admin');
        $res->assertStatus(200)
            ->assertSee('Pusat Komando Multi-Tenant ISP Nasional')
            ->assertSee('Audit Carrier Telco');
    }
}
