<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-mark"><x-icon name="shield" /></div>
        <div>
            <strong>Painel de Missões</strong>
            <span>25º Batalhão de Caçadores</span>
        </div>
    </div>

    <h1>Entrar</h1>
    <p class="auth-sub">Acesso restrito à seção. Use suas credenciais.</p>

    @error('email')
        <div class="auth-error" role="alert">{{ $message }}</div>
    @enderror

    <form wire:submit="login">
        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" autofocus>
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
