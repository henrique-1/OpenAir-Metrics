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
        Schema::table('medicoes', function (Blueprint $table) {
            $table->integer('iqa')->nullable()->after('poeira');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicoes', function (Blueprint $table) {
            $table->dropColumn('iqa');
        });
    }
};
