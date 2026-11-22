<?php

namespace Tests\Feature;

use App\Jobs\DispatchWebhookJob;
use App\Jobs\UnIsolateCustomerOnPaymentJob;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PaymentTransaction;
use App\Models\PlatformAuditLog;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\Payment\QrisPaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GlobalClientConversionPhase5Test extends TestCase
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
            'contact_phone'   => '+6281223554539',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 9999,
            'max_fiber_nodes' => 9999,
        ]);

        $this->rootAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->rootTenant->id,
            'name'      => 'Root Admin',
            'email'     => 'root@bentengsiber.com',
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
    public function global_usd_registration_sets_aligned_quotas_and_usd_starter_inventory(): void
    {
        $res = $this->post('/register-isp', [
            'company_name'          => 'Skyline Fiber LLC',
            'slug'                  => 'skyline-fiber',
            'contact_name'          => 'Alex Morgan',
            'contact_email'         => 'cto@skylinefiber.com',
            'contact_phone'         => '+14155550199',
            'password'              => 'SecurePass#2026',
            'password_confirmation' => 'SecurePass#2026',
            'plan'                  => 'growth',
            'currency'              => 'USD',
        ]);

        $res->assertRedirect(route('admin.dashboard'));

        $tenant = Tenant::where('slug', 'skyline-fiber')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('growth', $tenant->plan);
        $this->assertEquals(40, $tenant->max_technicians);
        $this->assertEquals(500, $tenant->max_fiber_nodes);
        $this->assertEquals('USD', $tenant->settings['currency']);

        // USD starter catalog seeded cleanly
        $ont = InventoryItem::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', 'ONT-XPON')
            ->first();
        $this->assertNotNull($ont);
        $this->assertEquals(22.00, (float) $ont->unit_price);
    }

    /** @test */
    public function dedicated_2500_project_booking_records_immutable_audit_and_dispatches_webhook(): void
    {
        Queue::fake();

        $res = $this->post('/register-isp/enterprise-inquiry', [
            'enterprise_company'    => 'Pacific WISP Corp',
            'enterprise_name'       => 'David Chen',
            'enterprise_email'      => 'david@pacificwisp.io',
            'enterprise_phone'      => '+14085550188',
            'estimated_technicians' => 30,
            'router_count'          => 5,
            'deployment_target'     => 'dedicated_cloud',
            'timeline'              => 'asap',
            'notes'                 => '5x CCR2004 + Stripe USD billing',
        ]);

        $res->assertSessionHas('enterprise_success');
        $res->assertSessionHas('enterprise_lead');

        $audit = PlatformAuditLog::where('action', 'enterprise.inquiry')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('dedicated_full_project_2500_usd', $audit->after_state['package']);
        $this->assertEquals(5, $audit->after_state['router_count']);
        $this->assertEquals('dedicated_cloud', $audit->after_state['deployment_target']);

        Queue::assertPushed(DispatchWebhookJob::class, fn ($job) => $job->event === 'ENTERPRISE_SALES_INQUIRY');
    }

    /** @test */
    public function multi_router_fleet_encrypts_passwords_and_enforces_strict_tenant_isolation(): void
    {
        $tenantA = Tenant::create([
            'name' => 'ISP Alpha', 'slug' => 'isp-alpha', 'contact_name' => 'A',
            'contact_email' => 'a@alpha.net', 'contact_phone' => '+1001', 'plan' => 'growth', 'status' => 'active',
        ]);
        $tenantB = Tenant::create([
            'name' => 'ISP Beta', 'slug' => 'isp-beta', 'contact_name' => 'B',
            'contact_email' => 'b@beta.net', 'contact_phone' => '+1002', 'plan' => 'growth', 'status' => 'active',
        ]);

        $adminA = User::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id, 'name' => 'Admin A', 'email' => 'admin@alpha.net',
            'password' => Hash::make('password123'), 'role' => 'super_admin', 'is_active' => true,
        ]);
        $adminB = User::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id, 'name' => 'Admin B', 'email' => 'admin@beta.net',
            'password' => Hash::make('password123'), 'role' => 'super_admin', 'is_active' => true,
        ]);

        // Admin A creates POP router
        $this->actingAs($adminA)->post('/settings/routers', [
            'name'              => 'North POP CCR2004',
            'code'              => 'POP-NORTH',
            'driver'            => 'simulator',
            'host'              => '203.0.113.10',
            'port'              => 443,
            'username'          => 'noc_api',
            'password'          => 'SuperRouterSecret#99',
            'isolation_profile' => 'ISOLIR',
            'use_ssl'           => '1',
            'is_default'        => '1',
        ])->assertSessionHas('success');

        $routerA = TenantRouter::withoutGlobalScopes()->where('code', 'POP-NORTH')->first();
        $this->assertNotNull($routerA);
        $this->assertEquals($tenantA->id, $routerA->tenant_id);

        // Raw DB column MUST be encrypted, never plaintext
        $rawPass = (string) DB::table('tenant_routers')->where('id', $routerA->id)->value('password');
        $this->assertStringNotContainsString('SuperRouterSecret#99', $rawPass);
        $this->assertNotEmpty(Crypt::decryptString($rawPass));
        $this->assertEquals('SuperRouterSecret#99', $routerA->password);
        $this->assertArrayNotHasKey('password', $routerA->toArray());

        // Admin A can test connectivity
        $this->actingAs($adminA)->post("/settings/routers/{$routerA->id}/test")
            ->assertSessionHas('success');
        $this->assertEquals('connected', $routerA->fresh()->last_test_status);

        // Admin B CANNOT test or delete Tenant A's router (Route Model Binding + TenantScope = 404)
        $this->actingAs($adminB)->post("/settings/routers/{$routerA->id}/test")->assertStatus(404);
        $this->actingAs($adminB)->delete("/settings/routers/{$routerA->id}")->assertStatus(404);
        $this->assertNotNull(TenantRouter::withoutGlobalScopes()->find($routerA->id));
    }

    /** @test */
    public function one_click_mikrotik_sync_and_csv_import_upsert_subscribers_safely(): void
    {
        $tenant = Tenant::create([
            'name' => 'Sync Fiber ISP', 'slug' => 'sync-fiber', 'contact_name' => 'Ops',
            'contact_email' => 'ops@syncfiber.net', 'contact_phone' => '+1003', 'plan' => 'growth', 'status' => 'active',
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'name' => 'Sync Admin', 'email' => 'admin@syncfiber.net',
            'password' => Hash::make('password123'), 'role' => 'super_admin', 'is_active' => true,
        ]);
        $router = TenantRouter::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'name' => 'Main POP', 'code' => 'POP-01',
            'driver' => 'simulator', 'host' => '198.51.100.5', 'is_default' => true, 'is_active' => true,
        ]);

        // 1. 1-Click MikroTik PPPoE Sync imports sample secrets including isolated line
        $this->actingAs($admin)->post('/customers/sync-mikrotik', [
            'router_id' => $router->id,
        ])->assertSessionHas('success');

        $this->assertEquals(3, Customer::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $isolated = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('pppoe_username', 'pppoe_northpop_03')
            ->first();
        $this->assertNotNull($isolated);
        $this->assertEquals('isolated', $isolated->status);
        $this->assertEquals($router->id, $isolated->router_id);

        // 2. Bulk CSV Import creates new & updates existing without duplicating
        $csvContent = "customer_code,name,phone,email,address,service_plan,pppoe_username,ont_serial_number,status\n"
                    . "CUST-CSV1,Oceanic Studio,+14155550900,it@oceanic.io,\"10 Bay St\",Fiber-500Mbps,pppoe_oceanic,ZTEG11223344,active\n"
                    . ",Metro Logistics Updated,+14155550103,ops@metro.io,\"99 Port Rd\",Fiber-200Mbps,pppoe_northpop_03,HWTC55667788,active\n";
        $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

        $this->actingAs($admin)->post('/customers/import-csv', [
            'csv_file'  => $file,
            'router_id' => $router->id,
        ])->assertSessionHas('success');

        $this->assertEquals(4, Customer::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertEquals('active', $isolated->fresh()->status);
        $this->assertEquals('Metro Logistics Updated', $isolated->fresh()->name);
    }

    /** @test */
    public function global_stripe_checkout_usd_payment_verifies_hmac_signature_and_queues_mikrotik_unblock(): void
    {
        Queue::fake();
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id'  => 'cs_test_a1b2c3d4',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4',
            ], 200),
        ]);

        $webhookSecret = 'whsec_test_global_secret_987654';
        $tenant = Tenant::create([
            'name' => 'Global Stripe ISP', 'slug' => 'stripe-isp', 'contact_name' => 'Stripe Admin',
            'contact_email' => 'stripe@isp.io', 'contact_phone' => '+14150001111', 'plan' => 'growth', 'status' => 'active',
            'settings' => [
                'currency' => 'USD',
                'payment'  => [
                    'driver'         => 'stripe',
                    'server_key'     => 'sk_test_51GlobalDemoKey',
                    'webhook_secret' => $webhookSecret,
                    'is_production'  => false,
                ],
            ],
        ]);
        TenantContext::setTenant($tenant);

        $customer = Customer::create([
            'tenant_id' => $tenant->id, 'customer_code' => 'US-001', 'name' => 'John Global',
            'phone' => '+14155550199', 'address' => '100 Main St', 'service_plan' => 'Fiber-300Mbps',
            'pppoe_username' => 'john_pppoe', 'status' => 'isolated',
        ]);

        $task = Task::create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'title' => 'Monthly Billing Collection',
            'customer_name' => $customer->name, 'customer_phone' => $customer->phone, 'address' => $customer->address,
            'status' => 'in_progress', 'priority' => 'medium', 'billing_amount' => 49.00,
        ]);

        // 1. USD $49.00 is accepted (not rejected by old IDR 1,000 minimum!) and calls Stripe Checkout API
        $tx = QrisPaymentService::forTenant($tenant)->generateTaskQris($task, 49.00);
        $this->assertEquals('stripe', $tx->provider);
        $this->assertEquals('stripe_checkout', $tx->payment_method);
        $this->assertEquals(49.00, (float) $tx->amount);
        $this->assertStringContainsString('https://checkout.stripe.com/', $tx->qr_payload);

        // 2. Tampered Stripe-Signature header MUST be rejected with HTTP 403
        $stripeEvent = json_encode([
            'id'   => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id'                  => 'cs_test_a1b2c3d4',
                    'client_reference_id' => $tx->reference_no,
                    'payment_status'      => 'paid',
                    'currency'            => 'usd',
                    'amount_total'        => 4900,
                ],
            ],
        ]);

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=' . time() . ',v1=invalid_signature_hash',
        ], $stripeEvent)->assertStatus(403);

        // 3. Valid HMAC-SHA256 Stripe-Signature settles invoice & queues async MikroTik un-isolation!
        $ts = time();
        $validSig = hash_hmac('sha256', "{$ts}.{$stripeEvent}", $webhookSecret);
        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$ts},v1={$validSig}",
        ], $stripeEvent)->assertStatus(200)->assertJsonPath('status', 'paid');

        $this->assertEquals('paid', $tx->fresh()->status);
        $this->assertEquals('paid', $task->fresh()->payment_status);

        Queue::assertPushed(UnIsolateCustomerOnPaymentJob::class, function ($job) use ($tenant, $customer, $tx) {
            return $job->tenantId === $tenant->id
                && $job->customerId === $customer->id
                && $job->referenceNo === $tx->reference_no;
        });
    }
}
