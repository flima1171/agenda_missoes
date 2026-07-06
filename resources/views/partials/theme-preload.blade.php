{{--
    Fase A6 (achado 5.1): mesmo script que a tela de login (Fase A2) já usava
    pra aplicar o tema salvo ANTES do primeiro paint (evita o "flash" claro→
    escuro). Compartilhado por /militares e /usuarios, que — como o login —
    não têm um componente Livewire controlando $darkMode (isso só existe no
    Painel); o tema aqui é 100% client-side via localStorage.
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
</script>
