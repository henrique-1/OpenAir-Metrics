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
        Schema::table('patrimonios', function (Blueprint $table) {
            $table->dropColumn('tipo_sugerido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimonios', function (Blueprint $table) {
            $table->enum('tipo_sugerido', ['Indefinido', 'Estação Matriz', 'Estação Satélite'])->default('Indefinido');
        });
    }
};
