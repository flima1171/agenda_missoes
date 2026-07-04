{{-- Card "Próxima missão" do painel (dashboard). Variante para o modo monitor: next-mission-tv.blade.php --}}
@if ($next)
    <div class="next-label"><span>Próxima missão</span><i class="live-dot"></i></div>
    <h2>{{ $next['title'] }}</h2>
    <p>{{ $next['notes'] }}</p>
    <div class="next-info">
        <div><span>Quando</span><strong>{{ $next['dateLabel'] }}, {{ $next['time'] }}</strong></div>
        <div><span>Responsável</span><strong>{{ $next['respNames'] }}</strong></div>
    </div>
    <div class="countdown">{{ $next['countdown']['prefix'] }}<strong>{{ $next['countdown']['value'] }}</strong>{{ $next['countdown']['suffix'] }}</div>
@else
    <div class="next-label"><span>Próxima missão</span></div>
    <h2>Nenhuma missão pendente</h2>
    <p>A seção está com o planejamento em dia.</p>
@endif
