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
        Schema::create('militares', function (Blueprint $table) {
            $table->id();
            $table->string('posto_graduacao', 20);
            $table->string('nome_guerra', 60);
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            // Campos opcionais pensando em integrações futuras (ex.: aviso via bot).
            $table->string('telegram_id', 40)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->timestamps();

            $table->index('ativo');
            $table->index('ordem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('militares');
    }
};
