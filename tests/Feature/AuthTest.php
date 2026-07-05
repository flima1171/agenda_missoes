<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase A2: cobre o login à mão, o middleware de autenticação, o gating de
 * administrador e a remoção da API REST antiga.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_valido_autentica_e_redireciona_para_o_painel(): void
    {
        $user = User::factory()->create([
            'email' => 'sgt@25bc.local',
            'password' => Hash::make('segredo123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'sgt@25bc.local')
            ->set('password', 'segredo123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('painel'));

        $this->assertAuthenticatedAs($user);
        // Trilha de auditoria: registra a entrada.
        $this->assertDatabaseHas('activity_log', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_login_com_senha_errada_gera_erro_e_nao_autentica(): void
    {
        User::factory()->create([
            'email' => 'sgt@25bc.local',
            'password' => Hash::make('segredo123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'sgt@25bc.local')
            ->set('password', 'errada')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_encerra_a_sessao(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_usuario_comum_nao_acessa_gestao_de_militares(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $this->get('/militares')->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
    }

    public function test_admin_acessa_gestao_de_militares_e_usuarios(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/militares')->assertOk();
        $this->get('/usuarios')->assertOk();
    }

    public function test_api_de_missoes_antiga_foi_removida(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // As rotas /missions não existem mais → 404 (não é mais uma fonte de vazamento).
        $this->actingAs($admin)->get('/missions')->assertNotFound();
        $this->actingAs($admin)->postJson('/missions')->assertNotFound();
    }
}
