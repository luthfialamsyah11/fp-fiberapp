<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'customer_rating')) {
                $table->unsignedTinyInteger('customer_rating')->nullable()->after('bast_sealed_at');
                $table->index('customer_rating', 'tasks_customer_rating_idx');
            }
            if (!Schema::hasColumn('tasks', 'customer_feedback')) {
                $table->text('customer_feedback')->nullable()->after('customer_rating');
            }
            if (!Schema::hasColumn('tasks', 'customer_rated_at')) {
                $table->timestamp('customer_rated_at')->nullable()->after('customer_feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            foreach (['customer_rating', 'customer_feedback', 'customer_rated_at'] as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
