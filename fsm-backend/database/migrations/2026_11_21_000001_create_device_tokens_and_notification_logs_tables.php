<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('platform', 20)->default('android');
                $table->string('token_hash', 64)->unique();
                $table->string('token', 512);
                $table->string('device_name', 120)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'platform'], 'device_tokens_user_platform_idx');
            });
        }

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
                $table->string('channel', 32); // fcm, whatsapp, email, webhook
                $table->string('recipient', 255)->nullable();
                $table->string('event', 80);
                $table->json('payload')->nullable();
                $table->string('status', 24)->default('queued'); // queued, sent, failed, skipped
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'channel', 'created_at'], 'notification_logs_status_idx');
                $table->index(['task_id', 'event'], 'notification_logs_task_event_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('device_tokens');
    }
};
