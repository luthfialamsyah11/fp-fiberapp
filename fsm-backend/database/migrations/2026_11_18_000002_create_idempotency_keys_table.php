<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $table) {
                $table->id();
                $table->string('idempotency_key', 128);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('method', 16);
                $table->string('path', 255);
                $table->unsignedSmallInteger('response_code');
                $table->longText('response_body');
                $table->timestamps();

                $table->unique(['idempotency_key', 'user_id', 'method', 'path'], 'idemp_unique_idx');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
