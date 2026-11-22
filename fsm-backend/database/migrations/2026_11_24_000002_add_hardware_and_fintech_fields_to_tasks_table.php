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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'optical_power_rx_dbm')) {
                $table->decimal('optical_power_rx_dbm', 6, 2)->nullable()->after('bast_hash');
            }
            if (!Schema::hasColumn('tasks', 'optical_power_tx_dbm')) {
                $table->decimal('optical_power_tx_dbm', 6, 2)->nullable()->after('optical_power_rx_dbm');
            }
            if (!Schema::hasColumn('tasks', 'hardware_verified_at')) {
                $table->timestamp('hardware_verified_at')->nullable()->after('optical_power_tx_dbm');
            }
            if (!Schema::hasColumn('tasks', 'billing_amount')) {
                $table->decimal('billing_amount', 12, 2)->default(0)->after('hardware_verified_at');
            }
            if (!Schema::hasColumn('tasks', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('billing_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['optical_power_rx_dbm', 'optical_power_tx_dbm', 'hardware_verified_at', 'billing_amount', 'payment_status'] as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
