<?php

namespace Tests\Feature;

use App\Livewire\UsuariosManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestão de usuários (só admin) — criar, redefinir senha, alternar papel.
 */
class UsuariosManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_cria_usuario_comum(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(UsuariosManager::class)
            ->set('name', 'João da Silva')
            ->set('posto_graduacao', 'Sd EP')
            ->set('nome_guerra', 'Silva')
            ->set('username', 'joao')
            ->set('password', 'senha1234')
            ->set('is_admin', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'joao', 'is_admin' => false]);
        $this->assertDatabaseHas('activity_log', ['action' => 'criar_usuario']);
    }

    public function test_senha_e_gravada_com_hash(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(UsuariosManager::class)
            ->set('name', 'João')
            ->set('posto_graduacao', 'Sd EP')
            ->set('nome_guerra', 'Silva')
            ->set('username', 'joao')
            ->set('password', 'senha1234')
            ->call('save')
            ->assertHasNoErrors();

        $novo = User::where('username', 'joao')->first();
        $this->assertTrue(Hash::check('senha1234', $novo->password));
    }

    public function test_nao_pode_remover_o_proprio_papel_de_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(UsuariosManager::class)
            ->call('toggleAdmin', $admin->id)
            ->assertHasErrors('toggle');

        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_admin_promove_e_rebaixa_outro_usuario(): void
    {
        $this->actingAs($this->admin());
        $outro = User::factory()->create(['is_admin' => false]);

        Livewire::test(UsuariosManager::class)->call('toggleAdmin', $outro->id);
        $this->assertTrue($outro->refresh()->is_admin);

        Livewire::test(UsuariosManager::class)->call('toggleAdmin', $outro->id);
        $this->assertFalse($outro->refresh()->is_admin);
    }

    public function test_usuario_comum_nao_pode_montar_o_componente(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        // Defesa em profundidade: o mount() aborta com 403 mesmo se a rota for burlada.
        Livewire::test(UsuariosManager::class)->assertForbidden();
    }
}
