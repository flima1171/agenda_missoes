# DEPLOY.md — Deploy offline na VM (Proxmox, Debian 11) com FrankenPHP

> A VM/LXC de produção fica na intranet da OM, atrás de um proxy que bloqueia
> a internet — sem `apt update`, sem baixar nada direto na VM. Estratégia:
> preparar TUDO (código + dependências PHP + binário do FrankenPHP) numa
> máquina COM internet e levar só um `.zip` para a VM. A VM só precisa saber
> descompactar um zip e rodar um script — não precisa de `apt`, Docker, nem
> PHP instalado à parte (o FrankenPHP já embute o PHP 8.5 com `pdo_sqlite`).

---

## Fluxo simplificado (use este)

### 1. Nesta máquina, a que TEM internet

```powershell
pwsh -File build-bundle.ps1
```

Gera `build/agenda-missoes-<timestamp>.zip` já com **tudo dentro**: código,
`vendor/` de produção, banco SQLite migrado (quadro de militares + admin
inicial, sem missões de demonstração), fontes self-hosted, o **binário do
FrankenPHP** (baixado automaticamente e cacheado em `build/.cache/` para não
baixar de novo nos próximos builds) e os scripts `install.sh`/`update.sh`.
Não mexe no `vendor/`, `.env` nem `database.sqlite` deste checkout de
desenvolvimento. Pré-requisitos aqui: PHP 8.2+ com `pdo_sqlite` e Composer no
PATH (e internet, só nesta etapa).

> Sem internet no momento do build? `pwsh -File build-bundle.ps1 -SkipFrankenPhp`
> gera o zip sem o binário — aí é preciso levá-lo à parte (ver
> [Apêndice B](#apêndice-b--baixar-o-frankenphp-manualmente)).

### 2. Levar o zip para a VM

Só este arquivo (pendrive, `scp`, o que for viável na rede da OM):
`build/agenda-missoes-<timestamp>.zip`.

### 3. Na VM — primeira instalação

```bash
sudo mkdir -p /opt/agenda-missoes
sudo unzip agenda-missoes-<timestamp>.zip -d /opt/agenda-missoes
cd /opt/agenda-missoes
sudo bash install.sh
```

(`bash install.sh` em vez de `./install.sh` porque o zip do Windows não
preserva a permissão de execução do arquivo.)

O `install.sh` faz tudo sozinho: confere/dá permissão de execução no
binário `frankenphp`, cria o usuário de sistema `www-data` se não existir,
gera o `Caddyfile` (porta 80) e o `.env` (com `APP_KEY` novo) se ainda não
existirem, roda `migrate`, gera os caches de produção, ajusta o dono dos
arquivos, instala e sobe o serviço systemd `agenda-missoes` (start automático
no boot, reinício automático em falha), e imprime no final a URL de acesso e
o lembrete para trocar a senha do admin. É **idempotente** — rodar de novo
não quebra nada (não sobrescreve `.env` nem `Caddyfile` já existentes).

Ao final, **troque a senha do admin** (usuário padrão `admin`, senha padrão
`admin123`) com o comando que o próprio script imprime:

```bash
cd /opt/agenda-missoes && sudo ./frankenphp php-cli artisan app:create-user --username=admin --admin
```

Para criar contas dos demais operadores da seção:

```bash
frankenphp php-cli artisan app:create-user --name="Fulano de Tal" --username=fulano          # usuário comum
frankenphp php-cli artisan app:create-user --name="Ciclano" --username=ciclano --admin       # administrador
```

> **Cuidado:** o papel (`is_admin`) é definido *só* pela flag `--admin`. Ao
> redefinir a senha de um admin existente, inclua sempre `--admin`, senão o
> comando o rebaixa para usuário comum.

Pronto — acesse `http://<ip-da-vm>/login`.

### 4. Atualizar depois de alterar o código

Repita o passo 1 nesta máquina (gera um zip novo com o código atualizado).
Na VM, descompacte o zip novo numa pasta **diferente** da instalação (não por
cima dela) e rode `update.sh` apontando para a instalação existente:

```bash
mkdir -p ~/agenda-update && cd ~/agenda-update
unzip ~/agenda-missoes-<timestamp-novo>.zip
sudo bash update.sh /opt/agenda-missoes
```

O `update.sh` faz backup do banco automaticamente antes de mexer em
qualquer coisa, para o serviço, substitui código/`vendor/`/binário do
FrankenPHP pela versão nova, **preserva** `.env`, `database/database.sqlite`
e tudo que já existe em `storage/` (sessões, logs, backups), roda `migrate`
e reconstrói os caches, e reinicia o serviço no final. Pode apagar a pasta
`~/agenda-update` depois.

---

## Apêndices — detalhamento manual, troubleshooting e o que fica por trás dos scripts

Os apêndices abaixo explicam o que `install.sh`/`update.sh` fazem
internamente (útil se algo der errado e for preciso rodar um passo à mão) e
cobrem cenários que os scripts não resolvem sozinhos. Trechos marcados
**PENDENTE DE VERIFICAÇÃO NO ALVO** só podem ser confirmados na VM real
(Proxmox VE 7.4-3 / Debian 11) — foram validados contra a documentação
oficial e, quando indicado, testados localmente.

### Apêndice A — o que o `install.sh` roda, passo a passo

Caso precise reproduzir manualmente (por exemplo, para depurar um erro):

```bash
cd /opt/agenda-missoes
chmod +x frankenphp

# Caddyfile (formato oficial de Laravel + FrankenPHP, https://frankenphp.dev/docs/laravel/):
cat > Caddyfile <<'EOF'
{
	frankenphp
}

:80 {
	root public/
	encode zstd br gzip
	php_server {
		try_files {path} index.php
	}
}
EOF
# Usamos :80 (sem nome de domínio) para não acionar o provisionamento
# automático de HTTPS via Let's Encrypt — sem sentido numa intranet sem
# domínio público.

cp .env.production.example .env
./frankenphp php-cli artisan key:generate --force
./frankenphp php-cli artisan migrate --force
./frankenphp php-cli artisan config:cache
./frankenphp php-cli artisan route:cache
./frankenphp php-cli artisan view:cache

# Confirma a extensão pdo_sqlite (a aplicação depende dela; os binários
# oficiais do FrankenPHP já vêm com ela, mas confirme no alvo):
./frankenphp php-cli -m | grep -i sqlite

chown -R www-data:www-data /opt/agenda-missoes
```

Unit systemd instalada pelo script em `/etc/systemd/system/agenda-missoes.service`:

```ini
[Unit]
Description=Agenda de Missoes (FrankenPHP)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/agenda-missoes
ExecStart=/opt/agenda-missoes/frankenphp run --config /opt/agenda-missoes/Caddyfile
Restart=on-failure
RestartSec=5
AmbientCapabilities=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

`AmbientCapabilities=CAP_NET_BIND_SERVICE` permite abrir a porta 80 sem rodar
como root. Ativar/depurar manualmente:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now agenda-missoes
sudo systemctl status agenda-missoes
sudo journalctl -u agenda-missoes -e   # logs do serviço
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:**
- Se o usuário `www-data` existe por padrão no Debian 11 minimal (o
  `install.sh` cria se não existir) e se `AmbientCapabilities` sozinho basta
  para abrir a porta 80 (alternativa: `setcap 'cap_net_bind_service=+ep' frankenphp`).
- Extensão `pdo_sqlite` de fato presente no binário baixado.
- Testar o restart automático (matar o processo e confirmar que o systemd
  sobe de novo) e o boot automático (reiniciar a VM/LXC).
- Caminho final de instalação (`/opt/agenda-missoes` é só uma sugestão) e
  `APP_URL`/timezone no `.env` gerado — ajuste conforme a convenção da OM.
- Permissão de leitura/escrita do usuário do serviço em `storage/` e
  `database/database.sqlite`, especialmente se a instalação for num LXC com
  mapeamento de UID diferente do esperado.

### Apêndice B — baixar o FrankenPHP manualmente

Só necessário se você rodou `build-bundle.ps1 -SkipFrankenPhp` (build sem
internet) ou se quiser confirmar/atualizar a versão à parte:

```bash
curl -L https://github.com/php/frankenphp/releases/latest/download/frankenphp-linux-x86_64-gnu -o frankenphp
chmod +x frankenphp
```

Isso reproduz o que o instalador oficial (`https://frankenphp.dev/install.sh`)
faz para Linux + glibc (Debian 11 se enquadra) — conferido lendo o script
diretamente. Não rode o instalador oficial completo direto na VM offline: ele
tenta usar `apt`/`dnf`/`apk`, que tentariam baixar de repositórios externos
inacessíveis atrás do proxy da OM.

Copie o binário baixado para dentro da pasta da instalação
(`/opt/agenda-missoes/frankenphp`) antes de rodar `install.sh`.

### Apêndice C — fallback: proxy da OM no `apt`/`composer`

Só é necessário se, em vez do binário levado manualmente, a equipe preferir
instalar o FrankenPHP direto na VM via pacote `.deb` (que já vem com systemd
configurado automaticamente). Isso exige que a VM enxergue a internet através
do proxy da OM.

`/etc/apt/apt.conf.d/95proxy` (criar):

```
Acquire::http::Proxy "http://usuario:senha@proxy.om.mil:PORTA/";
Acquire::https::Proxy "http://usuario:senha@proxy.om.mil:PORTA/";
```

Variáveis de ambiente padrão (para `composer`, `curl`, `wget` etc. — em
`/etc/environment` ou no perfil do usuário de deploy):

```
http_proxy=http://usuario:senha@proxy.om.mil:PORTA/
https_proxy=http://usuario:senha@proxy.om.mil:PORTA/
no_proxy=localhost,127.0.0.1
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:** endereço/porta/credenciais reais do
proxy da OM; e se o proxy libera os domínios usados pelo instalador
(`pkg.henderkes.com`, `rpm.henderkes.com`, `github.com`) mesmo configurado —
se não liberar, o caminho do binário manual (fluxo principal deste documento)
é o que funciona.

### Apêndice D — backup do SQLite

O `install.sh` já sugere a linha de cron no final da instalação. O script
`scripts/backup-sqlite.sh` (incluído no bundle) copia
`database/database.sqlite` para `storage/app/backups` com timestamp (usando
`sqlite3 .backup`, que faz o checkpoint do modo WAL corretamente) e apaga
backups com mais de 14 dias. Para agendar (backup diário às 02h):

```bash
sudo crontab -e
# adicionar a linha:
0 2 * * * /opt/agenda-missoes/scripts/backup-sqlite.sh >> /opt/agenda-missoes/storage/logs/backup-sqlite.log 2>&1
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:** confirmar se o binário `sqlite3` está
disponível no Debian da VM (`which sqlite3`); se não estiver, o script cai
para `cp` simples (funciona, mas é menos seguro que `.backup` com o banco em
uso — considerar `apt install sqlite3` se o proxy permitir, ou levar o
pacote manualmente).

### Apêndice E — SQLite em modo WAL

`config/database.php` liga WAL (`journal_mode=WAL`, `synchronous=NORMAL`,
`busy_timeout=5000`) para aguentar melhor as leituras concorrentes do
`wire:poll` sem travar escritas. O banco do bundle é gerado em modo `DELETE`
(arquivo único) e é convertido para WAL automaticamente no primeiro uso na
VM — nesse momento surgem os arquivos auxiliares `database.sqlite-wal` e
`database.sqlite-shm` ao lado do banco (efêmeros, não versionados).

### Resumo do que fica PENDENTE DE VERIFICAÇÃO NO ALVO (Debian 11 real)

- Extensão `pdo_sqlite` de fato presente no binário baixado.
- Se o proxy da OM libera os domínios do instalador oficial, caso optem pelo
  fallback via `apt` (Apêndice C).
- Permissões de arquivo/usuário e comportamento de restart da unit systemd.
- Caminho final de instalação e valor de `APP_URL` no `.env`.
