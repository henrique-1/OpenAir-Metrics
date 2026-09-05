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
            $table->string('mac_address', 17)->nullable()->change();
            $table->foreignId('patrimonio_id')->nullable()->after('mac_address')->constrained('patrimonios', 'private_id')->nullOnDelete();
            $table->enum('status_instalacao', ['Planejada', 'Em Instalação', 'Instalada', 'Inativa'])->default('Instalada')->after('tipo_estacao');
            $table->unsignedInteger('ordem_instalacao')->nullable()->after('status_instalacao');
            $table->foreignId('matriz_pai_id')->nullable()->after('ordem_instalacao')->constrained('estacoes', 'private_id')->nullOnDelete();
            $table->foreignId('estacao_origem_id')->nullable()->after('matriz_pai_id')->constrained('estacoes', 'private_id')->nullOnDelete();
            $table->decimal('distancia_origem_metros', 8, 2)->nullable()->after('estacao_origem_id');
            $table->dateTime('data_instalacao')->nullable()->after('distancia_origem_metros');
            $table->foreignId('instalado_por')->nullable()->after('data_instalacao')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estacoes', function (Blueprint $table) {
            $table->dropForeign(['patrimonio_id']);
            $table->dropForeign(['matriz_pai_id']);
            $table->dropForeign(['estacao_origem_id']);
            $table->dropForeign(['instalado_por']);
            $table->dropColumn([
                'patrimonio_id',
                'status_instalacao',
                'ordem_instalacao',
                'matriz_pai_id',
                'estacao_origem_id',
                'distancia_origem_metros',
                'data_instalacao',
                'instalado_por',
            ]);
        });
    }
};
