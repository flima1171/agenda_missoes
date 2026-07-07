<div
    class="painel-root {{ $monitorMode ? 'monitor-mode' : '' }} {{ $calendarMonitorMode ? 'calendar-monitor-mode' : '' }} {{ $darkMode ? 'theme-dark' : '' }}"
    x-data="{ toastShow: false, toastText: '' }"
    x-on:toast.window="
        toastText = $event.detail.message;
        toastShow = true;
        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => toastShow = false, 2800);
    "
    wire:keydown.escape.window="handleEscape"
    wire:poll.15s="refresh"
>
    {{-- ============================ PAINEL (admin) ============================ --}}
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark"><img src="{{ asset('images/logo-comunicacoes.png') }}" alt=""></div>
                <div><strong>Painel de Missões</strong><span>25º Batalhão de Caçadores</span></div>
            </div>
            <div class="nav-label">Painel</div>
            <nav class="nav">
                <button type="button" class="nav-btn {{ $view === 'dashboard' ? 'active' : '' }}" wire:click="setView('dashboard')" aria-label="Visão geral"><x-icon name="grid" /><span>Visão geral</span></button>
                <button type="button" class="nav-btn {{ $view === 'calendar' ? 'active' : '' }}" wire:click="setView('calendar')" aria-label="Calendário"><x-icon name="calendar" /><span>Calendário</span></button>
                <button type="button" class="nav-btn {{ $view === 'missions' ? 'active' : '' }}" wire:click="setView('missions')" aria-label="Todas as missões"><x-icon name="clipboard" /><span>Todas as missões</span></button>
                <button type="button" class="nav-btn {{ $view === 'history' ? 'active' : '' }}" wire:click="setView('history')" aria-label="Concluídas"><x-icon name="check" /><span>Concluídas</span></button>
            </nav>
            @if (auth()->user()->is_admin)
                <div class="nav-label">Administração</div>
                <nav class="nav nav-admin">
                    <a class="nav-btn" href="{{ route('militares.manage') }}"><span>Militares</span></a>
                    <a class="nav-btn" href="{{ route('usuarios.manage') }}"><span>Usuários</span></a>
                </nav>
            @endif
            <div class="sidebar-bottom">
                <div class="monitor-card">
                    <strong>Exibição no monitor</strong>
                    <p>Tela cheia com letras grandes, alternando entre hoje e a semana.</p>
                    <button type="button" class="monitor-btn" wire:click="enterMonitor">Ativar modo monitor</button>
                </div>
                <div class="profile">
                    <div class="dot">{{ $userInitials }}</div>
                    <div class="profile-info">
                        <strong title="{{ auth()->user()->nomeExibicao() }}">{{ auth()->user()->nomeExibicao() }}</strong>
                        <span>{{ auth()->user()->is_admin ? 'Administrador' : 'Usuário da seção' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn" title="Sair" aria-label="Encerrar sessão"><x-icon name="logout" /></button>
                    </form>
                </div>
            </div>
        </aside>

        <main>
            <header class="topbar">
                <div class="title-block">
                    <h1>{{ ['dashboard' => 'Bom dia, Seção.', 'calendar' => 'Planejamento semanal', 'missions' => 'Controle de missões', 'history' => 'Histórico da seção'][$view] }}</h1>
                    <p><x-live-clock long /></p>
                </div>
                <div class="top-actions">
                    <div class="clock"><x-live-clock /><span>Horário local</span></div>
                    <button type="button" class="icon-btn" title="Alternar modo escuro" aria-label="Alternar modo escuro" wire:click="toggleTheme"><x-icon :name="$darkMode ? 'sun' : 'moon'" /></button>
                    @if (app()->environment('local'))
                        <button type="button" class="icon-btn" id="resetBtn" title="Restaurar dados de demonstração" aria-label="Restaurar dados de demonstração"
                            x-on:click="if (confirm('Restaurar os dados iniciais da demonstração? As missões atuais serão apagadas.')) $wire.resetDemo()">
                            <x-icon name="refresh" />
                        </button>
                    @endif
                    <button type="button" class="primary-btn" wire:click="openNew" aria-label="Nova missão"><x-icon name="plus" /><span>Nova missão</span></button>
                </div>
            </header>

            {{-- Visão geral --}}
            <section class="view {{ $view === 'dashboard' ? 'active' : '' }}">
                <div class="stats">
                    @foreach ($stats as $s)
                        <article class="stat">
                            <div class="stat-top"><span class="stat-label">{{ $s['label'] }}</span><span class="stat-icon {{ $s['tone'] }}"><x-icon :name="$s['icon']" /></span></div>
                            <div class="stat-value"><strong class="mono">{{ $s['value'] }}</strong><span>{{ $s['sub'] }}</span></div>
                        </article>
                    @endforeach
                </div>
                <div class="dashboard-grid">
                    <div class="col-main">
                        <article class="card">
                            <div class="card-head"><div><h2>Missões de hoje</h2><p>Qualquer militar da seção pode atualizar a situação</p></div><button type="button" class="text-btn" wire:click="setView('missions')">Ver todas</button></div>
                            <div class="mission-list">
                                @forelse ($todayMissions as $m)
                                    @include('livewire.partials.mission-row', ['m' => $m])
                                @empty
                                    <div class="empty">Nenhuma missão pendente para hoje.</div>
                                @endforelse
                            </div>
                        </article>
                        <article class="card">
                            <div class="card-head"><div><h2>Próximos dias</h2><p>Missões previstas nesta semana</p></div><button type="button" class="text-btn" wire:click="setView('calendar')">Abrir calendário</button></div>
                            <div class="mission-list">
                                @forelse ($upcomingMissions as $m)
                                    @include('livewire.partials.mission-row', ['m' => $m])
                                @empty
                                    <div class="empty">Nenhuma missão prevista para os próximos dias.</div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                    <div class="col-side">
                        <article class="card next-card">
                            @include('livewire.partials.next-mission', ['next' => $nextMission])
                        </article>
                        <article class="card">
                            <div class="card-head"><div><h2>Progresso da semana</h2><p>Execução das missões planejadas</p></div><strong class="mono">{{ $week['pct'] }}%</strong></div>
                            <div class="progress-wrap">
                                <div class="progress-line"><div style="width: {{ $week['pct'] }}%"></div></div>
                                <div class="progress-labels"><span>{{ $week['doneWeek'] }} concluída{{ $week['doneWeek'] === 1 ? '' : 's' }}</span><span>{{ $week['total'] }} no total</span></div>
                            </div>
                        </article>
                        <article class="card">
                            <div class="card-head"><div><h2>Carga por militar</h2><p>Missões pendentes ou em andamento</p></div></div>
                            <div class="team-list">
                                @forelse ($team as $t)
                                    <div class="team-row"><div class="mini-avatar">{{ $t['initials'] }}</div><div><strong>{{ $t['name'] }}</strong><span>{{ $t['count'] }} {{ $t['label'] }}</span></div><div class="team-count mono">{{ $t['count'] }}</div></div>
                                @empty
                                    <div class="empty">Sem missões ativas.</div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            {{-- Calendário --}}
            <section class="view {{ $view === 'calendar' ? 'active' : '' }}">
                <div class="section-toolbar">
                    <div><h2>Calendário de missões</h2></div>
                    <div class="week-nav">
                        <button type="button" wire:click="prevWeek" aria-label="Semana anterior">‹</button>
                        <strong>{{ $weekLabel }}</strong>
                        <button type="button" wire:click="nextWeek" aria-label="Semana seguinte">›</button>
                        <button type="button" wire:click="todayWeek">Hoje</button>
                    </div>
                </div>
                <div class="cal-legend">
                    <span class="cal-legend-item"><i class="dot today"></i>Hoje</span>
                    <span class="cal-legend-item"><i class="dot weekend"></i>Fim de semana</span>
                    <button type="button" class="text-btn cal-monitor-btn" wire:click="enterCalendarMonitor">Ativar modo monitor do calendário</button>
                </div>
                <article class="card calendar"><div class="calendar-grid">@include('livewire.partials.calendar-grid', ['grid' => $calendar])</div></article>
            </section>

            {{-- Todas as missões --}}
            <section class="view {{ $view === 'missions' ? 'active' : '' }}">
                <div class="section-toolbar">
                    <div><h2>Todas as missões</h2></div>
                    <div class="segmented">
                        <button type="button" class="segment {{ $filter === 'todas' ? 'active' : '' }}" wire:click="setFilter('todas')">Todas</button>
                        <button type="button" class="segment {{ $filter === 'pendente' ? 'active' : '' }}" wire:click="setFilter('pendente')">Pendentes</button>
                        <button type="button" class="segment {{ $filter === 'andamento' ? 'active' : '' }}" wire:click="setFilter('andamento')">Em andamento</button>
                        <button type="button" class="segment {{ $filter === 'atrasada' ? 'active' : '' }}" wire:click="setFilter('atrasada')">Atrasadas</button>
                    </div>
                </div>
                <article class="card" style="overflow-x:auto">
                    <table class="all-table">
                        <thead><tr><th>Missão</th><th>Data e hora</th><th>Responsável</th><th>Prioridade</th><th>Situação</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($tableRows as $r)
                                <tr wire:key="table-row-{{ $r['id'] }}">
                                    <td class="table-title"><strong>{{ $r['title'] }}</strong><span>{{ $r['requester'] }}</span></td>
                                    <td class="mono">{{ $r['dateLabel'] }}, {{ $r['time'] }}</td>
                                    <td>{{ $r['respNames'] }}</td>
                                    <td><span class="badge b-{{ $r['priority'] }}">{{ $r['priorityLabel'] }}</span></td>
                                    <td><span class="badge s-{{ $r['status'] }}">{{ $r['statusLabel'] }}</span></td>
                                    <td><div class="row-actions"><button type="button" class="small-btn" title="Editar" aria-label="Editar {{ $r['title'] }}" wire:click="openEdit({{ $r['id'] }})"><x-icon name="edit" /></button></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">Nenhuma missão neste filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($tableHasMore)
                        <div class="load-more"><button type="button" class="text-btn" wire:click="loadMoreTable">Carregar mais missões</button></div>
                    @endif
                </article>
            </section>

            {{-- Concluídas / histórico --}}
            <section class="view {{ $view === 'history' ? 'active' : '' }}">
                <div class="section-toolbar"><div><h2>Missões concluídas</h2><p style="margin:5px 0 0;color:var(--muted);font-size:12px">Registro de quem concluiu e quando</p></div></div>
                <article class="card" style="overflow-x:auto">
                    <table class="all-table">
                        <thead><tr><th>Missão</th><th>Prevista para</th><th>Concluída por</th><th>Concluída em</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($historyRows as $r)
                                <tr wire:key="history-row-{{ $r['id'] }}">
                                    <td class="table-title"><strong>{{ $r['title'] }}</strong><span>{{ $r['requester'] }}</span></td>
                                    <td class="mono">{{ $r['dateLabel'] }}, {{ $r['time'] }}</td>
                                    <td>{{ $r['completedBy'] }}</td>
                                    <td class="mono">{{ $r['completedAtLabel'] }}</td>
                                    <td class="row-actions"><button type="button" class="small-btn" wire:click="reopen({{ $r['id'] }})">Reabrir</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty">Nenhuma missão concluída ainda.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($historyHasMore)
                        <div class="load-more"><button type="button" class="text-btn" wire:click="loadMoreHistory">Carregar mais missões concluídas</button></div>
                    @endif
                </article>
            </section>
        </main>
    </div>

    {{-- ============================ MODO MONITOR (TV) ============================ --}}
    @if ($tv)
        <div class="tv">
            @include('livewire.partials.tv-screen', ['tv' => $tv])
        </div>
        <div wire:poll.12s="rotateTv"></div>
    @endif

    {{-- ================= MODO MONITOR DO CALENDÁRIO (só o calendário) ================= --}}
    @if ($calTv)
        <div class="cal-tv">
            <header class="tv-head">
                <div class="tv-brand">
                    <div class="mark"><img src="{{ asset('images/logo-comunicacoes.png') }}" alt=""></div>
                    <div><strong>Calendário da Seção</strong><span>25º Batalhão de Caçadores</span></div>
                </div>
                <div class="tv-head-actions">
                    <div class="tv-clock"><x-live-clock /><span><x-live-clock long :year="false" /></span></div>
                    <button type="button" class="tv-theme-btn" title="Alternar modo escuro" aria-label="Alternar modo escuro" wire:click="toggleTheme"><x-icon :name="$darkMode ? 'sun' : 'moon'" /></button>
                </div>
            </header>
            <div class="tv-cal-legend">
                <strong class="mono">{{ $calTv['weekLabel'] }}</strong>
                <span class="tv-cal-legend-item"><i class="dot today"></i>Hoje</span>
                <span class="tv-cal-legend-item"><i class="dot weekend"></i>Fim de semana</span>
            </div>
            <div class="tv-cal-scroll">
                <div class="tv-calendar-grid">@include('livewire.partials.calendar-grid', ['grid' => $calTv['grid']])</div>
            </div>
            <button type="button" class="tv-exit" wire:click="exitCalendarMonitor">Sair do modo monitor (Esc)</button>
        </div>
    @endif

    {{-- ============================ MODAL ============================ --}}
    <div class="modal-backdrop {{ $showModal ? 'open' : '' }}" wire:click.self="closeModal">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="modal-head"><div><h2 id="modal-title">{{ $editingId ? 'Editar missão' : 'Nova missão' }}</h2><p>Preencha somente o necessário. Depois você pode editar.</p></div><button class="close-btn" type="button" wire:click="closeModal" aria-label="Fechar">×</button></div>
            <form wire:submit="save">
                <div class="form-grid">
                    <div class="field full"><label for="f-title">Missão *</label><input id="f-title" wire:model="form.title" required maxlength="120" placeholder="Ex.: VC — Verificação de Cumprimento"></div>
                    <div class="field"><label for="f-date">Data *</label><input id="f-date" wire:model="form.date" type="date" required></div>
                    <div class="field"><label for="f-time">Horário *</label><input id="f-time" wire:model="form.time" type="time" required></div>
                    <div class="field full"><label>Responsável(is) *</label><livewire:responsible-selector :people="$people" /></div>
                    <div class="field"><label for="f-priority">Prioridade</label><select id="f-priority" wire:model="form.priority"><option value="baixa">Baixa</option><option value="media">Média</option><option value="alta">Alta</option></select></div>
                    <div class="field"><label for="f-status">Situação</label><select id="f-status" wire:model.live="form.status"><option value="pendente">Pendente</option><option value="andamento">Em andamento</option><option value="concluida">Concluída</option></select></div>
                    <div class="field"><label for="f-requester">Demandante</label><input id="f-requester" wire:model="form.requester" maxlength="80" placeholder="Ex.: Cmt do 25º BC"></div>
                    <div class="field" style="{{ $form['status'] === 'concluida' ? '' : 'display:none' }}"><label for="f-completed_by">Concluída por</label>
                        <select id="f-completed_by" wire:model="form.completed_by">
                            @foreach ($completers as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full"><label for="f-notes">Observações</label><textarea id="f-notes" wire:model="form.notes" maxlength="500" placeholder="Informações importantes para o cumprimento da missão"></textarea></div>
                </div>
                @if ($errors->any())
                    <p class="form-error">{{ $errors->first() }}</p>
                @endif
                <div class="form-actions">
                    @if ($editingId)
                        <button type="button" class="secondary-btn danger-btn" x-on:click="if (confirm('Excluir a missão ' + @js($form['title']) + '?')) $wire.deleteMission()">Excluir</button>
                    @endif
                    <button type="button" class="secondary-btn" wire:click="closeModal">Cancelar</button>
                    <button type="submit" class="primary-btn">Salvar missão</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================ TOAST ============================ --}}
    <div class="toast" x-bind:class="{ show: toastShow }"><x-icon name="check" /><span x-text="toastText"></span></div>
</div>
