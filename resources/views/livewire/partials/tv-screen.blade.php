{{-- $tv vem de Painel::buildTvData() --}}
<header class="tv-head">
    <div class="tv-brand">
        <div class="mark"><img src="{{ asset('images/logo-comunicacoes.png') }}" alt=""></div>
        <div><strong>Missões da Seção</strong><span>25º Batalhão de Caçadores</span></div>
    </div>
    <div class="tv-head-actions">
        <div class="tv-clock"><x-live-clock /><span><x-live-clock long :year="false" /></span></div>
        <button type="button" class="tv-theme-btn" title="Alternar modo escuro" aria-label="Alternar modo escuro" wire:click="toggleTheme"><x-icon :name="$darkMode ? 'sun' : 'moon'" /></button>
    </div>
</header>
<div class="tv-body">
    <div class="tv-left">
        <div class="tv-screen-head">
            <h2>{{ $tv['screen'] === 0 ? 'Missões de hoje' : 'Visão da semana' }}</h2>
            <div class="tv-dots"><i class="{{ $tv['screen'] === 0 ? 'on' : '' }}"></i><i class="{{ $tv['screen'] === 1 ? 'on' : '' }}"></i></div>
        </div>
        @if ($tv['screen'] === 0)
            <div class="tv-today">
                @forelse ($tv['todayList'] as $m)
                    <div class="tv-mission p-{{ $m['priority'] }} {{ $m['done'] ? 'done' : '' }}">
                        <div class="t">{{ $m['time'] }}</div>
                        <div class="m"><strong title="{{ $m['title'] }}">{{ $m['title'] }}</strong><span>{{ $m['respNames'] }}</span></div>
                        <span class="pill tv-pill s-{{ $m['status'] }}">{{ $m['statusLabel'] }}</span>
                    </div>
                @empty
                    <div class="tv-empty">Nenhuma missão para hoje. Seção em dia. ✓</div>
                @endforelse
            </div>
        @else
            <div class="tv-week">
                @foreach ($tv['weekDays'] as $d)
                    <div class="tv-day {{ $d['isToday'] ? 'today' : '' }}">
                        <div class="tv-day-head"><strong>{{ $d['label'] }}</strong><span>{{ $d['dateLabel'] }}</span></div>
                        <div class="tv-day-list">
                            @forelse ($d['items'] as $m)
                                <div class="tv-chip p-{{ $m['priority'] }} {{ $m['done'] ? 'done' : '' }}">
                                    <span class="h">{{ $m['time'] }}</span><strong>{{ $m['title'] }}</strong><span class="r">{{ $m['respNames'] }}</span>
                                </div>
                            @empty
                                <span style="color:#7c93a3;font-size:13px">—</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="tv-side">
        <article class="tv-next">
            @include('livewire.partials.next-mission-tv', ['next' => $tv['next']])
        </article>
        <article class="tv-prog">
            <div class="top"><span>Semana</span><strong class="mono">{{ $tv['week']['pct'] }}%</strong></div>
            <div class="bar"><div style="width: {{ $tv['week']['pct'] }}%"></div></div>
            <div class="labels"><span>{{ $tv['week']['doneWeek'] }} concluída{{ $tv['week']['doneWeek'] === 1 ? '' : 's' }}</span><span>{{ $tv['week']['total'] }} no total</span></div>
        </article>
        <button type="button" class="tv-exit" wire:click="exitMonitor">Sair do modo monitor (Esc)</button>
    </div>
</div>
