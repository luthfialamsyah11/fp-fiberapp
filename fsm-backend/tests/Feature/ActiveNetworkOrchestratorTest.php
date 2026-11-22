<?php

namespace Tests\Feature;

use App\Domain\Tasks\TaskStateMachine;
use App\Models\Customer;
use App\Models\FiberNode;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\Network\Drivers\OltSimulatorDriver;
use App\Services\Network\MikrotikService;
use App\Services\Network\OltService;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActiveNetworkOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $technician;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        MikrotikSimulatorDriver::reset();
        OltSimulatorDriver::reset();

        $this->tenant = Tenant::create([
            'name'             => 'Citra Fiber Network',
            'slug'             => 'citra-fiber',
            'contact_name'     => 'Citra NOC Head',
            'contact_email'    => 'noc@citra.net.id',
            'contact_phone'    => '081299887766',
            'plan'             => 'enterprise',
            'status'           => 'active',
            'network_settings' => [
                'mikrotik_driver'                 => 'simulator',
                'olt_driver'                      => 'simulator',
                'require_hardware_olt_validation' => true,
                'opm_max_attenuation_dbm'         => -27.0,
            ],
        ]);

        TenantContext::setTenant($this->tenant);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin Citra',
            'email'     => 'admin@citra.net.id',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->technician = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Technician Citra',
            'email'     => 'tech@citra.net.id',
            'password'  => bcrypt('password'),
            'role'      => 'technician',
            'is_online' => true,
        ]);
    }

    public function test_mikrotik_service_can_provision_pppoe_secret_and_isolate_un_isolate_customer()
    {
        $customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-0099',
            'name'              => 'Budi Fiber User',
            'phone'             => '08123456789',
            'address'           => 'Jl. Merdeka No. 10',
            'service_plan'      => 'UP-TO-50MBPS',
            'pppoe_username'    => 'budi_pppoe',
            'ont_serial_number' => 'ZTEG00112233',
            'status'            => 'active',
        ]);

        $mikrotik = MikrotikService::forTenant($this->tenant);

        // 1. Provision PPPoE
        $secret = $mikrotik->provisionCustomerPppoe($customer, 'secret123', 'UP-TO-50MBPS');
        $this->assertEquals('budi_pppoe', $secret['name']);
        $this->assertEquals('UP-TO-50MBPS', $secret['profile']);

        $status = $mikrotik->getCustomerPppoeStatus($customer);
        $this->assertTrue($status['is_secret_on']);
        $this->assertTrue($status['is_online']);

        // 2. Isolate Customer
        $isolated = $mikrotik->isolateCustomer($customer, 'ISOLIR');
        $this->assertTrue($isolated);
        $this->assertEquals('isolated', $customer->fresh()->status);

        $secretAfterIsolate = $mikrotik->getDriver()->getSecret('budi_pppoe');
        $this->assertEquals('ISOLIR', $secretAfterIsolate['profile']);

        // 3. Un-isolate Customer
        $unisolated = $mikrotik->unIsolateCustomer($customer);
        $this->assertTrue($unisolated);
        $this->assertEquals('active', $customer->fresh()->status);

        $secretAfterUnIsolate = $mikrotik->getDriver()->getSecret('budi_pppoe');
        $this->assertEquals('UP-TO-50MBPS', $secretAfterUnIsolate['profile']);
    }

    public function test_olt_service_blocks_task_completion_when_ont_is_offline()
    {
        SettingService::set('require_proof_photo', '0');
        SettingService::set('require_customer_signature', '0');
        SettingService::set('require_hardware_olt_validation', '1');

        $customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-OFF-01',
            'name'              => 'Offline Subscriber',
            'phone'             => '08123456781',
            'address'           => 'Jl. Jambu No. 5',
            'ont_serial_number' => 'ZTEG_OFFLINE_SN_99',
            'status'            => 'active',
        ]);

        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $customer->id,
            'customer_name'    => $customer->name,
            'title'            => 'Fix FTTH Outage',
            'service_category' => 'outage_los',
            'status'           => 'on-going',
        ]);

        $stateMachine = app(TaskStateMachine::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('OFFLINE');

        $stateMachine->transition($task, 'completed', $this->technician);
    }

    public function test_olt_service_blocks_task_completion_when_optical_loss_is_excessive()
    {
        SettingService::set('require_proof_photo', '0');
        SettingService::set('require_customer_signature', '0');
        SettingService::set('require_hardware_olt_validation', '1');

        $customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-BAD-01',
            'name'              => 'High Loss Subscriber',
            'phone'             => '08123456782',
            'address'           => 'Jl. Nangka No. 12',
            'ont_serial_number' => 'HWTC_BADLOSS_SN_11',
            'status'            => 'active',
        ]);

        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $customer->id,
            'customer_name'    => $customer->name,
            'title'            => 'High Optical Loss Maintenance',
            'service_category' => 'slow_speed',
            'status'           => 'on-going',
        ]);

        $stateMachine = app(TaskStateMachine::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Redaman fisik optik live dari hardware (-31.2 dBm)');

        $stateMachine->transition($task, 'completed', $this->technician);
    }

    public function test_olt_service_allows_completion_and_records_telemetry_when_signal_is_healthy()
    {
        SettingService::set('require_proof_photo', '0');
        SettingService::set('require_customer_signature', '0');
        SettingService::set('require_hardware_olt_validation', '1');

        $node = FiberNode::create([
            'tenant_id'   => $this->tenant->id,
            'code'        => 'ODP-CITRA-01',
            'name'        => 'ODP Citra 01',
            'node_type'   => 'odp',
            'total_ports' => 8,
            'used_ports'  => 0,
        ]);

        $customer = Customer::create([
            'tenant_id'         => $this->tenant->id,
            'customer_code'     => 'CUST-GOOD-01',
            'name'              => 'Healthy Fiber Subscriber',
            'phone'             => '08123456783',
            'address'           => 'Jl. Flamboyan No. 7',
            'service_plan'      => 'CITRA-SUPER-100M',
            'ont_serial_number' => 'ZTEG_HEALTHY_SN_77',
            'status'            => 'pending',
        ]);

        $task = Task::create([
            'tenant_id'        => $this->tenant->id,
            'technician_id'    => $this->technician->id,
            'customer_id'      => $customer->id,
            'fiber_node_id'    => $node->id,
            'customer_name'    => $customer->name,
            'title'            => 'New Fiber Installation Citra 01',
            'service_category' => 'new_install',
            'status'           => 'on-going',
        ]);

        $stateMachine = app(TaskStateMachine::class);
        $completed = $stateMachine->transition($task, 'completed', $this->technician);

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals(-21.80, $completed->optical_power_rx_dbm);
        $this->assertNotNull($completed->hardware_verified_at);
        $this->assertNotNull($completed->bast_hash);

        // Verify port on ODP was auto-incremented
        $this->assertEquals(1, $node->fresh()->used_ports);

        // Verify customer was activated and PPPoE was provisioned on MikroTik
        $this->assertEquals('active', $customer->fresh()->status);
        $mikrotik = MikrotikService::forTenant($this->tenant);
        $secret = $mikrotik->getDriver()->getSecret($customer->fresh()->pppoe_username);
        $this->assertNotNull($secret);
        $this->assertEquals('CITRA-SUPER-100M', $secret['profile']);
    }

    public function test_network_services_respect_strict_tenant_isolation()
    {
        $tenant2 = Tenant::create([
            'name'             => 'Second ISP Network',
            'slug'             => 'second-isp',
            'contact_name'     => 'Second ISP NOC',
            'contact_email'    => 'noc@second.net.id',
            'contact_phone'    => '081299887755',
            'plan'             => 'growth',
            'status'           => 'active',
            'network_settings' => [
                'mikrotik_driver' => 'simulator',
            ],
        ]);

        $cust1 = Customer::create([
            'tenant_id'      => $this->tenant->id,
            'customer_code'  => 'C1',
            'name'           => 'User ISP 1',
            'phone'          => '08123456784',
            'address'        => 'Jl. Pahlawan No. 1',
            'service_plan'   => 'PLAN-A',
            'pppoe_username' => 'user_isp1',
        ]);

        $cust2 = Customer::create([
            'tenant_id'      => $tenant2->id,
            'customer_code'  => 'C2',
            'name'           => 'User ISP 2',
            'phone'          => '08123456785',
            'address'        => 'Jl. Veteran No. 2',
            'service_plan'   => 'PLAN-B',
            'pppoe_username' => 'user_isp2',
        ]);

        $svc1 = MikrotikService::forTenant($this->tenant);
        $svc2 = MikrotikService::forTenant($tenant2);

        $svc1->provisionCustomerPppoe($cust1);
        $svc2->provisionCustomerPppoe($cust2);

        $this->assertNotNull($svc1->getDriver()->getSecret('user_isp1'));
        $this->assertNotNull($svc2->getDriver()->getSecret('user_isp2'));
    }
}
