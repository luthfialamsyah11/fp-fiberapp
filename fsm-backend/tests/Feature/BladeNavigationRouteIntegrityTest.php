<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FiberNode;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BladeNavigationRouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'             => 'Test Fiber ISP',
            'slug'             => 'test-isp',
            'contact_name'     => 'Operations Manager',
            'contact_email'    => 'ops@testisp.com',
            'contact_phone'    => '08123456789',
            'plan'             => 'growth',
            'status'           => 'active',
            'max_technicians'  => 50,
            'max_fiber_nodes'  => 100,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Master Admin',
            'email'     => 'admin@testisp.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->technician = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Fiber Technician 1',
            'email'     => 'tech1@testisp.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function every_route_referenced_in_blade_views_is_registered_and_valid(): void
    {
        $viewPath = resource_path('views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewPath));
        $bladeFiles = [];

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $bladeFiles[] = $file->getPathname();
            }
        }

        $this->assertNotEmpty($bladeFiles, 'Blade templates should be found in resources/views');

        $missingRoutes = [];
        $totalRoutesChecked = 0;

        foreach ($bladeFiles as $filePath) {
            $content = file_get_contents($filePath);
            if (preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
                foreach ($matches[1] as $routeName) {
                    $totalRoutesChecked++;
                    if (!Route::has($routeName)) {
                        $missingRoutes[] = "{$routeName} (in {$filePath})";
                    }
                }
            }
        }

        $this->assertEmpty($missingRoutes, 'Found unresolved route() references in Blade views: ' . implode(', ', $missingRoutes));
        $this->assertGreaterThan(20, $totalRoutesChecked, 'Checked multiple route references across all views');
    }

    /** @test */
    public function live_pulse_and_delta_sync_signal_triggers_instantly_on_operational_changes(): void
    {
        TenantContext::setTenant($this->tenant);

        // 1. Initial live-pulse snapshot
        $pulse1 = $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.live_pulse'));

        $pulse1->assertOk();
        $v1 = $pulse1->json('version');
        $this->assertNotEmpty($v1);

        // 2. Technician checks sync-signal on mobile APK
        $sig1 = $this->actingAs($this->technician, 'sanctum')
            ->getJson('/api/tasks/sync-signal');
        $sig1->assertOk();
        $syncSig1 = $sig1->json('version');

        // 3. New work order is dispatched
        $task = Task::withoutGlobalScopes()->create([
            'tenant_id'      => $this->tenant->id,
            'title'          => 'Emergency Optical Splice #101',
            'customer_name'  => 'Enterprise Bank Alpha',
            'customer_phone' => '0812999999',
            'address'        => 'Sudirman Tower 1',
            'priority'       => 'high',
            'status'         => 'assigned',
            'technician_id'  => $this->technician->id,
            'billing_amount' => 350000,
        ]);

        // 4. NOC Dispatch Dashboard live-pulse detects the change immediately
        $pulse2 = $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.live_pulse'));

        $pulse2->assertOk();
        $v2 = $pulse2->json('version');
        $this->assertNotEquals($v1, $v2, 'Live pulse version hash MUST change immediately after task creation');
        $this->assertEquals(1, $pulse2->json('counts.assigned'));

        // 5. Technician mobile app sync-signal changes immediately
        $sig2 = $this->actingAs($this->technician, 'sanctum')
            ->getJson('/api/tasks/sync-signal');
        $sig2->assertOk();
        $syncSig2 = $sig2->json('version');
        $this->assertNotEquals($syncSig1, $syncSig2, 'Technician APK sync signal MUST change when new task is assigned');

        // 6. Technician accepts work order & uploads live GPS location
        $task->update(['status' => 'on-going']);

        $locRes = $this->actingAs($this->technician, 'sanctum')
            ->postJson('/api/location', [
                'latitude'   => -6.2088,
                'longitude'  => 106.8456,
                'accuracy_m' => 8.5,
                'speed_mps'  => 12.0,
            ]);
        $locRes->assertOk();

        // 7. NOC Fleet Tracking live-data receives the new telemetry
        $trackingRes = $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.tracking.live'));

        $trackingRes->assertOk();
        $techTelemetry = collect($trackingRes->json('technicians'))->firstWhere('id', $this->technician->id);
        $this->assertNotNull($techTelemetry);
        $this->assertEquals(-6.2088, $techTelemetry['latitude']);
        $this->assertEquals(106.8456, $techTelemetry['longitude']);

        // 8. Public customer tracking live endpoint returns the active mission & ETA
        $token = $task->ensureTrackingToken();
        $custTrackingRes = $this->getJson("/api/public/track/{$token}");
        $custTrackingRes->assertOk();
        $custTrackingRes->assertJsonFragment([
            'title'  => 'Emergency Optical Splice #101',
            'status' => 'on-going',
        ]);
        $this->assertEquals(-6.2088, $custTrackingRes->json('live_location.latitude'));
    }

    /** @test */
    public function full_cross_portal_navigation_flow_executes_seamlessly(): void
    {
        // 1. Guest visits public landing / login
        $this->get(route('admin.login'))->assertOk();

        // 2. Admin logs in
        $loginRes = $this->post(route('admin.login.post'), [
            'email'    => 'admin@testisp.com',
            'password' => 'password123',
        ]);
        $loginRes->assertRedirect(route('admin.dashboard'));

        // 3. Admin visits all primary operations workspaces
        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.dashboard'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.tasks.index'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.tracking'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.customers.index'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.fiber_assets.index'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.inventory.index'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.client_requests.index'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.assignments'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.reports'))->assertOk();

        $this->actingAs($this->admin)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('admin.settings'))->assertOk();

        // 4. Public client request submission & receipt
        $clientRes = $this->post(route('public.request.store'), [
            'customer_name'    => 'Dr. Siti Rahma',
            'customer_phone'   => '081377778888',
            'service_category' => 'outage_los',
            'priority'         => 'high',
            'address'          => 'Jl. Riau No. 45, Bandung',
            'description'      => 'Modem LOS blinking red light since morning.',
        ]);

        $createdTask = Task::withoutGlobalScopes()->where('customer_phone', '081377778888')->first();
        $this->assertNotNull($createdTask);
        $clientRes->assertRedirect(route('public.request.receipt', $createdTask->tracking_token));

        $receiptRes = $this->get(route('public.request.receipt', $createdTask->tracking_token));
        $receiptRes->assertOk();

        // 5. Customer public tracking page lookup & view
        $lookupRes = $this->post(route('tracking.process_lookup'), [
            'ticket_number' => '#TSK-' . str_pad($createdTask->id, 4, '0', STR_PAD_LEFT),
            'phone_last4'   => '8888',
        ]);
        $lookupRes->assertRedirect(route('customer.tracking', ['token' => $createdTask->tracking_token]));

        $trackPageRes = $this->get(route('customer.tracking', ['token' => $createdTask->tracking_token]));
        $trackPageRes->assertOk();
    }
}
