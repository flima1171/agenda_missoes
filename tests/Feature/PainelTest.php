<?php

namespace Tests\Feature;

use App\Livewire\Painel;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Baseline (Fase A0): fotografa o comportamento ATUAL do componente principal
 * (App\Livewire\Painel) antes de mudar qualquer coisa. Não é sobre o que "deveria"
 * ser — é sobre o que HOJE acontece, para as fases seguintes não regredirem sem aviso.
 */
class PainelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A2: o painel exige usuário logado (a sidebar lê auth()->user()).
        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function missaoValida(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Inspeção das instalações',
            'date' => '2026-07-10',
            'time' => '08:00',
            'responsibles' => ['Cb Luide'],
            'priority' => 'media',
            'status' => 'pendente',
        ], $overrides);
    }

    public function test_cria_missao_valida_pelo_componente(): void
    {
        Livewire::test(Painel::class)
            ->set('form.title', 'Inspeção das instalações')
            ->set('form.date', '2026-07-10')
            ->set('form.time', '08:00')
            ->set('form.priority', 'media')
            ->set('form.status', 'pendente')
            ->set('responsibles', ['Cb Luide'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('missions', ['title' => 'Inspeção das instalações']);
        $this->assertSame(['Cb Luide'], Mission::first()->responsibles);
    }

    public function test_sem_responsavel_gera_erro_e_nao_salva(): void
    {
        Livewire::test(Painel::class)
            ->set('form.title', 'Missão sem responsável')
            ->set('form.date', '2026-07-10')
            ->set('form.time', '08:00')
            ->set('form.priority', 'media')
            ->set('form.status', 'pendente')
            ->set('responsibles', [])
            ->call('save')
            ->assertHasErrors('form.responsibles');

        $this->assertDatabaseCount('missions', 0);
    }

    public function test_change_status_para_concluida_registra_conclusao(): void
    {
        $mission = Mission::create($this->missaoValida(['status' => 'andamento']));

        Livewire::test(Painel::class)
            ->call('changeStatus', $mission->id, 'concluida');

        $mission->refresh();
        $this->assertSame('concluida', $mission->status);
        $this->assertNotNull($mission->completed_at);
        // Situação anterior guardada para o "Reabrir".
        $this->assertSame('andamento', $mission->previous_status);
    }

    public function test_reopen_restaura_situacao_anterior_quando_existe(): void
    {
        $mission = Mission::create($this->missaoValida([
            'status' => 'concluida',
            'previous_status' => 'andamento',
            'completed_by' => 'Cb Luide',
            'completed_at' => now(),
        ]));

        Livewire::test(Painel::class)
            ->call('reopen', $mission->id);

        $mission->refresh();
        $this->assertSame('andamento', $mission->status);
        $this->assertNull($mission->completed_at);
        $this->assertNull($mission->completed_by);
    }

    public function test_reopen_cai_em_pendente_quando_nunca_houve_situacao_anterior(): void
    {
        // Missão criada já como concluída: nunca teve previous_status.
        $mission = Mission::create($this->missaoValida([
            'status' => 'concluida',
            'completed_at' => now(),
        ]));

        Livewire::test(Painel::class)
            ->call('reopen', $mission->id);

        $mission->refresh();
        $this->assertSame('pendente', $mission->status);
    }

    public function test_delete_mission_remove_a_missao_em_edicao(): void
    {
        $mission = Mission::create($this->missaoValida());

        Livewire::test(Painel::class)
            ->set('editingId', $mission->id)
            ->call('deleteMission');

        $this->assertDatabaseMissing('missions', ['id' => $mission->id]);
    }

    public function test_edita_missao_existente_atualiza_os_campos(): void
    {
        $mission = Mission::create($this->missaoValida());

        Livewire::test(Painel::class)
            ->call('openEdit', $mission->id)
            ->set('form.title', 'Título revisado')
            ->set('responsibles', ['Asp Araújo'])
            ->call('save')
            ->assertHasNoErrors();

        $mission->refresh();
        $this->assertSame('Título revisado', $mission->title);
        $this->assertSame(['Asp Araújo'], $mission->responsibles);
    }
}
