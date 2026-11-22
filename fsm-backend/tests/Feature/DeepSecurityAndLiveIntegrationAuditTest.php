<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\TechnicianLocation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\Payment\QrisPaymentService;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeepSecurityAndLiveIntegrationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantRoot;
    protected Tenant $tenantB;
    protected User $rootAdmin;
    protected User $adminB;
    protected User $techRoot;
    protected User $techB;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
        MikrotikSimulatorDriver::reset();

        $this->tenantRoot = Tenant::find(1) ?? Tenant::create([
            'id'              => 1,
            'name'            => 'Benteng Siber Root',
            'slug'            => 'root-isp',
            'contact_name'    => 'Root Owner',
            'contact_email'   => 'root@bentengsiber.com',
            'contact_phone'   => '0811000001',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 9999,
            'max_fiber_nodes' => 9999,
        ]);

        $this->tenantB = Tenant::create([
            'name'            => 'ISP Competitor Beta',
            'slug'            => 'competitor-beta',
            'contact_name'    => 'Beta Boss',
            'contact_email'   => 'boss@beta-isp.net',
            'contact_phone'   => '0812000002',
            'plan'            => 'starter',
            'status'          => 'active',
            'max_technicians' => 2,
            'max_fiber_nodes' => 10,
            'network_settings'=> ['mikrotik_driver' => 'simulator', 'olt_driver' => 'simulator'],
            'settings'        => ['payment' => ['driver' => 'simulator', 'server_key' => 'beta_key']],
        ]);

        $this->rootAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantRoot->id,
            'name'      => 'Platform Root Admin',
            'email'     => 'rootadmin@bentengsiber.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->adminB = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantB->id,
            'name'      => 'Beta Super Admin',
            'email'     => 'admin@beta-isp.net',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->techRoot = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantRoot->id,
            'name'      => 'Root Tech',
            'email'     => 'tech@root.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
        ]);

        $this->techB = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenantB->id,
            'name'      => 'Beta Tech',
            'email'     => 'tech@beta-isp.net',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $tenantBSettingsFile = SettingService::getFilePath($this->tenantB->id);
        if (File::exists($tenantBSettingsFile)) {
            File::delete($tenantBSettingsFile);
        }
        TenantContext::reset();
        parent::tearDown();
    }

    /** @test */
    public function non_root_tenant_super_admin_cannot_escalate_into_master_platform_console(): void
    {
        TenantContext::reset();

        // Tenant B's super_admin MUST receive 403 on all /master-admin endpoints
        $this->actingAs($this->adminB)->get('/master-admin')->assertStatus(403);
        $this->actingAs($this->adminB)->post("/master-admin/tenants/{$this->tenantRoot->id}/toggle")->assertStatus(403);
        $this->actingAs($this->adminB)->post("/master-admin/tenants/{$this->tenantB->id}/plan", ['plan' => 'enterprise'])->assertStatus(403);

        // Tenant B's plan must remain untouched (starter)
        $this->assertEquals('starter', $this->tenantB->fresh()->plan);

        // Root Platform Owner (tenant_id = 1) CAN still access /master-admin
        $this->actingAs($this->rootAdmin)->get('/master-admin')->assertStatus(200);
    }

    /** @test */
    public function route_model_binding_blocks_cross_tenant_idor_even_when_tenant_context_starts_null(): void
    {
        $taskRoot = Task::withoutGlobalScopes()->create([
            'tenant_id'     => $this->tenantRoot->id,
            'title'         => 'Classified Root Outage',
            'customer_name' => 'Root VIP',
            'address'       => 'Root HQ',
            'status'        => 'pending',
        ]);

        // Critical: reset TenantContext to null to simulate a fresh production HTTP request
        TenantContext::reset();

        $this->actingAs($this->adminB)->get("/tasks/{$taskRoot->id}")->assertStatus(404);
        $this->actingAs($this->adminB)->delete("/tasks/{$taskRoot->id}")->assertStatus(404);
        $this->assertDatabaseHas('tasks', ['id' => $taskRoot->id]);
    }

    /** @test */
    public function tenant_settings_and_gps_telemetry_are_strictly_isolated(): void
    {
        // Seed GPS points for both tenants (older than 10 days)
        $locRoot = TechnicianLocation::create([
            'technician_id' => $this->techRoot->id,
            'latitude'      => -6.2001,
            'longitude'     => 106.8001,
            'captured_at'   => now()->subDays(45),
        ]);
        // Add 51 recent points for Root so the old one would be eligible if unscoped
        for ($i = 0; $i < 52; $i++) {
            TechnicianLocation::create([
                'technician_id' => $this->techRoot->id,
                'latitude'      => -6.20 + ($i * 0.0001),
                'longitude'     => 106.80,
                'captured_at'   => now()->subMinutes($i),
            ]);
        }

        // Seed a proof of work on Root tenant
        $taskRoot = Task::withoutGlobalScopes()->create([
            'tenant_id'     => $this->tenantRoot->id,
            'title'         => 'Root Proof Task',
            'customer_name' => 'Root Cust',
            'address'       => 'Root St',
            'status'        => 'on-going',
        ]);
        ProofOfWork::create([
            'task_id'     => $taskRoot->id,
            'image'       => 'proof/root_secret.jpg',
            'description' => 'Top secret root installation photo',
        ]);

        TenantContext::reset();

        // 1. Tenant B live-pulse MUST report 0 proofs (not Root's proof!)
        $pulseB = $this->actingAs($this->adminB)->getJson(route('admin.live_pulse'));
        $pulseB->assertOk();
        $this->assertEquals(0, $pulseB->json('counts.total_proofs'));

        // 2. Tenant B trying to inspect Root technician's GPS trail via /tracking/live-data?technician_id=... gets empty route
        $trackB = $this->actingAs($this->adminB)->getJson(route('admin.tracking.live', ['technician_id' => $this->techRoot->id]));
        $trackB->assertOk();
        $this->assertEmpty($trackB->json('selected_route'));

        // 3. Tenant B purging GPS cache MUST NOT delete Root tenant's old GPS point
        $purgeB = $this->actingAs($this->adminB)->postJson(route('admin.settings.purge_cache'), ['telemetry_retention_days' => '7']);
        $purgeB->assertOk();
        $this->assertDatabaseHas('technician_locations', ['id' => $locRoot->id]);

        // 4. Tenant B saving Settings uses an isolated tenant file and persists network_settings
        $saveRes = $this->actingAs($this->adminB)->post(route('admin.settings.update'), [
            'company_name'             => 'Beta Fiber Exclusive',
            'support_email'            => 'noc@beta-isp.net',
            'support_phone'            => '0812000002',
            'currency'                 => 'IDR',
            'default_country_code'     => '62',
            'gps_refresh_interval'     => '15',
            'map_style'                => 'openstreetmap',
            'sla_warning_hours'        => '2',
            'geofence_check_meters'    => '0',
            'telemetry_retention_days' => '30',
            'mikrotik_driver'          => 'simulator',
            'olt_driver'               => 'simulator',
            'payment_driver'           => 'simulator',
            'require_hardware_olt_validation' => '1',
        ]);
        $saveRes->assertSessionHas('success');

        TenantContext::setTenant($this->tenantB);
        $this->assertEquals('Beta Fiber Exclusive', SettingService::get('company_name'));
        $this->assertTrue((bool) ($this->tenantB->fresh()->network_settings['require_hardware_olt_validation'] ?? false));

        TenantContext::setTenant($this->tenantRoot);
        $this->assertNotEquals('Beta Fiber Exclusive', SettingService::get('company_name'));
    }

    /** @test */
    public function external_customer_from_secondary_tenant_can_print_and_verify_bast_with_valid_token(): void
    {
        $taskB = Task::withoutGlobalScopes()->create([
            'tenant_id'     => $this->tenantB->id,
            'technician_id' => $this->techB->id,
            'title'         => 'Beta FTTH Activation',
            'customer_name' => 'Ibu Sari Beta',
            'address'       => 'Jl. Beta No. 9',
            'status'        => 'completed',
            'completed_at'  => now(),
        ]);
        $taskB->update([
            'bast_hash'      => $taskB->computeBastHash(),
            'bast_sealed_at' => now(),
        ]);
        $token = $taskB->ensureTrackingToken();

        // Guest customer (TenantContext defaults to Root Tenant 1!) can access Tenant B's BAST & Receipt via valid bearer token
        TenantContext::reset();
        $this->get("/tasks/{$taskB->id}/bast?token={$token}")->assertOk()->assertSee('Ibu Sari Beta');
        $this->getJson("/tasks/{$taskB->id}/bast/verify?token={$token}")->assertOk()->assertJson(['verified' => true]);
        $this->get("/request/receipt/{$token}")->assertOk()->assertSee('Ibu Sari Beta');

        // Wrong token is rejected
        $this->getJson("/tasks/{$taskB->id}/bast/verify?token=wrong_token")->assertStatus(403);
    }

    /** @test */
    public function payment_webhook_hardening_and_live_noc_updates_work_end_to_end(): void
    {
        $custB = Customer::withoutGlobalScopes()->create([
            'tenant_id'      => $this->tenantB->id,
            'customer_code'  => 'CUST-LIVE-01',
            'name'           => 'Bapak Hendra',
            'phone'          => '081234567000',
            'address'        => 'Perum Beta Blok C',
            'service_plan'   => 'BETA-100M',
            'pppoe_username' => 'hendra_beta',
            'status'         => 'active',
        ]);

        TenantContext::reset();

        // 1. Admin B provisions PPPoE & isolates customer live from CRM
        $this->actingAs($this->adminB)->post(route('admin.customers.provision_pppoe', $custB->id))->assertSessionHas('success');
        $this->actingAs($this->adminB)->post(route('admin.customers.isolate', $custB->id))->assertSessionHas('success');
        $this->assertEquals('isolated', $custB->fresh()->status);

        // 2. Create unpaid task for Hendra and generate Dynamic QRIS from Admin Task Detail
        $taskB = Task::withoutGlobalScopes()->create([
            'tenant_id'      => $this->tenantB->id,
            'technician_id'  => $this->techB->id,
            'customer_id'    => $custB->id,
            'customer_name'  => $custB->name,
            'title'          => 'Tagihan & Pemulihan Isolir',
            'address'        => $custB->address,
            'billing_amount' => 300000,
            'payment_status' => 'unpaid',
            'status'         => 'on-going',
        ]);

        $this->actingAs($this->adminB)->post(route('admin.tasks.qris', $taskB->id))->assertSessionHas('success');
        $tx = PaymentTransaction::withoutGlobalScopes()->where('task_id', $taskB->id)->first();
        $this->assertNotNull($tx);

        // Capture live-status signature BEFORE payment
        $beforeLive = $this->actingAs($this->adminB)->getJson(route('admin.tasks.live_status', $taskB->id));
        $beforeLive->assertOk()->assertJson(['payment_status' => 'pending']);
        $sigBefore = $beforeLive->json('state_signature');

        // 3. Unsigned webhook MUST be rejected with 403
        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $tx->reference_no,
            'transaction_status' => 'settlement',
            'gross_amount'       => '300000.00',
        ])->assertStatus(403);

        // 4. Amount mismatch webhook MUST be rejected (404 / not settled)
        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $tx->reference_no,
            'transaction_status' => 'settlement',
            'gross_amount'       => '1000.00',
        ], ['X-Callback-Signature' => 'simulated_valid_sig'])->assertStatus(404);
        $this->assertEquals('pending', $taskB->fresh()->payment_status);

        // 5. Valid signed webhook with exact amount settles payment & unblocks MikroTik
        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $tx->reference_no,
            'transaction_status' => 'settlement',
            'gross_amount'       => '300000.00',
        ], ['X-Callback-Signature' => 'simulated_valid_sig'])->assertOk();

        $this->assertEquals('paid', $taskB->fresh()->payment_status);
        $this->assertEquals('active', $custB->fresh()->status);

        // 6. Regenerating QRIS on an already-paid task MUST be blocked
        $this->actingAs($this->techB, 'sanctum')
            ->postJson("/api/tasks/{$taskB->id}/qris", ['amount' => 300000])
            ->assertStatus(422);

        // 7. Live Task Detail & NOC Dashboard live-pulse IMMEDIATELY reflect paid status!
        $afterLive = $this->actingAs($this->adminB)->getJson(route('admin.tasks.live_status', $taskB->id));
        $afterLive->assertOk()->assertJson(['payment_status' => 'paid']);
        $this->assertNotEquals($sigBefore, $afterLive->json('state_signature'));

        $pulseAfter = $this->actingAs($this->adminB)->getJson(route('admin.live_pulse'));
        $pulseAfter->assertOk();
        $this->assertEquals('payment', $pulseAfter->json('latest_event.type'));
    }
}
