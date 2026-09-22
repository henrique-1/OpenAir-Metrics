<?php

namespace Database\Seeders;

use App\Models\MalhaViaria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        DB::transaction(function () {
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
