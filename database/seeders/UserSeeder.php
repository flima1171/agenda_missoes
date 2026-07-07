<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Garante um administrador inicial para o primeiro acesso. A senha padrão
 * DEVE ser trocada no deploy (ver DEPLOY.md / comando app:create-user).
 * Idempotente: usa updateOrCreate para não duplicar em re-seeds.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador',
                'nome_guerra' => 'Admin',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );
    }
}
