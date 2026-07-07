<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Login por usuário/senha (sem e-mail): identificador simples para uso offline.
            $table->string('username')->unique()->after('nome_guerra');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->after('nome_guerra');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }
};
