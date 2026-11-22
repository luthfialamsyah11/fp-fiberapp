<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create immutable SHA-256 hash-chained platform governance audit ledger.
     */
    public function up(): void
    {
        if (!Schema::hasTable('platform_audit_logs')) {
            Schema::create('platform_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('actor_name', 160);
                $table->string('actor_email', 190);
                $table->unsignedBigInteger('target_tenant_id')->nullable()->index();
                $table->string('target_tenant_name', 190)->nullable();
                $table->string('action', 64)->index();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('prev_hash', 64);
                $table->string('entry_hash', 64)->unique();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
    }
};
