<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create inventory_items table
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // SKU / Part code, e.g. MAT-DC-1C
                $table->string('name');            // e.g. Kabel Dropcore 1 Core
                $table->string('unit')->default('pcs'); // meter, pcs, unit, roll
                $table->decimal('stock', 10, 2)->default(0);
                $table->decimal('unit_price', 12, 2)->default(0); // estimated price in IDR
                $table->string('category')->default('material'); // material, tool, device
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create task_materials table (Bill of Materials used in task)
        if (!Schema::hasTable('task_materials')) {
            Schema::create('task_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('serial_number')->nullable(); // For ONT modem / router
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Add tracking_token & customer_signature fields to tasks table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('tasks', 'customer_signature')) {
                $table->text('customer_signature')->nullable(); // Can store path or base64
            }
            if (!Schema::hasColumn('tasks', 'customer_signer_name')) {
                $table->string('customer_signer_name')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'customer_signed_at')) {
                $table->timestamp('customer_signed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $cols = ['tracking_token', 'customer_signature', 'customer_signer_name', 'customer_signed_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('tasks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('task_materials');
        Schema::dropIfExists('inventory_items');
    }
};
