<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d1b2a">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <title>Usuários — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
    @include('partials.theme-preload')
</head>
<body>
    <div id="usuarios-theme-root" class="painel-root">
        <x-theme-toggle root="usuarios-theme-root" />
        <main id="militares-page" style="max-width: 900px; margin: 0 auto; padding: 28px 20px 60px">
            <header class="topbar">
                <div class="title-block">
                    <p style="margin: 0 0 8px">
                        <a href="{{ route('painel') }}" class="text-btn" style="padding: 0">← Voltar ao painel</a>
                    </p>
                    <h1>Usuários do sistema</h1>
                    <p>Quem acessa o painel e quem administra a base.</p>
                </div>
            </header>

            <livewire:usuarios-manager />
        </main>
    </div>
    @include('partials.theme-sync', ['root' => 'usuarios-theme-root'])

    @livewireScripts
</body>
</html>
