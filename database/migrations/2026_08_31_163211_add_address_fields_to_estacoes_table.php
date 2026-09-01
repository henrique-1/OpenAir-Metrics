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
            $table->string('logradouro')->nullable()->after('bairro_id');
            $table->string('numero')->nullable()->after('logradouro');
            $table->string('bairro_nome')->nullable()->after('numero');
            $table->string('cidade_nome')->nullable()->after('bairro_nome');
            $table->string('estado_uf', 10)->nullable()->after('cidade_nome');
            $table->string('cep', 20)->nullable()->after('estado_uf');
            $table->text('endereco_completo')->nullable()->after('cep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->dropColumn([
                'logradouro',
                'numero',
                'bairro_nome',
                'cidade_nome',
                'estado_uf',
                'cep',
                'endereco_completo',
            ]);
        });
    }
};
