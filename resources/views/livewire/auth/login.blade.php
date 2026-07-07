<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-mark"><img src="{{ asset('images/logo-comunicacoes.png') }}" alt=""></div>
        <strong>Painel de Missões</strong>
        <img class="auth-unit-mark" src="{{ asset('images/brasao-25bc.png') }}" alt="">
    </div>
    <p class="auth-brand-sub">25º Batalhão de Caçadores</p>

    <h1>Entrar</h1>
    <p class="auth-sub">Acesso restrito à seção. Use suas credenciais.</p>

    @error('username')
        <div class="auth-error" role="alert">{{ $message }}</div>
    @enderror

    <form wire:submit="login">
        <div class="field">
            <label for="username">Usuário</label>
            <input id="username" type="text" wire:model="username" autocomplete="username" autofocus>
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password">
        </div>

        <label class="auth-remember">
            <input type="checkbox" wire:model="remember">
            Manter-me conectado neste computador
        </label>

        <button type="submit" class="primary-btn" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login">Entrando…</span>
        </button>
    </form>
</div>
