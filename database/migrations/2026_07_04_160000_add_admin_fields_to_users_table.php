<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Papel mínimo: admin (gere militares/usuários) vs. usuário comum (gere missões).
            $table->boolean('is_admin')->default(false)->after('password');
            // Nome de guerra opcional para exibir "quem concluiu / quem fez" na trilha.
            $table->string('nome_guerra')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'nome_guerra']);
        });
    }
};
