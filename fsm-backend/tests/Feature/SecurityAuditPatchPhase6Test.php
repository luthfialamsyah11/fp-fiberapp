<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\Drivers\QrisSimulatorDriver;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
}
