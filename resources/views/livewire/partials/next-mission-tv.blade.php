{{-- Igual a next-mission.blade.php, mas com as classes do modo monitor (.tv-next) --}}
@if ($next)
    <div class="label"><span>Próxima missão</span><i class="live-dot"></i></div>
    <h2>{{ $next['title'] }}</h2>
    <p>{{ $next['notes'] }}</p>
    <div class="grid">
        <div><span>Quando</span><strong class="mono">{{ $next['dateLabel'] }}, {{ $next['time'] }}</strong></div>
        <div><span>Responsável</span><strong>{{ $next['respNames'] }}</strong></div>
    </div>
    <div class="cd">{{ $next['countdown']['prefix'] }}<strong>{{ $next['countdown']['value'] }}</strong>{{ $next['countdown']['suffix'] }}</div>
@else
    <div class="label"><span>Próxima missão</span></div>
    <h2>Nenhuma missão pendente</h2>
    <p>A seção está com o planejamento em dia.</p>
@endif
