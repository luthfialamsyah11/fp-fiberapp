<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\TaskMaterial;
use App\Models\TaskStatusHistory;
use App\Models\TechnicianLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FsmIntegrationSuiteTest extends TestCase
{
    use RefreshDatabase;

    private string $validPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        if (File::exists(storage_path('app/settings.json'))) {
            File::delete(storage_path('app/settings.json'));
        }
    }

    private function createTechnician(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'      => 'Alex Gunawan',
            'email'     => 'alex@fsm.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'phone'     => '+628123456789',
            'is_active' => true,
            'is_online' => true,
        ], $overrides));
    }

    private function createAdmin(): User
    {
        return User::create([
            'name'      => 'NOC Dispatcher',
            'email'     => 'admin@fsm.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_signature_upload_rejects_php_extension_and_non_image_bytes(): void
    {
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'         => 'FTTH Splice Repair',
            'customer_name' => 'Global Corp',
            'address'       => '100 Fiber Way',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
        ]);

        // 1. Attempt to upload a .php data URI
        $maliciousPhp = 'data:image/php;base64,' . base64_encode('<?php system($_GET["cmd"]); ?>');
        $res1 = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $maliciousPhp,
            'signer_name' => 'Attacker',
        ]);
        $res1->assertStatus(422);

        // 2. Attempt to upload PHP code disguised with a data:image/png header
        $disguisedPhp = 'data:image/png;base64,' . base64_encode('<?php echo "pwned"; ?>' . str_repeat('A', 100));
        $res2 = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $disguisedPhp,
            'signer_name' => 'Attacker',
        ]);
        $res2->assertStatus(422);

        // 3. Valid PNG succeeds and stores with .png extension
        $res3 = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $this->validPngBase64,
            'signer_name' => 'John Subscriber',
        ]);
        $res3->assertStatus(200);
        $this->assertStringEndsWith('.png', $res3->json('customer_signature'));
    }

    public function test_deterministic_task_lifecycle_and_cryptographic_bast_seal(): void
    {
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'         => 'ONT Provisioning',
            'customer_name' => 'Acme Telecom',
            'address'       => 'Tower A',
            'technician_id' => $tech->id,
            'status'        => 'assigned',
        ]);

        // Accept -> Start
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/accept")->assertOk();
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/start")->assertOk();

        // Cannot complete without proof photo
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete")
            ->assertStatus(422);

        // Attach proof photo & signature
        ProofOfWork::create([
            'task_id'     => $task->id,
            'image'       => 'proof/test.jpg',
            'description' => 'OPM -18.2 dBm',
        ]);
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $this->validPngBase64,
            'signer_name' => 'Acme Director',
        ])->assertOk();

        // Complete succeeds and generates SHA-256 BAST seal
        $completeRes = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete");
        $completeRes->assertOk();

        $fresh = $task->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertNotNull($fresh->bast_hash);
        $this->assertEquals(64, strlen($fresh->bast_hash));

        // Audit history recorded every transition
        $this->assertEquals(3, TaskStatusHistory::where('task_id', $task->id)->count());

        // Public BAST verification endpoint confirms SHA-256 match
        $verifyRes = $this->getJson("/tasks/{$task->id}/bast/verify?token={$fresh->tracking_token}");
        $verifyRes->assertOk()->assertJson([
            'verified'    => true,
            'stored_hash' => $fresh->bast_hash,
        ]);

        // Signature cannot be overwritten after completion
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $this->validPngBase64,
            'signer_name' => 'Tamperer',
        ])->assertStatus(409);
    }

    public function test_admin_cannot_bypass_state_machine_without_override_reason(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'         => 'Backbone Cut',
            'customer_name' => 'Metro ISP',
            'address'       => 'Pop 1',
            'technician_id' => $tech->id,
            'status'        => 'pending',
            'priority'      => 'high',
        ]);

        // Attempting to jump pending -> completed without override_reason fails
        $res1 = $this->actingAs($admin)->put("/tasks/{$task->id}", [
            'title'         => 'Backbone Cut',
            'customer_name' => 'Metro ISP',
            'address'       => 'Pop 1',
            'technician_id' => $tech->id,
            'priority'      => 'high',
            'status'        => 'completed',
        ]);
        $res1->assertSessionHasErrors('status');
        $this->assertEquals('pending', $task->fresh()->status);

        // Providing override_reason allows emergency administrative closure and logs it
        $res2 = $this->actingAs($admin)->put("/tasks/{$task->id}", [
            'title'           => 'Backbone Cut',
            'customer_name'   => 'Metro ISP',
            'address'         => 'Pop 1',
            'technician_id'   => $tech->id,
            'priority'        => 'high',
            'status'          => 'completed',
            'override_reason' => 'NOC supervisor verified remote OLT restoration',
        ]);
        $res2->assertSessionHasNoErrors();
        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id'   => $task->id,
            'to_status' => 'completed',
            'reason'    => 'NOC supervisor verified remote OLT restoration',
        ]);
    }

    public function test_gps_batch_deduplication_validation_and_presence_expiry(): void
    {
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'         => 'Active Splice',
            'customer_name' => 'Client A',
            'address'       => 'Site A',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
        ]);

        // Null Island (0,0) is rejected
        $this->actingAs($tech, 'sanctum')->postJson('/api/location', [
            'latitude'  => 0,
            'longitude' => 0,
        ])->assertStatus(422);

        // Batch ingestion deduplicates by client_uuid and skips bad accuracy
        $batchRes = $this->actingAs($tech, 'sanctum')->postJson('/api/location/batch', [
            'points' => [
                [
                    'latitude'    => -6.2088,
                    'longitude'   => 106.8456,
                    'accuracy_m'  => 12.5,
                    'client_uuid' => 'uuid-point-1',
                ],
                [
                    'latitude'    => -6.2089,
                    'longitude'   => 106.8457,
                    'accuracy_m'  => 15.0,
                    'client_uuid' => 'uuid-point-1', // duplicate UUID
                ],
                [
                    'latitude'    => -6.2090,
                    'longitude'   => 106.8458,
                    'accuracy_m'  => 500.0, // > 250m accuracy threshold
                    'client_uuid' => 'uuid-point-2',
                ],
            ],
        ]);

        $batchRes->assertOk()->assertJson([
            'accepted' => 1,
            'skipped'  => 2,
        ]);

        $loc = TechnicianLocation::where('client_uuid', 'uuid-point-1')->first();
        $this->assertNotNull($loc);
        $this->assertEquals($task->id, $loc->task_id);

        // Technician is currently effectively online
        $this->assertTrue($tech->fresh()->isEffectivelyOnline(10));

        // Simulate phone dying 25 minutes ago: even with is_online=true, effective online becomes false
        $tech->update(['last_seen_at' => now()->subMinutes(25)]);
        TechnicianLocation::where('technician_id', $tech->id)->update(['created_at' => now()->subMinutes(25)]);
        $this->assertFalse($tech->fresh()->isEffectivelyOnline(10));
    }

    public function test_customer_tracking_privacy_redaction_and_24h_expiry(): void
    {
        $tech = $this->createTechnician(['name' => 'Budi Santoso', 'phone' => '+62811999888']);
        $task = Task::create([
            'title'         => 'FTTx Drop Install',
            'customer_name' => 'Sarah Connor',
            'address'       => 'Sector 7',
            'latitude'      => -6.2000,
            'longitude'     => 106.8100,
            'technician_id' => $tech->id,
            'status'        => 'accepted',
        ]);

        // 1. Before any GPS ping exists, state is 'waiting' and live_location is null (no fake coordinates)
        $res1 = $this->getJson("/api/public/track/{$task->tracking_token}");
        $res1->assertOk()
            ->assertJsonPath('state', 'waiting')
            ->assertJsonPath('live_location', null)
            ->assertJsonPath('technician.name', 'Budi')
            ->assertJsonMissingPath('technician.phone');

        // 2. When technician sends a ping, live_location & ETA are returned
        $this->actingAs($tech, 'sanctum')->postJson('/api/location', [
            'latitude'  => -6.2050,
            'longitude' => 106.8150,
        ])->assertOk();

        $res2 = $this->getJson("/api/public/track/{$task->tracking_token}");
        $res2->assertOk()
            ->assertJsonPath('state', 'tracking')
            ->assertJsonPath('live_location.latitude', -6.205);

        // 3. 25 hours after completion, tracking link returns HTTP 410 Expired
        $task->update([
            'status'       => 'completed',
            'completed_at' => now()->subHours(25),
        ]);
        $this->getJson("/api/public/track/{$task->tracking_token}")->assertStatus(410);
    }

    public function test_material_bom_idempotency_and_post_completion_lock(): void
    {
        $tech = $this->createTechnician();
        $item = InventoryItem::create([
            'code'       => 'ONT-XPON-01',
            'name'       => 'Dual-Band XPON ONT',
            'unit'       => 'unit',
            'stock'      => 10,
            'unit_price' => 45,
            'category'   => 'device',
            'is_active'  => true,
        ]);
        $task = Task::create([
            'title'         => 'New Subscriber ONT',
            'customer_name' => 'DataCenter Inc',
            'address'       => 'Suite 400',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
        ]);

        // Send duplicate POST with same Idempotency-Key
        $headers = ['Idempotency-Key' => 'idem-mat-key-001'];
        $payload = ['inventory_item_id' => $item->id, 'quantity' => 2];

        $first = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/materials", $payload, $headers);
        $first->assertStatus(201);

        $second = $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/materials", $payload, $headers);
        $second->assertStatus(201)->assertHeader('X-Idempotent-Replay', 'true');

        // Stock must only be deducted once (10 - 2 = 8)
        $this->assertEquals(8, (float) $item->fresh()->stock);

        // Once task is completed, technician cannot add or delete materials
        $task->update(['status' => 'completed']);
        $matId = $first->json('data.id');

        $this->actingAs($tech, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}/materials/{$matId}")
            ->assertStatus(409);

        $this->assertEquals(8, (float) $item->fresh()->stock);
    }

    public function test_login_rate_limiting_blocks_brute_force(): void
    {
        $this->createTechnician();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email'    => 'alex@fsm.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // 6th attempt within 1 minute is throttled with HTTP 429
        $this->postJson('/api/login', [
            'email'    => 'alex@fsm.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_admin_task_show_renders_with_customer_phone_without_route_crash(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'          => 'Enterprise Fiber Splice',
            'customer_name'  => 'Meridian Bank',
            'customer_phone' => '081234567890',
            'address'        => 'Financial District Block A',
            'technician_id'  => $tech->id,
            'status'         => 'on-going',
            'priority'       => 'high',
        ]);

        $res = $this->actingAs($admin)->get("/tasks/{$task->id}");
        $res->assertStatus(200);
        $res->assertSee('Meridian Bank');
        $res->assertSee($task->tracking_token);
    }

    public function test_media_controller_serves_public_files_and_blocks_path_traversal(): void
    {
        $rawPng = base64_decode(substr($this->validPngBase64, strpos($this->validPngBase64, ',') + 1));
        $targetFile = storage_path('app/public/proof/unit_test_img.png');
        File::ensureDirectoryExists(dirname($targetFile));
        File::put($targetFile, $rawPng);

        try {
            $res = $this->get('/storage/proof/unit_test_img.png');
            $res->assertStatus(200);
            $res->assertHeader('Access-Control-Allow-Origin', '*');

            // Path traversal attempt must return 404
            $this->get('/storage/..%2F.env')->assertStatus(404);
            $this->get('/storage/proof/../../settings.json')->assertStatus(404);
        } finally {
            if (File::exists($targetFile)) {
                File::delete($targetFile);
            }
        }
    }

    public function test_deactivating_technician_revokes_sanctum_tokens_and_blocks_api_requests(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $token = $tech->createToken('mobile-app')->plainTextToken;

        // Active technician can access /api/me
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk();

        // Admin deactivates technician via Web controller
        $this->actingAs($admin)->delete("/technicians/{$tech->id}")->assertRedirect();

        $this->assertFalse((bool) $tech->fresh()->is_active);
        $this->assertEquals(0, $tech->tokens()->count());

        // Even if actingAs a deactivated user directly, EnsureUserIsActive returns 403
        $this->actingAs($tech->fresh(), 'sanctum')
            ->getJson('/api/tasks')
            ->assertStatus(403);
    }

    public function test_admin_settings_save_preserves_signature_gate_and_currency(): void
    {
        $admin = $this->createAdmin();

        $res = $this->actingAs($admin)->post('/settings', [
            'company_name'               => 'FiberOps Global Carrier',
            'support_email'              => 'noc@bentengsiber.com',
            'support_phone'              => '+1-800-555-0199',
            'currency'                   => 'EUR',
            'default_country_code'       => '44',
            'gps_refresh_interval'       => '15',
            'map_style'                  => 'carto',
            'sla_warning_hours'          => '4',
            'geofence_check_meters'      => '100',
            'require_proof_photo'        => '1',
            'require_customer_signature' => '1',
            'telemetry_retention_days'   => '30',
        ]);

        $res->assertRedirect();
        $this->assertEquals('1', \App\Services\SettingService::get('require_customer_signature'));
        $this->assertEquals('EUR', \App\Services\SettingService::get('currency'));
        $this->assertEquals('44', \App\Services\SettingService::get('default_country_code'));
    }

    public function test_admin_request_rework_reopens_completed_task_and_clears_bast_seal(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'          => 'Fiber Splice Audit',
            'customer_name'  => 'Apex Data',
            'address'        => 'Rack 12',
            'technician_id'  => $tech->id,
            'status'         => 'completed',
            'completed_at'   => now(),
            'bast_hash'      => str_repeat('a', 64),
            'bast_sealed_at' => now(),
        ]);

        $res = $this->actingAs($admin)->post("/tasks/{$task->id}/request-rework", [
            'reason' => 'After photo is blurry; please re-upload OPM reading.',
        ]);

        $res->assertRedirect();
        $fresh = $task->fresh();
        $this->assertEquals('on-going', $fresh->status);
        $this->assertNull($fresh->bast_hash);
        $this->assertNull($fresh->completed_at);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id'     => $task->id,
            'from_status' => 'completed',
            'to_status'   => 'on-going',
        ]);
    }

    public function test_inventory_item_referenced_in_bom_cannot_be_hard_deleted(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $item = InventoryItem::create([
            'code'       => 'SFP-10G-LR',
            'name'       => '10G LR Optical Transceiver',
            'unit'       => 'pcs',
            'stock'      => 20,
            'unit_price' => 120,
            'category'   => 'device',
            'is_active'  => true,
        ]);
        $task = Task::create([
            'title'         => 'Core Switch Upgrade',
            'customer_name' => 'IXP Node',
            'address'       => 'MMR 1',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
        ]);

        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity'          => 1,
        ])->assertStatus(201);

        // API delete must return 422 and keep item
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/inventory/{$item->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
    }

    public function test_live_tracking_delta_polling_and_active_task_coordinates(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $task = Task::create([
            'title'         => 'Metro Ring Restoration',
            'customer_name' => 'Carrier Corp',
            'address'       => 'Junction 9',
            'latitude'      => -6.2100,
            'longitude'     => 106.8200,
            'technician_id' => $tech->id,
            'status'        => 'on-going',
        ]);

        $loc1 = TechnicianLocation::create([
            'technician_id' => $tech->id,
            'task_id'       => $task->id,
            'latitude'      => -6.2050,
            'longitude'     => 106.8150,
        ]);
        $loc2 = TechnicianLocation::create([
            'technician_id' => $tech->id,
            'task_id'       => $task->id,
            'latitude'      => -6.2060,
            'longitude'     => 106.8160,
        ]);

        $res = $this->actingAs($admin)->getJson("/tracking/live-data?since_id={$loc1->id}");
        $res->assertOk()
            ->assertJsonPath('latest_log_id', $loc2->id)
            ->assertJsonCount(1, 'history_logs')
            ->assertJsonPath('technicians.0.active_task.latitude', -6.21);
    }

    public function test_create_and_edit_task_pages_render_interactive_map_picker_and_fleet_data(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Marco Silva']);

        TechnicianLocation::create([
            'technician_id' => $tech->id,
            'latitude'      => -6.2015,
            'longitude'     => 106.8210,
        ]);

        $existingTask = Task::create([
            'title'          => 'Existing Subscriber Fiber Link',
            'customer_name'  => 'Vanguard Towers',
            'customer_phone' => '+6281199887766',
            'address'        => 'Jl. Sudirman Kav 10',
            'latitude'       => -6.214620,
            'longitude'      => 106.845130,
            'technician_id'  => $tech->id,
            'status'         => 'assigned',
            'priority'       => 'high',
        ]);

        // 1. Create page renders Geo-Dispatcher workbench elements
        $createRes = $this->actingAs($admin)->get('/tasks/create');
        $createRes->assertStatus(200)
            ->assertSee('location-picker-map', false)
            ->assertSee('gmaps-paste-input', false)
            ->assertSee('Marco Silva')
            ->assertSee('Vanguard Towers');

        // 2. Edit page renders with existing task coordinates prefilled
        $editRes = $this->actingAs($admin)->get("/tasks/{$existingTask->id}/edit");
        $editRes->assertStatus(200)
            ->assertSee('location-picker-map', false)
            ->assertSee('-6.21462', false)
            ->assertSee('106.84513', false);

        // 3. Submitting a new task with precise map coordinates persists cleanly
        $storeRes = $this->actingAs($admin)->post('/tasks', [
            'title'          => 'FTTH New Subscriber Installation',
            'customer_name'  => 'Horizon Logistics',
            'customer_phone' => '+628123450000',
            'address'        => 'Jl. Gatot Subroto No. 45, Jakarta',
            'latitude'       => -6.229728,
            'longitude'      => 106.819560,
            'priority'       => 'high',
            'technician_id'  => $tech->id,
        ]);
        $storeRes->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', [
            'title'         => 'FTTH New Subscriber Installation',
            'customer_name' => 'Horizon Logistics',
            'technician_id' => $tech->id,
            'status'        => 'assigned',
        ]);
    }

    public function test_sla_single_source_of_truth_between_model_and_filter(): void
    {
        $admin = $this->createAdmin();

        // Future scheduled task created 6h ago -> MUST NOT be SLA breached
        $futureScheduled = Task::create([
            'title'         => 'Future Scheduled Install',
            'customer_name' => 'Future Client',
            'address'       => 'Block F',
            'status'        => 'pending',
            'priority'      => 'medium',
            'scheduled_at'  => now()->addDay(),
        ]);
        \Illuminate\Support\Facades\DB::table('tasks')
            ->where('id', $futureScheduled->id)
            ->update(['created_at' => now()->subHours(6)]);

        // Overdue scheduled task -> MUST be SLA breached
        $overdueTask = Task::create([
            'title'         => 'Overdue Splice Repair',
            'customer_name' => 'Overdue Client',
            'address'       => 'Block O',
            'status'        => 'pending',
            'priority'      => 'high',
            'scheduled_at'  => now()->subHours(5),
        ]);

        $this->assertFalse($futureScheduled->fresh()->isSlaBreached(2));
        $this->assertTrue($overdueTask->fresh()->isSlaBreached(2));

        $res = $this->actingAs($admin)->get('/tasks?status=sla_breach');
        $res->assertStatus(200)
            ->assertSee('Overdue Splice Repair')
            ->assertDontSee('Future Scheduled Install');
    }

    public function test_completed_task_override_reseals_bast_and_blocks_bom_tampering(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();
        $item = InventoryItem::create([
            'code'       => 'DROP-1C-50M',
            'name'       => 'Dropcore 1C Cable',
            'unit'       => 'meter',
            'stock'      => 100,
            'unit_price' => 2.5,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $task = Task::create([
            'title'         => 'Core Fiber Closure',
            'customer_name' => 'Initial Client',
            'address'       => 'Site 1',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
            'priority'      => 'high',
        ]);

        // Admin updates customer_name AND completes via override in one request
        $this->actingAs($admin)->put("/tasks/{$task->id}", [
            'title'           => 'Core Fiber Closure',
            'customer_name'   => 'Corrected Client Name',
            'address'         => 'Site 1',
            'technician_id'   => $tech->id,
            'priority'        => 'high',
            'status'          => 'completed',
            'override_reason' => 'Supervisor corrected customer name during closure',
        ])->assertRedirect("/tasks/{$task->id}");

        $fresh = $task->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertTrue($fresh->isBastIntegrityValid());

        // Public BAST JSON verification confirms hash matches corrected customer name
        $this->getJson("/tasks/{$task->id}/bast/verify?token={$fresh->tracking_token}")
            ->assertOk()
            ->assertJson(['verified' => true]);

        // Admin attempting to tamper BOM on completed task via API gets HTTP 409 Conflict
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/materials", [
                'inventory_item_id' => $item->id,
                'quantity'          => 5,
            ])
            ->assertStatus(409);
    }

    public function test_task_detail_inline_notes_bom_live_status_and_special_chars(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => "O'Connor Tech"]);
        $item = InventoryItem::create([
            'code'       => 'CONN-SC-APC',
            'name'       => 'SC/APC Fast Connector',
            'unit'       => 'pcs',
            'stock'      => 50,
            'unit_price' => 3,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $task = Task::create([
            'title'          => "Fiber Fix at O'Neill Plaza",
            'customer_name'  => "O'Neill Corp",
            'customer_phone' => '+628123456789',
            'address'        => "O'Neill Tower\nFloor 5, Suite `A`",
            'latitude'       => -6.2088,
            'longitude'      => 106.8456,
            'technician_id'  => $tech->id,
            'status'         => 'on-going',
            'priority'       => 'high',
        ]);

        // Clear tracking_token to prove legacy task auto-recovery without 500 crash
        \Illuminate\Support\Facades\DB::table('tasks')->where('id', $task->id)->update(['tracking_token' => null]);

        // 1. Task detail renders cleanly and recovers tracking token
        $showRes = $this->actingAs($admin)->get("/tasks/{$task->id}");
        $showRes->assertStatus(200)->assertSee('Work Order Command Details');
        $this->assertNotEmpty($task->fresh()->tracking_token);

        // 2. Post inline NOC dispatcher note
        $this->actingAs($admin)->post("/tasks/{$task->id}/notes", [
            'note' => 'Please verify ODP-04 splitter loss before closing.',
        ])->assertRedirect();
        $this->assertDatabaseHas('progress_updates', [
            'task_id' => $task->id,
            'note'    => '[NOC Dispatcher] Please verify ODP-04 splitter loss before closing.',
        ]);

        // 3. Add BOM material inline and verify stock deduction
        $this->actingAs($admin)->post("/tasks/{$task->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity'          => 4,
            'serial_number'     => 'SN-FAST-01',
        ])->assertRedirect();
        $this->assertEquals(46, (float) $item->fresh()->stock);

        // 4. Live status JSON endpoint returns task & technician telemetry
        TechnicianLocation::create([
            'technician_id' => $tech->id,
            'task_id'       => $task->id,
            'latitude'      => -6.2000,
            'longitude'     => 106.8400,
        ]);
        $this->actingAs($admin)->getJson("/tasks/{$task->id}/live-status")
            ->assertOk()
            ->assertJsonPath('task_id', $task->id)
            ->assertJsonPath('technician.name', "O'Connor Tech");

        // 5. Task list multi-facet filter (priority + unassigned)
        Task::create([
            'title'         => 'Unassigned High Priority Cut',
            'customer_name' => 'Metro Bank',
            'address'       => 'Main St',
            'status'        => 'pending',
            'priority'      => 'high',
        ]);
        $this->actingAs($admin)->get('/tasks?priority=high&technician_id=unassigned')
            ->assertStatus(200)
            ->assertSee('Unassigned High Priority Cut')
            ->assertDontSee("Fiber Fix at O'Neill Plaza");
    }

    public function test_task_management_actions_tracking_token_recovery_and_modal_delete_restores_stock(): void
    {
        $admin = $this->createAdmin();
        $item = InventoryItem::create([
            'code'       => 'CBL-ACT-01',
            'name'       => 'Action Test Cable',
            'unit'       => 'meter',
            'stock'      => 100,
            'unit_price' => 2.5,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $task = Task::create([
            'title'              => 'Completed Fiber Drop with Null Token',
            'customer_name'      => 'PT Nusantara',
            'address'            => 'Jl. Sudirman 10',
            'status'             => 'completed',
            'priority'           => 'medium',
            'customer_signature' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

        // Simulate legacy task with null tracking_token
        \Illuminate\Support\Facades\DB::table('tasks')->where('id', $task->id)->update(['tracking_token' => null]);

        // Allocate BOM material manually to confirm deletion restores warehouse stock
        TaskMaterial::create([
            'task_id'           => $task->id,
            'inventory_item_id' => $item->id,
            'quantity'          => 15,
            'unit_price'        => 2.5,
            'total_price'       => 37.5,
        ]);
        $item->decrement('stock', 15);
        $this->assertEquals(85.0, (float) $item->fresh()->stock);

        // 1. Index auto-heals null tracking_token & renders reliable modal delete + BAST + Copy Link actions
        $res = $this->actingAs($admin)->get('/tasks');
        $res->assertStatus(200)
            ->assertSee('openDeleteTaskModal')
            ->assertSee('copyTaskTrackingLink')
            ->assertDontSee('return confirm(');

        $freshToken = $task->fresh()->tracking_token;
        $this->assertNotEmpty($freshToken);
        $res->assertSee("token={$freshToken}");

        // 2. Delete action removes task and restores BOM stock atomically
        $this->actingAs($admin)->delete("/tasks/{$task->id}")
            ->assertRedirect(route('admin.tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertEquals(100.0, (float) $item->fresh()->stock);
    }

    public function test_inventory_atomic_adjust_stock_toggle_status_export_and_apk_sync(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician();

        $inStockItem = InventoryItem::create([
            'code'       => 'ONT-ZTE-F670',
            'name'       => 'ZTE F670L Dual-Band ONT',
            'unit'       => 'unit',
            'stock'      => 12,
            'unit_price' => 24.50,
            'category'   => 'device',
            'is_active'  => true,
        ]);

        $zeroStockItem = InventoryItem::create([
            'code'       => 'SFP-10G-LR',
            'name'       => '10G SFP+ Transceiver',
            'unit'       => 'pcs',
            'stock'      => 0,
            'unit_price' => 45.00,
            'category'   => 'device',
            'is_active'  => true,
        ]);

        // 1. Atomic restock (+8 -> 20)
        $this->actingAs($admin)
            ->post("/inventory/{$inStockItem->id}/adjust-stock", ['mode' => 'add', 'quantity' => 8])
            ->assertRedirect();
        $this->assertEquals(20.0, (float) $inStockItem->fresh()->stock);

        // 2. Atomic subtract (-5 -> 15)
        $this->actingAs($admin)
            ->post("/inventory/{$inStockItem->id}/adjust-stock", ['mode' => 'subtract', 'quantity' => 5])
            ->assertRedirect();
        $this->assertEquals(15.0, (float) $inStockItem->fresh()->stock);

        // 3. Reject negative subtraction (-999 -> stays 15 with error flash)
        $this->actingAs($admin)
            ->post("/inventory/{$inStockItem->id}/adjust-stock", ['mode' => 'subtract', 'quantity' => 999])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertEquals(15.0, (float) $inStockItem->fresh()->stock);

        // 4. Atomic set exact (= 7)
        $this->actingAs($admin)
            ->post("/inventory/{$inStockItem->id}/adjust-stock", ['mode' => 'set', 'quantity' => 7])
            ->assertRedirect();
        $this->assertEquals(7.0, (float) $inStockItem->fresh()->stock);

        // 5. APK API orders in-stock items ahead of 0-stock items and includes in_stock flag
        $apiRes = $this->actingAs($tech, 'sanctum')->getJson('/api/inventory')->assertOk();
        $apiData = $apiRes->json('data');
        $this->assertCount(2, $apiData);
        $this->assertEquals('ONT-ZTE-F670', $apiData[0]['code']);
        $this->assertTrue($apiData[0]['in_stock']);
        $this->assertEquals('SFP-10G-LR', $apiData[1]['code']);
        $this->assertFalse($apiData[1]['in_stock']);

        // 6. Toggle status deactivates item and hides it from Technician APK
        $this->actingAs($admin)
            ->post("/inventory/{$inStockItem->id}/toggle-status")
            ->assertRedirect();
        $this->assertFalse((bool) $inStockItem->fresh()->is_active);

        $apiAfterToggle = $this->actingAs($tech, 'sanctum')->getJson('/api/inventory')->assertOk()->json('data');
        $this->assertCount(1, $apiAfterToggle);
        $this->assertEquals('SFP-10G-LR', $apiAfterToggle[0]['code']);

        // 7. Inventory page & CSV export render cleanly
        $this->actingAs($admin)->get('/inventory?active_status=inactive')
            ->assertStatus(200)
            ->assertSee('ONT-ZTE-F670')
            ->assertDontSee('return confirm(');

        $this->actingAs($admin)->get('/inventory/export')
            ->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_assignment_monitoring_priority_order_queue_isolation_and_gps_auto_dispatch(): void
    {
        $admin = $this->createAdmin();

        $nearTech = $this->createTechnician(['name' => 'Near Tech', 'email' => 'near@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);
        $farTech = $this->createTechnician(['name' => 'Far Tech', 'email' => 'far@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);

        // Near Tech is right next to customer (-6.2005, 106.8455); Far Tech is 15km away (-6.3300, 106.9500)
        TechnicianLocation::create([
            'technician_id' => $nearTech->id,
            'latitude'      => -6.2005,
            'longitude'     => 106.8455,
        ]);
        TechnicianLocation::create([
            'technician_id' => $farTech->id,
            'latitude'      => -6.3300,
            'longitude'     => 106.9500,
        ]);

        $lowTask = Task::create([
            'title'         => 'Low Priority Maintenance',
            'customer_name' => 'Client Low',
            'address'       => 'Addr Low',
            'status'        => 'pending',
            'priority'      => 'low',
        ]);
        $mediumTask = Task::create([
            'title'         => 'Medium Priority Install',
            'customer_name' => 'Client Med',
            'address'       => 'Addr Med',
            'status'        => 'pending',
            'priority'      => 'medium',
        ]);
        $highTask = Task::create([
            'title'         => 'Emergency Backbone Fiber Cut',
            'customer_name' => 'Client High',
            'address'       => 'Jl. Thamrin',
            'latitude'      => -6.2000,
            'longitude'     => 106.8450,
            'status'        => 'pending',
            'priority'      => 'high',
        ]);
        $completedUnassigned = Task::create([
            'title'         => 'Legacy Completed Unassigned Ticket',
            'customer_name' => 'Old Client',
            'address'       => 'Old Addr',
            'technician_id' => null,
            'status'        => 'completed',
            'priority'      => 'high',
        ]);

        // 1. GET /assignments sorts pendingQueue strictly High -> Medium -> Low and excludes completed unassigned task
        $res = $this->actingAs($admin)->get('/assignments')->assertStatus(200);
        $pendingQueue = $res->viewData('pendingQueue');

        $queueIds = $pendingQueue->pluck('id')->all();
        $this->assertEquals([$highTask->id, $mediumTask->id, $lowTask->id], $queueIds);
        $this->assertNotContains($completedUnassigned->id, $queueIds);

        // 2. 1-Click Auto-Dispatch picks Near Tech via Haversine GPS and transitions task to assigned
        $this->actingAs($admin)
            ->post("/assignments/{$highTask->id}/auto-dispatch")
            ->assertRedirect()
            ->assertSessionHas('success');

        $dispatched = $highTask->fresh();
        $this->assertEquals('assigned', $dispatched->status);
        $this->assertEquals($nearTech->id, $dispatched->technician_id);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id'     => $highTask->id,
            'from_status' => 'pending',
            'to_status'   => 'assigned',
        ]);
    }
}
