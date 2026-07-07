{{--
    Botão de alternar tema para páginas sem componente Livewire de tema
    ($root = id do wrapper .painel-root da página, ex.: /militares e
    /usuarios — o painel principal tem seu próprio botão, ligado a
    Painel::toggleTheme()).
--}}
@props(['root'])
<button type="button" class="icon-btn theme-toggle-btn" title="Alternar modo escuro" aria-label="Alternar modo escuro"
    onclick="(function () { var root = document.getElementById('{{ $root }}'); var dark = root.classList.toggle('theme-dark'); try { localStorage.setItem('painel-theme', dark ? 'dark' : 'light'); } catch (e) {} })()">
    <x-icon name="moon" />
</button>
