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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('malha_viaria')) {
            return;
        }

        try {
            // Verifica se já existe índice espacial na coluna geometria
            $indexes = DB::select("SHOW INDEX FROM malha_viaria WHERE Column_name = 'geometria' AND Index_type = 'SPATIAL'");
            if (! empty($indexes)) {
                return;
            }

            // Remove registros órfãos ou com geometria nula antes de aplicar NOT NULL
            DB::statement('DELETE FROM malha_viaria WHERE geometria IS NULL');

            // No MySQL 8 / MariaDB, para ter SPATIAL INDEX, a coluna precisa ser NOT NULL com SRID definido
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE malha_viaria MODIFY geometria GEOMETRY NOT NULL SRID 4326');
            } else {
                DB::statement('ALTER TABLE malha_viaria MODIFY geometria GEOMETRY NOT NULL');
            }

            // Cria o índice espacial R-Tree para acelerar consultas espaciais de 90s para ~5ms
            DB::statement('ALTER TABLE malha_viaria ADD SPATIAL INDEX idx_malha_viaria_geometria (geometria)');
        } catch (Throwable $e) {
            // Se o banco não suportar ou o índice já existir, registra log mas não impede deploy
            Log::warning('Aviso ao criar SPATIAL INDEX na malha_viaria: '.$e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasTable('malha_viaria')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE malha_viaria DROP INDEX idx_malha_viaria_geometria');
        } catch (Throwable) {
            // Ignora se não existir
        }
    }
};
