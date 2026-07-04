<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#101b17">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agenda de Missões — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    {{-- ============================ PAINEL (admin) ============================ --}}
    <div class="app">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark" id="brandIcon"></div>
                <div><strong>Painel de Missões</strong><span id="omName">25º Batalhão de Caçadores</span></div>
            </div>
            <div class="nav-label">Painel</div>
            <nav class="nav">
                <button class="nav-btn active" data-view="dashboard"><span class="icon" data-icon="grid"></span><span>Visão geral</span></button>
                <button class="nav-btn" data-view="calendar"><span class="icon" data-icon="calendar"></span><span>Calendário</span></button>
                <button class="nav-btn" data-view="missions"><span class="icon" data-icon="clipboard"></span><span>Todas as missões</span></button>
                <button class="nav-btn" data-view="history"><span class="icon" data-icon="check"></span><span>Concluídas</span></button>
            </nav>
            <div class="sidebar-bottom">
                <div class="monitor-card">
                    <strong>Exibição no monitor</strong>
                    <p>Tela cheia com letras grandes, alternando entre hoje e a semana.</p>
                    <button class="monitor-btn" id="monitorBtn">Ativar modo monitor</button>
                </div>
                <div class="profile">
                    <div class="dot" id="omSigla">25º BC</div>
                    <div><strong id="omNameProfile">25º Batalhão de Caçadores</strong><span>Toda a seção pode editar</span></div>
                </div>
            </div>
        </aside>

        <main>
            <header class="topbar">
                <div class="title-block"><h1 id="pageTitle">Bom dia, Seção.</h1><p id="todayText">Carregando…</p></div>
                <div class="top-actions">
                    <div class="clock"><strong class="mono" id="clock">--:--</strong><span>Horário local</span></div>
                    <button class="icon-btn" id="themeBtn" title="Alternar modo escuro"></button>
                    @if (app()->environment('local'))
                        <button class="icon-btn" id="resetBtn" title="Restaurar dados de demonstração"><span class="icon" data-icon="refresh"></span></button>
                    @endif
                    <button class="primary-btn" id="newMissionBtn"><span class="icon" data-icon="plus"></span><span>Nova missão</span></button>
                </div>
            </header>

            {{-- Visão geral --}}
            <section class="view active" id="view-dashboard">
                <div class="stats" id="stats"></div>
                <div class="dashboard-grid">
                    <div class="col-main">
                        <article class="card">
                            <div class="card-head"><div><h2>Missões de hoje</h2><p>Qualquer militar da seção pode atualizar a situação</p></div><button class="text-btn" data-go="missions">Ver todas</button></div>
                            <div class="mission-list" id="todayMissions"></div>
                        </article>
                        <article class="card">
                            <div class="card-head"><div><h2>Próximos dias</h2><p>Missões previstas nesta semana</p></div><button class="text-btn" data-go="calendar">Abrir calendário</button></div>
                            <div class="mission-list" id="upcomingMissions"></div>
                        </article>
                    </div>
                    <div class="col-side">
                        <article class="card next-card" id="nextMission"></article>
                        <article class="card">
                            <div class="card-head"><div><h2>Progresso da semana</h2><p>Execução das missões planejadas</p></div><strong class="mono" id="progressPercent">0%</strong></div>
                            <div class="progress-wrap"><div class="progress-line"><div id="progressBar"></div></div><div class="progress-labels"><span id="progressDone">0 concluídas</span><span id="progressTotal">0 no total</span></div></div>
                        </article>
                        <article class="card">
                            <div class="card-head"><div><h2>Carga por militar</h2><p>Missões pendentes ou em andamento</p></div></div>
                            <div class="team-list" id="teamList"></div>
                        </article>
                    </div>
                </div>
            </section>

            {{-- Calendário --}}
            <section class="view" id="view-calendar">
                <div class="section-toolbar">
                    <div><h2>Calendário de missões</h2></div>
                    <div class="week-nav"><button id="prevWeek">‹</button><strong id="weekLabel"></strong><button id="nextWeek">›</button><button id="todayWeek">Hoje</button></div>
                </div>
                <div class="cal-legend">
                    <span class="cal-legend-item"><i class="dot today"></i>Hoje</span>
                    <span class="cal-legend-item"><i class="dot weekend"></i>Fim de semana</span>
                    <button class="text-btn cal-monitor-btn" id="calendarMonitorBtn">Ativar modo monitor do calendário</button>
                </div>
                <article class="card calendar"><div class="calendar-grid" id="calendarGrid"></div></article>
            </section>

            {{-- Todas as missões --}}
            <section class="view" id="view-missions">
                <div class="section-toolbar">
                    <div><h2>Todas as missões</h2></div>
                    <div class="segmented" id="missionFilters">
                        <button class="segment active" data-filter="todas">Todas</button>
                        <button class="segment" data-filter="pendente">Pendentes</button>
                        <button class="segment" data-filter="andamento">Em andamento</button>
                        <button class="segment" data-filter="atrasada">Atrasadas</button>
                    </div>
                </div>
                <article class="card" style="overflow-x:auto">
                    <table class="all-table"><thead><tr><th>Missão</th><th>Data e hora</th><th>Responsável</th><th>Prioridade</th><th>Situação</th><th></th></tr></thead><tbody id="missionsTable"></tbody></table>
                </article>
            </section>

            {{-- Concluídas / histórico --}}
            <section class="view" id="view-history">
                <div class="section-toolbar"><div><h2>Missões concluídas</h2><p style="margin:5px 0 0;color:var(--muted);font-size:12px">Registro de quem concluiu e quando</p></div></div>
                <article class="card" style="overflow-x:auto">
                    <table class="all-table"><thead><tr><th>Missão</th><th>Prevista para</th><th>Concluída por</th><th>Concluída em</th><th></th></tr></thead><tbody id="historyTable"></tbody></table>
                </article>
            </section>
        </main>
    </div>

    {{-- ============================ MODO MONITOR (TV) ============================ --}}
    <div class="tv">
        <header class="tv-head">
            <div class="tv-brand">
                <div class="mark" id="tvOmSigla">25º BC</div>
                <div><strong>Missões da Seção</strong><span id="tvOmName">25º Batalhão de Caçadores</span></div>
            </div>
            <div class="tv-clock"><strong class="mono" id="tvClock">--:--</strong><span id="tvDate"></span></div>
        </header>
        <div class="tv-body">
            <div class="tv-left">
                <div class="tv-screen-head"><h2 id="tvScreenTitle">Missões de hoje</h2><div class="tv-dots"><i id="tvDot0" class="on"></i><i id="tvDot1"></i></div></div>
                <div class="tv-today" id="tvToday"></div>
                <div class="tv-week" id="tvWeek" style="display:none"></div>
            </div>
            <div class="tv-side">
                <article class="tv-next" id="tvNext"></article>
                <article class="tv-prog">
                    <div class="top"><span>Semana</span><strong class="mono" id="tvProgPct">0%</strong></div>
                    <div class="bar"><div id="tvProgBar"></div></div>
                    <div class="labels"><span id="tvProgDone">0 concluídas</span><span id="tvProgTotal">0 no total</span></div>
                </article>
                <button class="tv-exit" id="tvExit">Sair do modo monitor (Esc)</button>
            </div>
        </div>
    </div>

    {{-- ================= MODO MONITOR DO CALENDÁRIO (só o calendário) ================= --}}
    <div class="cal-tv">
        <header class="tv-head">
            <div class="tv-brand">
                <div class="mark" id="calTvSigla">25º BC</div>
                <div><strong>Calendário da Seção</strong><span id="calTvName">25º Batalhão de Caçadores</span></div>
            </div>
            <div class="tv-clock"><strong class="mono" id="calTvClock">--:--</strong><span id="calTvDate"></span></div>
        </header>
        <div class="tv-cal-legend">
            <strong class="mono" id="calTvWeekLabel"></strong>
            <span class="tv-cal-legend-item"><i class="dot today"></i>Hoje</span>
            <span class="tv-cal-legend-item"><i class="dot weekend"></i>Fim de semana</span>
        </div>
        <div class="tv-cal-scroll">
            <div class="tv-calendar-grid" id="calTvGrid"></div>
        </div>
        <button class="tv-exit" id="calTvExit">Sair do modo monitor (Esc)</button>
    </div>

    {{-- ============================ MODAL ============================ --}}
    <div class="modal-backdrop" id="modalBackdrop">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head"><div><h2 id="modalTitle">Nova missão</h2><p>Preencha somente o necessário. Depois você pode editar.</p></div><button class="close-btn" type="button" id="closeModal">×</button></div>
            <form id="missionForm">
                <div class="form-grid">
                    <div class="field full"><label for="f-title">Missão *</label><input id="f-title" name="title" required maxlength="120" placeholder="Ex.: VC — Verificação de Cumprimento"></div>
                    <div class="field"><label for="f-date">Data *</label><input id="f-date" name="date" type="date" required></div>
                    <div class="field"><label for="f-time">Horário *</label><input id="f-time" name="time" type="time" required></div>
                    <div class="field full"><label>Responsável(is) *</label><div class="chip-group" id="f-responsible"></div></div>
                    <div class="field"><label for="f-priority">Prioridade</label><select id="f-priority" name="priority"><option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option></select></div>
                    <div class="field"><label for="f-status">Situação</label><select id="f-status" name="status"><option value="pendente">Pendente</option><option value="andamento">Em andamento</option><option value="concluida">Concluída</option></select></div>
                    <div class="field"><label for="f-requester">Demandante</label><input id="f-requester" name="requester" maxlength="80" placeholder="Ex.: Cmt do 25º BC"></div>
                    <div class="field" id="completedByField" style="display:none"><label for="f-completed_by">Concluída por</label><select id="f-completed_by" name="completed_by"></select></div>
                    <div class="field full"><label for="f-notes">Observações</label><textarea id="f-notes" name="notes" maxlength="500" placeholder="Informações importantes para o cumprimento da missão"></textarea></div>
                </div>
                <p class="form-error" id="formError"></p>
                <div class="form-actions">
                    <button type="button" class="secondary-btn danger-btn hidden" id="deleteBtn">Excluir</button>
                    <button type="button" class="secondary-btn" id="cancelBtn">Cancelar</button>
                    <button type="submit" class="primary-btn">Salvar missão</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================ TOAST ============================ --}}
    <div class="toast" id="toast"><span class="icon" data-icon="check"></span><span id="toastText">Feito.</span></div>

    @php
        $painelPeople = ['Asp Araújo', '3º Sgt Rodrigues Silva', 'Cb Luide', 'Sd EP Jones', 'Sd EP Ferreira Lima', 'Sd EP Edilson', 'Toda a seção'];
    @endphp
    <script>
        window.__PAINEL__ = {
            omName: @json(config('app.name') === 'Agenda de Missões' ? '25º Batalhão de Caçadores' : config('app.name')),
            omSigla: '25º BC',
            csrf: @json(csrf_token()),
            tvRotateSeconds: 12,
            people: @json($painelPeople),
            routes: {
                index: @json(route('missions.index')),
                store: @json(route('missions.store')),
                reset: @json(route('missions.reset')),
                update: @json(url('missions/__ID__')),
                destroy: @json(url('missions/__ID__')),
            }
        };
    </script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
