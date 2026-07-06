<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase A5 (achado 4.3, opcional): índice composto para as consultas que
     * filtram/ordenam por data E hora juntas (calendário, ordenação do painel).
     */
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->index(['date', 'time']);
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex(['date', 'time']);
        });
    }
};
