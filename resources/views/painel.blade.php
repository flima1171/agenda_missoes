<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#101b17">
    <title>Agenda de Missões — {{ config('app.name') }}</title>
    {{-- Fase A4: fontes self-hosted via @font-face em public/css/app.css (public/fonts/).
         Não há mais link ao Google Fonts — o app roda 100% offline. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <livewire:painel />

    {{--
        Fase 5: única ponte de JS que sobrou (o resto virou 100% Livewire/PHP).
        São 3 coisas que só o navegador pode fazer, não o servidor:
        1) ler o tema salvo no localStorage e mandar pro componente ao carregar;
        2) persistir o tema no localStorage quando o componente avisa que mudou;
        3) pedir/sair da tela cheia (Fullscreen API) quando o modo monitor liga/desliga,
           e avisar o componente se o navegador sair da tela cheia sozinho (ex.: Esc).
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
