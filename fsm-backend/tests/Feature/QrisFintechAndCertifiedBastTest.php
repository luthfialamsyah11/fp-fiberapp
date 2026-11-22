<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\Network\MikrotikService;
use App\Services\Payment\QrisPaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisFintechAndCertifiedBastTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $technician;
    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        MikrotikSimulatorDriver::reset();

        $this->tenant = Tenant::create([
            'name'             => 'Nusantara Fiber Telecom',
            'slug'             => 'nusantara-fiber',
            'contact_name'     => 'Nusantara Billing Admin',
            'contact_email'    => 'billing@nusantara.net.id',
            'contact_phone'    => '081233445566',
            'plan'             => 'enterprise',
            'status'           => 'active',
            'network_settings' => [
                'mikrotik_driver' => 'simulator',
            ],
            'settings'         => [
                'payment' => [
                    'driver'     => 'simulator',
                    'server_key' => 'test_server_key',
                ],
            ],
        ]);

        TenantContext::setTenant($this->tenant);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Nusantara',
            'email'     => 'admin@nusantara.net.id',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->technician = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Technician Nusantara',
            'email'     => 'tech@nusantara.net.id',
            'password'  => bcrypt('password'),
            'role'      => 'technician',
            'is_online' => true,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-FIN-01',
            'name'              => 'Pak RT Agus',
            'phone'             => '081234567890',
            'address'           => 'Komplek Griya Indah No. 4',
            'service_plan'      => 'NUSANTARA-50M',
            'pppoe_username'    => 'agus_nusantara',
            'ont_serial_number' => 'ZTEG99887766',
            'status'            => 'isolated', // Customer is currently isolated due to unpaid bill
        ]);

        // Pre-configure secret on simulated router as ISOLIR
        $mikrotik = MikrotikService::forTenant($this->tenant);
        $mikrotik->getDriver()->upsertSecret('agus_nusantara', 'pass123', 'ISOLIR');
    }

    public function test_technician_can_generate_dynamic_qris_for_unpaid_task()
    {
        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $this->customer->id,
            'customer_name'    => $this->customer->name,
            'title'            => 'Tagihan Tertunggak dan Isolir On-Site',
            'service_category' => 'tunggakan',
            'billing_amount'   => 275000.0,
            'payment_status'   => 'unpaid',
            'status'           => 'on-going',
        ]);

        $res = $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/qris", [
                'amount'         => 275000.0,
                'expiry_minutes' => 15,
            ]);

        $res->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'transaction' => [
                    'id',
                    'reference_no',
                    'amount',
                    'qr_payload',
                    'qr_image_url',
                    'status',
                    'expires_at',
                ]
            ]);

        $this->assertEquals('pending', $task->fresh()->payment_status);
        $this->assertDatabaseHas('payment_transactions', [
            'task_id' => $task->id,
            'amount'  => 275000.0,
            'status'  => 'pending',
        ]);

        // Test polling payment status
        $statusRes = $this->actingAs($this->technician, 'sanctum')
            ->getJson("/api/tasks/{$task->id}/payment-status");

        $statusRes->assertStatus(200)
            ->assertJson([
                'task_id'        => $task->id,
                'payment_status' => 'pending',
                'is_paid'        => false,
            ]);
    }

    public function test_payment_webhook_verifies_signature_and_instantly_unblocks_customer_on_mikrotik()
    {
        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $this->customer->id,
            'customer_name'    => $this->customer->name,
            'title'            => 'Penagihan Tunggakan Fiber',
            'billing_amount'   => 250000.0,
            'payment_status'   => 'unpaid',
            'status'           => 'on-going',
        ]);

        $paymentService = QrisPaymentService::forTenant($this->tenant);
        $transaction = $paymentService->generateTaskQris($task, 250000.0);

        $this->assertEquals('isolated', $this->customer->fresh()->status);
        $this->assertEquals('ISOLIR', MikrotikService::forTenant($this->tenant)->getDriver()->getSecret('agus_nusantara')['profile']);

        // Post public webhook from payment gateway
        $res = $this->postJson('/api/webhooks/payment', [
            'order_id'           => $transaction->reference_no,
            'status_code'        => '200',
            'gross_amount'       => '250000.00',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
        ], [
            'X-Callback-Signature' => 'simulated_valid_sig',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'status' => 'paid',
            ]);

        // Assert transaction & task marked as paid
        $this->assertEquals('paid', $transaction->fresh()->status);
        $this->assertEquals('paid', $task->fresh()->payment_status);

        // ASSERT INSTANT AUTO-UNBLOCK ON MIKROTIK:
        // Customer profile MUST be restored from ISOLIR back to NUSANTARA-50M!
        $this->assertEquals('active', $this->customer->fresh()->status);
        $secret = MikrotikService::forTenant($this->tenant)->getDriver()->getSecret('agus_nusantara');
        $this->assertEquals('NUSANTARA-50M', $secret['profile']);
    }

    public function test_payment_webhook_is_strictly_idempotent()
    {
        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $this->customer->id,
            'customer_name'    => $this->customer->name,
            'title'            => 'Idempotent Webhook Test',
            'billing_amount'   => 150000.0,
            'payment_status'   => 'unpaid',
            'status'           => 'on-going',
        ]);

        $paymentService = QrisPaymentService::forTenant($this->tenant);
        $transaction = $paymentService->generateTaskQris($task, 150000.0);

        $payload = [
            'order_id'           => $transaction->reference_no,
            'transaction_status' => 'settlement',
        ];

        // Call 1
        $res1 = $this->postJson('/api/webhooks/payment', $payload, ['X-Callback-Signature' => 'simulated_valid_sig']);
        $res1->assertStatus(200);

        $firstPaidAt = $transaction->fresh()->paid_at;
        $this->assertNotNull($firstPaidAt);

        // Call 2 (Duplicate Webhook retry from gateway)
        $res2 = $this->postJson('/api/webhooks/payment', $payload, ['X-Callback-Signature' => 'simulated_valid_sig']);
        $res2->assertStatus(200);

        // Assert paid_at has not been tampered with or modified
        $this->assertEquals($firstPaidAt->toIso8601String(), $transaction->fresh()->paid_at->toIso8601String());
    }

    public function test_public_bast_verification_endpoint_renders_cryptographic_and_optical_proof()
    {
        $task = Task::create([
            'tenant_id'            => $this->tenant->id,
            'technician_id'        => $this->technician->id,
            'customer_id'          => $this->customer->id,
            'customer_name'        => $this->customer->name,
            'title'                => 'FTTH High Speed Link Activation',
            'service_category'     => 'new_install',
            'status'               => 'completed',
            'optical_power_rx_dbm' => -21.40,
            'optical_power_tx_dbm' => 2.50,
            'hardware_verified_at' => now(),
            'payment_status'       => 'paid',
            'completed_at'         => now(),
        ]);

        $task->update([
            'bast_hash'      => $task->computeBastHash(),
            'bast_sealed_at' => now(),
        ]);

        // 1. JSON API verification
        $apiRes = $this->getJson("/bast/verify/{$task->bast_hash}");
        $apiRes->assertStatus(200)
            ->assertJson([
                'verified'          => true,
                'task_id'           => $task->id,
                'customer_name'     => $this->customer->name,
                'optical_rx_dbm'    => -21.40,
                'hardware_verified' => true,
                'payment_status'    => 'paid',
            ]);

        // 2. Public HTML Webpage verification (rendered when scanned by camera)
        $htmlRes = $this->get("/bast/verify/{$task->bast_hash}");
        $htmlRes->assertStatus(200)
            ->assertSee('Sertifikat BAST Asli')
            ->assertSee('Nusantara Fiber Telecom')
            ->assertSee('Pak RT Agus')
            ->assertSee('-21.4 dBm (LOLOS)')
            ->assertSee($task->bast_hash);

        // 3. Invalid/Tampered Hash Test
        $badRes = $this->getJson("/bast/verify/0000000000000000000000000000000000000000000000000000000000000000");
        $badRes->assertStatus(404)
            ->assertJson(['verified' => false]);
    }
}
