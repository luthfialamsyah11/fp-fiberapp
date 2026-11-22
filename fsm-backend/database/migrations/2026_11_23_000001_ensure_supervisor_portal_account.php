<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $supervisor = DB::table('users')->where('email', 'supervisor@fsm.com')->first();

        if (!$supervisor) {
            $data = [
                'name'       => 'Field Supervisor',
                'email'      => 'supervisor@fsm.com',
                'password'   => Hash::make('password123'),
                'role'       => 'field_supervisor',
                'phone'      => '081122334466',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('users', 'tenant_id')) {
                $data['tenant_id'] = 1;
            }
            DB::table('users')->insert($data);
        } else {
            $updates = [];
            if ($supervisor->role !== 'field_supervisor') {
                $updates['role'] = 'field_supervisor';
            }
            if (!$supervisor->is_active) {
                $updates['is_active'] = true;
            }
            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('users')->where('id', $supervisor->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->where('email', 'supervisor@fsm.com')->delete();
        }
    }
};
