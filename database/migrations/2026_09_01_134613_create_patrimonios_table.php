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
        Schema::create('patrimonios', function (Blueprint $table) {
            $table->id('private_id');
            $table->uuid('public_id')->unique();
            $table->string('mac_address', 17)->unique();
            $table->string('numero_patrimonio', 50)->nullable();
            $table->enum('tipo_sugerido', ['Indefinido', 'Estação Matriz', 'Estação Satélite'])->default('Indefinido');
            $table->enum('status', ['Disponível', 'Alocado', 'Instalado', 'Manutenção', 'Descartado'])->default('Disponível');
            $table->date('data_aquisicao')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrimonios');
    }
};
