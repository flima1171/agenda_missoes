<?php

namespace Tests\Feature;

use App\Livewire\ResponsibleSelector;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Baseline (Fase A0): comportamento ATUAL do seletor progressivo de responsáveis.
 */
class ResponsibleSelectorTest extends TestCase
{
    public function test_options_for_exclui_quem_ja_foi_escolhido_em_outra_linha(): void
    {
        $component = Livewire::test(ResponsibleSelector::class, [
            'people' => ['Asp Araújo', 'Cb Luide', 'Toda a seção'],
        ])->set('rows', ['Asp Araújo', '']);

        // Linha 1 (vazia): "Asp Araújo" já foi usado na linha 0 → some das opções.
        $this->assertSame(
            ['Cb Luide', 'Toda a seção'],
            $component->instance()->optionsFor(1)
        );

        // Linha 0: mantém o próprio valor atual + os ainda livres.
        $this->assertSame(
            ['Asp Araújo', 'Cb Luide', 'Toda a seção'],
            $component->instance()->optionsFor(0)
        );
    }

    public function test_can_add_row_so_quando_todas_as_linhas_estao_preenchidas_e_sobra_gente(): void
    {
        $people = ['Asp Araújo', 'Cb Luide', 'Toda a seção'];

        // Linha atual vazia → não pode adicionar.
        $vazio = Livewire::test(ResponsibleSelector::class, ['people' => $people])
            ->set('rows', ['']);
        $this->assertFalse($vazio->instance()->canAddRow());

        // Uma linha preenchida, ainda sobra gente → pode adicionar.
        $preenchido = Livewire::test(ResponsibleSelector::class, ['people' => $people])
            ->set('rows', ['Asp Araújo']);
        $this->assertTrue($preenchido->instance()->canAddRow());

        // Todas as pessoas já escolhidas → não sobra ninguém.
        $cheio = Livewire::test(ResponsibleSelector::class, ['people' => $people])
            ->set('rows', ['Asp Araújo', 'Cb Luide', 'Toda a seção']);
        $this->assertFalse($cheio->instance()->canAddRow());
    }

    public function test_remove_row_mantem_pelo_menos_uma_linha(): void
    {
        $component = Livewire::test(ResponsibleSelector::class, [
            'people' => ['Asp Araújo', 'Cb Luide'],
        ])->set('rows', ['Asp Araújo', 'Cb Luide']);

        $component->call('removeRow', 1);
        $this->assertSame(['Asp Araújo'], $component->get('rows'));

        // Remover a última ainda deixa uma linha vazia, nunca zero linhas.
        $component->call('removeRow', 0);
        $this->assertSame([''], $component->get('rows'));
    }

    public function test_options_for_mantem_responsavel_inativado_como_opcao(): void
    {
        // Achado 1.3 (A3): "Sd EP Inativo" foi atribuído à missão e depois
        // inativado — não está mais em people (só ativos). Precisa continuar
        // sendo opção da própria linha, senão some do <select> e é perdido.
        $component = Livewire::test(ResponsibleSelector::class, [
            'people' => ['Cb Luide', 'Toda a seção'],
        ])->set('rows', ['Sd EP Inativo', '']);

        $this->assertContains('Sd EP Inativo', $component->instance()->optionsFor(0));
        $this->assertTrue($component->instance()->isInactive('Sd EP Inativo'));
        $this->assertFalse($component->instance()->isInactive('Cb Luide'));

        // Numa OUTRA linha (vazia), o inativado não aparece — só na dele.
        $this->assertNotContains('Sd EP Inativo', $component->instance()->optionsFor(1));
    }
}
