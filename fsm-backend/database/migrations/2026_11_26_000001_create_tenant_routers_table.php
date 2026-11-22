<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_routers')) {
            Schema::create('tenant_routers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name', 140);
                $table->string('code', 40);
                $table->string('driver', 30)->default('simulator'); // simulator | rest
                $table->string('host', 255)->nullable();
                $table->unsignedInteger('port')->default(443);
                $table->string('username', 120)->default('admin');
                $table->longText('password')->nullable(); // AES-256-CBC encrypted via Laravel encrypted cast
                $table->boolean('use_ssl')->default(true);
                $table->boolean('verify_ssl')->default(false);
                $table->string('isolation_profile', 80)->default('ISOLIR');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_tested_at')->nullable();
                $table->string('last_test_status', 40)->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
                $table->index(['tenant_id', 'is_default', 'is_active']);
            });
        }

        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'router_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('router_id')
                    ->nullable()
                    ->after('fiber_node_id')
                    ->constrained('tenant_routers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'router_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('router_id');
            });
        }

        Schema::dropIfExists('tenant_routers');
    }
};
