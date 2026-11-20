<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $supervisor = User::where('email', 'supervisor@fsm.com')->first();

        if (!$supervisor) {
            User::create([
                'name'      => 'Field Supervisor',
                'email'     => 'supervisor@fsm.com',
                'password'  => Hash::make('password123'),
                'role'      => 'field_supervisor',
                'phone'     => '081122334466',
                'is_active' => true,
            ]);
        } else {
            $updates = [];
            if ($supervisor->role !== 'field_supervisor') {
                $updates['role'] = 'field_supervisor';
            }
            if (!$supervisor->is_active) {
                $updates['is_active'] = true;
            }
            if (!empty($updates)) {
                $supervisor->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            User::where('email', 'supervisor@fsm.com')->delete();
        }
    }
};
