{{-- $m vem de Painel::buildMissionRows() --}}
<div class="mission-row p-{{ $m['priority'] }} {{ $m['kind'] === 'upcoming' ? 'upcoming' : '' }}"
     wire:key="mission-row-{{ $m['kind'] }}-{{ $m['id'] }}"
     wire:click="openEdit({{ $m['id'] }})" style="cursor:pointer">
    @if ($m['kind'] === 'upcoming')
        <div class="mission-time mono">{{ $m['dateLabel'] }} {{ $m['time'] }}</div>
    @else
        <div class="mission-time mono">{{ $m['time'] }}</div>
    @endif
    <div class="mission-main">
        <strong>{{ $m['title'] }}</strong>
        <div class="mission-meta">{{ $m['dateLabel'] }}{{ $m['requester'] ? ' · '.$m['requester'] : '' }}</div>
    </div>
    <div class="responsible">
        <div class="avatar-stack">
            @forelse ($m['avatars'] as $a)
                <div class="mini-avatar" title="{{ $a['name'] }}">{{ $a['initials'] }}</div>
            @empty
                <div class="mini-avatar">—</div>
            @endforelse
            @if ($m['more'] > 0)
                <div class="mini-avatar more">+{{ $m['more'] }}</div>
            @endif
        </div>
        <span>{{ $m['respNames'] }}</span>
    </div>
    @if ($m['kind'] === 'upcoming')
        <span class="status-pill s-{{ $m['actualStatus'] }}">{{ $m['statusLabel'] }}</span>
    @else
        <div class="status-cell">
            @if ($m['isOverdue'])
                <span class="overdue-tag">Atrasada</span>
            @endif
            {{-- stopPropagation: um clique pra abrir o <select> nao pode também abrir o modal de edição (mesma exclusão que o app.js antigo fazia via `!e.target.closest('select')`) --}}
            <select class="status-select s-{{ $m['actualStatus'] }}" onclick="event.stopPropagation()" wire:change="changeStatus({{ $m['id'] }}, $event.target.value)">
                <option value="pendente" @selected($m['status'] === 'pendente')>Pendente</option>
                <option value="andamento" @selected($m['status'] === 'andamento')>Em andamento</option>
                <option value="concluida" @selected($m['status'] === 'concluida')>Concluída</option>
            </select>
        </div>
    @endif
</div>
