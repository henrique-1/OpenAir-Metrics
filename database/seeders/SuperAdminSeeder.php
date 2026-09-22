<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@openair.com'],
            [
                'name' => 'Super Usuário',
                'password' => Hash::make('superadmin123'),
                'nivel' => 'superadmin',
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
