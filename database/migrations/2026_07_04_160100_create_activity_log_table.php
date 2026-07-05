<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            // Nullable: se o autor for removido no futuro, a trilha não some junto
            // (nullOnDelete). Ações do sistema (ex.: seeds) também ficam sem autor.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');            // ex.: criar_missao, excluir_missao, concluir_missao
            $table->string('subject')->nullable(); // ex.: mission, militar, usuario
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
