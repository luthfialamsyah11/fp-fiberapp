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
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'network_settings')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->text('network_settings')->nullable()->after('settings');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'network_settings')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('network_settings');
            });
        }
    }
};
