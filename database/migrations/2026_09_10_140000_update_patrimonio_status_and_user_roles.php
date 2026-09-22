<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // No MySQL/MariaDB, expande a coluna primeiro para evitar erro 1265 Data truncated
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE patrimonios MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Disponível'");
        }

        // Normaliza registros existentes
        DB::table('patrimonios')->where('status', 'Instalado')->update(['status' => 'Instalada']);
        DB::table('patrimonios')->whereIn('status', ['Alocado', 'Manutenção'])->update(['status' => 'Disponível']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE patrimonios MODIFY COLUMN status ENUM('Disponível', 'Instalada', 'Descartado') NOT NULL DEFAULT 'Disponível'");
            DB::statement("ALTER TABLE users MODIFY COLUMN nivel ENUM('superadmin', 'administrador', 'cadastrador', 'planejador', 'instalador') NOT NULL DEFAULT 'cadastrador'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE patrimonios MODIFY COLUMN status ENUM('Disponível', 'Alocado', 'Instalado', 'Instalada', 'Manutenção', 'Descartado') NOT NULL DEFAULT 'Disponível'");
            DB::statement("ALTER TABLE users MODIFY COLUMN nivel ENUM('administrador', 'cadastrador', 'instalador') NOT NULL DEFAULT 'cadastrador'");
        }
    }
};
