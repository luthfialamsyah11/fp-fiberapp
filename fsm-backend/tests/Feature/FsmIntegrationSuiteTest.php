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
                    'accuracy_m'  => 1500.0, // > 1000m accuracy threshold
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

        // Technician is currently effectively online (2-minute heartbeat window)
        $this->assertTrue($tech->fresh()->isEffectivelyOnline());

        // Simulate phone dying 3 minutes ago: even with is_online=true, effective online becomes false
        $tech->update(['last_seen_at' => now()->subMinutes(3)]);
        TechnicianLocation::where('technician_id', $tech->id)->update([
            'created_at'  => now()->subMinutes(3),
            'captured_at' => now()->subMinutes(3),
        ]);
        $this->assertFalse($tech->fresh()->isEffectivelyOnline());
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

    public function test_all_admin_portal_navigation_routes_return_200_ok(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['email' => 'navtech@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);

        $item = InventoryItem::create([
            'code'       => 'NAV-CBL-01',
            'name'       => 'Navigation Test Dropcore',
            'unit'       => 'meter',
            'stock'      => 250,
            'unit_price' => 1.5,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $task = Task::create([
            'title'          => 'Full Navigation Smoke Ticket',
            'customer_name'  => 'Smoke Test Client',
            'customer_phone' => '+628111111111',
            'address'        => 'Jl. Merdeka No. 1',
            'latitude'       => -6.2088,
            'longitude'      => 106.8456,
            'technician_id'  => $tech->id,
            'status'         => 'on-going',
            'priority'       => 'high',
        ]);

        TaskMaterial::create([
            'task_id'           => $task->id,
            'inventory_item_id' => $item->id,
            'quantity'          => 10,
            'unit_price'        => 1.5,
            'total_price'       => 15,
        ]);

        TechnicianLocation::create([
            'technician_id' => $tech->id,
            'task_id'       => $task->id,
            'latitude'      => -6.2080,
            'longitude'     => 106.8450,
        ]);

        ProofOfWork::create([
            'task_id'     => $task->id,
            'image'       => 'proofs/smoke.png',
            'description' => 'Before install photo',
            'slot'        => 'before',
        ]);

        $htmlRoutes = [
            '/dashboard',
            '/tasks',
            '/tasks/create',
            "/tasks/{$task->id}",
            "/tasks/{$task->id}/edit",
            "/tasks/{$task->id}/bast?token={$task->tracking_token}",
            '/inventory',
            '/assignments',
            '/technicians',
            '/technicians/create',
            "/technicians/{$tech->id}/edit",
            '/tracking',
            '/proofs',
            '/history',
            '/reports',
            '/settings',
            '/guide',
        ];

        foreach ($htmlRoutes as $route) {
            $resp = $this->actingAs($admin)->get($route);
            $resp->assertStatus(200);
            $resp->assertDontSee('return confirm(');
        }

        // JSON & CSV endpoints
        $this->actingAs($admin)->getJson("/tasks/{$task->id}/live-status")->assertOk();
        $this->actingAs($admin)->getJson('/tracking/live-data')->assertOk();
        $this->actingAs($admin)->get('/inventory/export')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/export')->assertStatus(200);
    }

    public function test_apk_gps_replay_preserves_newest_position_and_2min_offline_window(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['email' => 'gpstech@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);

        // 1. Fresh GPS fix posted FIRST
        $freshTime = now()->subSeconds(10)->toIso8601String();
        $this->actingAs($tech, 'sanctum')->postJson('/api/location', [
            'latitude'    => -6.2000,
            'longitude'   => 106.8000,
            'accuracy_m'  => 18.0,
            'captured_at' => $freshTime,
            'client_uuid' => 'fresh-fix-uuid',
            'source'      => 'foreground',
        ])->assertOk();

        // 2. Older offline buffered batch replayed AFTERWARD (gets higher DB id!)
        $olderTime = now()->subMinutes(5)->toIso8601String();
        $this->actingAs($tech, 'sanctum')->postJson('/api/location/batch', [
            'points' => [
                [
                    'latitude'    => -6.9999,
                    'longitude'   => 107.9999,
                    'accuracy_m'  => 420.0, // indoor low-accuracy (< 1000m accepted)
                    'captured_at' => $olderTime,
                    'client_uuid' => 'replayed-old-uuid',
                    'source'      => 'background',
                ],
            ],
        ])->assertOk()->assertJson(['accepted' => 1, 'skipped' => 0]);

        // 3. Admin live tracking MUST show the FRESH position (-6.2, 106.8), NOT the older replayed point!
        $liveRes = $this->actingAs($admin)->getJson('/tracking/live-data')->assertOk();
        $techRow = collect($liveRes->json('technicians'))->firstWhere('id', $tech->id);
        $this->assertNotNull($techRow);
        $this->assertEquals(-6.2, round((float) $techRow['latitude'], 4));
        $this->assertEquals(106.8, round((float) $techRow['longitude'], 4));
        $this->assertTrue($techRow['is_online']);
        $this->assertFalse($techRow['is_position_stale']);
        $this->assertNotNull($techRow['captured_at']);

        // 4. After 3 minutes without heartbeat, Admin marks technician Offline & GPS stale
        $tech->update(['last_seen_at' => now()->subMinutes(3)]);
        TechnicianLocation::where('technician_id', $tech->id)->update([
            'captured_at' => now()->subMinutes(3),
            'created_at'  => now()->subMinutes(3),
        ]);

        $staleRes = $this->actingAs($admin)->getJson('/tracking/live-data')->assertOk();
        $staleRow = collect($staleRes->json('technicians'))->firstWhere('id', $tech->id);
        $this->assertFalse($staleRow['is_online']);
        $this->assertTrue($staleRow['is_position_stale']);

        // 5. Explicit Offline preference is preserved across login (AP-10)
        $tech->update(['is_online' => false]);
        $loginRes = $this->postJson('/api/login', [
            'email'    => 'gpstech@fsm.com',
            'password' => 'password123',
        ])->assertOk();
        $this->assertFalse((bool) $loginRes->json('user.is_online'));
    }

    public function test_apk_queue_filters_active_only_and_end_to_end_admin_apk_sync(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['email' => 'e2etech@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);
        $item = InventoryItem::create([
            'code'       => 'E2E-ONT-01',
            'name'       => 'FiberOps ONT Router',
            'unit'       => 'unit',
            'stock'      => 10,
            'unit_price' => 30.0,
            'category'   => 'device',
            'is_active'  => true,
        ]);

        // Seed an already-completed task and a rejected task for this tech
        $oldCompleted = Task::create([
            'title'         => 'Old Completed Job',
            'customer_name' => 'Past Client',
            'address'       => 'Old Rd',
            'technician_id' => $tech->id,
            'status'        => 'completed',
            'priority'      => 'low',
        ]);
        $oldRejected = Task::create([
            'title'         => 'Old Rejected Job',
            'customer_name' => 'Declined Client',
            'address'       => 'No Rd',
            'technician_id' => $tech->id,
            'status'        => 'rejected',
            'priority'      => 'low',
        ]);

        // 1. Admin creates & dispatches new work order
        $this->actingAs($admin)->post('/tasks', [
            'title'          => 'E2E Fiber Activation',
            'customer_name'  => 'PT Siber Maju',
            'customer_phone' => '+6281234567890',
            'address'        => 'Jl. Gatot Subroto 99',
            'latitude'       => -6.2200,
            'longitude'      => 106.8200,
            'technician_id'  => $tech->id,
            'priority'       => 'high',
        ])->assertRedirect();

        $task = Task::where('title', 'E2E Fiber Activation')->firstOrFail();

        // 2. APK active queue excludes completed & rejected tasks (AP-6), while /tasks/history returns completed
        $queueIds = collect($this->actingAs($tech, 'sanctum')->getJson('/api/tasks')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertContains($task->id, $queueIds);
        $this->assertNotContains($oldCompleted->id, $queueIds);
        $this->assertNotContains($oldRejected->id, $queueIds);

        $historyIds = collect($this->actingAs($tech, 'sanctum')->getJson('/api/tasks/history')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertContains($oldCompleted->id, $historyIds);

        // 3. APK executes full lifecycle: accept -> start -> progress -> proof -> BOM -> signature -> complete
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/accept")->assertOk();
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/start")->assertOk();
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/progress", [
            'note'             => 'Spliced dropcore at ODP-12',
            'progress_percent' => 60,
        ])->assertCreated();

        $pngFile = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'after.png',
            base64_decode(str_replace('data:image/png;base64,', '', $this->validPngBase64))
        );
        $this->actingAs($tech, 'sanctum')->post("/api/tasks/{$task->id}/proof", [
            'image'       => $pngFile,
            'description' => 'ONT online -17 dBm',
            'slot'        => 'after',
        ])->assertCreated();

        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity'          => 1,
            'serial_number'     => 'SN-E2E-99',
        ])->assertCreated();

        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => $this->validPngBase64,
            'signer_name' => 'Bapak Andi',
        ])->assertOk();

        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete")->assertOk();

        // 4. Verify Admin Portal reflects every mutation immediately
        $freshTask = $task->fresh();
        $this->assertEquals('completed', $freshTask->status);
        $this->assertEquals(9.0, (float) $item->fresh()->stock);

        $this->actingAs($admin)->get("/tasks/{$task->id}")
            ->assertStatus(200)
            ->assertSee('E2E Fiber Activation')
            ->assertSee('SN-E2E-99');

        $this->actingAs($admin)->get('/proofs')
            ->assertStatus(200)
            ->assertSee('E2E Fiber Activation');

        $this->actingAs($admin)->get('/history')
            ->assertStatus(200)
            ->assertSee('PT Siber Maju')
            ->assertSee('#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT));

        $this->getJson("/tasks/{$task->id}/bast/verify?token={$freshTask->tracking_token}")
            ->assertOk()
            ->assertJson(['verified' => true]);
    }

    public function test_profile_me_returns_real_stats_and_settings_and_password_change_revokes_other_tokens(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['email' => 'proftech@fsm.com', 'is_online' => true]);

        // Save custom NOC support info in Admin Settings
        $this->actingAs($admin)->post('/settings', [
            'company_name'               => 'Benteng Siber Fiber',
            'support_email'              => 'helpdesk@bentengsiber.com',
            'support_phone'              => '+6289900011122',
            'currency'                   => 'USD',
            'default_country_code'       => '62',
            'gps_refresh_interval'       => '15',
            'map_style'                  => 'openstreetmap',
            'auto_assign'                => '1',
            'sla_warning_hours'          => '2',
            'require_proof_photo'        => '1',
            'require_customer_signature' => '0',
            'geofence_check_meters'      => '0',
            'telemetry_retention_days'   => '30',
        ])->assertRedirect();

        // Seed 1 completed task with proof + 1 declined history event => 50% acceptance, 100% proof compliance
        $doneTask = Task::create([
            'title'         => 'Completed Drop Install',
            'customer_name' => 'Client Done',
            'address'       => 'Addr 1',
            'technician_id' => $tech->id,
            'status'        => 'completed',
            'priority'      => 'medium',
        ]);
        ProofOfWork::create([
            'task_id'     => $doneTask->id,
            'image'       => 'proofs/done.png',
            'description' => 'Verified',
            'slot'        => 'after',
        ]);
        TaskStatusHistory::create([
            'task_id'     => $doneTask->id,
            'actor_id'    => $tech->id,
            'actor_role'  => 'technician',
            'from_status' => 'assigned',
            'to_status'   => 'pending',
            'reason'      => 'Busy on another outage',
        ]);

        // 1. GET /api/me returns real DB stats & dynamic Admin support contact
        $meRes = $this->actingAs($tech, 'sanctum')->getJson('/api/me')->assertOk();
        $meRes->assertJsonPath('stats.completed_count', 1)
            ->assertJsonPath('stats.declined_count', 1)
            ->assertJsonPath('stats.acceptance_rate', 50)
            ->assertJsonPath('stats.proof_compliance_rate', 100)
            ->assertJsonPath('config.company_name', 'Benteng Siber Fiber')
            ->assertJsonPath('config.support_email', 'helpdesk@bentengsiber.com')
            ->assertJsonPath('config.support_phone', '+6289900011122');

        // 2. Issue two real Sanctum tokens (current device + stale device) and reset test auth guard
        $this->app['auth']->forgetGuards();
        $currentToken = $tech->createToken('current_device')->plainTextToken;
        $staleToken = $tech->createToken('stale_device')->plainTextToken;

        // Wrong current_password rejected with 422
        $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->putJson('/api/profile/password', [
                'current_password'      => 'wrongpass',
                'password'              => 'newSecurePass123',
                'password_confirmation' => 'newSecurePass123',
            ])
            ->assertStatus(422);

        // Valid password update succeeds & revokes stale device token while keeping current token
        $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->putJson('/api/profile/password', [
                'current_password'      => 'password123',
                'password'              => 'newSecurePass123',
                'password_confirmation' => 'newSecurePass123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('newSecurePass123', $tech->fresh()->password));
        $this->assertEquals(1, $tech->tokens()->count());
        $this->assertNotEmpty($staleToken);
    }

    public function test_settings_ssrf_validation_country_code_normalization_and_dynamic_purge_window(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'GPS Tech', 'email' => 'gpstech@fsm.com']);

        $basePayload = [
            'company_name'               => 'Benteng Siber NOC',
            'support_email'              => 'noc@bentengsiber.com',
            'support_phone'              => '+62811000222',
            'currency'                   => 'IDR',
            'default_country_code'       => '+62 ',
            'gps_refresh_interval'       => '15',
            'map_style'                  => 'carto',
            'auto_assign'                => '1',
            'sla_warning_hours'          => '4',
            'require_proof_photo'        => '1',
            'require_customer_signature' => '1',
            'geofence_check_meters'      => '100',
            'telemetry_retention_days'   => '30',
        ];

        // 1. SSRF webhook URL must be rejected on Save (not only on Test)
        $this->actingAs($admin)->post('/settings', array_merge($basePayload, [
            'webhook_url' => 'http://127.0.0.1:8080/internal-hook',
        ]))->assertSessionHasErrors('webhook_url');

        $this->actingAs($admin)->post('/settings', array_merge($basePayload, [
            'webhook_url' => 'http://169.254.169.254/latest/meta-data/',
        ]))->assertSessionHasErrors('webhook_url');

        // 2. Non-numeric country dial code must be rejected
        $this->actingAs($admin)->post('/settings', array_merge($basePayload, [
            'default_country_code' => 'abc',
        ]))->assertSessionHasErrors('default_country_code');

        // 3. Valid payload succeeds and normalizes "+62 " -> "62"
        $this->actingAs($admin)->post('/settings', $basePayload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals('62', \App\Services\SettingService::get('default_country_code'));
        $this->assertEquals('carto', \App\Services\SettingService::get('map_style'));

        // 4. Seed 55 GPS points captured 20 days ago for this technician
        $oldTs = now()->subDays(20);
        for ($i = 0; $i < 55; $i++) {
            TechnicianLocation::create([
                'technician_id' => $tech->id,
                'latitude'      => -6.2000 + ($i * 0.0001),
                'longitude'     => 106.8000 + ($i * 0.0001),
                'captured_at'   => $oldTs->copy()->addMinutes($i),
            ]);
        }

        // Purge with 30-day window deletes 0 records
        $this->actingAs($admin)->postJson('/settings/purge-cache', [
            'telemetry_retention_days' => '30',
        ])->assertOk()
            ->assertJsonPath('deleted_count', 0)
            ->assertJsonPath('total_remaining', 55);

        // Purge with 7-day window overrides unsaved dropdown, deletes 5 oldest, safeguards latest 50 per tech
        $this->actingAs($admin)->postJson('/settings/purge-cache', [
            'telemetry_retention_days' => '7',
        ])->assertOk()
            ->assertJsonPath('deleted_count', 5)
            ->assertJsonPath('total_remaining', 50)
            ->assertJsonPath('retention_days', 7)
            ->assertJsonStructure(['oldest_ping_label']);

        // 5. GET /settings renders in-page modal & live KPI elements without native confirm()
        $page = $this->actingAs($admin)->get('/settings')->assertOk();
        $page->assertSee('id="purge-confirm-modal"', false)
            ->assertSee('id="gps-total-points"', false)
            ->assertSee('id="gps-oldest-ping"', false)
            ->assertDontSee('if (!confirm(', false);
    }

    public function test_admin_operations_guide_syncs_live_settings_and_accurate_v2_documentation(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/settings', [
            'company_name'               => 'Nusantara Fiber Backbone',
            'support_email'              => 'ops@nusantarafiber.id',
            'support_phone'              => '+62-21-5009-888',
            'currency'                   => 'IDR',
            'default_country_code'       => '62',
            'gps_refresh_interval'       => '15',
            'map_style'                  => 'openstreetmap',
            'auto_assign'                => '1',
            'sla_warning_hours'          => '4',
            'require_proof_photo'        => '1',
            'require_customer_signature' => '1',
            'geofence_check_meters'      => '100',
            'telemetry_retention_days'   => '30',
        ])->assertRedirect();

        $guide = $this->actingAs($admin)->get('/guide')->assertOk();
        $guide->assertSee('Nusantara Fiber Backbone')
            ->assertSee('+62-21-5009-888')
            ->assertSee('ops@nusantarafiber.id')
            ->assertSee('4 Hours')
            ->assertSee('2 minutes (120 seconds)')
            ->assertSee('Green Marker')
            ->assertSee('Adaptive Delta Polling')
            ->assertSee('Inventory &amp; Materials Ledger', false)
            ->assertSee('Assignment Monitoring &amp; 1-Click Auto-Dispatch', false)
            ->assertSee('SHA-256')
            ->assertSee('Android Background Geolocation')
            ->assertSee('id="guide-search-empty"', false)
            ->assertDontSee('+1 (800) 555-0199')
            ->assertDontSee('Gold Marker')
            ->assertDontSee('last 30 minutes')
            ->assertDontSee('Telemetry Socket Feed')
            ->assertDontSee('`/tracking`', false);

        // Verify BAST printout also honors dynamic company_name from Settings
        $task = Task::create([
            'title'         => 'BAST Branding Verification',
            'customer_name' => 'PT Pelanggan Jaya',
            'address'       => 'Jl. Sudirman 10',
            'status'        => 'completed',
            'priority'      => 'medium',
        ]);
        $this->actingAs($admin)->get("/tasks/{$task->id}/bast")
            ->assertOk()
            ->assertSee('NUSANTARA FIBER BACKBONE')
            ->assertSee('+62-21-5009-888');
    }

    public function test_admin_dashboard_renders_live_analytics_sla_filter_and_unified_map_controls(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Raka Fiber', 'email' => 'raka@fsm.com']);

        TechnicianLocation::create([
            'technician_id' => $tech->id,
            'latitude'      => -6.2100,
            'longitude'     => 106.8200,
            'accuracy_m'    => 12,
            'captured_at'   => now(),
        ]);

        // Seed 1 SLA-breached urgent task + 1 completed today task + 1 low-stock material
        $slaTask = Task::create([
            'title'         => 'Backbone Cut Urgent',
            'customer_name' => 'PT Telko Mandiri',
            'address'       => 'Jl. Gatot Subroto',
            'status'        => 'pending',
            'priority'      => 'high',
        ]);
        $slaTask->forceFill(['created_at' => now()->subHours(5)])->save();

        Task::create([
            'title'         => 'ONT Replacement Done',
            'customer_name' => 'Budi Santoso',
            'address'       => 'Jl. Kemang 8',
            'technician_id' => $tech->id,
            'status'        => 'completed',
            'priority'      => 'medium',
            'completed_at'  => now(),
        ]);

        InventoryItem::create([
            'code'       => 'MAT-LOW-01',
            'name'       => 'Patchcord SC/APC',
            'unit'       => 'pcs',
            'stock'      => 3,
            'unit_price' => 15000,
            'is_active'  => true,
        ]);

        $res = $this->actingAs($admin)->get('/dashboard')->assertOk();
        $res->assertSee('NOC Field Operations Command Center')
            ->assertSee('7-Day Work Order Throughput')
            ->assertSee('Work Order Pipeline')
            ->assertSee('Live Fleet Telemetry Map')
            ->assertSee('1 SLA Breached')
            ->assertSee('1 Low-Stock Materials')
            ->assertSee('+1 closed today')
            ->assertSee('focusTechOnMap(' . $tech->id . ')', false)
            ->assertSee('id="btn-map-sync"', false)
            ->assertSee('id="dash-task-search"', false)
            ->assertSee('id="sidebar-collapse-btn"', false)
            ->assertSee('id="sidebar-rail-tooltip"', false)
            ->assertSee('fsm_admin_sidebar_collapsed', false)
            ->assertDontSee("createMarkerIcon('gold')", false);

        // Verify all server filter tabs return 200 OK including the new `sla` filter
        foreach (['all', 'pending', 'ongoing', 'urgent', 'sla', 'completed'] as $filterTab) {
            $this->actingAs($admin)->get('/dashboard?filter=' . $filterTab)->assertOk();
        }
    }

    public function test_new_task_dispatch_triggers_instant_sync_signal_change_and_no_cache_headers(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Dimas Splicer', 'email' => 'dimas@fsm.com']);

        // 1. Initial sync-signal before any task is assigned
        $sig1 = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $sig1->assertJsonPath('active_count', 0)
            ->assertJsonPath('assigned_count', 0);
        $this->assertStringContainsString('no-store', (string) $sig1->headers->get('Cache-Control'));
        $v1 = $sig1->json('version');

        // 2. Admin creates a new work order and selects Dimas Splicer
        $createRes = $this->actingAs($admin)->post('/tasks', [
            'title'          => 'Instant Dispatch FTTH Cut',
            'customer_name'  => 'PT Cepat Sambung',
            'customer_phone' => '+62812000999',
            'address'        => 'Jl. Thamrin No. 1',
            'priority'       => 'high',
            'technician_id'  => $tech->id,
        ])->assertRedirect(route('admin.tasks.index'));

        $createRes->assertSessionHas('success', function (string $msg) {
            return str_contains($msg, 'Dimas Splicer');
        });

        $createdTask = Task::where('title', 'Instant Dispatch FTTH Cut')->firstOrFail();
        $this->assertEquals('assigned', $createdTask->status);
        $this->assertEquals($tech->id, $createdTask->technician_id);

        // 3. Immediately polling /api/tasks/sync-signal returns a brand-new version hash
        $sig2 = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $sig2->assertJsonPath('active_count', 1)
            ->assertJsonPath('assigned_count', 1)
            ->assertJsonPath('latest_task_id', $createdTask->id);
        $this->assertNotEquals($v1, $sig2->json('version'));

        // 4. GET /api/tasks returns the new task immediately with no-store cache headers
        $listRes = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks')->assertOk();
        $this->assertStringContainsString('no-store', (string) $listRes->headers->get('Cache-Control'));
        $ids = collect($listRes->json('data'))->pluck('id')->all();
        $this->assertContains($createdTask->id, $ids);
    }

    public function test_bidirectional_apk_to_admin_live_pulse_proof_bom_completion_and_inventory_sync(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Fajar Field', 'email' => 'fajar@fsm.com']);

        $item = InventoryItem::create([
            'code'       => 'INV-SYNC-01',
            'name'       => 'Drop Core 1C',
            'unit'       => 'm',
            'stock'      => 100,
            'unit_price' => 2500,
            'category'   => 'material',
            'is_active'  => true,
        ]);

        $task = Task::create([
            'title'         => 'Bi-Directional Sync Fiber Install',
            'customer_name' => 'PT Sinar Baru',
            'address'       => 'Jl. Rasuna Said 12',
            'technician_id' => $tech->id,
            'status'        => 'on-going',
            'priority'      => 'high',
        ]);

        // 1. Initial Admin /live-pulse & /tasks/{id}/live-status snapshots
        $pulse1 = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertStringContainsString('no-store', (string) $pulse1->headers->get('Cache-Control'));
        $vPulse1 = $pulse1->json('version');
        $vInvScope1 = $pulse1->json('scopes.inventory');
        $vProofScope1 = $pulse1->json('scopes.proofs');

        $liveStat1 = $this->actingAs($admin)->getJson("/tasks/{$task->id}/live-status")->assertOk();
        $sig1 = $liveStat1->json('state_signature');

        $apkSig1 = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $apkTaskVer1 = $apkSig1->json('version');
        $apkInvVer1 = $apkSig1->json('inventory_version');

        // 2. Technician uploads a Proof of Work photo from APK -> bumps task.updated_at, Admin /live-pulse, and /tasks/{id}/live-status
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('opm_after.png', $pngBytes);
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/proof", [
            'image'       => $file,
            'slot'        => 'after',
            'description' => 'OPM reading -18.2 dBm verified',
        ])->assertCreated();

        $pulse2 = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertNotEquals($vPulse1, $pulse2->json('version'));
        $this->assertNotEquals($vProofScope1, $pulse2->json('scopes.proofs'));
        $this->assertEquals('proof', $pulse2->json('latest_event.type'));

        $liveStat2 = $this->actingAs($admin)->getJson("/tasks/{$task->id}/live-status")->assertOk();
        $this->assertEquals(1, $liveStat2->json('proofs_count'));
        $this->assertNotEquals($sig1, $liveStat2->json('state_signature'));

        // 3. Technician deducts 25m of Drop Core 1C from APK -> updates Admin inventory scope & APK inventory_version
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity'          => 25,
            'notes'             => 'Outdoor span',
        ])->assertCreated();

        $pulse3 = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertNotEquals($vInvScope1, $pulse3->json('scopes.inventory'));

        $apkSig2 = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $this->assertNotEquals($apkInvVer1, $apkSig2->json('inventory_version'));

        // 4. Admin posts a Dispatcher Note on /tasks/{id} -> bumps task.updated_at so APK sync-signal version changes immediately
        $apkTaskVerBeforeNote = $apkSig2->json('version');
        $this->travel(2)->seconds();
        $this->actingAs($admin)->post("/tasks/{$task->id}/notes", [
            'note'             => 'OPM photo verified, proceed to customer sign-off.',
            'progress_percent' => 90,
        ])->assertRedirect();

        $apkSig3 = $this->actingAs($tech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $this->assertNotEquals($apkTaskVerBeforeNote, $apkSig3->json('version'));

        // 5. Technician captures signature and completes task -> Admin /live-pulse immediately reports completed & BAST sealed
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/signature", [
            'signature'   => 'data:image/png;base64,' . base64_encode($pngBytes),
            'signer_name' => 'Bapak Hendra',
        ])->assertOk();

        $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete")->assertOk();

        $pulse4 = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertEquals(1, $pulse4->json('counts.completed'));
        $this->assertEquals('completed', $pulse4->json('latest_event.type'));

        $liveStatFinal = $this->actingAs($admin)->getJson("/tasks/{$task->id}/live-status")->assertOk();
        $liveStatFinal->assertJsonPath('status', 'completed')
            ->assertJsonPath('has_signature', true)
            ->assertJsonPath('proofs_count', 1)
            ->assertJsonPath('materials_count', 1);
        $this->assertNotEmpty($liveStatFinal->json('bast_hash'));
    }

    public function test_public_client_request_portal_admin_inbox_assignment_and_live_sync(): void
    {
        $admin = $this->createAdmin();

        // Offline tech is CLOSER (-6.2002, 106.8452), Online tech is slightly farther (-6.2050, 106.8500)
        // Auto-assign MUST prioritize the Online tech! Manual assign can freely pick either.
        $offlineNearTech = $this->createTechnician([
            'name'         => 'Offline Near Tech',
            'email'        => 'offline-near@fsm.com',
            'is_online'    => false,
            'last_seen_at' => now()->subHours(2),
        ]);
        $onlineTech = $this->createTechnician([
            'name'         => 'Online Active Tech',
            'email'        => 'online-active@fsm.com',
            'is_online'    => true,
            'last_seen_at' => now(),
        ]);

        TechnicianLocation::create([
            'technician_id' => $offlineNearTech->id,
            'latitude'      => -6.2002,
            'longitude'     => 106.8452,
        ]);
        TechnicianLocation::create([
            'technician_id' => $onlineTech->id,
            'latitude'      => -6.2050,
            'longitude'     => 106.8500,
        ]);

        // 1. Public /request renders cleanly without any authentication
        $this->get('/request')
            ->assertStatus(200)
            ->assertSee('Submit Fiber Service or Repair Request')
            ->assertSee('No Login Required');

        // 2. Honeypot anti-spam trap blocks bots
        $this->post('/request', [
            'website_url'      => 'https://spam.example.com',
            'service_category' => 'outage_los',
            'customer_name'    => 'Spam Bot',
            'customer_phone'   => '0811111111',
            'priority'         => 'high',
            'title'            => 'Spam ticket',
            'description'      => 'Spam',
            'address'          => 'Spam St',
        ])->assertRedirect()->assertSessionHasErrors('form');

        $this->assertEquals(0, Task::count());

        // 3. Valid public submission with 1x1 PNG issue photo creates unified Task (source=client_portal, status=pending)
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $photo = \Illuminate\Http\UploadedFile::fake()->createWithContent('los_modem.png', $pngBytes);

        $submitRes = $this->post('/request', [
            'service_category' => 'outage_los',
            'customer_name'    => 'Ibu Sari Wijaya',
            'customer_phone'   => '081234567890',
            'customer_email'   => 'sari@example.com',
            'priority'         => 'high',
            'title'            => 'ONT blinking red LOS after heavy rain',
            'description'      => 'No internet since 18:00, outdoor drop cable looks loose.',
            'address'          => 'Jl. Kemang Raya No. 45, Jakarta Selatan',
            'latitude'         => -6.2000,
            'longitude'        => 106.8450,
            'client_photo'     => $photo,
        ]);

        $task = Task::firstOrFail();
        $this->assertEquals('client_portal', $task->source);
        $this->assertEquals('outage_los', $task->service_category);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('sari@example.com', $task->customer_email);
        $this->assertNotEmpty($task->tracking_token);
        $this->assertNotEmpty($task->client_photo);

        $submitRes->assertRedirect(route('public.request.receipt', $task->tracking_token));

        // 4. Receipt & Live Customer Tracking work publicly without auth
        $ticketCode = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $this->get("/request/receipt/{$task->tracking_token}")
            ->assertStatus(200)
            ->assertSee($ticketCode)
            ->assertSee("/track/{$task->tracking_token}");

        $this->getJson("/api/public/track/{$task->tracking_token}")
            ->assertOk()
            ->assertJsonPath('task.status', 'pending')
            ->assertJsonPath('technician.name', 'Awaiting NOC Dispatch');

        // 5. Admin /live-pulse immediately reflects the pending client request & emits client_request live event
        $pulse = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertEquals(1, $pulse->json('counts.client_requests_pending'));
        $this->assertEquals('client_request', $pulse->json('latest_event.type'));

        // 6. Admin /client-requests inbox renders the request, Eye modal trigger, and Client Portal badge on /tasks
        $this->actingAs($admin)->get('/client-requests')
            ->assertStatus(200)
            ->assertSee('Ibu Sari Wijaya')
            ->assertSee('openClientRequestModal')
            ->assertSee('Online Active Tech');

        $this->actingAs($admin)->get('/tasks')
            ->assertStatus(200)
            ->assertSee('Client Portal');

        $this->actingAs($admin)->get("/tasks/{$task->id}")
            ->assertStatus(200)
            ->assertSee('Client Portal')
            ->assertSee('Client Attached Issue Photo')
            ->assertSee('sari@example.com');

        // 7. Capture APK sync-signal before dispatch -> Auto-Assign Online picks Online Active Tech over closer Offline Near Tech
        $apkBefore = $this->actingAs($onlineTech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $this->assertEquals(0, $apkBefore->json('assigned_count'));

        $this->actingAs($admin)
            ->post("/client-requests/{$task->id}/auto-assign")
            ->assertRedirect();

        $task->refresh();
        $this->assertEquals('assigned', $task->status);
        $this->assertEquals($onlineTech->id, $task->technician_id);

        // APK sync-signal immediately detects the newly assigned client request!
        $apkAfter = $this->actingAs($onlineTech, 'sanctum')->getJson('/api/tasks/sync-signal')->assertOk();
        $this->assertEquals(1, $apkAfter->json('assigned_count'));
        $this->assertNotEquals($apkBefore->json('version'), $apkAfter->json('version'));

        // Pending client requests counter drops to 0 in /live-pulse
        $pulseAfterDispatch = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertEquals(0, $pulseAfterDispatch->json('counts.client_requests_pending'));

        // 8. Admin can also freely assign ANY active technician (including offline) via manual assign
        $task2 = Task::create([
            'source'           => 'client_portal',
            'service_category' => 'new_install',
            'title'            => 'New FTTH 100Mbps Installation',
            'customer_name'    => 'Pak Budi',
            'customer_phone'   => '081999888777',
            'address'          => 'Jl. Sudirman No. 8',
            'priority'         => 'medium',
            'status'           => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/client-requests/{$task2->id}/assign", [
                'technician_id' => $offlineNearTech->id,
                'dispatch_note' => 'Assigning dedicated area specialist.',
            ])
            ->assertRedirect();

        $task2->refresh();
        $this->assertEquals('assigned', $task2->status);
        $this->assertEquals($offlineNearTech->id, $task2->technician_id);

        // 9. Admin can decline/reject a pending client request with reason
        $task3 = Task::create([
            'source'           => 'client_portal',
            'service_category' => 'relocation',
            'title'            => 'Duplicate relocation ticket',
            'customer_name'    => 'Duplicate User',
            'customer_phone'   => '081222333444',
            'address'          => 'Jl. Thamrin No. 1',
            'priority'         => 'low',
            'status'           => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/client-requests/{$task3->id}/reject", [
                'reason' => 'Duplicate request already handled under #TSK-0001.',
            ])
            ->assertRedirect();

        $this->assertEquals('rejected', $task3->fresh()->status);

        // Clean up uploaded test file from public storage disk
        if ($task->client_photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($task->client_photo);
        }
    }

    public function test_client_request_audit_edge_cases_equator_same_tech_html_and_live_pulse_tiebreaker(): void
    {
        $admin = $this->createAdmin();
        $techA = $this->createTechnician(['name' => 'Tech Alpha', 'email' => 'alpha-edge@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);
        $techB = $this->createTechnician(['name' => 'Tech Beta', 'email' => 'beta-edge@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);

        // 1. HTML-only inputs and half-coordinates are rejected cleanly (BUG-4)
        $this->post('/request', [
            'service_category' => 'outage_los',
            'customer_name'    => '<b></b>',
            'customer_phone'   => '<i></i>',
            'priority'         => 'high',
            'address'          => '<div> </div>',
            'description'      => '<script></script>',
            'latitude'         => 0.0,
        ])->assertRedirect()->assertSessionHasErrors(['longitude']);

        $this->post('/request', [
            'service_category' => 'outage_los',
            'customer_name'    => '<b></b>',
            'customer_phone'   => '0812345678',
            'priority'         => 'high',
            'address'          => 'Jl. Khatulistiwa',
            'description'      => 'LOS red',
        ])->assertRedirect()->assertSessionHasErrors(['customer_name']);

        // 2. Equator latitude 0.0 (Pontianak, Indonesia) is preserved cleanly & never treated as falsey null (BUG-3)
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $photo = \Illuminate\Http\UploadedFile::fake()->createWithContent('equator_photo.png', $pngBytes);

        $this->post('/request', [
            'service_category' => 'outage_los',
            'customer_name'    => 'Klien Pontianak',
            'customer_phone'   => '081299990000',
            'priority'         => 'high',
            'title'            => 'Equator Fiber Cut',
            'description'      => 'Red LOS at Equator Monument',
            'address'          => 'Jl. Khatulistiwa, Pontianak',
            'latitude'         => 0.0,
            'longitude'        => 109.3425,
            'client_photo'     => $photo,
        ])->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame(0.0, (float) $task->latitude);
        $this->assertSame(109.3425, (float) $task->longitude);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($task->client_photo));

        $trackJson = $this->getJson("/api/public/track/{$task->tracking_token}")->assertOk();
        $this->assertSame(0, (int) $trackJson->json('task.destination_lat'));
        $this->assertNotNull($trackJson->json('task.destination_lat'));

        // 3. Re-submitting SAME technician on an on-going task does NOT downgrade status back to assigned (BUG-1)
        $task->update(['technician_id' => $techA->id, 'status' => 'on-going']);

        $this->actingAs($admin)
            ->post("/client-requests/{$task->id}/assign", ['technician_id' => $techA->id])
            ->assertRedirect();
        $this->assertEquals('on-going', $task->fresh()->status);

        $this->actingAs($admin)
            ->post("/tasks/{$task->id}/assign", ['technician_id' => $techA->id])
            ->assertRedirect();
        $this->assertEquals('on-going', $task->fresh()->status);

        // 4. Auto-assign changing technician on an already-assigned task logs ProgressUpdate audit note (BUG-1)
        $task->update(['status' => 'assigned', 'technician_id' => $techA->id]);
        TechnicianLocation::create(['technician_id' => $techA->id, 'latitude' => -6.2000, 'longitude' => 106.8450]);
        TechnicianLocation::create(['technician_id' => $techB->id, 'latitude' => 0.0001, 'longitude' => 109.3426]);

        $progCountBefore = \App\Models\ProgressUpdate::where('task_id', $task->id)->count();
        $this->actingAs($admin)->post("/client-requests/{$task->id}/auto-assign")->assertRedirect();
        $this->assertEquals($techB->id, $task->fresh()->technician_id);
        $this->assertGreaterThan($progCountBefore, \App\Models\ProgressUpdate::where('task_id', $task->id)->count());

        // 5. /live-pulse event 1 second AFTER client request is NOT hijacked by client_request ts+1 (BUG-2)
        $task->update(['status' => 'on-going']);
        $this->travel(1)->seconds();
        $proofFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('proof_1s.png', $pngBytes);
        $this->actingAs($techB, 'sanctum')->postJson("/api/tasks/{$task->id}/proof", [
            'image'       => $proofFile,
            'slot'        => 'after',
            'description' => 'Spliced at +1s',
        ])->assertCreated();

        $pulse = $this->actingAs($admin)->getJson('/live-pulse')->assertOk();
        $this->assertEquals('proof', $pulse->json('latest_event.type'));

        // 6. Deleting task cleans up uploaded client_photo from public storage disk (BUG-4)
        $photoPath = $task->client_photo;
        $this->actingAs($admin)->delete("/tasks/{$task->id}")->assertRedirect();
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($photoPath));
    }

    public function test_milestone1_csat_rating_apk_client_photo_parity_and_reports_csat(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Tech CSAT', 'email' => 'csat@fsm.com', 'is_online' => true]);

        $task = Task::create([
            'source'           => 'client_portal',
            'service_category' => 'outage_los',
            'title'            => 'LOS Fiber Repair CSAT',
            'customer_name'    => 'Pak Dedi',
            'customer_phone'   => '081234567890',
            'address'          => 'Jl. Merdeka 1',
            'latitude'         => 0.0,
            'longitude'        => 109.3425,
            'client_photo'     => 'client_requests/sample_los.jpg',
            'technician_id'    => $tech->id,
            'status'           => 'assigned',
            'priority'         => 'high',
        ]);

        // 1. APK /api/tasks/{id} exposes client_photo_url, source, service_category_label for field engineer
        $this->actingAs($tech, 'sanctum')->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.source', 'client_portal')
            ->assertJsonPath('data.service_category_label', 'Total Outage / LOS Red Light')
            ->assertJsonPath('data.client_photo_url', url('/storage/client_requests/sample_los.jpg'));

        // 2. Rating is rejected before completion (422)
        $this->postJson("/api/public/track/{$task->tracking_token}/rating", [
            'rating'   => 5,
            'feedback' => 'Early rating attempt',
        ])->assertStatus(422);

        // 3. Complete task -> submit 5-star CSAT rating -> duplicate submission blocked (409)
        $task->update(['status' => 'completed', 'completed_at' => now()]);

        $this->postJson("/api/public/track/{$task->tracking_token}/rating", [
            'rating'   => 5,
            'feedback' => 'Super fast fiber splice and tidy cable!',
        ])->assertOk()
            ->assertJsonPath('data.customer_rating', 5);

        $this->postJson("/api/public/track/{$task->tracking_token}/rating", [
            'rating' => 1,
        ])->assertStatus(409);

        // 4. Real CSAT propagates to APK /api/me, Admin /live-pulse, /reports, /tasks/{id}, and /track/{token}
        $this->actingAs($tech, 'sanctum')->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('stats.avg_rating', 5)
            ->assertJsonPath('stats.rated_count', 1);

        $this->actingAs($admin)->getJson('/live-pulse')
            ->assertOk()
            ->assertJsonPath('latest_event.type', 'rating');

        $this->actingAs($admin)->get('/reports')
            ->assertOk()
            ->assertSee('Fleet CSAT: 5.0 / 5.0');

        $this->actingAs($admin)->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertSee('Customer CSAT Service Rating')
            ->assertSee('Super fast fiber splice and tidy cable!');

        $this->get("/track/{$task->tracking_token}")
            ->assertOk()
            ->assertSee('Thank You for Rating Our Field Service!')
            ->assertSee('Official BAST PDF');
    }

    public function test_milestone2_device_tokens_async_notification_ledger_dispatch_service_and_compiled_css(): void
    {
        $admin = $this->createAdmin();
        $tech = $this->createTechnician(['name' => 'Tech Push', 'email' => 'push@fsm.com', 'is_online' => true, 'last_seen_at' => now()]);

        // 1. Technician registers & updates FCM push token via /api/devices/token
        $this->actingAs($tech, 'sanctum')
            ->postJson('/api/devices/token', [
                'token'       => 'fcm_token_abcdef1234567890_device_1',
                'platform'    => 'android',
                'device_name' => 'Pixel Field Phone',
            ])
            ->assertCreated()
            ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $tech->id,
            'token'   => 'fcm_token_abcdef1234567890_device_1',
        ]);

        // 2. DispatchService assigns a pending client request -> logs queued FCM push in notification_logs
        $task = Task::create([
            'source'           => 'client_portal',
            'service_category' => 'outage_los',
            'title'            => 'Push Notification Dispatch Test',
            'customer_name'    => 'Ibu Rina',
            'customer_phone'   => '081234567899',
            'customer_email'   => 'rina@example.com',
            'address'          => 'Jl. Gatot Subroto 9',
            'priority'         => 'high',
            'status'           => 'pending',
        ]);

        $res = app(\App\Domain\Tasks\DispatchService::class)->autoAssign($task, $admin);
        $this->assertTrue($res['ok']);
        $this->assertEquals($tech->id, $task->fresh()->technician_id);

        $this->assertDatabaseHas('notification_logs', [
            'task_id'   => $task->id,
            'channel'   => 'fcm',
            'recipient' => 'fcm_token_abcdef1234567890_device_1',
            'event'     => 'TASK_ASSIGNED',
        ]);

        // 3. Unregistering device token removes it cleanly
        $this->actingAs($tech, 'sanctum')
            ->deleteJson('/api/devices/token', ['token' => 'fcm_token_abcdef1234567890_device_1'])
            ->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm_token_abcdef1234567890_device_1']);

        // 4. Compiled production Tailwind CSS exists & Admin layout no longer loads cdn.tailwindcss.com
        $this->assertFileExists(public_path('build/app.css'));
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('build/app.css')
            ->assertDontSee('cdn.tailwindcss.com');
    }

    public function test_milestone3_isp_crm_fiber_nodes_opm_gate_granular_rbac_and_sla_escalation(): void
    {
        $admin = $this->createAdmin();
        $warehouseAdmin = User::create([
            'name'      => 'Warehouse Staff',
            'email'     => 'warehouse@fsm.com',
            'password'  => bcrypt('password123'),
            'role'      => 'warehouse_admin',
            'is_active' => true,
        ]);
        $dispatcher = User::create([
            'name'      => 'NOC Dispatcher',
            'email'     => 'dispatcher@fsm.com',
            'password'  => bcrypt('password123'),
            'role'      => 'noc_dispatcher',
            'is_active' => true,
        ]);
        $tech = $this->createTechnician(['name' => 'FTTH Engineer', 'email' => 'ftth@fsm.com', 'is_online' => true]);

        // 1. Granular Multi-Role RBAC isolation:
        //    - warehouse_admin can access /inventory (200) & /dashboard (200), blocked from /settings (403) & /client-requests (403)
        //    - noc_dispatcher can access /customers (200), /fiber-assets (200), /client-requests (200), blocked from /settings (403)
        $this->actingAs($warehouseAdmin)->get('/inventory')->assertOk();
        $this->actingAs($warehouseAdmin)->get('/dashboard')->assertOk();
        $this->actingAs($warehouseAdmin)->get('/settings')->assertStatus(403);
        $this->actingAs($warehouseAdmin)->get('/client-requests')->assertStatus(403);

        $this->actingAs($dispatcher)->get('/customers')->assertOk();
        $this->actingAs($dispatcher)->get('/fiber-assets')->assertOk();
        $this->actingAs($dispatcher)->get('/client-requests')->assertOk();
        $this->actingAs($dispatcher)->get('/settings')->assertStatus(403);

        // 2. Fiber GIS Asset Registry (OLT -> ODP) + Nearby API for APK
        $this->actingAs($dispatcher)->post('/fiber-assets', [
            'code'        => 'ODP-KMG-01',
            'name'        => 'ODP Kemang Pole 01',
            'node_type'   => 'odp',
            'latitude'    => -6.2615,
            'longitude'   => 106.8106,
            'total_ports' => 8,
            'used_ports'  => 5,
            'status'      => 'operational',
        ])->assertRedirect();

        $odp = \App\Models\FiberNode::where('code', 'ODP-KMG-01')->firstOrFail();
        $this->assertEquals(3, $odp->available_ports);

        $this->actingAs($tech, 'sanctum')
            ->getJson('/api/fiber-nodes/nearby?lat=-6.2610&lng=106.8100')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ODP-KMG-01');

        // 3. Subscriber CRM registration linked to ODP
        $this->actingAs($dispatcher)->post('/customers', [
            'name'              => 'PT Fiber Nusantara',
            'phone'             => '081234560000',
            'address'           => 'Jl. Kemang Selatan 12',
            'service_plan'      => 'FTTH 200Mbps',
            'pppoe_username'    => 'ptfn@fiberops',
            'ont_serial_number' => 'ZTEGC991122',
            'fiber_node_id'     => $odp->id,
            'odp_port_number'   => 6,
        ])->assertRedirect();

        $cust = \App\Models\Customer::where('pppoe_username', 'ptfn@fiberops')->firstOrFail();
        $this->assertEquals($odp->id, $cust->fiber_node_id);

        // 4. OPM Optical Power (-dBm) recording & configurable strict completion gate
        $task = Task::create([
            'title'              => 'FTTH Attenuation Repair',
            'customer_name'      => $cust->name,
            'customer_id'        => $cust->id,
            'fiber_node_id'      => $odp->id,
            'address'            => $cust->address,
            'technician_id'      => $tech->id,
            'status'             => 'on-going',
            'priority'           => 'high',
            'customer_signature' => 'data:image/png;base64,iVBORw0KGgo=',
        ]);

        // Enable strict OPM gate (-27.0 dBm threshold)
        $settingsFile = \App\Services\SettingService::getFilePath();
        $origSettings = file_exists($settingsFile) ? file_get_contents($settingsFile) : null;
        $merged = array_merge(\App\Services\SettingService::all(), [
            'require_opm_reading'     => '1',
            'opm_max_attenuation_dbm' => '-27.0',
        ]);
        file_put_contents($settingsFile, json_encode($merged));

        try {
            $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            // Upload bad optical reading (-31.5 dBm) -> completion MUST be rejected (422)
            $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/proof", [
                'image'             => \Illuminate\Http\UploadedFile::fake()->createWithContent('bad_opm.png', $pngBytes),
                'slot'              => 'before',
                'optical_power_dbm' => -31.5,
                'description'       => 'High attenuation before splice',
            ])->assertCreated()->assertJsonPath('data.optical_status', 'high_loss');

            $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete")
                ->assertStatus(422);

            // Upload good optical reading (-18.4 dBm) -> completion succeeds!
            $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/proof", [
                'image'             => \Illuminate\Http\UploadedFile::fake()->createWithContent('good_opm.png', $pngBytes),
                'slot'              => 'after',
                'optical_power_dbm' => -18.4,
                'description'       => 'Clean fusion splice',
            ])->assertCreated()->assertJsonPath('data.optical_status', 'good');

            $this->actingAs($tech, 'sanctum')->postJson("/api/tasks/{$task->id}/complete")->assertOk();
            $this->assertEquals('completed', $task->fresh()->status);

            $this->actingAs($admin)->get("/tasks/{$task->id}")
                ->assertOk()
                ->assertSee('OPM -18.4 dBm GOOD');
        } finally {
            if ($origSettings !== null) {
                file_put_contents($settingsFile, $origSettings);
            } elseif (file_exists($settingsFile)) {
                @unlink($settingsFile);
            }
        }

        // 5. Automated SLA Escalation Engine fires exactly once per overdue ticket
        $overdueTask = Task::create([
            'title'         => 'Unassigned Urgent Outage',
            'customer_name' => 'VIP Client',
            'address'       => 'Jl. Sudirman',
            'priority'      => 'high',
            'status'        => 'pending',
        ]);
        \Illuminate\Support\Facades\DB::table('tasks')
            ->where('id', $overdueTask->id)
            ->update(['created_at' => now()->subMinutes(30)]);

        $svc = app(\App\Services\SlaEscalationService::class);
        $this->assertEquals(1, $svc->runEscalationSweep());
        $this->assertNotNull($overdueTask->fresh()->sla_escalated_at);
        // Second sweep MUST NOT double-escalate!
        $this->assertEquals(0, $svc->runEscalationSweep());
    }

    public function test_milestone4_staff_rbac_and_customer_odp_workflow(): void
    {
        $superAdmin = $this->createAdmin();
        $superAdmin->update(['role' => 'super_admin']);

        // 1. Staff Management UI: List, Create, Toggle Status & Delete
        $this->actingAs($superAdmin)->get('/staff')
            ->assertOk()
            ->assertSee('Portal Staff Directory');

        $staffData = [
            'name'                  => 'Rina Dispatcher',
            'email'                 => 'rina.disp@bentengsiber.com',
            'role'                  => 'noc_dispatcher',
            'phone'                 => '+6281987654321',
            'password'              => 'securePass123',
            'password_confirmation' => 'securePass123',
            'is_active'             => 1,
        ];

        $this->actingAs($superAdmin)->post('/staff', $staffData)
            ->assertRedirect(route('admin.staff.index'));

        $rina = User::where('email', 'rina.disp@bentengsiber.com')->first();
        $this->assertNotNull($rina);
        $this->assertEquals('noc_dispatcher', $rina->role);
        $this->assertTrue($rina->is_active);

        // Toggle Status
        $this->actingAs($superAdmin)->post("/staff/{$rina->id}/toggle-status")
            ->assertRedirect();
        $this->assertFalse($rina->fresh()->is_active);

        // Safety: Cannot deactivate own session
        $this->actingAs($superAdmin)->post("/staff/{$superAdmin->id}/toggle-status")
            ->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->is_active);

        // 2. Customer CRM & Fiber Node Task Provisioning Integration
        $fiberNode = \App\Models\FiberNode::create([
            'code'        => 'ODP-T4-01',
            'name'        => 'ODP Test Cluster 01',
            'node_type'   => 'odp',
            'latitude'    => -6.2088,
            'longitude'   => 106.8456,
            'address'     => 'Jl. Testing ODP',
            'total_ports' => 8,
            'used_ports'  => 3,
            'status'      => 'active',
        ]);

        $customer = \App\Models\Customer::create([
            'customer_code'     => 'CUST-T4-001',
            'name'              => 'Budi Testing',
            'phone'             => '+6281234567890',
            'email'             => 'budi@test.com',
            'address'           => 'Jl. Testing No. 42',
            'latitude'          => -6.2090,
            'longitude'         => 106.8459,
            'service_plan'      => 'FTTH 100 Mbps',
            'pppoe_username'    => 'budi_pppoe',
            'ont_serial_number' => 'ZTEG98765432',
            'fiber_node_id'     => $fiberNode->id,
            'odp_port_number'   => 4,
            'status'            => 'active',
        ]);

        $task = Task::create([
            'title'            => 'FTTH New Installation - Budi',
            'customer_name'    => $customer->name,
            'customer_phone'   => $customer->phone,
            'address'          => $customer->address,
            'service_category' => 'new_install',
            'customer_id'      => $customer->id,
            'fiber_node_id'    => $fiberNode->id,
            'status'           => 'on-going',
            'priority'         => 'high',
        ]);

        // State Machine auto-increments ODP used ports upon task completion
        $stateMachine = app(\App\Domain\Tasks\TaskStateMachine::class);
        $stateMachine->transition($task, 'completed', $superAdmin, 'Installation successful', ['unassign' => false], true);

        $this->assertEquals(4, $fiberNode->fresh()->used_ports);

        // State Machine decrements ODP used ports upon task rework / status rollback
        $stateMachine->transition($task, 'on-going', $superAdmin, 'Rework requested', [], true);
        $this->assertEquals(3, $fiberNode->fresh()->used_ports);

        // 3. Public Ticket Lookup & Verification (/track)
        $this->get('/track')->assertOk()->assertSee('Track Your Work Order');

        // Invalid lookup
        $this->from('/track')->post('/track/lookup', [
            'ticket_number' => (string) $task->id,
            'phone_last4'   => '0000', // Wrong last 4 digits
        ])->assertSessionHas('error');

        // Valid lookup (matching ticket ID and phone last 4: 7890)
        $resp = $this->from('/track')->post('/track/lookup', [
            'ticket_number' => '#TSK-' . $task->id,
            'phone_last4'   => '7890',
        ]);
        $resp->assertRedirect(route('customer.tracking', ['token' => $task->fresh()->tracking_token]));

        // 4. Enhanced BAST Acceptance Printout
        $task->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
        $this->actingAs($superAdmin)->get("/tasks/{$task->id}/bast")
            ->assertOk()
            ->assertSee('budi_pppoe')
            ->assertSee('ZTEG98765432')
            ->assertSee('ODP-T4-01')
            ->assertSee('Optical Power Meter (OPM) Calibration &amp; Signal Quality Audit', false);
    }
}
