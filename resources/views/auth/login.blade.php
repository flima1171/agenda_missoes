<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d1b2a">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <title>Entrar — Agenda de Missões</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
    {{--
        Aplica o tema salvo (localStorage) ANTES do paint pra não piscar
        claro→escuro. O toggle de tema completo é do painel; aqui só
        respeitamos a preferência já salva e deixamos alternar nesta tela.
    --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('painel-theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (t ? t === 'dark' : prefersDark) {
                    document.documentElement.dataset.theme = 'dark';
                }
            } catch (e) {}
        })();
        function toggleAuthTheme() {
            var root = document.getElementById('auth-root');
            var dark = root.classList.toggle('theme-dark');
            try { localStorage.setItem('painel-theme', dark ? 'dark' : 'light'); } catch (e) {}
        }
    </script>
</head>
<body>
    <div id="auth-root" class="painel-root auth-root">
        <button type="button" class="icon-btn auth-theme-btn" title="Alternar modo escuro" aria-label="Alternar modo escuro" onclick="toggleAuthTheme()">
            <x-icon name="moon" />
        </button>
        <livewire:auth.login />
    </div>

    <script>
        // Sincroniza a classe .theme-dark do wrapper com o que foi decidido no <head>.
        if (document.documentElement.dataset.theme === 'dark') {
            document.getElementById('auth-root').classList.add('theme-dark');
        }
    </script>
    @livewireScripts
</body>
</html>
