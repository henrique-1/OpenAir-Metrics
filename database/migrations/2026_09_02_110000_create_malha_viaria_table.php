<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('malha_viaria');

        Schema::create('malha_viaria', function (Blueprint $table) {
            $table->id();
            $table->string('logradouro', 256)->nullable()->index();
            $table->string('tipo_via', 50)->index();
            $table->geometry('geometria', subtype: 'linestring', srid: 4326);

            // Adiciona SPATIAL INDEX para MariaDB / MySQL
            if (DB::getDriverName() !== 'sqlite') {
                $table->spatialIndex('geometria');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('malha_viaria');
    }
};
