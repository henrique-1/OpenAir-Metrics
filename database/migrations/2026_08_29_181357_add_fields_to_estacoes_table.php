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
        Schema::table('estacoes', function (Blueprint $table) {
            $table->string('mac_address')->unique()->after('public_id');
            $table->enum('tipo_estacao', ['Estação Matriz', 'Estação Satélite'])->after('mac_address');
            $table->foreignId('bairro_id')->constrained('bairros')->restrictOnDelete()->after('tipo_estacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->dropForeign(['bairro_id']);
            $table->dropColumn(['mac_address', 'tipo_estacao', 'bairro_id']);
        });
    }
};
