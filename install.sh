#!/usr/bin/env bash
#
# Instalador de primeiro uso da Agenda de Missões (VM/LXC offline, Proxmox).
#
# Rode DENTRO da pasta onde o bundle foi descompactado (ex.: /opt/agenda-missoes).
# É seguro rodar de novo (idempotente): não sobrescreve .env nem Caddyfile já
# existentes, e recria o serviço systemd do zero a cada execução.
#
# Uso:
#   sudo ./install.sh
#
# Variáveis de ambiente opcionais:
#   SERVICE_USER=www-data   # usuário do sistema que roda o serviço (criado se não existir)
#   HTTP_PORT=80            # porta HTTP do Caddyfile gerado na primeira instalação

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "ERRO: rode como root (sudo ./install.sh)." >&2
    exit 1
fi

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_USER="${SERVICE_USER:-www-data}"
HTTP_PORT="${HTTP_PORT:-80}"
cd "$APP_DIR"

echo "==> Instalando a Agenda de Missões em $APP_DIR"

if [ ! -f "./frankenphp" ]; then
    echo "ERRO: não encontrei ./frankenphp na raiz do bundle. Gere o bundle com build-bundle.ps1" >&2
    echo "      (ele baixa o binário automaticamente) ou copie-o manualmente para $APP_DIR." >&2
    exit 1
fi
chmod +x ./frankenphp

if ! id "$SERVICE_USER" >/dev/null 2>&1; then
    echo "==> Usuário de sistema '$SERVICE_USER' não existe, criando (sem shell de login)..."
    useradd --system --no-create-home --shell /usr/sbin/nologin "$SERVICE_USER"
fi

if [ ! -f "./Caddyfile" ]; then
    echo "==> Gerando Caddyfile (porta $HTTP_PORT)..."
    cat > "./Caddyfile" <<EOF
{
	frankenphp
}

:$HTTP_PORT {
	root public/
	encode zstd br gzip
	php_server {
		try_files {path} index.php
	}
}
EOF
else
    echo "==> Caddyfile já existe, mantendo (edite-o manualmente se precisar mudar a porta)."
fi

if [ ! -f "./.env" ]; then
    echo "==> Criando .env a partir de .env.production.example e gerando APP_KEY..."
    cp "./.env.production.example" "./.env"
    ./frankenphp php-cli artisan key:generate --force
else
    echo "==> .env já existe, mantendo (não mexo em configuração existente)."
fi

echo "==> Conferindo extensão pdo_sqlite no binário do FrankenPHP..."
./frankenphp php-cli -m 2>/dev/null | grep -i sqlite || \
    echo "    (não consegui confirmar automaticamente — se o app der erro de banco, verifique manualmente)"

echo "==> Rodando migrations..."
./frankenphp php-cli artisan migrate --force

echo "==> Gerando caches de produção..."
./frankenphp php-cli artisan config:cache
./frankenphp php-cli artisan route:cache
./frankenphp php-cli artisan view:cache

echo "==> Ajustando dono dos arquivos para '$SERVICE_USER'..."
chown -R "$SERVICE_USER":"$SERVICE_USER" "$APP_DIR"

echo "==> Instalando serviço systemd (agenda-missoes)..."
cat > /etc/systemd/system/agenda-missoes.service <<EOF
[Unit]
Description=Agenda de Missoes (FrankenPHP)
After=network.target

[Service]
Type=simple
User=$SERVICE_USER
WorkingDirectory=$APP_DIR
ExecStart=$APP_DIR/frankenphp run --config $APP_DIR/Caddyfile
Restart=on-failure
RestartSec=5
AmbientCapabilities=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now agenda-missoes
sleep 1
systemctl --no-pager --full status agenda-missoes || true

cat <<EOF

==================================================================
Instalação concluída.

Acesse: http://<ip-da-vm>:$HTTP_PORT/login
Usuário padrão: admin   |   Senha padrão: admin123

TROQUE A SENHA AGORA (obrigatório):
  cd $APP_DIR && sudo ./frankenphp php-cli artisan app:create-user --username=admin --admin

Backup diário do banco (recomendado) — adicionar ao crontab do root:
  0 2 * * * $APP_DIR/scripts/backup-sqlite.sh >> $APP_DIR/storage/logs/backup-sqlite.log 2>&1

Para atualizar o código depois de uma alteração, veja update.sh (DEPLOY.md).
==================================================================
EOF
