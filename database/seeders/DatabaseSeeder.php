<?php

namespace Database\Seeders;

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
        // Garante que o usuário seja criado ou atualizado se já existir,
        // evitando erros de violação de chave única (Unique Constraint).
        User::updateOrCreate(
            ['email' => 'admin@admin.com'], // Condição de busca
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(), // Opcional: já marca o e-mail como verificado
            ]
        );
    }
}
