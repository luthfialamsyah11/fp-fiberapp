<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Task;
use App\Models\InventoryItem;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    /**
     * 1. Self-Service ISP Onboarding Registration Flow
     */
    public function test_tenant_self_service_registration_creates_isolated_tenant_and_admin(): void
    {
        $response = $this->post('/register-isp', [
            'company_name'          => 'PT Citra Nusantara Fiber',
            'slug'                  => 'citra-fiber',
            'contact_name'          => 'Pak Budi Direktur',
            'contact_email'         => 'budi@citrafiber.net.id',
            'contact_phone'         => '081234567890',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'plan'                  => 'growth',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();

        // Verify Tenant in database
        $tenant = Tenant::where('slug', 'citra-fiber')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('PT Citra Nusantara Fiber', $tenant->name);
        $this->assertEquals('growth', $tenant->plan);
        $this->assertEquals('trial', $tenant->status);
        $this->assertEquals(20, $tenant->max_technicians);
        $this->assertEquals(250, $tenant->max_fiber_nodes);
        $this->assertTrue($tenant->trial_ends_at->isFuture());

        // Verify Admin User
        $admin = User::where('email', 'budi@citrafiber.net.id')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('super_admin', $admin->role);
        $this->assertEquals($tenant->id, $admin->tenant_id);

        // Verify Starter Inventory seeded
        $inventoryCount = InventoryItem::where('tenant_id', $tenant->id)->count();
        $this->assertEquals(5, $inventoryCount);
    }

    /**
     * 2. Strict Cross-Tenant Data Isolation (Anti-IDOR at Database Level)
     */
    public function test_strict_cross_tenant_data_isolation_between_isps(): void
    {
        // Tenant A (Demo ISP)
        $tenantA = Tenant::find(1) ?? Tenant::create([
            'name'            => 'ISP Alpha',
            'slug'            => 'alpha',
            'contact_name'    => 'Admin Alpha',
            'contact_email'   => 'alpha@isp.com',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 99,
        ]);

        // Tenant B (Competitor ISP)
        $tenantB = Tenant::create([
            'name'            => 'ISP Beta',
            'slug'            => 'beta',
            'contact_name'    => 'Admin Beta',
            'contact_email'   => 'beta@isp.com',
            'plan'            => 'growth',
            'status'          => 'active',
            'max_technicians' => 20,
        ]);

        // Create Task A under Tenant A
        TenantContext::setTenant($tenantA);
        $adminA = User::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Admin Alpha',
            'email'     => 'admin@alpha.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
        $taskA = Task::create([
            'tenant_id'     => $tenantA->id,
            'title'         => 'Alpha Outage Repair',
            'customer_name' => 'Alpha Customer',
            'address'       => 'Jalan Alpha No 1',
            'status'        => 'pending',
        ]);

        // Create Task B under Tenant B
        TenantContext::setTenant($tenantB);
        $adminB = User::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Admin Beta',
            'email'     => 'admin@beta.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
        $taskB = Task::create([
            'tenant_id'     => $tenantB->id,
            'title'         => 'Beta Fiber Installation',
            'customer_name' => 'Beta Customer',
            'address'       => 'Jalan Beta No 2',
            'status'        => 'pending',
        ]);

        // Authenticate as Admin B
        TenantContext::setTenant($tenantB);
        $this->actingAs($adminB);

        // Eloquent query should ONLY return Task B, zero trace of Task A
        $tasksVisible = Task::all();
        $this->assertCount(1, $tasksVisible);
        $this->assertEquals($taskB->id, $tasksVisible->first()->id);
        $this->assertFalse($tasksVisible->contains('id', $taskA->id));

        // Direct IDOR attempt: Admin B requests Task A's detail page
        $res = $this->get(route('admin.tasks.show', $taskA));
        $res->assertStatus(404);
    }

    /**
     * 3. Quota Limit Enforcer Blocks Exceeding Plan Technicians
     */
    public function test_quota_enforcer_blocks_exceeding_plan_technicians(): void
    {
        $tenantStarter = Tenant::create([
            'name'            => 'Small ISP',
            'slug'            => 'small-isp',
            'contact_name'    => 'Small Admin',
            'contact_email'   => 'small@isp.com',
            'plan'            => 'starter',
            'status'          => 'active',
            'max_technicians' => 2, // Low limit for test
        ]);

        TenantContext::setTenant($tenantStarter);
        $admin = User::create([
            'tenant_id' => $tenantStarter->id,
            'name'      => 'Admin Small',
            'email'     => 'admin@small.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        // Add 2 technicians (at limit)
        User::create(['tenant_id' => $tenantStarter->id, 'name' => 'Tech 1', 'email' => 't1@small.com', 'password' => Hash::make('123'), 'role' => 'technician', 'is_active' => true]);
        User::create(['tenant_id' => $tenantStarter->id, 'name' => 'Tech 2', 'email' => 't2@small.com', 'password' => Hash::make('123'), 'role' => 'technician', 'is_active' => true]);

        // Attempt to create 3rd technician via POST technicians
        $response = $this->actingAs($admin)->post(route('admin.technicians.store'), [
            'name'      => 'Tech 3 Overlimit',
            'email'     => 't3@small.com',
            'password'  => 'password123',
            'phone'     => '081299998888',
            'is_active' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Batas kuota teknisi', session('error'));

        // Verify Tech 3 was NOT created
        $this->assertDatabaseMissing('users', ['email' => 't3@small.com']);
    }

    /**
     * 4. Suspended Tenant Gateway Block
     */
    public function test_suspended_tenant_is_blocked_at_middleware_gateway(): void
    {
        $tenantSuspended = Tenant::create([
            'name'            => 'Delinquent ISP',
            'slug'            => 'delinquent',
            'contact_name'    => 'Delinquent Admin',
            'contact_email'   => 'delinquent@isp.com',
            'plan'            => 'starter',
            'status'          => 'suspended',
            'max_technicians' => 5,
        ]);

        TenantContext::setTenant($tenantSuspended);
        $admin = User::create([
            'tenant_id' => $tenantSuspended->id,
            'name'      => 'Admin Delinquent',
            'email'     => 'admin@delinquent.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        // Web access blocked with HTTP 403
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        // API access blocked with HTTP 403 JSON
        $apiResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/tasks');
        $apiResponse->assertStatus(403);
        $this->assertEquals('suspended', $apiResponse->json('status'));
    }

    /**
     * 5. Platform Master Admin Console
     */
    public function test_master_admin_console_lists_all_tenants_and_can_toggle_status(): void
    {
        $defaultTenant = Tenant::find(1) ?? Tenant::create([
            'name'            => 'Benteng Siber Master Tenant',
            'slug'            => 'master',
            'contact_name'    => 'Owner',
            'contact_email'   => 'owner@bentengsiber.com',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 9999,
        ]);

        $masterUser = User::create([
            'tenant_id' => $defaultTenant->id,
            'name'      => 'Master Owner',
            'email'     => 'master@bentengsiber.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $targetTenant = Tenant::create([
            'name'            => 'Target ISP',
            'slug'            => 'target-isp',
            'contact_name'    => 'Target PIC',
            'contact_email'   => 'pic@target.com',
            'plan'            => 'starter',
            'status'          => 'active',
            'max_technicians' => 5,
        ]);

        // Master admin visits /master-admin
        $res = $this->actingAs($masterUser)->get('/master-admin');
        $res->assertStatus(200);
        $res->assertSee('Pusat Komando Multi-Tenant ISP Nasional');
        $res->assertSee('Target ISP');

        // Toggle status to suspended
        $postRes = $this->actingAs($masterUser)->post("/master-admin/tenants/{$targetTenant->id}/toggle");
        $postRes->assertRedirect();
        $this->assertEquals('suspended', $targetTenant->fresh()->status);
    }
}
