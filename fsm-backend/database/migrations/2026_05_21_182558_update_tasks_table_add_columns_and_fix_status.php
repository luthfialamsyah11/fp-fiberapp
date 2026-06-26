<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Change status column from enum to string so we can use more values
        // MySQL enum modifications require rebuild – easiest is to use string
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");

        // Step 2: Add missing columns if they don't exist
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'location')) {
                $table->text('location')->nullable()->after('address');
            }
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('status');
            }
            if (!Schema::hasColumn('tasks', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('tasks', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('priority');
            }
            // description and address are currently NOT NULL, let's make them nullable for flexibility
        });

        // Step 3: Make description and address nullable
        DB::statement("ALTER TABLE tasks MODIFY COLUMN description TEXT NULL");
        DB::statement("ALTER TABLE tasks MODIFY COLUMN address TEXT NULL");

        // Step 4: Update old 'in_progress' status values to 'on-going'
        DB::table('tasks')->where('status', 'in_progress')->update(['status' => 'on-going']);
        DB::table('tasks')->where('status', 'assigned')->orWhere('status', '')->update(['status' => 'assigned']);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $columns = ['location', 'priority', 'customer_phone', 'scheduled_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('assigned','accepted','on-going','completed') NOT NULL DEFAULT 'assigned'");
    }
};
