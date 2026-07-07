{{--
    Sincroniza a classe .theme-dark do wrapper (#$root) com o que o
    partials.theme-preload decidiu no <head> — mesmo padrão de auth/login.blade.php.
--}}
<script>
    if (document.documentElement.dataset.theme === 'dark') {
        document.getElementById('{{ $root }}').classList.add('theme-dark');
    }
</script>
