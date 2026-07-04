{{-- $grid vem de Painel::buildWeekGrid(): reaproveitado pelo calendário normal
     e pelo modo monitor do calendário ($grid['interactive'] = false lá). --}}
<div class="cal-corner"></div>
@foreach ($grid['days'] as $day)
    <div class="cal-head {{ $day['isToday'] ? 'today' : '' }} {{ $day['isWeekend'] ? 'weekend' : '' }}">
        {{ $day['label'] }}<strong>{{ $day['dayNum'] }}</strong>
    </div>
@endforeach
@foreach ($grid['hours'] as $h)
    <div class="time-cell">{{ sprintf('%02d:00', $h) }}</div>
    @foreach ($grid['days'] as $i => $day)
        <div class="day-cell {{ $day['isToday'] ? 'today' : '' }} {{ $day['isWeekend'] ? 'weekend' : '' }}"
            @if ($grid['interactive']) wire:dblclick="openNew('{{ $day['iso'] }}', '{{ sprintf('%02d:00', $h) }}')" @endif>
            @foreach ($grid['cells'][$h][$i] as $item)
                <div class="cal-mission {{ $item['priority'] }}"
                    @if ($grid['interactive']) wire:click="openEdit({{ $item['id'] }})" @endif
                    title="{{ $item['time'].' · '.$item['title'].' — '.$item['respNames'] }}">
                    <strong>{{ $item['time'] }} · {{ $item['title'] }}</strong>
                    <span>{{ $item['respNames'] }}</span>
                </div>
            @endforeach
        </div>
    @endforeach
@endforeach
