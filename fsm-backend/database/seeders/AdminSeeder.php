<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'      => 'Admin FSM',
            'email'     => 'admin@fsm.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Teknisi Demo',
            'email'     => 'teknisi@fsm.com',
            'password'  => Hash::make('password123'),
            'role'      => 'technician',
            'phone'     => '08123456789',
            'is_active' => true,
        ]);
    }
}