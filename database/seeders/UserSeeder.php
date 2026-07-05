<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fase A2: garante um administrador inicial para o primeiro acesso. A senha
 * padrão DEVE ser trocada no deploy (ver DEPLOY.md / comando app:create-user).
 * Idempotente: usa updateOrCreate para não duplicar em re-seeds.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@25bc.local'],
            [
                'name' => 'Administrador',
                'nome_guerra' => 'Admin',
                'password' => Hash::make('admin1234'),
                'is_admin' => true,
            ]
        );
    }
}
