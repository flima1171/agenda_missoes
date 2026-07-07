<?php

namespace Tests\Feature;

use App\Livewire\MilitaresManager;
use App\Models\Militar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cobre o CRUD de militares.
 */
class MilitaresManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A2: gestão de militares é restrita a administradores.
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_cria_militar_com_ativo_verdadeiro_e_ordem_no_fim(): void
    {
        Militar::create(['posto_graduacao' => 'Cb', 'nome_guerra' => 'Luide', 'ativo' => true, 'ordem' => 1]);

        Livewire::test(MilitaresManager::class)
            ->set('posto_graduacao', 'Asp')
            ->set('nome_guerra', 'Araújo')
            ->call('save')
            ->assertHasNoErrors();

        $novo = Militar::where('nome_guerra', 'Araújo')->first();
        $this->assertNotNull($novo);
        $this->assertTrue($novo->ativo);
        // Vai para o fim da ordem (maior ordem existente + 1).
        $this->assertSame(2, $novo->ordem);
    }

    public function test_toggle_ativo_alterna_a_situacao_sem_apagar(): void
    {
        $militar = Militar::create(['posto_graduacao' => 'Cb', 'nome_guerra' => 'Luide', 'ativo' => true, 'ordem' => 1]);

        Livewire::test(MilitaresManager::class)->call('toggleAtivo', $militar->id);
        $this->assertFalse($militar->refresh()->ativo);

        Livewire::test(MilitaresManager::class)->call('toggleAtivo', $militar->id);
        $this->assertTrue($militar->refresh()->ativo);

        // Nunca some do banco.
        $this->assertDatabaseHas('militares', ['id' => $militar->id]);
    }

    public function test_move_up_e_move_down_trocam_a_ordem_com_o_vizinho(): void
    {
        $primeiro = Militar::create(['posto_graduacao' => 'Asp', 'nome_guerra' => 'Araújo', 'ativo' => true, 'ordem' => 1]);
        $segundo = Militar::create(['posto_graduacao' => 'Cb', 'nome_guerra' => 'Luide', 'ativo' => true, 'ordem' => 2]);

        Livewire::test(MilitaresManager::class)->call('moveUp', $segundo->id);
        $this->assertSame(1, $segundo->refresh()->ordem);
        $this->assertSame(2, $primeiro->refresh()->ordem);

        Livewire::test(MilitaresManager::class)->call('moveDown', $segundo->id);
        $this->assertSame(2, $segundo->refresh()->ordem);
        $this->assertSame(1, $primeiro->refresh()->ordem);
    }

    public function test_edita_militar_existente_atualiza_os_campos(): void
    {
        $militar = Militar::create(['posto_graduacao' => 'Sd EP', 'nome_guerra' => 'Jones', 'ativo' => true, 'ordem' => 1]);

        Livewire::test(MilitaresManager::class)
            ->call('edit', $militar->id)
            ->set('posto_graduacao', 'Cb')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Cb', $militar->refresh()->posto_graduacao);
    }

    public function test_mensagem_de_validacao_sai_em_pt_br_com_nome_amigavel(): void
    {
        // A3: sem lang/pt_BR + validationAttributes, sairia "The nome guerra
        // field is required." — agora deve usar o nome amigável em pt-BR.
        $component = Livewire::test(MilitaresManager::class)
            ->set('posto_graduacao', 'Cb')
            ->set('nome_guerra', '')
            ->call('save')
            ->assertHasErrors('nome_guerra');

        $mensagem = $component->instance()->getErrorBag()->first('nome_guerra');
        $this->assertStringContainsString('nome de guerra', $mensagem);
        $this->assertStringContainsString('obrigatório', $mensagem);
        $this->assertStringNotContainsString('field is required', $mensagem);
    }
}
