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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdversarialSecurityAndQuotaAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $techA;
    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        // Tenant A: Starter plan (max 2 technicians, max 2 fiber nodes)
        $this->tenantA = Tenant::create([
            'name'            => 'ISP Alpha',
            'slug'            => 'alpha-isp',
            'contact_name'    => 'Alpha Admin',
            'contact_email'   => 'admin@alpha.com',
            'contact_phone'   => '0811111111',
            'plan'            => 'starter',
            'status'          => 'active',
            'max_technicians' => 2,
            'max_fiber_nodes' => 2,
        ]);

        $this->adminA = User::create([
            'name'      => 'Alpha Admin',
            'email'     => 'admin@alpha.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->techA = User::create([
            'name'      => 'Tech Alpha 1',
            'email'     => 'tech1@alpha.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
            'tenant_id' => $this->tenantA->id,
        ]);

        // Tenant B: Growth plan
        $this->tenantB = Tenant::create([
            'name'            => 'ISP Beta',
            'slug'            => 'beta-isp',
            'contact_name'    => 'Beta Admin',
            'contact_email'   => 'admin@beta.com',
            'contact_phone'   => '0822222222',
            'plan'            => 'growth',
            'status'          => 'active',
            'max_technicians' => 25,
            'max_fiber_nodes' => 100,
        ]);

        $this->adminB = User::create([
            'name'      => 'Beta Admin',
            'email'     => 'admin@beta.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
            'tenant_id' => $this->tenantB->id,
        ]);
    }

    /** @test */
    public function tenant_technician_quota_strictly_enforced_in_web_controller(): void
    {
        TenantContext::setTenant($this->tenantA);

        // Alpha already has 1 technician. Adding 2nd should succeed.
        $res1 = $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.technicians.store'), [
                'name'                  => 'Tech Alpha 2',
                'email'                 => 'tech2@alpha.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'phone'                 => '08123456789',
                'is_active'             => '1',
            ]);

        $res1->assertSessionHas('success');
        $this->assertEquals(2, User::where('tenant_id', $this->tenantA->id)->where('role', 'technician')->count());

        // Adding 3rd technician should be blocked by quota limit
        $res2 = $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.technicians.store'), [
                'name'                  => 'Tech Alpha 3',
                'email'                 => 'tech3@alpha.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'phone'                 => '08123456790',
                'is_active'             => '1',
            ]);

        $res2->assertSessionHas('error');
        $this->assertEquals(2, User::where('tenant_id', $this->tenantA->id)->where('role', 'technician')->count());
    }

    /** @test */
    public function tenant_fiber_node_quota_strictly_enforced_in_web_controller(): void
    {
        TenantContext::setTenant($this->tenantA);

        // Max is 2. Create 1st node
        $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.fiber_assets.store'), [
                'code'        => 'ODP-01',
                'name'        => 'ODP 01 Alpha',
                'node_type'   => 'odp',
                'total_ports' => 8,
                'used_ports'  => 0,
            ])->assertSessionHas('success');

        // Create 2nd node
        $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.fiber_assets.store'), [
                'code'        => 'ODP-02',
                'name'        => 'ODP 02 Alpha',
                'node_type'   => 'odp',
                'total_ports' => 8,
                'used_ports'  => 0,
            ])->assertSessionHas('success');

        // 3rd node should be blocked
        $res = $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.fiber_assets.store'), [
                'code'        => 'ODP-03',
                'name'        => 'ODP 03 Alpha',
                'node_type'   => 'odp',
                'total_ports' => 8,
                'used_ports'  => 0,
            ]);

        $res->assertSessionHas('error');
        $this->assertEquals(2, FiberNode::where('tenant_id', $this->tenantA->id)->count());
    }

    /** @test */
    public function cross_tenant_foreign_key_idor_is_blocked_in_task_creation(): void
    {
        // Create Beta assets
        $betaCust = Customer::withoutGlobalScopes()->create([
            'tenant_id'     => $this->tenantB->id,
            'customer_code' => 'CUST-B01',
            'name'          => 'Beta Customer',
            'phone'         => '0822222222',
            'address'       => 'Beta Address',
            'status'        => 'active',
        ]);

        $betaOdp = FiberNode::withoutGlobalScopes()->create([
            'tenant_id'   => $this->tenantB->id,
            'code'        => 'ODP-B01',
            'name'        => 'Beta ODP',
            'node_type'   => 'odp',
            'total_ports' => 8,
            'used_ports'  => 0,
            'status'      => 'operational',
        ]);

        $betaTech = User::create([
            'tenant_id' => $this->tenantB->id,
            'name'      => 'Beta Tech',
            'email'     => 'tech@beta.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
        ]);

        TenantContext::setTenant($this->tenantA);

        // Alpha admin attempts to link Beta's customer, fiber node, or technician to Alpha's task
        $res = $this->actingAs($this->adminA)
            ->withSession(['active_tenant_id' => $this->tenantA->id])
            ->post(route('admin.tasks.store'), [
                'title'         => 'Malicious Cross-Tenant Task',
                'customer_name' => 'Fake Customer',
                'address'       => 'Alpha Street',
                'priority'      => 'medium',
                'customer_id'   => $betaCust->id,
                'fiber_node_id' => $betaOdp->id,
                'technician_id' => $betaTech->id,
            ]);

        // Validation should fail on all 3 foreign keys because they belong to tenant B
        $res->assertSessionHasErrors(['customer_id', 'fiber_node_id', 'technician_id']);
        $this->assertEquals(0, Task::where('tenant_id', $this->tenantA->id)->count());
    }

    /** @test */
    public function cross_tenant_bom_material_injection_is_rejected(): void
    {
        // Item belonging to Beta
        $betaItem = InventoryItem::withoutGlobalScopes()->create([
            'tenant_id'  => $this->tenantB->id,
            'code'       => 'MODEM-BETA',
            'name'       => 'Beta Fiber Modem',
            'unit'       => 'unit',
            'stock'      => 50,
            'unit_price' => 250000,
            'category'   => 'device',
            'is_active'  => true,
        ]);

        // Task belonging to Alpha
        TenantContext::setTenant($this->tenantA);
        $task = Task::withoutGlobalScopes()->create([
            'tenant_id'     => $this->tenantA->id,
            'title'         => 'Alpha Repair Task',
            'customer_name' => 'Alpha Customer',
            'address'       => 'Alpha St 10',
            'technician_id' => $this->techA->id,
            'status'        => 'on-going',
            'priority'      => 'medium',
        ]);

        // Technician Alpha attempts to consume Beta's inventory item
        $res = $this->actingAs($this->techA, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/materials", [
                'inventory_item_id' => $betaItem->id,
                'quantity'          => 1,
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['inventory_item_id']);
    }

    /** @test */
    public function zero_billing_task_rejects_qris_generation_without_override(): void
    {
        TenantContext::setTenant($this->tenantA);
        $task = Task::withoutGlobalScopes()->create([
            'tenant_id'      => $this->tenantA->id,
            'title'          => 'Warranty Free Maintenance',
            'customer_name'  => 'Warranty Customer',
            'address'        => 'Warranty St',
            'technician_id'  => $this->techA->id,
            'status'         => 'on-going',
            'priority'       => 'low',
            'billing_amount' => 0,
            'payment_status' => 'unpaid',
        ]);

        // Technician attempts to generate QRIS without specifying amount on a 0-billing work order
        $res = $this->actingAs($this->techA, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/qris", []);

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Cannot generate QRIS: work order has zero billing amount and no override amount was specified. Please specify an amount of at least IDR 1,000.',
        ]);
    }
}
