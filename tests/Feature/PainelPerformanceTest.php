<?php

namespace Tests\Feature;

use App\Livewire\Painel;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase A5: trava o comportamento das otimizações de performance —
 * escopo das queries por janela de data, paginação/limite de "Todas as
 * missões" e "Concluídas", e a memoização de people()/completers()/weekData()
 * dentro de um MESMO render() (o teste conta as queries de verdade, não
 * confia em "deveria memoizar").
 */
class PainelPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_tabela_de_todas_as_missoes_respeita_o_limite_e_carrega_mais(): void
    {
        Mission::create($this->missaoValida(['title' => 'Missão 1', 'date' => '2026-07-10']));
        Mission::create($this->missaoValida(['title' => 'Missão 2', 'date' => '2026-07-11']));
        Mission::create($this->missaoValida(['title' => 'Missão 3', 'date' => '2026-07-12']));

        $component = Livewire::test(Painel::class)->set('tableLimit', 2);

        $this->assertCount(2, $component->viewData('tableRows'));
        $this->assertTrue($component->viewData('tableHasMore'));

        $component->call('loadMoreTable');

        $this->assertCount(3, $component->viewData('tableRows'));
        $this->assertFalse($component->viewData('tableHasMore'));
    }

    public function test_trocar_filtro_reinicia_o_limite_da_tabela(): void
    {
        $component = Livewire::test(Painel::class)->set('tableLimit', 200);

        $component->call('setFilter', 'pendente');

        $this->assertSame(50, $component->get('tableLimit'));
    }

    public function test_historico_respeita_o_limite_e_carrega_mais(): void
    {
        Mission::create($this->missaoValida([
            'title' => 'Concluída 1', 'date' => '2026-06-01', 'status' => 'concluida', 'completed_at' => now(),
        ]));
        Mission::create($this->missaoValida([
            'title' => 'Concluída 2', 'date' => '2026-06-02', 'status' => 'concluida', 'completed_at' => now(),
        ]));
        Mission::create($this->missaoValida([
            'title' => 'Concluída 3', 'date' => '2026-06-03', 'status' => 'concluida', 'completed_at' => now(),
        ]));

        $component = Livewire::test(Painel::class)->set('historyLimit', 2);

        $this->assertCount(2, $component->viewData('historyRows'));
        $this->assertTrue($component->viewData('historyHasMore'));

        $component->call('loadMoreHistory');

        $this->assertCount(3, $component->viewData('historyRows'));
        $this->assertFalse($component->viewData('historyHasMore'));
    }

    public function test_calendario_so_traz_missoes_da_semana_exibida(): void
    {
        Mission::create($this->missaoValida(['title' => 'Da semana atual', 'date' => now()->toDateString(), 'time' => '09:00']));
        Mission::create($this->missaoValida(['title' => 'De outra época', 'date' => now()->subMonths(3)->toDateString(), 'time' => '09:00']));

        $grid = Livewire::test(Painel::class)->viewData('calendar');

        $titulos = collect();
        foreach ($grid['cells'] as $dayRows) {
            foreach ($dayRows as $items) {
                $titulos = $titulos->merge(collect($items)->pluck('title'));
            }
        }

        $this->assertTrue($titulos->contains('Da semana atual'));
        $this->assertFalse($titulos->contains('De outra época'));
    }

    public function test_render_consulta_militares_apenas_uma_vez_por_render(): void
    {
        Mission::create($this->missaoValida());

        DB::enableQueryLog();
        Livewire::test(Painel::class);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $militaresQueries = $queries->filter(fn ($sql) => str_contains(strtolower($sql), 'militares'))->count();

        // people()/completers() consultam Militar::ativos() — sem memoização,
        // isto daria 2 (completers() chama people() de novo internamente).
        $this->assertSame(1, $militaresQueries);
    }
}
