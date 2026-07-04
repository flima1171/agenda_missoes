<?php

namespace App\Livewire;

use App\Models\Militar;
use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Fase 5: substitui public/js/app.js. Toda a interatividade do painel
 * (visão geral, calendário, tabela de missões, concluídas, modal, modo
 * monitor) vira estado/métodos deste componente. O `render()` pré-calcula
 * tudo em "view models" (arrays simples) para a view ficar só exibindo
 * dados, sem lógica.
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
        } else {
            Mission::create($data);
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

        $mission->delete();
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
        $this->toast('"'.$mission->title.'" atualizada para '.mb_strtolower(self::STATUS_LABEL[$status] ?? $status).'.');
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
        return Militar::ativos()->get()->map(fn ($m) => $m->nomeExibicao())->push('Toda a seção')->all();
    }

    /**
     * @return array<int, string>
     */
    private function completers(): array
    {
        return collect($this->people())->reject(fn ($p) => $p === 'Toda a seção')->values()->all();
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
     * @return array<int, array<string, mixed>>
     */
    private function buildStats(Collection $missions): array
    {
        $today = now()->toDateString();
        $doneWeek = $this->weekData($missions)['doneWeek'];
        $overdue = $missions->filter(fn ($m) => $this->actualStatus($m) === 'atrasada')->count();

        return [
            ['icon' => 'clipboard', 'label' => 'Missões hoje', 'value' => $missions->where('date', $today)->count(), 'sub' => 'programadas', 'tone' => 'tone-blue'],
            ['icon' => 'clock', 'label' => 'Em andamento', 'value' => $missions->where('status', 'andamento')->count(), 'sub' => 'na seção', 'tone' => 'tone-amber'],
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
     * @return array{doneWeek: int, total: int, pct: int}
     */
    private function weekData(Collection $missions): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(7);
        $week = $missions->filter(fn ($m) => $this->fromISO($m->date)->gte($weekStart) && $this->fromISO($m->date)->lt($weekEnd));
        $doneWeek = $week->where('status', 'concluida')->count();
        $total = $week->count();

        return ['doneWeek' => $doneWeek, 'total' => $total, 'pct' => $total ? (int) round($doneWeek / $total * 100) : 0];
    }

    /**
     * @return array<int, array{name: string, initials: string, count: int, label: string}>
     */
    private function teamWorkload(Collection $missions): array
    {
        $counts = [];
        foreach ($missions->where('status', '!=', 'concluida') as $m) {
            foreach ($this->respList($m) as $p) {
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
     * @return array<int, array<string, mixed>>
     */
    private function buildTableRows(Collection $missions, string $filter): array
    {
        $list = $this->sorted($missions->where('status', '!=', 'concluida'));
        if ($filter !== 'todas') {
            $list = $list->filter(fn ($m) => $this->actualStatus($m) === $filter)->values();
        }

        return $list->map(function (Mission $m) {
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

        $hours = range(self::CAL_START, self::CAL_END - 1);
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
     * @return array<string, mixed>
     */
    private function buildTvData(Collection $missions): array
    {
        $today = now()->toDateString();
        $names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $todayList = $this->sorted($missions->where('date', $today))->take(6)->map(fn (Mission $m) => [
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
            $items = $this->sorted($missions->where('date', $iso))->take(4)->map(fn (Mission $m) => [
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
            'next' => $this->buildNextMission($missions),
            'week' => $this->weekData($missions),
        ];
    }

    public function render()
    {
        $missions = Mission::orderBy('date')->orderBy('time')->get();
        $today = now()->toDateString();

        return view('livewire.painel', [
            'stats' => $this->buildStats($missions),
            'todayMissions' => $this->buildMissionRows($this->sorted($missions->where('date', $today)->where('status', '!=', 'concluida')), 'today'),
            'upcomingMissions' => $this->buildMissionRows(
                $this->sorted($missions->filter(fn ($m) => $m->date > $today && $this->fromISO($m->date)->lt(now()->addDays(8)) && $m->status !== 'concluida'))->take(4),
                'upcoming'
            ),
            'nextMission' => $this->buildNextMission($missions),
            'week' => $this->weekData($missions),
            'team' => $this->teamWorkload($missions),
            'calendar' => $this->buildWeekGrid($missions, $this->calMonday, true),
            'weekLabel' => $this->weekLabel($this->calMonday),
            'tableRows' => $this->buildTableRows($missions, $this->filter),
            'historyRows' => $this->buildHistoryRows($missions),
            'tv' => $this->monitorMode ? $this->buildTvData($missions) : null,
            'calTv' => $this->calendarMonitorMode ? [
                'grid' => $this->buildWeekGrid($missions, now()->startOfWeek(Carbon::MONDAY)->toDateString(), false),
                'weekLabel' => $this->weekLabel(now()->startOfWeek(Carbon::MONDAY)->toDateString()),
            ] : null,
            'people' => $this->people(),
            'completers' => $this->completers(),
        ]);
    }
}
