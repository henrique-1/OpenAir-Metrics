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
            $table->foreignId('cidade_id')->nullable()->after('numero_patrimonio')->constrained('cidades')->nullOnDelete();
        });

        Schema::table('estacoes', function (Blueprint $table) {
            $table->boolean('solicitacao_substituicao')->default(false)->after('status_instalacao');
            $table->dateTime('solicitacao_substituicao_em')->nullable()->after('solicitacao_substituicao');
            $table->text('motivo_substituicao')->nullable()->after('solicitacao_substituicao_em');
            $table->foreignId('solicitado_por')->nullable()->after('motivo_substituicao')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->dropForeign(['solicitado_por']);
            $table->dropColumn([
                'solicitacao_substituicao',
                'solicitacao_substituicao_em',
                'motivo_substituicao',
                'solicitado_por',
            ]);
        });

        Schema::table('patrimonios', function (Blueprint $table) {
            $table->dropForeign(['cidade_id']);
            $table->dropColumn('cidade_id');
        });
    }
};
