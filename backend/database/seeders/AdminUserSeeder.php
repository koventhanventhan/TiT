<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'koventhanventhan153@gmail.com'],
            [
                'name' => 'Admin Koventhan',
                'role' => 'admin',
                'password' => Hash::make('Venthan153!'),
                'plain_password' => 'Venthan153!',
                'institute_id' => 1, // Main Institute
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'superadmin@lenova.lk'],
            [
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => Hash::make('superadmin123'),
                'plain_password' => 'superadmin123',
                'institute_id' => null, 
                'email_verified_at' => now(),
            ]
        );
    }
}
