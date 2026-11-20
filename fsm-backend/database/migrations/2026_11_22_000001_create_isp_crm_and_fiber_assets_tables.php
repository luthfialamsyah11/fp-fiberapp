<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen users.role from legacy ENUM('admin','technician') on existing MySQL production installs
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(32) NOT NULL DEFAULT 'technician'");
        }

        if (!Schema::hasTable('fiber_nodes')) {
            Schema::create('fiber_nodes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 60)->unique();
                $table->string('name', 160);
                $table->string('node_type', 24)->default('odp'); // olt, odc, odp, closure
                $table->foreignId('parent_id')->nullable()->constrained('fiber_nodes')->nullOnDelete();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('address', 500)->nullable();
                $table->unsignedSmallInteger('total_ports')->default(8);
                $table->unsignedSmallInteger('used_ports')->default(0);
                $table->string('status', 24)->default('operational'); // operational, degraded, maintenance
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['node_type', 'status'], 'fiber_nodes_type_status_idx');
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code', 40)->unique();
                $table->string('name', 200);
                $table->string('phone', 50);
                $table->string('email', 255)->nullable();
                $table->text('address');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('service_plan', 120)->nullable();
                $table->string('pppoe_username', 120)->nullable();
                $table->string('ont_serial_number', 120)->nullable();
                $table->foreignId('fiber_node_id')->nullable()->constrained('fiber_nodes')->nullOnDelete();
                $table->unsignedSmallInteger('odp_port_number')->nullable();
                $table->string('status', 24)->default('active'); // active, suspended, terminated
                $table->timestamps();

                $table->index(['status', 'name'], 'customers_status_name_idx');
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('technician_id')->constrained('customers')->nullOnDelete();
            }
            if (!Schema::hasColumn('tasks', 'fiber_node_id')) {
                $table->foreignId('fiber_node_id')->nullable()->after('customer_id')->constrained('fiber_nodes')->nullOnDelete();
            }
            if (!Schema::hasColumn('tasks', 'sla_escalated_at')) {
                $table->timestamp('sla_escalated_at')->nullable()->after('customer_rated_at');
            }
        });

        Schema::table('proof_of_work', function (Blueprint $table) {
            if (!Schema::hasColumn('proof_of_work', 'optical_power_dbm')) {
                $table->decimal('optical_power_dbm', 5, 2)->nullable()->after('slot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proof_of_work', function (Blueprint $table) {
            if (Schema::hasColumn('proof_of_work', 'optical_power_dbm')) {
                $table->dropColumn('optical_power_dbm');
            }
        });
        Schema::table('tasks', function (Blueprint $table) {
            foreach (['customer_id', 'fiber_node_id', 'sla_escalated_at'] as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('customers');
        Schema::dropIfExists('fiber_nodes');
    }
};
