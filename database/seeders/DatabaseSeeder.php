<?php

namespace Database\Seeders;

use App\Models\MalhaViaria;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador Municipal padrão
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'nivel' => 'administrador',
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::transaction(function () {
            $this->call(LocalidadesSeeder::class);
        });

        try {
            if (MalhaViaria::doesntExist()) {
                $this->call(MalhaViariaSeeder::class);
            }
        } catch (\Throwable) {
            $this->call(MalhaViariaSeeder::class);
        }
    }
}
