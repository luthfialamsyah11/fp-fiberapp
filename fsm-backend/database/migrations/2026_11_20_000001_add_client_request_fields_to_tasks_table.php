<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'source')) {
                $table->string('source', 30)->default('admin')->after('priority');
                $table->index('source', 'tasks_source_idx');
            }
            if (!Schema::hasColumn('tasks', 'service_category')) {
                $table->string('service_category', 60)->nullable()->after('source');
            }
            if (!Schema::hasColumn('tasks', 'customer_email')) {
                $table->string('customer_email', 255)->nullable()->after('customer_phone');
            }
            if (!Schema::hasColumn('tasks', 'client_photo')) {
                $table->string('client_photo', 255)->nullable()->after('additional_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach (['source', 'service_category', 'customer_email', 'client_photo'] as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
