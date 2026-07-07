<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d1b2a">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <title>Agenda de Missões — {{ config('app.name') }}</title>
    {{-- Fontes self-hosted via @font-face em public/css/app.css (public/fonts/).
         Não há link ao Google Fonts — o app roda 100% offline. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <livewire:painel />

    {{--
        Única ponte de JS que sobrou (o resto é 100% Livewire/PHP). São 4
        coisas que só o navegador pode fazer, não o servidor:
        1) ler o tema salvo no localStorage e mandar pro componente ao carregar;
        2) persistir o tema no localStorage quando o componente avisa que mudou;
        3) pedir/sair da tela cheia (Fullscreen API) quando o modo monitor liga/desliga,
           e avisar o componente se o navegador sair da tela cheia sozinho (ex.: Esc).
        4) focar o 1º campo do modal ao abrir e prender o foco (Tab/Shift+Tab)
           dentro dele enquanto estiver aberto — acessibilidade que o DOM/CSS não
           fazem sozinhos.
        Nenhuma regra de negócio mora aqui — é só o encaixe dos eventos
        Livewire.dispatch()/Livewire.on() com APIs do navegador.
    --}}
    <script>
        // Registra os listeners assim que o Livewire inicializa...
        document.addEventListener('livewire:init', () => {
            Livewire.on('theme-changed', ({ dark }) => {
                try { localStorage.setItem('painel-theme', dark ? 'dark' : 'light'); } catch (e) {}
            });

            Livewire.on('enter-fullscreen', () => {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(() => {});
                }
            });
            Livewire.on('exit-fullscreen', () => {
                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
            });
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement) Livewire.dispatch('fullscreen-exited-natively');
            });

            // A6: ao abrir o modal (Painel::openNew()/openEdit()), foca o
            // primeiro campo. requestAnimationFrame espera o DOM já ter
            // recebido a classe "open" (o dispatch chega depois do morph).
            Livewire.on('modal-opened', () => {
                requestAnimationFrame(() => {
                    const first = document.querySelector('.modal-backdrop.open .modal #f-title');
                    if (first) first.focus();
                });
            });
        });

        // A6: prende o foco (Tab/Shift+Tab) dentro do modal enquanto ele
        // estiver aberto — não precisa de listener por abertura, só olha se
        // existe um ".modal-backdrop.open" no momento da tecla.
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') return;
            const modal = document.querySelector('.modal-backdrop.open .modal');
            if (!modal) return;

            const focusables = Array.prototype.filter.call(
                modal.querySelectorAll('input, select, textarea, button, [href], [tabindex]:not([tabindex="-1"])'),
                (el) => !el.disabled && el.offsetParent !== null
            );
            if (focusables.length === 0) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        // ...mas só manda o tema salvo depois que os componentes já hidrataram
        // (livewire:initialized), senão o #[On('set-initial-theme')] do
        // componente ainda não existe pra escutar e o dispatch se perde.
        document.addEventListener('livewire:initialized', () => {
            let savedTheme = null;
            try { savedTheme = localStorage.getItem('painel-theme'); } catch (e) {}
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            Livewire.dispatch('set-initial-theme', { dark: savedTheme ? savedTheme === 'dark' : prefersDark });
        });
    </script>
    @livewireScripts
</body>
</html>
