<?php

namespace Tests\Feature;

use App\Livewire\MilitaresManager;
use App\Livewire\Painel;
use App\Livewire\ResponsibleSelector;
use App\Models\Militar;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cobre UX/acessibilidade: tema escuro em /militares e /usuarios, nomes
 * acessíveis em botões só-ícone, ícones decorativos marcados com
 * aria-hidden, e o modal com rótulo/foco.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_militares_tem_wrapper_de_tema_escuro(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/militares')
            ->assertOk()
            ->assertSee('id="militares-theme-root" class="painel-root"', false)
            ->assertSee('aria-label="Alternar modo escuro"', false);
    }

    public function test_pagina_de_usuarios_tem_wrapper_de_tema_escuro(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/usuarios')
            ->assertOk()
            ->assertSee('id="usuarios-theme-root" class="painel-root"', false)
            ->assertSee('aria-label="Alternar modo escuro"', false);
    }

    public function test_icone_e_decorativo_por_padrao_e_pode_deixar_de_ser(): void
    {
        $decorativo = $this->blade('<x-icon name="plus" />');
        $this->assertStringContainsString('aria-hidden="true"', $decorativo);

        $explicito = $this->blade('<x-icon name="plus" :decorative="false" />');
        $this->assertStringNotContainsString('aria-hidden="true"', $explicito);
    }

    public function test_botoes_so_icone_da_sidebar_tem_nome_acessivel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(Painel::class)
            ->assertSeeHtml('aria-label="Visão geral"')
            ->assertSeeHtml('aria-label="Calendário"')
            ->assertSeeHtml('aria-label="Todas as missões"')
            ->assertSeeHtml('aria-label="Concluídas"')
            ->assertSeeHtml('aria-label="Nova missão"')
            ->assertSeeHtml('aria-label="Alternar modo escuro"')
            ->assertSeeHtml('aria-label="Fechar"');
    }

    public function test_abrir_modal_dispara_evento_para_o_foco_inicial(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Painel::class)
            ->call('openNew')
            ->assertDispatched('modal-opened');

        $mission = Mission::create([
            'title' => 'Missão de teste',
            'date' => '2026-07-10',
            'time' => '08:00',
            'responsibles' => ['Cb Luide'],
            'priority' => 'media',
            'status' => 'pendente',
        ]);

        Livewire::test(Painel::class)
            ->call('openEdit', $mission->id)
            ->assertDispatched('modal-opened');
    }

    public function test_modal_tem_titulo_associado_via_aria_labelledby(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Painel::class)
            ->assertSeeHtml('aria-labelledby="modal-title"')
            ->assertSeeHtml('id="modal-title"');
    }

    public function test_botao_remover_responsavel_tem_nome_acessivel(): void
    {
        Livewire::test(ResponsibleSelector::class, ['people' => ['Cb Luide', 'Asp Araújo']])
            ->set('rows', ['Cb Luide', 'Asp Araújo'])
            ->assertSeeHtml('aria-label="Remover responsável"');
    }

    public function test_botoes_de_reordenar_militar_tem_nome_acessivel(): void
    {
        $militar = Militar::create([
            'posto_graduacao' => '3º Sgt', 'nome_guerra' => 'Teste', 'ativo' => true, 'ordem' => 1,
        ]);
        Militar::create(['posto_graduacao' => 'Cb', 'nome_guerra' => 'Outro', 'ativo' => true, 'ordem' => 2]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(MilitaresManager::class)
            ->assertSeeHtml('aria-label="Mover 3º Sgt Teste para cima"')
            ->assertSeeHtml('aria-label="Mover 3º Sgt Teste para baixo"');
    }
}
