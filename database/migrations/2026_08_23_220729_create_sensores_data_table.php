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
        Schema::create('sensores_data', function (Blueprint $table) {
            $table->id('private_id');
            $table->uuid('public_id')->unique();

            $table->foreignId('sensor_id')
                ->constrained('sensores', 'private_id')
                ->cascadeOnDelete();

            $table->decimal('temperatura', 4, 1);
            $table->decimal('umidade', 4, 1);
            $table->integer('co2');
            $table->decimal('poeira', 6, 2);
            $table->dateTime('data_hora');

            $table->timestamps();

            // Índice composto para otimizar as consultas de longo prazo
            $table->index(['sensor_id', 'data_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensores_data');
    }
};
