<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop global unique indexes on business identifiers and replace with
     * tenant-scoped composite unique indexes (tenant_id + code).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'tenant_id')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->dropUnique('customers_customer_code_unique');
                    $table->unique(['tenant_id', 'customer_code'], 'customers_tenant_code_unique');
                });
            }

            if (Schema::hasTable('fiber_nodes') && Schema::hasColumn('fiber_nodes', 'tenant_id')) {
                Schema::table('fiber_nodes', function (Blueprint $table) {
                    $table->dropUnique('fiber_nodes_code_unique');
                    $table->unique(['tenant_id', 'code'], 'fiber_nodes_tenant_code_unique');
                });
            }

            if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'tenant_id')) {
                Schema::table('inventory_items', function (Blueprint $table) {
                    $table->dropUnique('inventory_items_code_unique');
                    $table->unique(['tenant_id', 'code'], 'inventory_items_tenant_code_unique');
                });
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'tenant_id')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->dropUnique('customers_tenant_code_unique');
                    $table->unique('customer_code', 'customers_customer_code_unique');
                });
            }

            if (Schema::hasTable('fiber_nodes') && Schema::hasColumn('fiber_nodes', 'tenant_id')) {
                Schema::table('fiber_nodes', function (Blueprint $table) {
                    $table->dropUnique('fiber_nodes_tenant_code_unique');
                    $table->unique('code', 'fiber_nodes_code_unique');
                });
            }

            if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'tenant_id')) {
                Schema::table('inventory_items', function (Blueprint $table) {
                    $table->dropUnique('inventory_items_tenant_code_unique');
                    $table->unique('code', 'inventory_items_code_unique');
                });
            }
        }
    }
};
