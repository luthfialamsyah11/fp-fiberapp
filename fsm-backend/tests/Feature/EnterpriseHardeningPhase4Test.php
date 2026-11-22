<?php

namespace Tests\Feature;

use App\Domain\Tasks\TaskStateMachine;
use App\Jobs\DispatchWebhookJob;
use App\Jobs\ProvisionCustomerPppoeJob;
use App\Jobs\UnIsolateCustomerOnPaymentJob;
use App\Models\Customer;
use App\Models\FiberNode;
use App\Models\PlatformAuditLog;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\Network\MikrotikService;
use App\Services\Payment\QrisPaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnterpriseHardeningPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $rootTenant;
    protected User $rootAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
        MikrotikSimulatorDriver::reset();

        $this->rootTenant = Tenant::find(1) ?? Tenant::create([
            'id'              => 1,
            'name'            => 'Benteng Siber Root Platform',
            'slug'            => 'root-platform',
            'contact_name'    => 'Platform Owner',
            'contact_email'   => 'owner@bentengsiber.com',
            'contact_phone'   => '0811000001',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 9999,
            'max_fiber_nodes' => 9999,
        ]);

        $this->rootAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->rootTenant->id,
            'name'      => 'Root Master Admin',
            'email'     => 'master@bentengsiber.com',
            'password'  => Hash::make('password123'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    /** @test */
    public function tenant_credentials_are_encrypted_aes256_at_rest_with_legacy_json_fallback(): void
    {
        $tenant = Tenant::create([
            'name'             => 'Encrypted Fiber ISP',
            'slug'             => 'encrypted-isp',
            'contact_name'     => 'CTO Encrypted',
            'contact_email'    => 'cto@encrypted-isp.net',
            'contact_phone'    => '08123456789',
            'plan'             => 'growth',
            'status'           => 'active',
            'network_settings' => [
                'mikrotik_driver'   => 'rest',
                'mikrotik_host'     => '10.99.1.1',
                'mikrotik_password' => 'UltraSecretMikroTik#2026!',
            ],
            'settings'         => [
                'currency' => 'IDR',
                'payment'  => [
                    'driver'     => 'midtrans',
                    'server_key' => 'Mid-server-TOPSECRET999',
                ],
            ],
        ]);

        // 1. Raw database columns MUST contain AES-256-CBC ciphertext, NEVER plaintext secrets
        $rawNetwork = (string) DB::table('tenants')->where('id', $tenant->id)->value('network_settings');
        $rawSettings = (string) DB::table('tenants')->where('id', $tenant->id)->value('settings');

        $this->assertStringNotContainsString('UltraSecretMikroTik#2026!', $rawNetwork);
        $this->assertStringNotContainsString('Mid-server-TOPSECRET999', $rawSettings);
        $this->assertNotEmpty(Crypt::decryptString($rawNetwork));
        $this->assertNotEmpty(Crypt::decryptString($rawSettings));

        // 2. Eloquent transparently decrypts back into structured arrays
        $fresh = $tenant->fresh();
        $this->assertEquals('UltraSecretMikroTik#2026!', $fresh->network_settings['mikrotik_password']);
        $this->assertEquals('Mid-server-TOPSECRET999', $fresh->settings['payment']['server_key']);

        // 3. Serialization hides network_settings from default JSON responses
        $this->assertArrayNotHasKey('network_settings', $fresh->toArray());

        // 4. Legacy plaintext JSON written directly in DB is still read safely without crashing
        DB::table('tenants')->where('id', $tenant->id)->update([
            'network_settings' => json_encode(['mikrotik_driver' => 'simulator', 'legacy_flag' => true]),
        ]);
        $legacyRead = $tenant->fresh();
        $this->assertTrue($legacyRead->network_settings['legacy_flag']);
    }

    /** @test */
    public function payment_webhook_and_task_completion_dispatch_async_should_queue_jobs_with_backoff(): void
    {
        Queue::fake();

        $tenant = Tenant::create([
            'name'             => 'Queued ISP',
            'slug'             => 'queued-isp',
            'contact_name'     => 'Queued Admin',
            'contact_email'    => 'ops@queued-isp.net',
            'contact_phone'    => '0812999888',
            'plan'             => 'growth',
            'status'           => 'active',
            'network_settings' => ['mikrotik_driver' => 'simulator', 'olt_driver' => 'simulator'],
            'settings'         => ['payment' => ['driver' => 'simulator', 'server_key' => 'sim_key']],
        ]);
        TenantContext::setTenant($tenant);

        $tech = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Field Tech Queue',
            'email'     => 'tech@queued-isp.net',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
        ]);

        $odp = FiberNode::create([
            'tenant_id'   => $tenant->id,
            'code'        => 'ODP-Q01',
            'name'        => 'ODP Queue 1',
            'node_type'   => 'odp',
            'total_ports' => 8,
            'used_ports'  => 0,
            'status'      => 'operational',
        ]);

        $customer = Customer::create([
            'tenant_id'      => $tenant->id,
            'customer_code'  => 'CUST-Q01',
            'name'           => 'Budi Queued',
            'phone'          => '0813555666',
            'address'        => 'Jl. Queue No. 1',
            'service_plan'   => 'QUEUE-100M',
            'pppoe_username' => 'budi_queue',
            'status'         => 'isolated',
        ]);

        $task = Task::create([
            'tenant_id'        => $tenant->id,
            'technician_id'    => $tech->id,
            'customer_id'      => $customer->id,
            'fiber_node_id'    => $odp->id,
            'customer_name'    => $customer->name,
            'title'            => 'New Install Fiber Link',
            'service_category' => 'new_install',
            'address'          => $customer->address,
            'billing_amount'   => 200000,
            'payment_status'   => 'unpaid',
            'status'           => 'on-going',
        ]);

        // 1. Generate QRIS & trigger settlement webhook -> must push async jobs onto hardware & notifications queues
        $tx = QrisPaymentService::forTenant($tenant)->generateTaskQris($task, 200000);
        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $tx->reference_no,
            'transaction_status' => 'settlement',
            'gross_amount'       => '200000.00',
        ], ['X-Callback-Signature' => 'simulated_valid_sig'])->assertOk();

        Queue::assertPushedOn('hardware', UnIsolateCustomerOnPaymentJob::class, function (UnIsolateCustomerOnPaymentJob $job) use ($tenant, $customer) {
            return $job->tenantId === $tenant->id
                && $job->customerId === $customer->id
                && $job->tries === 3
                && $job->backoff === [5, 30, 120];
        });

        Queue::assertPushedOn('notifications', DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($tenant) {
            return $job->tenantId === $tenant->id && $job->event === 'TASK_PAYMENT_SETTLED';
        });

        // 2. Completing a new_install task dispatches ProvisionCustomerPppoeJob onto hardware queue
        TenantContext::setTenant($tenant);
        app(TaskStateMachine::class)->transition($task, 'completed', $this->rootAdmin, 'Admin override completion');
        Queue::assertPushedOn('hardware', ProvisionCustomerPppoeJob::class, function (ProvisionCustomerPppoeJob $job) use ($tenant, $customer) {
            return $job->tenantId === $tenant->id && $job->customerId === $customer->id;
        });

        // 3. Running UnIsolateCustomerOnPaymentJob directly restores MikroTik profile & customer status
        MikrotikService::forTenant($tenant)->getDriver()->upsertSecret('budi_queue', 'pass123', 'ISOLIR');
        (new UnIsolateCustomerOnPaymentJob($tenant->id, $customer->id, $tx->reference_no))->handle();
        $this->assertEquals('active', $customer->fresh()->status);
        $this->assertEquals('QUEUE-100M', MikrotikService::forTenant($tenant)->getDriver()->getSecret('budi_queue')['profile']);
    }

    /** @test */
    public function master_admin_actions_record_immutable_sha256_chained_platform_audit_logs(): void
    {
        $target = Tenant::create([
            'name'            => 'Audited ISP Nusantara',
            'slug'            => 'audited-nusantara',
            'contact_name'    => 'PIC Audited',
            'contact_email'   => 'pic@audited.net',
            'contact_phone'   => '0812777888',
            'plan'            => 'starter',
            'status'          => 'active',
            'max_technicians' => 5,
            'max_fiber_nodes' => 50,
        ]);

        // 1. Suspend tenant
        $this->actingAs($this->rootAdmin)
            ->post(route('master_admin.tenants.toggle', $target->id))
            ->assertRedirect();

        // 2. Upgrade plan to enterprise
        $this->actingAs($this->rootAdmin)
            ->post(route('master_admin.tenants.plan', $target->id), ['plan' => 'enterprise'])
            ->assertRedirect();

        $logs = PlatformAuditLog::orderBy('id', 'asc')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals('tenant.suspended', $logs[0]->action);
        $this->assertEquals('tenant.plan_updated', $logs[1]->action);
        $this->assertEquals($logs[0]->entry_hash, $logs[1]->prev_hash);
        $this->assertEquals('enterprise', $logs[1]->after_state['plan']);

        // 3. Verify cryptographic hash chain integrity
        $check = PlatformAuditLog::verifyChainIntegrity();
        $this->assertTrue($check['valid']);
        $this->assertEquals(2, $check['checked']);

        // 4. Immutability: updating or deleting an audit log entry MUST throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $logs[0]->update(['action' => 'tampered.action']);
    }

    /** @test */
    public function platform_audit_log_blocks_deletion_and_renders_in_master_console(): void
    {
        $entry = PlatformAuditLog::record('tenant.activated', $this->rootTenant, ['status' => 'suspended'], ['status' => 'active'], $this->rootAdmin);

        // Master console displays the ledger & verified chain badge
        $this->actingAs($this->rootAdmin)
            ->get(route('master_admin.index'))
            ->assertOk()
            ->assertSee('Immutable Platform Audit Ledger')
            ->assertSee('SHA-256 Chain Verified')
            ->assertSee('tenant.activated');

        $this->expectException(\RuntimeException::class);
        $entry->delete();
    }

    /** @test */
    public function self_service_registration_blocks_enterprise_plan_and_captures_enterprise_sales_inquiries(): void
    {
        Queue::fake();

        // 1. Attempting to self-register Enterprise trial is strictly rejected with 302 validation error
        $blocked = $this->post(route('tenant.register.post'), [
            'company_name'          => 'Abusive Free Enterprise ISP',
            'slug'                  => 'free-enterprise-abuse',
            'contact_name'          => 'Sneaky User',
            'contact_email'         => 'sneaky@abuse.com',
            'contact_phone'         => '0812000111',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'plan'                  => 'enterprise',
        ]);

        $blocked->assertSessionHasErrors(['plan']);
        $this->assertDatabaseMissing('tenants', ['slug' => 'free-enterprise-abuse']);

        // 2. Submitting an Enterprise Sales Inquiry succeeds, logs into PlatformAuditLog & dispatches sales webhook
        $inq = $this->post(route('tenant.enterprise_inquiry'), [
            'enterprise_company'    => 'PT Telco Nasional Tbk',
            'enterprise_name'       => 'Ir. Agung CTO',
            'enterprise_email'      => 'agung@telconasional.co.id',
            'enterprise_phone'      => '081199988877',
            'estimated_technicians' => 120,
            'estimated_fiber_nodes' => 2500,
            'notes'                 => 'Need 4 ZTE OLTs and custom domain noc.telconasional.co.id',
        ]);

        $inq->assertSessionHas('enterprise_success');
        $this->assertDatabaseHas('platform_audit_logs', [
            'action'      => 'enterprise.inquiry',
            'actor_email' => 'agung@telconasional.co.id',
        ]);
        Queue::assertPushedOn('notifications', DispatchWebhookJob::class, function (DispatchWebhookJob $job) {
            return $job->event === 'ENTERPRISE_SALES_INQUIRY';
        });
    }
}
