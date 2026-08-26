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
        Schema::create('estacoes', function (Blueprint $table) {
            $table->id('private_id');
            $table->uuid('public_id')->unique();

            // Substitui latitude e longitude separadas por um tipo espacial POINT
            $table->geometry('coordenadas', subtype: 'point');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estacoes');
    }
};
