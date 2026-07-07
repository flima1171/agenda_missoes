#!/usr/bin/env bash
#
# Atualiza uma instalação existente da Agenda de Missões com o código de um
# bundle NOVO, sem apagar banco de dados (database/database.sqlite), .env
# nem backups. Faz backup do banco automaticamente antes de mexer em nada.
#
# Rode este script de DENTRO da pasta do bundle NOVO recém-descompactado
# (uma pasta DIFERENTE da instalação existente), apontando para a instalação
# existente:
#
#   sudo ./update.sh /opt/agenda-missoes
#
# Se omitir o caminho, usa /opt/agenda-missoes por padrão.

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "ERRO: rode como root (sudo ./update.sh [caminho-da-instalacao])." >&2
    exit 1
fi

NEW_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${1:-/opt/agenda-missoes}"

if [ ! -d "$APP_DIR" ]; then
    echo "ERRO: instalação existente não encontrada em $APP_DIR. Rode install.sh primeiro." >&2
    exit 1
fi
if [ "$NEW_DIR" = "$APP_DIR" ]; then
    echo "ERRO: descompacte o bundle NOVO em outra pasta (não dentro de $APP_DIR) antes de atualizar." >&2
    exit 1
fi

SERVICE_USER="$(systemctl show -p User --value agenda-missoes 2>/dev/null || true)"
[ -z "$SERVICE_USER" ] && SERVICE_USER="www-data"

echo "==> Atualizando $APP_DIR a partir de $NEW_DIR"

if [ -f "$APP_DIR/scripts/backup-sqlite.sh" ]; then
    echo "==> Backup do banco antes de atualizar..."
    "$APP_DIR/scripts/backup-sqlite.sh" || echo "    (aviso: backup falhou, continuando mesmo assim)"
fi

echo "==> Parando o serviço..."
systemctl stop agenda-missoes 2>/dev/null || true

# Itens substituídos pela versão nova. database/, storage/ (conteúdo já
# existente) e .env NÃO entram aqui de propósito — ficam intocados, é o que
# preserva os dados entre atualizações.
ITEMS=(app bootstrap config public resources routes scripts artisan composer.json composer.lock vendor frankenphp install.sh update.sh .env.production.example)

for item in "${ITEMS[@]}"; do
    src="$NEW_DIR/$item"
    [ -e "$src" ] || continue
    rm -rf "${APP_DIR:?}/$item"
    cp -a "$src" "$APP_DIR/$item"
done

# Copia só o que for NOVO em storage/ (ex.: subpastas que uma versão nova passe
# a exigir), sem sobrescrever nada que já exista (logs, sessões, backups).
if [ -d "$NEW_DIR/storage" ]; then
    cp -rn "$NEW_DIR/storage/." "$APP_DIR/storage/" 2>/dev/null || true
fi

chmod +x "$APP_DIR/frankenphp" "$APP_DIR/install.sh" "$APP_DIR/update.sh" "$APP_DIR/scripts"/*.sh 2>/dev/null || true

cd "$APP_DIR"
echo "==> Rodando migrations..."
./frankenphp php-cli artisan migrate --force

echo "==> Reconstruindo caches..."
./frankenphp php-cli artisan config:clear
./frankenphp php-cli artisan config:cache
./frankenphp php-cli artisan route:clear
./frankenphp php-cli artisan route:cache
./frankenphp php-cli artisan view:clear
./frankenphp php-cli artisan view:cache

echo "==> Ajustando dono dos arquivos para '$SERVICE_USER'..."
chown -R "$SERVICE_USER":"$SERVICE_USER" "$APP_DIR"

echo "==> Reiniciando o serviço..."
systemctl start agenda-missoes
sleep 1
systemctl --no-pager --full status agenda-missoes || true

echo ""
echo "==> Atualização concluída."
