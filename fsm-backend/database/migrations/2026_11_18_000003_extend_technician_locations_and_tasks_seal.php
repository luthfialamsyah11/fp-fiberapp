<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('technician_locations', 'accuracy_m')) {
                $table->float('accuracy_m')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('technician_locations', 'speed_mps')) {
                $table->float('speed_mps')->nullable()->after('accuracy_m');
            }
            if (!Schema::hasColumn('technician_locations', 'heading_deg')) {
                $table->float('heading_deg')->nullable()->after('speed_mps');
            }
            if (!Schema::hasColumn('technician_locations', 'captured_at')) {
                $table->timestamp('captured_at')->nullable()->after('heading_deg');
            }
            if (!Schema::hasColumn('technician_locations', 'task_id')) {
                $table->foreignId('task_id')->nullable()->after('technician_id')->constrained('tasks')->nullOnDelete();
            }
            if (!Schema::hasColumn('technician_locations', 'client_uuid')) {
                $table->string('client_uuid', 64)->nullable()->unique()->after('task_id');
            }
            if (!Schema::hasColumn('technician_locations', 'source')) {
                $table->string('source', 24)->default('foreground')->after('client_uuid');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_online');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('customer_signed_at');
            }
            if (!Schema::hasColumn('tasks', 'bast_hash')) {
                $table->string('bast_hash', 64)->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('tasks', 'bast_sealed_at')) {
                $table->timestamp('bast_sealed_at')->nullable()->after('bast_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('technician_locations', function (Blueprint $table) {
            $cols = array_filter(
                ['accuracy_m', 'speed_mps', 'heading_deg', 'captured_at', 'task_id', 'client_uuid', 'source'],
                fn ($c) => Schema::hasColumn('technician_locations', $c)
            );
            if (!empty($cols)) {
                if (in_array('task_id', $cols, true)) {
                    $table->dropForeign(['task_id']);
                }
                $table->dropColumn($cols);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $cols = array_filter(
                ['completed_at', 'bast_hash', 'bast_sealed_at'],
                fn ($c) => Schema::hasColumn('tasks', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
