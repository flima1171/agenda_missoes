<#
.SYNOPSIS
    Gera um "bundle offline" da Agenda de Missoes para levar a uma VM sem
    acesso a internet (ver DEPLOY.md).

.DESCRIPTION
    Copia o projeto para uma pasta temporaria, roda "composer install --no-dev"
    e migra um banco SQLite novo (schema, sem dados de demonstracao) dentro
    dessa copia -- SEM tocar no vendor/, no .env ou no database.sqlite deste
    checkout de desenvolvimento. Ao final, compacta tudo num .zip em .\build\.

    O zip NAO contem: .env, .git, .claude, tests/, backups locais.

.PARAMETER OutputDir
    Pasta onde o .zip final sera salvo. Padrao: .\build dentro do projeto.

.EXAMPLE
    pwsh -File build-bundle.ps1

.NOTES
    Pre-requisitos nesta maquina (a que TEM internet): PHP 8.2+ com extensao
    pdo_sqlite habilitada, e Composer no PATH.
#>

param(
    [string]$OutputDir
)

$ErrorActionPreference = "Stop"

$ProjectRoot = $PSScriptRoot
if (-not $OutputDir) {
    $OutputDir = Join-Path $ProjectRoot "build"
}

$Timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$StageDir = Join-Path $env:TEMP "agenda-missoes-bundle-$Timestamp"
$ZipPath = Join-Path $OutputDir "agenda-missoes-$Timestamp.zip"

function Assert-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Comando '$Name' nao encontrado no PATH. Instale antes de rodar este script."
    }
}

Assert-Command "php"
Assert-Command "composer"

Write-Host "==> Preparando pastas..."
New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
New-Item -ItemType Directory -Force -Path $StageDir | Out-Null

Write-Host "==> Copiando arquivos do projeto para staging (sem vendor/, .git, .claude, tests, .env)..."
$ItemsToCopy = @(
    "app", "bootstrap", "config", "database", "public", "resources", "routes",
    "scripts", "artisan", "composer.json", "composer.lock",
    ".env.production.example"
)
foreach ($item in $ItemsToCopy) {
    $src = Join-Path $ProjectRoot $item
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination (Join-Path $StageDir $item) -Recurse -Force
    }
}

# Fase A4: as fontes self-hosted (public/fonts/*.woff2) tem que ir no bundle,
# senao a UI cai para fonte do sistema na VM offline. Como sao servidas de
# public/ (ja copiado acima), so validamos que chegaram ao staging.
$StageFonts = Join-Path $StageDir "public\fonts"
$FontCount = @(Get-ChildItem -Path $StageFonts -Filter "*.woff2" -ErrorAction SilentlyContinue).Count
if ($FontCount -lt 1) {
    throw "Nenhuma fonte .woff2 encontrada em public/fonts/ do staging -- o app ficaria sem as fontes offline."
}
Write-Host "==> Fontes self-hosted incluidas: $FontCount arquivo(s) .woff2 em public/fonts/."

# storage/ precisa existir com a estrutura de pastas que o Laravel espera, mas
# sem conteudo dinamico de dev (sessoes, views compiladas, logs, backups).
Copy-Item -Path (Join-Path $ProjectRoot "storage") -Destination (Join-Path $StageDir "storage") -Recurse -Force
$DynamicDirs = @(
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\framework\cache\data",
    "storage\framework\testing",
    "storage\logs",
    "storage\app\backups",
    "storage\app\public",
    "storage\app\private"
)
foreach ($dir in $DynamicDirs) {
    $full = Join-Path $StageDir $dir
    if (Test-Path $full) {
        Get-ChildItem -Path $full -Force |
            Where-Object { $_.Name -ne ".gitignore" } |
            Remove-Item -Recurse -Force
    }
}

# O database.sqlite de dev tem dados de demonstracao/teste e NAO deve ir para
# o bundle -- um banco novo, so com o schema migrado, e gerado mais abaixo.
Get-ChildItem -Path (Join-Path $StageDir "database") -Filter "*.sqlite*" -File -ErrorAction SilentlyContinue |
    Remove-Item -Force

# .env temporario, so para o artisan conseguir rodar as migrations aqui no
# build. E APAGADO no finally abaixo (antes de zipar) -- cada instalacao real
# deve gerar sua propria APP_KEY na VM (ver DEPLOY.md). Definido ANTES do try
# para que o finally consiga remove-lo mesmo se algo falhar no meio.
$TempEnv = Join-Path $StageDir ".env"

Write-Host "==> Rodando 'composer install --no-dev' dentro do staging (nao afeta o vendor/ deste checkout)..."
Push-Location $StageDir
try {
    & composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer install --no-dev falhou (codigo $LASTEXITCODE)" }

    Write-Host "==> Gerando banco SQLite migrado (schema, sem dados de demonstracao)..."
    $StageDb = Join-Path $StageDir "database\database.sqlite"
    New-Item -ItemType File -Force -Path $StageDb | Out-Null

    Copy-Item (Join-Path $StageDir ".env.production.example") $TempEnv -Force
    # Fase A4: o banco de PRODUCAO roda em WAL (config/database.php), mas aqui
    # no build forcamos journal_mode=DELETE para o .sqlite gerado ser um arquivo
    # UNICO e consistente -- sem os auxiliares -wal/-shm no zip. Na VM, ao abrir
    # o banco, o Laravel aplica PRAGMA journal_mode=WAL e o converte no 1o uso.
    Add-Content -Path $TempEnv -Value "DB_JOURNAL_MODE=DELETE"

    & php artisan key:generate --ansi --force
    if ($LASTEXITCODE -ne 0) { throw "php artisan key:generate falhou" }

    & php artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw "php artisan migrate falhou" }

    # MilitarSeeder popula o quadro inicial de militares (dado real, nao
    # demonstracao -- sem ele o app sobe sem ninguem pra escolher como
    # responsavel). MissionSeeder NAO roda aqui de proposito: missoes de
    # demonstracao nao devem ir para producao.
    & php artisan db:seed --class=MilitarSeeder --force
    if ($LASTEXITCODE -ne 0) { throw "php artisan db:seed (MilitarSeeder) falhou" }

    # UserSeeder cria o administrador inicial (admin@25bc.local / senha padrao
    # a TROCAR no deploy via 'php artisan app:create-user' -- ver DEPLOY.md).
    # Sem ele o app subiria sem nenhum usuario e a tela de login travaria o acesso.
    & php artisan db:seed --class=UserSeeder --force
    if ($LASTEXITCODE -ne 0) { throw "php artisan db:seed (UserSeeder) falhou" }
}
finally {
    if (Test-Path $TempEnv) { Remove-Item $TempEnv -Force }
    Pop-Location
}

Write-Host "==> Compactando bundle em $ZipPath ..."
if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
Compress-Archive -Path (Join-Path $StageDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal

Remove-Item $StageDir -Recurse -Force

Write-Host ""
Write-Host "Bundle gerado: $ZipPath"
Write-Host "Contem: codigo + vendor/ (producao) + database/database.sqlite migrado (sem dados de demo)."
Write-Host "NAO contem: .env, .git, tests/, backups locais, .claude."
Write-Host "Proximo passo: ver DEPLOY.md para levar este zip e o binario do FrankenPHP para a VM."
