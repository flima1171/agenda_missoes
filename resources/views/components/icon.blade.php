{{-- Fase A6 (achado 5.2): todo ícone aqui é puramente decorativo — o nome
     acessível do controle vem do texto visível ou do aria-label do próprio
     botão/link, nunca do SVG. Por isso aria-hidden fica ligado por padrão;
     o prop existe só para o caso (hoje inexistente) de um ícone precisar
     ser o único conteúdo semântico de algo. --}}
@props(['name', 'decorative' => true])
@php
    // Mesmos ícones SVG que existiam em ICONS (public/js/app.js), só que
    // renderizados pelo Blade em vez de injetados via innerHTML pelo JS.
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="3"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6M9 18h4"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'refresh' => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 9A7 7 0 0 1 18.5 7.5L20 12M4 12l1.5 4.5A7 7 0 0 0 17.9 15"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'edit' => '<path d="m4 20 4.5-1 10-10a2 2 0 0 0-3-3l-10 10L4 20Zm10-13 3 3"/>',
        'flag' => '<path d="M5 21V4M5 5h11l-2 4 2 4H5"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.6 2.9 8 7 10 4.1-2 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'moon' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'icon']) }} @if ($decorative) aria-hidden="true" @endif><svg viewBox="0 0 24 24">{!! $paths[$name] ?? '' !!}</svg></span>
