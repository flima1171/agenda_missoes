<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migração.
     */
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            // Guarda a situação anterior à conclusão, para o "Reabrir" restaurá-la
            // em vez de sempre voltar para "pendente".
            $table->string('previous_status', 12)->nullable()->after('completed_at');
        });
    }

    /**
     * Reverte a migração.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('previous_status');
        });
    }
};
