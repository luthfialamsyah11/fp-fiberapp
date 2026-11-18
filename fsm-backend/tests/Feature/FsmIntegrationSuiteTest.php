<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProofOfWork;
use App\Models\Task;
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
            ->assertStatus(422);

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
}
