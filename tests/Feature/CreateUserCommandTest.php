<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Comando offline para criar/redefinir usuário (sem "esqueci a senha").
 */
class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_admin_pela_linha_de_comando(): void
    {
        $this->artisan('app:create-user', [
            '--name' => 'Admin da VM',
            '--username' => 'admin',
            '--password' => 'senha1234',
            '--admin' => true,
        ])->assertSuccessful();

        $user = User::where('username', 'admin')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('senha1234', $user->password));
    }

    public function test_redefine_senha_de_usuario_existente(): void
    {
        $user = User::factory()->create([
            'username' => 'sgt',
            'password' => Hash::make('antiga123'),
        ]);

        $this->artisan('app:create-user', [
            '--username' => 'sgt',
            '--password' => 'novasenha123',
        ])->assertSuccessful();

        $this->assertTrue(Hash::check('novasenha123', $user->refresh()->password));
        // Não duplica o usuário.
        $this->assertSame(1, User::where('username', 'sgt')->count());
    }

    public function test_recusa_senha_curta(): void
    {
        $this->artisan('app:create-user', [
            '--name' => 'Fulano',
            '--username' => 'fulano',
            '--password' => 'curta',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['username' => 'fulano']);
    }
}
