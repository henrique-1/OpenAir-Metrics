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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('nivel', ['superadmin', 'administrador', 'cadastrador', 'planejador', 'instalador'])->default('cadastrador')->after('password');
            $table->boolean('ativo')->default(true)->after('nivel');
            $table->foreignId('cidade_id')->nullable()->after('ativo')->constrained('cidades')->nullOnDelete();
            $table->string('logradouro')->nullable()->after('cidade_id');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            $table->string('bairro')->nullable()->after('complemento');
            $table->string('estado', 2)->nullable()->after('bairro');
            $table->string('cep', 9)->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cidade_id']);
            $table->dropColumn([
                'nivel',
                'ativo',
                'cidade_id',
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'estado',
                'cep',
            ]);
        });
    }
};
