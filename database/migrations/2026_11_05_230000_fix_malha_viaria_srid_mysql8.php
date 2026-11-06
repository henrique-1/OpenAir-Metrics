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

        // 1. Cadastra o SRID 1 no MySQL 8 caso não exista (evita erro 3548 caso o GDAL/ogr2ogr use SRID 1)
        try {
            DB::statement("
                CREATE SPATIAL REFERENCE SYSTEM IF NOT EXISTS 1
                NAME 'WGS 84 (GIS)'
                ORGANIZATION 'EPSG'
                IDENTIFIED BY 4326
                DEFINITION 'GEOGCS[\"WGS 84\",DATUM[\"WGS_1984\",SPHEROID[\"WGS 84\",6378137,298.257223563,AUTHORITY[\"EPSG\",\"7030\"]],AUTHORITY[\"EPSG\",\"6326\"]],PRIMEM[\"Greenwich\",0,AUTHORITY[\"EPSG\",\"8901\"]],UNIT[\"degree\",0.01745329251994328,AUTHORITY[\"EPSG\",\"9122\"]],AXIS[\"Lat\",NORTH],AXIS[\"Long\",EAST],AUTHORITY[\"EPSG\",\"4326\"]]'
            ");
        } catch (Throwable) {
            // Em MariaDB ou se o usuário do banco não possuir privilégio SUPER, ignora silenciosamente
        }

        // 2. Converte as geometrias existentes que não possuem SRID 4326 (como as importadas com SRID 1)
        // para SRID 4326 nativo do WGS 84
        try {
            DB::statement('
                UPDATE malha_viaria 
                SET geometria = ST_GeomFromWKB(SUBSTRING(geometria, 5), 4326)
                WHERE geometria IS NOT NULL 
                  AND LEFT(geometria, 4) != 0xe6100000
            ');
        } catch (Throwable) {
            // Caso ocorra qualquer inconsistência, ignora
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
