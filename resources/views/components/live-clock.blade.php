@props(['long' => false, 'year' => true])
{{--
    Único trecho com expressão JS "à mão" na interface: o relógio/data ao
    vivo precisa atualizar a cada segundo sem round-trip ao servidor (wire:poll
    de 1 em 1 segundo seria pesado e desnecessário). Usa o Alpine.js que já
    vem embutido no bundle do Livewire (confirmado em
    vendor/livewire/livewire/dist/livewire.js, sem instalar nada extra).
    É só formatação de data/hora — nenhuma regra de negócio aqui.
--}}
@if ($long)
    <span x-data="{ t: '', showYear: @js($year) }" x-init="
        const u = () => {
            const opts = { weekday: 'long', day: '2-digit', month: 'long' };
            if (showYear) opts.year = 'numeric';
            t = new Date().toLocaleDateString('pt-BR', opts).replace(/^./, c => c.toUpperCase());
        };
        u(); setInterval(u, 30000);
    " x-text="t"></span>
@else
    <strong {{ $attributes->merge(['class' => 'mono']) }} x-data="{ t: '--:--' }" x-init="
        const u = () => { t = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }); };
        u(); setInterval(u, 1000);
    " x-text="t"></strong>
@endif
