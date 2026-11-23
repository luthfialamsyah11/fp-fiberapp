<?php

namespace Tests\Feature;

use App\Domain\Tasks\TaskStateMachine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\PaymentTransaction;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\TechnicianLocation;
use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Payment\Drivers\QrisSimulatorDriver;
use App\Services\Payment\Drivers\StripePaymentDriver;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAuditPatchPhase6Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $rootTenant;
    protected Tenant $usdTenant;
    protected User $usdAdmin;
    protected User $usdTech;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();

        $this->rootTenant = Tenant::find(1) ?? Tenant::create([
            'id'              => 1,
            'name'            => 'Root Platform ISP',
            'slug'            => 'root-isp',
            'contact_name'    => 'Root',
            'contact_email'   => 'root@bentengsiber.com',
            'contact_phone'   => '+620001',
            'plan'            => 'enterprise',
            'status'          => 'active',
            'max_technicians' => 9999,
            'max_fiber_nodes' => 9999,
            'settings'        => ['currency' => 'IDR'],
        ]);

        $this->usdTenant = Tenant::create([
            'name'            => 'Global USD Fiber LLC',
            'slug'            => 'global-usd',
            'contact_name'    => 'Alex',
            'contact_email'   => 'alex@globalusd.io',
            'contact_phone'   => '+14155550100',
            'plan'            => 'growth',
            'status'          => 'active',
            'max_technicians' => 40,
            'max_fiber_nodes' => 500,
            'settings'        => [
                'currency' => 'USD',
                'payment'  => ['driver' => 'simulator', 'is_production' => false],
            ],
        ]);

        $this->usdAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->usdTenant->id,
            'name'      => 'USD Super Admin',
            'email'     => 'admin@globalusd.io',
            'password'  => Hash::make('SecurePass#2026'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->usdTech = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->usdTenant->id,
            'name'      => 'USD Field Tech',
            'email'     => 'tech@globalusd.io',
            'password'  => Hash::make('SecurePass#2026'),
            'role'      => 'technician',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    /** @test */
    public function usd_tenant_can_generate_sub_1000_dollar_payments_via_api_and_web(): void
    {
        TenantContext::setTenant($this->usdTenant);
        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'USD Monthly Collect',
            'customer_name'  => 'Jane Doe',
            'customer_phone' => '+14155550111',
            'address'        => '10 Market St',
            'status'         => 'in_progress',
            'priority'       => 'medium',
            'billing_amount' => 49.00,
        ]);

        // 1. Mobile API accepts $49.00 USD (previously rejected by hardcoded IDR 1,000 minimum!)
        Sanctum::actingAs($this->usdTech);
        $this->postJson("/api/tasks/{$task->id}/qris", ['amount' => 49.00])
            ->assertStatus(201)
            ->assertJsonPath('transaction.amount', 49);

        // Payment status API must return tenant currency metadata for Mobile PWA dynamic formatting
        $this->getJson("/api/tasks/{$task->id}/payment-status")
            ->assertStatus(200)
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('is_paid', false);

        // 2. Web NOC Console also accepts $25.00 USD
        $this->actingAs($this->usdAdmin)
            ->post("/tasks/{$task->id}/qris", ['amount' => 25.00])
            ->assertSessionHas('success');
    }

    /** @test */
    public function super_admin_tenant_owner_can_access_api_admin_endpoints(): void
    {
        Sanctum::actingAs($this->usdAdmin);

        $this->getJson('/api/admin/dashboard')->assertStatus(200);
        $this->getJson('/api/admin/tasks')->assertStatus(200);
    }

    /** @test */
    public function expired_tracking_token_is_rejected_on_csat_rating_submission(): void
    {
        TenantContext::setTenant($this->usdTenant);
        $token = Str::random(32);
        Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'Completed 48h Ago',
            'customer_name'  => 'Expired Customer',
            'customer_phone' => '+14155550122',
            'address'        => '20 Pine St',
            'status'         => 'completed',
            'priority'       => 'low',
            'tracking_token' => $token,
            'completed_at'   => now()->subHours(48),
        ]);

        $this->postJson("/api/public/track/{$token}/rating", [
            'rating'   => 5,
            'feedback' => 'Late rating attempt',
        ])->assertStatus(410);
    }

    /** @test */
    public function inventory_csv_export_neutralizes_excel_formula_injection(): void
    {
        TenantContext::setTenant($this->usdTenant);
        InventoryItem::create([
            'tenant_id'   => $this->usdTenant->id,
            'code'        => '=CMD|A',
            'name'        => '+SUM(1,2) Drop Cable',
            'category'    => 'material',
            'unit'        => '@meter',
            'stock'       => 100,
            'unit_price'  => 1.50,
            'description' => '-1+1 dangerous formula',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->usdAdmin)->get('/inventory/export');
        $response->assertStatus(200);
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=CMD|A", $csv);
        $this->assertStringContainsString("'+SUM(1,2) Drop Cable", $csv);
        $this->assertStringContainsString("'@meter", $csv);
        $this->assertStringContainsString("'-1+1 dangerous formula", $csv);
    }

    /** @test */
    public function staff_password_policy_and_role_guard_and_report_invalid_date_are_hardened(): void
    {
        // 1. Weak 6-char password rejected (min:8 enforced)
        $this->actingAs($this->usdAdmin)->post('/staff', [
            'name'                  => 'Weak Staff',
            'email'                 => 'weak@globalusd.io',
            'password'              => '123456',
            'password_confirmation' => '123456',
            'role'                  => 'noc_dispatcher',
        ])->assertSessionHasErrors('password');

        // 2. Staff toggleStatus refuses non-portal technician role (404)
        $this->actingAs($this->usdAdmin)
            ->post("/staff/{$this->usdTech->id}/toggle-status")
            ->assertStatus(404);

        // 3. ReportController handles malformed custom date range without HTTP 500
        $this->actingAs($this->usdAdmin)
            ->get('/reports?timeframe=custom&start_date=not-a-date&end_date=also-bad')
            ->assertStatus(200);
    }

    /** @test */
    public function media_controller_blocks_gif_and_sets_private_nosniff_and_noindex_headers(): void
    {
        $publicRoot = storage_path('app/public/security_test');
        @mkdir($publicRoot, 0775, true);
        $pngFile = $publicRoot . DIRECTORY_SEPARATOR . 'sample.png';
        $gifFile = $publicRoot . DIRECTORY_SEPARATOR . 'sample.gif';
        file_put_contents($pngFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        file_put_contents($gifFile, 'GIF89a');

        try {
            // GIF rejected with 404
            $this->get('/storage/security_test/sample.gif')->assertStatus(404);

            // PNG served with hardened headers & non-wildcard CORS
            $res = $this->withHeaders(['Origin' => 'https://app.bentengsiber.com'])
                ->get('/storage/security_test/sample.png');
            $res->assertStatus(200);
            $res->assertHeader('X-Content-Type-Options', 'nosniff');
            $res->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $res->assertHeader('Access-Control-Allow-Origin', 'https://app.bentengsiber.com');
            $this->assertStringContainsString('private', (string) $res->headers->get('Cache-Control'));
        } finally {
            @unlink($pngFile);
            @unlink($gifFile);
            @rmdir($publicRoot);
        }
    }

    /** @test */
    public function simulator_driver_rejects_default_hardcoded_secret_in_production_environment(): void
    {
        $originalEnv = app()->environment();
        app()->detectEnvironment(fn () => 'production');
        try {
            $driver = new QrisSimulatorDriver([]);
            $this->assertFalse($driver->verifyWebhookSignature(['order_id' => 'X'], 'simulated_valid_sig'));

            $customDriver = new QrisSimulatorDriver(['webhook_secret' => 'custom_strong_secret_2026']);
            $this->assertTrue($customDriver->verifyWebhookSignature(['order_id' => 'X'], 'custom_strong_secret_2026'));
        } finally {
            app()->detectEnvironment(fn () => $originalEnv);
        }
    }

    /** @test */
    public function equator_zero_latitude_enforces_geofence_gate_instead_of_bypassing(): void
    {
        TenantContext::setTenant($this->usdTenant);
        SettingService::set('geofence_check_meters', '100');

        // Pontianak / Equator task with latitude = 0.0 (previously treated as falsy!)
        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'Equator FTTH Install',
            'customer_name'  => 'Equator Subscriber',
            'customer_phone' => '+628111000222',
            'address'        => 'Jl. Khatulistiwa 0.0',
            'latitude'       => 0.0,
            'longitude'      => 109.333,
            'status'         => 'accepted',
            'priority'       => 'medium',
        ]);

        // Technician GPS is ~5.5km north of the Equator site
        TechnicianLocation::create([
            'technician_id' => $this->usdTech->id,
            'task_id'       => $task->id,
            'latitude'      => 0.05,
            'longitude'     => 109.333,
        ]);

        $fsm = app(TaskStateMachine::class);

        try {
            $fsm->transition($task, 'on-going', $this->usdTech);
            $this->fail('Expected ValidationException on geofence when task latitude is 0.0.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('geofence', $e->errors());
        }
    }

    /** @test */
    public function olt_hardware_gate_blocks_completion_when_ont_serial_is_missing_for_linked_subscriber(): void
    {
        $this->usdTenant->update([
            'network_settings' => [
                'olt_driver'                      => 'simulator',
                'require_hardware_olt_validation' => true,
                'opm_max_attenuation_dbm'         => -27.0,
            ],
        ]);
        TenantContext::setTenant($this->usdTenant->fresh());
        SettingService::set('geofence_check_meters', '0');

        $customer = Customer::create([
            'tenant_id'         => $this->usdTenant->id,
            'customer_code'     => 'CUST-NO-ONT',
            'name'              => 'Subscriber Missing ONT SN',
            'phone'             => '+14155550199',
            'address'           => '500 Fiber Way',
            'service_plan'      => '1Gbps',
            'ont_serial_number' => null,
            'status'            => 'active',
        ]);

        $task = Task::create([
            'tenant_id'          => $this->usdTenant->id,
            'technician_id'      => $this->usdTech->id,
            'customer_id'        => $customer->id,
            'title'              => 'FTTH Activation Missing ONT SN',
            'customer_name'      => $customer->name,
            'customer_phone'     => $customer->phone,
            'address'            => $customer->address,
            'status'             => 'on-going',
            'priority'           => 'high',
            'customer_signature' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

        ProofOfWork::create([
            'task_id'     => $task->id,
            'image'       => 'proofs/sample_ont.jpg',
            'slot'        => 'after',
            'description' => 'Installed drop cable',
        ]);

        $fsm = app(TaskStateMachine::class);

        try {
            $fsm->transition($task, 'completed', $this->usdTech);
            $this->fail('Expected ValidationException on ont_serial_number when OLT gate is enabled and ONT SN is missing.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ont_serial_number', $e->errors());
        }
    }

    /** @test */
    public function idempotency_middleware_acquires_atomic_lock_and_replays_cached_response(): void
    {
        TenantContext::setTenant($this->usdTenant);
        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'Idempotent Checkout Task',
            'customer_name'  => 'Replay Customer',
            'customer_phone' => '+14155550144',
            'address'        => '88 Mission St',
            'status'         => 'on-going',
            'priority'       => 'medium',
            'billing_amount' => 35.00,
        ]);

        Sanctum::actingAs($this->usdTech);
        $idemKey = 'idem-test-' . Str::uuid();

        $first = $this->withHeaders(['Idempotency-Key' => $idemKey])
            ->postJson("/api/tasks/{$task->id}/qris", ['amount' => 35.00]);
        $first->assertStatus(201);
        $firstRef = $first->json('transaction.reference_no');
        $this->assertNotEmpty($firstRef);

        $second = $this->withHeaders(['Idempotency-Key' => $idemKey])
            ->postJson("/api/tasks/{$task->id}/qris", ['amount' => 35.00]);
        $second->assertStatus(201);
        $second->assertHeader('X-Idempotent-Replay', 'true');
        $this->assertSame($firstRef, $second->json('transaction.reference_no'));

        $this->assertSame(
            1,
            PaymentTransaction::where('task_id', $task->id)->count(),
            'Idempotent replay must not create a duplicate PaymentTransaction row.'
        );
    }

    /** @test */
    public function stripe_webhook_rejects_simulator_provider_mismatch_and_settles_stripe_even_after_driver_switch(): void
    {
        $whSecret = 'whsec_test_audit_bug06_secret';
        $this->usdTenant->update([
            'settings' => [
                'currency' => 'USD',
                'payment'  => [
                    'driver'         => 'simulator', // Active driver switched away from stripe
                    'server_key'     => 'sk_test_dummy',
                    'webhook_secret' => $whSecret,
                    'is_production'  => false,
                ],
            ],
        ]);
        TenantContext::setTenant($this->usdTenant->fresh());

        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'Stripe Webhook Isolation Check',
            'customer_name'  => 'Global Client',
            'customer_phone' => '+14155550900',
            'address'        => '100 Main St',
            'status'         => 'on-going',
            'priority'       => 'medium',
            'billing_amount' => 49.00,
        ]);

        // 1. A SIMULATOR transaction must NOT be settleable via /api/webhooks/stripe (cross-driver forgery blocked!)
        $simTx = PaymentTransaction::create([
            'tenant_id'      => $this->usdTenant->id,
            'task_id'        => $task->id,
            'reference_no'   => 'SIM-CROSS-01',
            'amount'         => 49.00,
            'payment_method' => 'qris',
            'provider'       => 'simulator',
            'status'         => 'pending',
            'expires_at'     => now()->addMinutes(30),
        ]);

        $simPayload = json_encode([
            'id'   => 'evt_forged_sim',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $simTx->reference_no,
                    'payment_status'      => 'paid',
                    'currency'            => 'usd',
                    'amount_total'        => 4900,
                ],
            ],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 'simulated_valid_sig'],
            $simPayload
        )->assertStatus(422);
        $this->assertEquals('pending', $simTx->fresh()->status);

        // 2. A real STRIPE transaction MUST still settle cleanly via valid Stripe HMAC even though tenant switched active driver to simulator!
        $stripeTx = PaymentTransaction::create([
            'tenant_id'      => $this->usdTenant->id,
            'task_id'        => $task->id,
            'reference_no'   => 'STR-VALID-02',
            'amount'         => 49.00,
            'payment_method' => 'stripe_checkout',
            'provider'       => 'stripe',
            'status'         => 'pending',
            'expires_at'     => now()->addMinutes(30),
        ]);

        $stripeBody = json_encode([
            'id'   => 'evt_real_stripe_02',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $stripeTx->reference_no,
                    'payment_status'      => 'paid',
                    'currency'            => 'usd',
                    'amount_total'        => 4900,
                ],
            ],
        ]);
        $ts = time();
        $sig = hash_hmac('sha256', "{$ts}.{$stripeBody}", $whSecret);

        $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t={$ts},v1={$sig}"],
            $stripeBody
        )->assertStatus(200);
        $this->assertEquals('paid', $stripeTx->fresh()->status);
    }

    /** @test */
    public function stripe_driver_treats_idr_as_two_decimal_currency_and_jpy_as_zero_decimal(): void
    {
        TenantContext::setTenant($this->usdTenant);
        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'IDR Stripe Minor Unit Test',
            'customer_name'  => 'Budi',
            'customer_phone' => '+6281111111',
            'address'        => 'Jakarta',
            'status'         => 'on-going',
            'priority'       => 'medium',
            'billing_amount' => 250000,
        ]);

        $capturedUnitAmounts = [];
        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$capturedUnitAmounts) {
            $data = $request->data();
            $capturedUnitAmounts[$data['line_items[0][price_data][currency]'] ?? ''] = $data['line_items[0][price_data][unit_amount]'] ?? null;
            return Http::response([
                'id'  => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            ], 200);
        });

        $idrDriver = new StripePaymentDriver(['server_key' => 'sk_test_123', 'currency' => 'IDR']);
        $idrDriver->createDynamicQris($task, 250000.0);

        $jpyDriver = new StripePaymentDriver(['server_key' => 'sk_test_123', 'currency' => 'JPY']);
        $jpyDriver->createDynamicQris($task, 5000.0);

        // Per Stripe spec: IDR is 2-decimal (250,000 IDR => 25,000,000 sen), JPY is 0-decimal (5,000 JPY => 5,000)
        $this->assertSame(25000000, (int) ($capturedUnitAmounts['idr'] ?? 0));
        $this->assertSame(5000, (int) ($capturedUnitAmounts['jpy'] ?? 0));
    }

    /** @test */
    public function master_admin_plan_update_applies_canonical_quotas_without_halving_limits(): void
    {
        $rootAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->rootTenant->id,
            'name'      => 'Root Platform Owner',
            'email'     => 'platform-owner@bentengsiber.com',
            'password'  => Hash::make('SecurePass#2026'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($rootAdmin)
            ->post("/master-admin/tenants/{$this->usdTenant->id}/plan", ['plan' => 'growth'])
            ->assertRedirect();

        $fresh = $this->usdTenant->fresh();
        $this->assertSame(40, $fresh->max_technicians);
        $this->assertSame(500, $fresh->max_fiber_nodes);

        $this->actingAs($rootAdmin)
            ->post("/master-admin/tenants/{$this->usdTenant->id}/plan", ['plan' => 'starter'])
            ->assertRedirect();

        $fresh = $this->usdTenant->fresh();
        $this->assertSame(10, $fresh->max_technicians);
        $this->assertSame(100, $fresh->max_fiber_nodes);
    }

    /** @test */
    public function settings_router_normalizes_case_and_boolean_zero_flags_and_csv_supports_semicolon(): void
    {
        TenantContext::setTenant($this->usdTenant);

        // 1. Create POP-JKT-01 then attempt lowercase duplicate pop-jkt-01 (must fail validation cleanly, NOT HTTP 500!)
        $this->actingAs($this->usdAdmin)->post('/settings/routers', [
            'name'   => 'Jakarta Core POP',
            'code'   => 'POP-JKT-01',
            'driver' => 'simulator',
        ])->assertSessionHas('success');

        $this->actingAs($this->usdAdmin)->post('/settings/routers', [
            'name'   => 'Duplicate Lowercase POP',
            'code'   => 'pop-jkt-01',
            'driver' => 'simulator',
        ])->assertSessionHasErrors('code');
        $this->assertSame(1, TenantRouter::where('tenant_id', $this->usdTenant->id)->count());

        // 2. Explicit '0' on payment_is_production must store false (NOT true from $request->has()) + AUD currency allowed
        $this->actingAs($this->usdAdmin)->post('/settings', [
            'company_name'             => 'Global USD Fiber LLC',
            'support_email'            => 'ops@globalusd.io',
            'support_phone'            => '+14155550100',
            'default_country_code'     => '1',
            'currency'                 => 'AUD',
            'gps_refresh_interval'     => 15,
            'map_style'                => 'openstreetmap',
            'sla_warning_hours'        => 4,
            'geofence_check_meters'    => 100,
            'telemetry_retention_days' => 30,
            'payment_driver'           => 'stripe',
            'payment_is_production'    => '0',
            'mikrotik_driver'          => 'simulator',
            'mikrotik_use_ssl'         => '0',
        ])->assertSessionHas('success');

        $freshTenant = $this->usdTenant->fresh();
        $this->assertSame('AUD', $freshTenant->settings['currency'] ?? null);
        $this->assertFalse((bool) ($freshTenant->settings['payment']['is_production'] ?? true));
        $this->assertFalse((bool) ($freshTenant->network_settings['mikrotik_use_ssl'] ?? true));

        // 3. Semicolon-delimited Excel CSV import works seamlessly
        $csvContent = "customer_code;name;phone;pppoe_username;service_plan;status\n"
            . "EU-001;Hans Mueller;+491512345;hans_pppoe;1Gbps Fiber;active\n";
        $csvFile = UploadedFile::fake()->createWithContent('subscribers_eu.csv', $csvContent);

        $this->actingAs($this->usdAdmin)->post('/customers/import-csv', [
            'csv_file' => $csvFile,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'tenant_id'      => $this->usdTenant->id,
            'customer_code'  => 'EU-001',
            'pppoe_username' => 'hans_pppoe',
        ]);
    }

    /** @test */
    public function master_platform_owner_cast_and_allowlist_controls_access_and_sidebar(): void
    {
        $rootAdmin = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->rootTenant->id,
            'name'      => 'Root Demo Admin',
            'email'     => 'admin@fsm.com',
            'password'  => Hash::make('SecurePass#2026'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $hqOwner = User::withoutGlobalScopes()->create([
            'tenant_id' => $this->rootTenant->id,
            'name'      => 'Benteng Siber HQ Owner',
            'email'     => 'leoallogne@gmail.com',
            'password'  => Hash::make('SecurePass#2026'),
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        // 1. Default mode: integer cast ensures tenant_id === 1 matches reliably and renders Master Platform link + console
        $this->assertSame(1, $rootAdmin->fresh()->tenant_id);
        $this->assertTrue($rootAdmin->fresh()->isPlatformOwner());
        $this->actingAs($rootAdmin)->get('/master-admin')->assertStatus(200)->assertSee('Master Platform');

        // 2. Production allowlist mode: isolates public demo admin@fsm.com out of /master-admin while HQ Owner retains access
        config(['app.platform_owner_emails' => 'leoallogne@gmail.com']);
        try {
            $this->assertFalse($rootAdmin->fresh()->isPlatformOwner());
            $this->assertTrue($hqOwner->fresh()->isPlatformOwner());

            $this->actingAs($rootAdmin)->get('/master-admin')->assertStatus(403);
            $this->actingAs($hqOwner)->get('/master-admin')->assertStatus(200);
        } finally {
            config(['app.platform_owner_emails' => '']);
        }
    }

    /** @test */
    public function non_stripe_webhook_enforces_transaction_provider_driver_and_rejects_stripe_transactions(): void
    {
        $this->usdTenant->update([
            'settings' => [
                'currency' => 'IDR',
                'payment'  => [
                    'driver'        => 'simulator', // Active driver toggled back to simulator
                    'server_key'    => 'Mid-server-secret-2026',
                    'is_production' => false,
                ],
            ],
        ]);
        TenantContext::setTenant($this->usdTenant->fresh());

        $task = Task::create([
            'tenant_id'      => $this->usdTenant->id,
            'technician_id'  => $this->usdTech->id,
            'title'          => 'Provider Locked Webhook Test',
            'customer_name'  => 'Siti',
            'customer_phone' => '+6281199999',
            'address'        => 'Bandung',
            'status'         => 'on-going',
            'priority'       => 'medium',
            'billing_amount' => 150000,
        ]);

        // 1. Stripe tx submitted to non-Stripe /api/webhooks/payment must be rejected with 422
        $stripeTx = PaymentTransaction::create([
            'tenant_id'      => $this->usdTenant->id,
            'task_id'        => $task->id,
            'reference_no'   => 'STR-WRONG-ENDPOINT',
            'amount'         => 150000,
            'payment_method' => 'stripe_checkout',
            'provider'       => 'stripe',
            'status'         => 'pending',
            'expires_at'     => now()->addMinutes(30),
        ]);

        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $stripeTx->reference_no,
            'transaction_status' => 'settlement',
        ], ['X-Callback-Signature' => 'simulated_valid_sig'])->assertStatus(422);

        // 2. Midtrans tx must reject simulator signature even when active tenant driver is simulator, and accept real Midtrans SHA-512
        $midtransTx = PaymentTransaction::create([
            'tenant_id'      => $this->usdTenant->id,
            'task_id'        => $task->id,
            'reference_no'   => 'BS-MID-LOCK-01',
            'amount'         => 150000,
            'payment_method' => 'qris',
            'provider'       => 'midtrans',
            'status'         => 'pending',
            'expires_at'     => now()->addMinutes(30),
        ]);

        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $midtransTx->reference_no,
            'status_code'        => '200',
            'gross_amount'       => '150000.00',
            'transaction_status' => 'settlement',
        ], ['X-Callback-Signature' => 'simulated_valid_sig'])->assertStatus(403);

        $validMidtransSig = hash('sha512', $midtransTx->reference_no . '200' . '150000.00' . 'Mid-server-secret-2026');
        $this->postJson('/api/webhooks/payment', [
            'order_id'           => $midtransTx->reference_no,
            'status_code'        => '200',
            'gross_amount'       => '150000.00',
            'transaction_status' => 'settlement',
            'signature_key'      => $validMidtransSig,
        ])->assertStatus(200);
        $this->assertEquals('paid', $midtransTx->fresh()->status);
    }
}
