<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proof_of_work')) {
            Schema::table('proof_of_work', function (Blueprint $table) {
                if (!Schema::hasColumn('proof_of_work', 'slot')) {
                    $table->string('slot', 16)->nullable()->after('image');
                }
            });

            try {
                Schema::table('proof_of_work', function (Blueprint $table) {
                    $table->index(['task_id', 'id'], 'proof_task_id_idx');
                });
            } catch (\Throwable $e) {
                // Index already exists
            }
        }

        if (Schema::hasTable('tasks')) {
            $taskIndexes = [
                'tasks_status_idx'        => ['status'],
                'tasks_tech_status_idx'   => ['technician_id', 'status'],
                'tasks_prio_status_idx'   => ['priority', 'status'],
                'tasks_created_at_idx'    => ['created_at'],
            ];

            foreach ($taskIndexes as $name => $cols) {
                try {
                    Schema::table('tasks', function (Blueprint $table) use ($cols, $name) {
                        $table->index($cols, $name);
                    });
                } catch (\Throwable $e) {
                    // Index already exists
                }
            }
        }

        if (Schema::hasTable('technician_locations')) {
            $locIndexes = [
                'tech_loc_tech_id_idx'    => ['technician_id', 'id'],
                'tech_loc_created_at_idx' => ['created_at'],
            ];

            foreach ($locIndexes as $name => $cols) {
                try {
                    Schema::table('technician_locations', function (Blueprint $table) use ($cols, $name) {
                        $table->index($cols, $name);
                    });
                } catch (\Throwable $e) {
                    // Index already exists
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proof_of_work') && Schema::hasColumn('proof_of_work', 'slot')) {
            Schema::table('proof_of_work', function (Blueprint $table) {
                $table->dropColumn('slot');
            });
        }
    }
};
