<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('technician_locations')) {
            return;
        }

        // Backfill NULL captured_at on legacy GPS points so ofMany(['captured_at' => 'max', 'id' => 'max']) never hides them
        if (Schema::hasColumn('technician_locations', 'captured_at')) {
            DB::table('technician_locations')
                ->whereNull('captured_at')
                ->update(['captured_at' => DB::raw('created_at')]);

            try {
                Schema::table('technician_locations', function (Blueprint $table) {
                    $table->index(['technician_id', 'captured_at'], 'idx_tech_loc_tech_captured');
                });
            } catch (\Throwable $e) {
                // Index already exists on MySQL/SQLite
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('technician_locations')) {
            return;
        }

        try {
            Schema::table('technician_locations', function (Blueprint $table) {
                $table->dropIndex('idx_tech_loc_tech_captured');
            });
        } catch (\Throwable $e) {
            // Ignore if index does not exist
        }
    }
};
