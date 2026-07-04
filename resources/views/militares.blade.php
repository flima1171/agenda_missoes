<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#101b17">
    <title>Militares — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
    <main id="militares-page" style="max-width: 900px; margin: 0 auto; padding: 28px 20px 60px">
        <header class="topbar">
            <div class="title-block">
                <p style="margin: 0 0 8px">
                    <a href="{{ route('painel') }}" class="text-btn" style="padding: 0">← Voltar ao painel</a>
                </p>
                <h1>Militares da seção</h1>
                <p>Quem entra, sai ou é promovido — sem editar código.</p>
            </div>
        </header>

        <livewire:militares-manager />
    </main>

    @livewireScripts
</body>
</html>
