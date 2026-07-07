<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Militar;
use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Toda a interatividade do painel (visão geral, calendário, tabela de
 * missões, concluídas, modal, modo monitor) vive no estado/métodos deste
 * componente. O `render()` pré-calcula tudo em "view models" (arrays
 * simples) para a view ficar só exibindo dados, sem lógica.
 */
class Painel extends Component
{
    public string $view = 'dashboard';

    public string $filter = 'todas';

    public string $calMonday = '';

    public bool $monitorMode = false;

    public bool $calendarMonitorMode = false;

    public int $tvScreen = 0;

    public bool $darkMode = false;

    public bool $showModal = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [
        'title' => '',
        'date' => '',
        'time' => '',
        'priority' => 'media',
        'status' => 'pendente',
        'requester' => '',
        'notes' => '',
        'completed_by' => '',
    ];

    /** @var array<int, string> */
    public array $responsibles = [];

    private const MESES = [
        '01' => 'jan', '02' => 'fev', '03' => 'mar', '04' => 'abr',
        '05' => 'mai', '06' => 'jun', '07' => 'jul', '08' => 'ago',
        '09' => 'set', '10' => 'out', '11' => 'nov', '12' => 'dez',
    ];

    private const STATUS_LABEL = [
        'pendente' => 'Pendente', 'andamento' => 'Em andamento',
        'concluida' => 'Concluída', 'atrasada' => 'Atrasada',
    ];

    private const PRIORITY_LABEL = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];

    private const CAL_START = 7;

    private const CAL_END = 18;

    /** Tamanho da página de "Todas as missões" e "Concluídas". */
    private const LIST_PAGE_SIZE = 50;

    public int $tableLimit = self::LIST_PAGE_SIZE;

    public int $historyLimit = self::LIST_PAGE_SIZE;

    /**
     * Cache por render (a instância do Livewire é recriada a cada request,
     * então isto só evita recalcular a MESMA consulta/valor mais de uma vez
     * dentro do MESMO render() — não persiste entre requisições).
     */
    private ?array $peopleCache = null;

    private ?array $completersCache = null;

    private ?array $weekDataCache = null;

    public function mount(): void
    {
        $this->calMonday = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return collect(Mission::rules())
            ->except(['responsibles', 'responsibles.*'])
            ->mapWithKeys(fn ($rule, $field) => ['form.'.$field => $rule])
            ->all();
    }

    /**
     * Nomes amigáveis dos campos para as mensagens de validação em pt-BR (A3):
     * sem isto o erro sairia com a chave crua ("O campo form.title é obrigatório.").
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'form.title' => 'título',
            'form.date' => 'data',
            'form.time' => 'horário',
            'form.priority' => 'prioridade',
            'form.status' => 'situação',
            'form.requester' => 'demandante',
            'form.notes' => 'observações',
            'form.completed_by' => 'concluído por',
            'form.responsibles' => 'responsáveis',
        ];
    }

    // ---------- navegação ----------

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function refresh(): void
    {
        // sem corpo: só existe pra dar um alvo ao wire:poll e forçar o
        // recálculo das view models no próximo render() (dados podem ter
        // mudado por outro computador na rede).
    }

    // ---------- calendário ----------

    public function prevWeek(): void
    {
        $this->calMonday = Carbon::parse($this->calMonday)->subDays(7)->toDateString();
    }

    public function nextWeek(): void
    {
        $this->calMonday = Carbon::parse($this->calMonday)->addDays(7)->toDateString();
    }

    public function todayWeek(): void
    {
        $this->calMonday = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->tableLimit = self::LIST_PAGE_SIZE;
    }

    public function loadMoreTable(): void
    {
        $this->tableLimit += self::LIST_PAGE_SIZE;
    }

    public function loadMoreHistory(): void
    {
        $this->historyLimit += self::LIST_PAGE_SIZE;
    }

    // ---------- modo monitor / tema ----------

    public function enterMonitor(): void
    {
        $this->monitorMode = true;
        $this->tvScreen = 0;
        $this->dispatch('enter-fullscreen');
    }

    public function exitMonitor(): void
    {
        $this->monitorMode = false;
        $this->dispatch('exit-fullscreen');
    }

    public function enterCalendarMonitor(): void
    {
        $this->calendarMonitorMode = true;
        $this->dispatch('enter-fullscreen');
    }

    public function exitCalendarMonitor(): void
    {
        $this->calendarMonitorMode = false;
        $this->dispatch('exit-fullscreen');
    }

    public function rotateTv(): void
    {
        $this->tvScreen = ($this->tvScreen + 1) % 2;
    }

    public function handleEscape(): void
    {
        if ($this->showModal) {
            $this->closeModal();

            return;
        }
        if ($this->monitorMode) {
            $this->exitMonitor();

            return;
        }
        if ($this->calendarMonitorMode) {
            $this->exitCalendarMonitor();
        }
    }

    #[On('fullscreen-exited-natively')]
    public function onFullscreenExitedNatively(): void
    {
        // O navegador já saiu da tela cheia sozinho (ex.: usuário apertou Esc
        // fora do nosso controle); só sincroniza o estado do componente.
        $this->monitorMode = false;
        $this->calendarMonitorMode = false;
    }

    #[On('set-initial-theme')]
    public function setInitialTheme(bool $dark): void
    {
        $this->darkMode = $dark;
    }

    public function toggleTheme(): void
    {
        $this->darkMode = ! $this->darkMode;
        $this->dispatch('theme-changed', dark: $this->darkMode);
    }

    // ---------- modal / CRUD ----------

    #[On('responsibles-changed')]
    public function syncResponsibles(array $responsibles): void
    {
        $this->responsibles = $responsibles;
    }

    public function openNew(?string $date = null, ?string $time = null): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->responsibles = [];
        $this->form = [
            'title' => '',
            'date' => $date ?: now()->toDateString(),
            'time' => $time ?: '08:00',
            'priority' => 'media',
            'status' => 'pendente',
            'requester' => '',
            'notes' => '',
            'completed_by' => '',
        ];
        $this->showModal = true;
        $this->dispatch('set-responsibles', list: []);
        // O shell (painel.blade.php) escuta este evento para focar o
        // primeiro campo do modal (acessibilidade: foco inicial previsível).
        $this->dispatch('modal-opened');
    }

    public function openEdit(int $id): void
    {
        $mission = Mission::find($id);
        if (! $mission) {
            return;
        }

        $this->editingId = $mission->id;
        $this->resetValidation();
        $this->responsibles = $this->respList($mission);
        $this->form = [
            'title' => $mission->title,
            'date' => $mission->date,
            'time' => $mission->time,
            'priority' => $mission->priority,
            'status' => $mission->status,
            'requester' => $mission->requester ?? '',
            'notes' => $mission->notes ?? '',
            'completed_by' => $mission->completed_by ?: ($this->fallbackCompleter($mission) ?? ''),
        ];
        $this->showModal = true;
        $this->dispatch('set-responsibles', list: $this->responsibles);
        $this->dispatch('modal-opened');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(): void
    {
        // $this->validate() com regras "form.campo" devolve os dados já
        // aninhados de volta em $validated['form'], não com a chave "form.campo".
        $data = $this->validate()['form'];

        if ($this->responsibles === []) {
            $this->addError('form.responsibles', 'Selecione ao menos um responsável.');

            return;
        }

        $data['responsibles'] = $this->responsibles;
        $atual = $this->editingId ? Mission::find($this->editingId) : null;
        $data = Mission::applyCompletion($data, $atual);

        if ($this->editingId && $atual) {
            $atual->update($data);
            ActivityLog::record('editar_missao', 'mission', $atual->id, 'Editou a missão "'.$atual->title.'".');
        } else {
            $nova = Mission::create($data);
            ActivityLog::record('criar_missao', 'mission', $nova->id, 'Criou a missão "'.$nova->title.'".');
        }

        $wasEditing = (bool) $this->editingId;
        $this->closeModal();
        $this->toast($wasEditing ? 'Missão atualizada com sucesso.' : 'Missão adicionada ao painel.');
    }

    public function deleteMission(): void
    {
        if (! $this->editingId) {
            return;
        }

        $mission = Mission::find($this->editingId);
        if (! $mission) {
            return;
        }

        $titulo = $mission->title;
        $mission->delete();
        ActivityLog::record('excluir_missao', 'mission', $mission->id, 'Excluiu a missão "'.$titulo.'".');
        $this->closeModal();
        $this->toast('Missão excluída.');
    }

    public function changeStatus(int $id, string $status): void
    {
        $mission = Mission::find($id);
        if (! $mission) {
            return;
        }

        $data = Mission::applyCompletion([
            'title' => $mission->title,
            'date' => $mission->date,
            'time' => $mission->time,
            'responsibles' => $this->respList($mission),
            'priority' => $mission->priority,
            'status' => $status,
            'requester' => $mission->requester,
            'notes' => $mission->notes,
            'completed_by' => $status === 'concluida' ? ($mission->completed_by ?: $this->fallbackCompleter($mission)) : null,
        ], $mission);

        $mission->update($data);
        $situacao = mb_strtolower(self::STATUS_LABEL[$status] ?? $status);
        ActivityLog::record('alterar_situacao_missao', 'mission', $mission->id, 'Alterou "'.$mission->title.'" para '.$situacao.'.');
        $this->toast('"'.$mission->title.'" atualizada para '.$situacao.'.');
    }

    public function reopen(int $id): void
    {
        $mission = Mission::find($id);
        if (! $mission) {
            return;
        }

        $status = $mission->previous_status ?: 'pendente';
        $data = Mission::applyCompletion([
            'title' => $mission->title,
            'date' => $mission->date,
            'time' => $mission->time,
            'responsibles' => $this->respList($mission),
            'priority' => $mission->priority,
            'status' => $status,
            'requester' => $mission->requester,
            'notes' => $mission->notes,
            'completed_by' => null,
        ], $mission);

        $mission->update($data);
        ActivityLog::record('reabrir_missao', 'mission', $mission->id, 'Reabriu a missão "'.$mission->title.'".');
        $this->toast('"'.$mission->title.'" reaberta.');
    }

    public function resetDemo(): void
    {
        if (! app()->environment('local')) {
            abort(404);
        }

        Mission::query()->delete();
        Artisan::call('db:seed', ['--class' => 'MissionSeeder', '--force' => true]);
        $this->toast('Dados de demonstração restaurados.');
    }

    private function toast(string $message): void
    {
        $this->dispatch('toast', message: $message);
    }

    // ---------- pessoas ----------

    /**
     * @return array<int, string>
     */
    private function people(): array
    {
        return $this->peopleCache ??= Militar::ativos()->get()->map(fn ($m) => $m->nomeExibicao())->push('Toda a seção')->all();
    }

    /**
     * @return array<int, string>
     */
    private function completers(): array
    {
        return $this->completersCache ??= collect($this->people())->reject(fn ($p) => $p === 'Toda a seção')->values()->all();
    }

    // ---------- helpers de domínio (porta de public/js/app.js) ----------

    /**
     * @return array<int, string>
     */
    private function respList(Mission $m): array
    {
        return $m->responsibles ?? [];
    }

    private function respNames(Mission $m): string
    {
        $list = $this->respList($m);

        return $list !== [] ? implode(', ', $list) : 'Sem responsável';
    }

    private function fallbackCompleter(Mission $m): ?string
    {
        // Sugere quem está logado como autor da conclusão (é quem está
        // clicando). Continua sendo só uma sugestão — o <select> é editável.
        if ($nome = auth()->user()?->nomeExibicao()) {
            return $nome;
        }

        $person = collect($this->respList($m))->first(fn ($p) => $p !== 'Toda a seção');

        return $person ?? ($this->completers()[0] ?? null);
    }

    private function initials(?string $name): string
    {
        $stop = ['da', 'de', 'do', 'ep'];
        $letters = collect(preg_split('/\s+/', trim((string) $name)))
            ->filter(fn ($x) => $x !== '' && ! in_array(mb_strtolower($x), $stop, true))
            ->map(fn ($x) => mb_substr($x, 0, 1))
            ->implode('');

        return mb_strtoupper(mb_substr($letters, 0, 2));
    }

    private function dateLabel(string $date): string
    {
        [$y, $m, $d] = explode('-', $date);

        return $d.' de '.(self::MESES[$m] ?? $m);
    }

    private function fromISO(string $date, ?string $time = null): Carbon
    {
        return Carbon::parse($date.' '.($time ?: '12:00'));
    }

    private function actualStatus(Mission $m): string
    {
        // Decisão confirmada com o usuário: uma missão PODE estar "em andamento"
        // e, ainda assim, "atrasada". Portanto qualquer missão não concluída com
        // data/hora já no passado é exibida como "atrasada" — só a conclusão
        // remove o rótulo.
        return $m->status !== 'concluida' && $this->fromISO($m->date, $m->time)->lt(now())
            ? 'atrasada'
            : $m->status;
    }

    /**
     * @return array{expired: bool, prefix: string, value: string, suffix: string}
     */
    private function countdown(Mission $m): array
    {
        $target = $this->fromISO($m->date, $m->time);
        $diffMinutes = (int) round(now()->diffInSeconds($target, false) / 60);

        if ($diffMinutes < 0) {
            return ['expired' => true, 'prefix' => '', 'value' => 'Prazo ultrapassado', 'suffix' => ' — requer atualização'];
        }

        $days = intdiv($diffMinutes, 1440);
        $hours = intdiv($diffMinutes % 1440, 60);

        if ($days > 0) {
            $value = $days.' dia'.($days > 1 ? 's' : '').($hours ? ' e '.$hours.'h' : '');

            return ['expired' => false, 'prefix' => 'Começa em ', 'value' => $value, 'suffix' => ''];
        }

        if ($hours > 0) {
            $value = $hours.'h '.($diffMinutes % 60).'min';

            return ['expired' => false, 'prefix' => 'Começa em ', 'value' => $value, 'suffix' => ''];
        }

        return ['expired' => false, 'prefix' => 'Começa em ', 'value' => max(0, $diffMinutes).' minutos', 'suffix' => ''];
    }

    /**
     * @return array{avatars: array<int, array{initials: string, name: string}>, more: int}
     */
    private function avatars(Mission $m): array
    {
        $list = $this->respList($m);
        $max = 3;
        $shown = array_slice($list, 0, $max);

        return [
            'avatars' => collect($shown)->map(fn ($n) => ['initials' => $this->initials($n), 'name' => $n])->all(),
            'more' => max(0, count($list) - $max),
        ];
    }

    private function sorted(Collection $missions): Collection
    {
        return $missions->sortBy(fn (Mission $m) => $m->date.' '.$m->time)->values();
    }

    // ---------- view models ----------

    /**
     * Recebe as coleções já escopadas por `render()` — $open (todas as não
     * concluídas, de qualquer data, pois uma missão "atrasada" pode ser
     * antiga) e $todayAny/$weekMissions (só a janela de data necessária).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildStats(Collection $open, Collection $todayAny, Collection $weekMissions): array
    {
        $doneWeek = $this->weekData($weekMissions)['doneWeek'];
        $overdue = $open->filter(fn ($m) => $this->actualStatus($m) === 'atrasada')->count();

        return [
            ['icon' => 'clipboard', 'label' => 'Missões hoje', 'value' => $todayAny->count(), 'sub' => 'programadas', 'tone' => 'tone-blue'],
            ['icon' => 'clock', 'label' => 'Em andamento', 'value' => $open->where('status', 'andamento')->count(), 'sub' => 'na seção', 'tone' => 'tone-amber'],
            ['icon' => 'check', 'label' => 'Concluídas', 'value' => $doneWeek, 'sub' => 'nesta semana', 'tone' => 'tone-green'],
            ['icon' => 'flag', 'label' => 'Atrasadas', 'value' => $overdue, 'sub' => $overdue === 1 ? 'requer atenção' : 'requerem atenção', 'tone' => 'tone-red'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMissionRows(Collection $list, string $kind): array
    {
        return $list->map(function (Mission $m) use ($kind) {
            $st = $this->actualStatus($m);
            $av = $this->avatars($m);

            return [
                'id' => $m->id,
                'kind' => $kind,
                'time' => $m->time,
                'dateLabel' => $this->dateLabel($m->date),
                'title' => $m->title,
                'requester' => $m->requester,
                'respNames' => $this->respNames($m),
                'avatars' => $av['avatars'],
                'more' => $av['more'],
                'priority' => $m->priority,
                'status' => $m->status,
                'actualStatus' => $st,
                'statusLabel' => self::STATUS_LABEL[$st] ?? $st,
                'isOverdue' => $st === 'atrasada',
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildNextMission(Collection $missions): ?array
    {
        $now = now();
        $pending = $missions->filter(fn ($m) => $m->status !== 'concluida');
        $next = $this->sorted($pending->filter(fn ($m) => $this->fromISO($m->date, $m->time)->gte($now)))->first()
            ?? $this->sorted($pending)->first();

        if (! $next) {
            return null;
        }

        return [
            'title' => $next->title,
            'notes' => $next->notes ?: 'Sem observações registradas.',
            'dateLabel' => $this->dateLabel($next->date),
            'time' => $next->time,
            'respNames' => $this->respNames($next),
            'countdown' => $this->countdown($next),
        ];
    }

    /**
     * Memoizado — chamado 2-3× por render (stats, view model "week" e o modo
     * monitor) com a MESMA janela; sem cache recalculava tudo de novo cada vez.
     *
     * @return array{doneWeek: int, total: int, pct: int}
     */
    private function weekData(Collection $missions): array
    {
        if ($this->weekDataCache !== null) {
            return $this->weekDataCache;
        }

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(7);
        $week = $missions->filter(fn ($m) => $this->fromISO($m->date)->gte($weekStart) && $this->fromISO($m->date)->lt($weekEnd));
        $doneWeek = $week->where('status', 'concluida')->count();
        $total = $week->count();

        return $this->weekDataCache = ['doneWeek' => $doneWeek, 'total' => $total, 'pct' => $total ? (int) round($doneWeek / $total * 100) : 0];
    }

    /**
     * @return array<int, array{name: string, initials: string, count: int, label: string}>
     */
    private function teamWorkload(Collection $missions): array
    {
        $counts = [];
        foreach ($missions->where('status', '!=', 'concluida') as $m) {
            foreach ($this->respList($m) as $p) {
                // "Toda a seção" não é um militar — não faz sentido contá-la como
                // carga individual ao lado das pessoas.
                if ($p === 'Toda a seção') {
                    continue;
                }
                $counts[$p] = ($counts[$p] ?? 0) + 1;
            }
        }
        arsort($counts);
        $counts = array_slice($counts, 0, 6, true);

        return collect($counts)->map(fn ($c, $name) => [
            'name' => $name,
            'initials' => $this->initials($name),
            'count' => $c,
            'label' => $c === 1 ? 'missão ativa' : 'missões ativas',
        ])->values()->all();
    }

    /**
     * Pagina "Todas as missões" — a segmentação por status é calculada em
     * memória (`actualStatus` é derivado, não é coluna), então o limite é
     * aplicado DEPOIS do filtro, e o total real (antes do limite) volta junto
     * para a view decidir se mostra "Carregar mais".
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    private function buildTableRows(Collection $missions, string $filter, int $limit): array
    {
        $list = $this->sorted($missions->where('status', '!=', 'concluida'));
        if ($filter !== 'todas') {
            $list = $list->filter(fn ($m) => $this->actualStatus($m) === $filter)->values();
        }

        $rows = $list->take($limit)->map(function (Mission $m) {
            $st = $this->actualStatus($m);

            return [
                'id' => $m->id,
                'title' => $m->title,
                'requester' => $m->requester ?: 'Sem demandante',
                'dateLabel' => $this->dateLabel($m->date),
                'time' => $m->time,
                'respNames' => $this->respNames($m),
                'priority' => $m->priority,
                'priorityLabel' => self::PRIORITY_LABEL[$m->priority] ?? $m->priority,
                'status' => $st,
                'statusLabel' => self::STATUS_LABEL[$st] ?? $st,
            ];
        })->all();

        return ['rows' => $rows, 'total' => $list->count()];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildHistoryRows(Collection $missions): array
    {
        $history = $this->sorted($missions->where('status', 'concluida'))->reverse()->values();

        return $history->map(function (Mission $m) {
            $done = $m->completed_at;
            $doneLabel = $done ? $this->dateLabel($done->toDateString()).', '.$done->format('H:i') : '—';

            return [
                'id' => $m->id,
                'title' => $m->title,
                'requester' => $m->requester ?: 'Sem demandante',
                'dateLabel' => $this->dateLabel($m->date),
                'time' => $m->time,
                'completedBy' => $m->completed_by ?: $this->respNames($m),
                'completedAtLabel' => $doneLabel,
            ];
        })->all();
    }

    /**
     * @return array{days: array<int, array<string, mixed>>, hours: array<int, int>, cells: array<string, array<int, array<int, array<string, mixed>>>>}
     */
    private function buildWeekGrid(Collection $missions, string $mondayIso, bool $interactive): array
    {
        $monday = Carbon::parse($mondayIso);
        $names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $today = now()->toDateString();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->copy()->addDays($i);
            $days[] = [
                'iso' => $d->toDateString(),
                'label' => $names[$i],
                'dayNum' => $d->day,
                'isToday' => $d->toDateString() === $today,
                'isWeekend' => $i >= 5,
            ];
        }

        // A faixa 07h-18h é só a baseline (evita achatar o grid em semanas
        // vazias). Se alguma missão da semana exibida cair fora dela, a
        // faixa se expande pra incluir a hora — sem isso a missão fica
        // salva no banco mas nunca aparece em nenhuma célula do grid.
        $weekIsos = collect($days)->pluck('iso');
        $missionHours = $missions
            ->filter(fn (Mission $m) => $weekIsos->contains($m->date))
            ->map(fn (Mission $m) => (int) substr($m->time, 0, 2));

        $start = min(self::CAL_START, $missionHours->min() ?? self::CAL_START);
        $end = max(self::CAL_END, ($missionHours->max() ?? (self::CAL_END - 1)) + 1);

        $hours = range($start, $end - 1);
        $cells = [];
        foreach ($hours as $h) {
            $cells[$h] = [];
            foreach ($days as $day) {
                $items = $this->sorted($missions->filter(
                    fn (Mission $m) => $m->date === $day['iso'] && (int) substr($m->time, 0, 2) === $h
                ));
                $cells[$h][] = $items->map(fn (Mission $m) => [
                    'id' => $m->id,
                    'time' => $m->time,
                    'title' => $m->title,
                    'priority' => $m->priority,
                    'respNames' => $this->respNames($m),
                ])->all();
            }
        }

        return ['days' => $days, 'hours' => $hours, 'cells' => $cells, 'interactive' => $interactive];
    }

    private function weekLabel(string $mondayIso): string
    {
        $monday = Carbon::parse($mondayIso);

        return $this->dateLabel($monday->toDateString()).' — '.$this->dateLabel($monday->copy()->addDays(6)->toDateString());
    }

    /**
     * Recebe $open (não concluídas, qualquer data — para "próxima missão"
     * poder achar uma pendente antiga) e $todayAny/$weekMissions (já
     * escopadas por `render()` à janela de data que a TV realmente exibe).
     *
     * @return array<string, mixed>
     */
    private function buildTvData(Collection $open, Collection $todayAny, Collection $weekMissions): array
    {
        $today = now()->toDateString();
        $names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $todayList = $this->sorted($todayAny)->take(6)->map(fn (Mission $m) => [
            'time' => $m->time,
            'title' => $m->title,
            'respNames' => $this->respNames($m),
            'priority' => $m->priority,
            'status' => $this->actualStatus($m),
            'statusLabel' => self::STATUS_LABEL[$this->actualStatus($m)] ?? $m->status,
            'done' => $m->status === 'concluida',
        ])->values()->all();

        $weekDays = [];
        for ($i = 0; $i < 5; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $iso = $d->toDateString();
            $items = $this->sorted($weekMissions->where('date', $iso))->take(4)->map(fn (Mission $m) => [
                'time' => $m->time,
                'title' => $m->title,
                'respNames' => $this->respNames($m),
                'priority' => $m->priority,
                'done' => $m->status === 'concluida',
            ])->values()->all();
            $weekDays[] = [
                'label' => $names[$i],
                'dateLabel' => $d->format('d/m'),
                'isToday' => $iso === $today,
                'items' => $items,
            ];
        }

        return [
            'screen' => $this->tvScreen,
            'todayList' => $todayList,
            'weekDays' => $weekDays,
            'next' => $this->buildNextMission($open),
            'week' => $this->weekData($weekMissions),
        ];
    }

    public function render()
    {
        // Uma única `Mission::orderBy(...)->get()` carregaria TODA a tabela
        // (inclusive anos de missões já concluídas) em toda requisição, mesmo
        // com `wire:poll`. Por isso cada view model recebe só a janela de
        // data que realmente precisa:
        // - $open: não concluídas, qualquer data (uma "atrasada" pode ser
        //   antiga — não dá pra restringir por semana).
        // - $todayAny / $weekMissions: só hoje / só a semana atual (qualquer
        //   status, pois o calendário e a TV mostram concluídas também).
        // - $calWindow: só a semana navegada no calendário (calMonday..+6).
        // - histórico: paginado direto na consulta (ver buildHistoryRows).
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekStartIso = $weekStart->toDateString();
        $weekEndIso = $weekStart->copy()->addDays(6)->toDateString();
        $calEndIso = Carbon::parse($this->calMonday)->addDays(6)->toDateString();

        $open = Mission::where('status', '!=', 'concluida')->orderBy('date')->orderBy('time')->get();
        $todayAny = Mission::where('date', $today)->orderBy('time')->get();
        $weekMissions = Mission::whereBetween('date', [$weekStartIso, $weekEndIso])->get();
        $calWindow = $this->calMonday === $weekStartIso
            ? $weekMissions
            : Mission::whereBetween('date', [$this->calMonday, $calEndIso])->get();

        $historyTotal = Mission::where('status', 'concluida')->count();
        $historyMissions = Mission::where('status', 'concluida')
            ->orderByDesc('date')->orderByDesc('time')
            ->take($this->historyLimit)
            ->get();

        $table = $this->buildTableRows($open, $this->filter, $this->tableLimit);

        return view('livewire.painel', [
            'stats' => $this->buildStats($open, $todayAny, $weekMissions),
            'todayMissions' => $this->buildMissionRows($this->sorted($open->where('date', $today)), 'today'),
            'upcomingMissions' => $this->buildMissionRows(
                $this->sorted($open->filter(fn ($m) => $m->date > $today && $this->fromISO($m->date)->lt(now()->addDays(8))))->take(4),
                'upcoming'
            ),
            'nextMission' => $this->buildNextMission($open),
            'week' => $this->weekData($weekMissions),
            'team' => $this->teamWorkload($open),
            'calendar' => $this->buildWeekGrid($calWindow, $this->calMonday, true),
            'weekLabel' => $this->weekLabel($this->calMonday),
            'tableRows' => $table['rows'],
            'tableHasMore' => $table['total'] > count($table['rows']),
            'historyRows' => $this->buildHistoryRows($historyMissions),
            'historyHasMore' => $historyTotal > $historyMissions->count(),
            'tv' => $this->monitorMode ? $this->buildTvData($open, $todayAny, $weekMissions) : null,
            'calTv' => $this->calendarMonitorMode ? [
                'grid' => $this->buildWeekGrid($weekMissions, $weekStartIso, false),
                'weekLabel' => $this->weekLabel($weekStartIso),
            ] : null,
            'people' => $this->people(),
            'completers' => $this->completers(),
            'userInitials' => $this->initials(auth()->user()?->nomeExibicao()),
        ]);
    }
}
