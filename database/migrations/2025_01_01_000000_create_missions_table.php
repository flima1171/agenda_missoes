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
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('date', 10);            // AAAA-MM-DD
            $table->string('time', 5);             // HH:MM
            $table->string('responsible', 80);
            $table->string('priority', 10)->default('media');   // baixa | media | alta
            $table->string('status', 12)->default('pendente');  // pendente | andamento | concluida
            $table->string('requester', 80)->nullable();
            $table->text('notes')->nullable();
            $table->string('completed_by', 80)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverte a migração.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
