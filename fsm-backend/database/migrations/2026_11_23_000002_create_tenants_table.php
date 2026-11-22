<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('contact_name');
            $table->string('contact_email')->unique();
            $table->string('contact_phone')->nullable();
            $table->enum('plan', ['starter', 'growth', 'enterprise'])->default('starter');
            $table->enum('status', ['trial', 'active', 'suspended', 'expired'])->default('trial');
            $table->unsignedInteger('max_technicians')->default(5);
            $table->unsignedInteger('max_fiber_nodes')->default(50);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->string('brand_logo_url')->nullable();
            $table->string('brand_color')->nullable()->default('#0f3559');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'plan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
