<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables requiring row-level tenant partitioning.
     */
    protected array $tables = [
        'users',
        'tasks',
        'customers',
        'fiber_nodes',
        'inventory_items',
        'device_tokens',
        'notification_logs',
        'task_status_histories',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure default tenant exists so existing data remains 100% valid
        $defaultTenantId = DB::table('tenants')->insertGetId([
            'name'             => 'Benteng Siber Demo Operations',
            'slug'             => 'demo',
            'contact_name'     => 'System Super Admin',
            'contact_email'    => 'admin@bentengsiber.com',
            'contact_phone'    => '081223554539',
            'plan'             => 'enterprise',
            'status'           => 'active',
            'max_technicians'  => 9999,
            'max_fiber_nodes'  => 9999,
            'trial_ends_at'    => now()->addYears(10),
            'brand_color'      => '#0f3559',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // 2. Add tenant_id to each table, backfill to defaultTenantId, and index
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                        $table->index('tenant_id');
                    }
                });

                // Backfill existing records
                DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
