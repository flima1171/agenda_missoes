#!/usr/bin/env bash
#
# Backup do banco SQLite da Agenda de Missões.
# Copia database/database.sqlite para storage/app/backups com timestamp
# e apaga backups mais antigos que $RETENTION_DAYS dias.
#
# Uso manual:
#   ./scripts/backup-sqlite.sh
#
# Agendamento (cron na VM), backup diário às 02h mantendo 14 dias:
#   0 2 * * * /caminho/para/agenda_missoes/scripts/backup-sqlite.sh >> /caminho/para/agenda_missoes/storage/logs/backup-sqlite.log 2>&1

set -euo pipefail

RETENTION_DAYS="${RETENTION_DAYS:-14}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DB_FILE="$PROJECT_DIR/database/database.sqlite"
BACKUP_DIR="$PROJECT_DIR/storage/app/backups"

if [ ! -f "$DB_FILE" ]; then
    echo "[backup-sqlite] ERRO: banco não encontrado em $DB_FILE" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
DEST="$BACKUP_DIR/database-$TIMESTAMP.sqlite"

# sqlite3 .backup faz uma cópia consistente mesmo com o banco em uso e já
# faz o checkpoint do WAL — é o caminho preferido. Se o binário
# sqlite3 não estiver disponível, cai para cp simples; nesse caso, como o
# banco roda em modo WAL, é preciso copiar TAMBÉM os arquivos -wal e -shm
# junto (com o mesmo nome-base do destino), senão o backup ficaria sem as
# escritas ainda não integradas ao arquivo principal.
if command -v sqlite3 >/dev/null 2>&1; then
    sqlite3 "$DB_FILE" ".backup '$DEST'"
else
    cp "$DB_FILE" "$DEST"
    [ -f "$DB_FILE-wal" ] && cp "$DB_FILE-wal" "$DEST-wal"
    [ -f "$DB_FILE-shm" ] && cp "$DB_FILE-shm" "$DEST-shm"
fi

echo "[backup-sqlite] Backup criado: $DEST"

# Remove backups mais antigos que RETENTION_DAYS dias.
find "$BACKUP_DIR" -name 'database-*.sqlite' -type f -mtime "+$RETENTION_DAYS" -print -delete
