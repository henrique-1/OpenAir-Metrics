<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isDevOrTest = app()->isLocal() || app()->runningUnitTests();
        $password = env('INITIAL_SUPERADMIN_PASSWORD') ?: ($isDevOrTest ? 'superadmin123' : Str::random(24));

        User::updateOrCreate(
            ['email' => 'superadmin@openair.com'],
            [
                'name' => 'Super Usuário',
                'password' => Hash::make($password),
                'nivel' => 'superadmin',
                'ativo' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($this->command) {
            $this->command->info('Super Usuário pronto: superadmin@openair.com');

            if (env('INITIAL_SUPERADMIN_PASSWORD')) {
                $this->command->line(' [Senha definida via INITIAL_SUPERADMIN_PASSWORD no .env]');
            } elseif (app()->isLocal()) {
                $this->command->line(" [Ambiente local: senha padrão 'superadmin123']");
            } else {
                $this->command->warn(" [ATENÇÃO] Senha aleatória gerada para o Super Usuário: {$password}");
                $this->command->warn(' Guarde esta senha em local seguro para o primeiro acesso.');
            }
        }
    }
}
