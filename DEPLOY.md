# DEPLOY.md — Deploy offline na VM (Proxmox, Debian 11) com FrankenPHP

> A VM de produção fica atrás de um proxy que bloqueia a maior parte da
> internet. Estratégia: preparar tudo (dependências PHP + binário do
> FrankenPHP) numa máquina COM internet e levar só o resultado para a VM.
> A VM só precisa saber descompactar um zip e rodar um binário — não precisa
> de `apt`, Docker, nem PHP instalado à parte (o FrankenPHP já embute o PHP).

Trechos marcados com **PENDENTE DE VERIFICAÇÃO NO ALVO** só podem ser
confirmados de fato na VM real (Proxmox VE 7.4-3 / Debian 11) — aqui foram
validados apenas contra a documentação oficial e, quando indicado, testados
localmente.

---

## Passo 1 — Gerar o bundle da aplicação (nesta máquina, com internet)

```powershell
pwsh -File build-bundle.ps1
```

Isso roda `composer install --no-dev --optimize-autoloader` numa cópia
temporária do projeto (não mexe no `vendor/` nem no `.env` deste checkout de
desenvolvimento), gera um `database/database.sqlite` novo já migrado e semeado
só com o **quadro de militares** (via `MilitarSeeder` — dado real da seção,
não demonstração) e compacta tudo em `build/agenda-missoes-<timestamp>.zip`.

O zip contém: código da aplicação, `vendor/` de produção, banco SQLite
migrado com o quadro de militares, `.env.production.example`. **Não contém**:
`.env`, `.git`, `tests/`, `.claude/`, backups locais, e **nenhuma missão de
demonstração** (`MissionSeeder` não roda no bundle de propósito).

---

## Passo 2 — Obter o binário do FrankenPHP (máquina com internet)

Os binários pré-compilados das releases oficiais já trazem o PHP embutido
(PHP 8.5) e não exigem nenhuma dependência do sistema quando se usa a
variante `-gnu` num Linux com glibc, como o Debian 11.

```bash
curl -L https://github.com/php/frankenphp/releases/latest/download/frankenphp-linux-x86_64-gnu -o frankenphp
chmod +x frankenphp
```

Isso reproduz exatamente o que o instalador oficial faz: o script
`https://frankenphp.dev/install.sh` detecta Linux + glibc (via
`getconf GNU_LIBC_VERSION`) e baixa esse mesmo asset
(`frankenphp-linux-x86_64-gnu`) da release mais recente do GitHub — conferido
lendo o script diretamente. Também é possível rodar o instalador oficial
inteiro (`curl https://frankenphp.dev/install.sh | sh`), mas ele tenta usar
`apt`/`dnf`/`apk` se detectar um desses gerenciadores — não use essa forma
direto na VM offline, pois ela tentaria baixar de repositórios externos (ver
Passo 4 sobre o fallback de proxy).

**Extensão `pdo_sqlite`** (a aplicação depende dela): os binários
pré-compilados das releases oficiais incluem `pdo_sqlite` e `sqlite3` por
padrão — confirmado na lista `defaultExtensions` do script oficial de build,
`https://github.com/php/frankenphp/blob/main/build-static.sh`. Mesmo assim,
**confirme no alvo** (comando no Passo 5).

---

## Passo 3 — Levar para a VM

Leve só estes dois arquivos (pendrive, `scp`, o que for viável na rede da
OM):

- `frankenphp` (binário único)
- `agenda-missoes-<timestamp>.zip` (gerado no Passo 1)

Na VM:

```bash
sudo mkdir -p /opt/agenda-missoes
sudo unzip agenda-missoes-<timestamp>.zip -d /opt/agenda-missoes
chmod +x frankenphp
sudo mv frankenphp /usr/local/bin/frankenphp
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:** caminho de instalação (`/opt/agenda-missoes`)
é uma sugestão — ajuste conforme a convenção da OM, e confirme que o usuário
que vai rodar o serviço (Passo 7) tem permissão de leitura/escrita ali
(especialmente em `storage/` e no `database.sqlite`).

---

## Passo 4 — Fallback: proxy da OM no `apt`/`composer`

Só é necessário se, em vez do binário levado manualmente (Passo 2-3), a
equipe preferir instalar o FrankenPHP direto na VM via pacote `.deb` (que já
vem com systemd configurado automaticamente — ver nota no Passo 7). Isso
exige que a VM enxergue a internet através do proxy da OM.

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
se não liberar, o caminho do binário manual (Passo 2-3) é o que funciona.

---

## Passo 5 — Rodar a aplicação com FrankenPHP

Dentro de `/opt/agenda-missoes`, crie um `Caddyfile` (formato confirmado na
documentação oficial de Laravel + FrankenPHP,
`https://frankenphp.dev/docs/laravel/`):

```caddyfile
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
```

> Usamos `:80` (sem nome de domínio) para não acionar o provisionamento
> automático de HTTPS via Let's Encrypt — não faz sentido numa intranet sem
> domínio público (a doc oficial de produção sugere o mesmo padrão,
> `SERVER_NAME=:80`, para desativar HTTPS automático).

Testar em primeiro plano:

```bash
cd /opt/agenda-missoes
frankenphp run --config Caddyfile
```

(`run` e a flag `--config <arquivo>` são documentados oficialmente.)

Confirmar que a extensão `pdo_sqlite` está mesmo presente neste binário:

```bash
frankenphp php-cli -m | grep -i sqlite
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:** a doc oficial confirma que `php-cli`
executa scripts/comandos como o PHP CLI normal, mas não achamos um exemplo
literal de `php-cli -m`. Se a flag `-m` não for aceita dessa forma, alternativas:
`frankenphp php-cli --ri pdo_sqlite`, ou rodar um script PHP com
`<?php var_dump(extension_loaded('pdo_sqlite'));` via
`frankenphp php-cli caminho/script.php`.

---

## Passo 6 — Migrar, gerar `APP_KEY` e caches de produção

```bash
cd /opt/agenda-missoes
cp .env.production.example .env
frankenphp php-cli artisan key:generate --force
frankenphp php-cli artisan migrate --force
frankenphp php-cli artisan config:cache
frankenphp php-cli artisan route:cache
frankenphp php-cli artisan view:cache
```

A sequência `key:generate` / `migrate` (via `php-cli`) é a documentada
oficialmente em "Laravel apps as standalone binaries"
(`https://frankenphp.dev/docs/laravel/`); `config:cache` / `route:cache` /
`view:cache` são comandos padrão do Artisan do Laravel.

Depois de criar o `.env`, confira/ajuste: `APP_URL` (IP ou nome interno da
VM na intranet) e o timezone (`APP_TIMEZONE=America/Sao_Paulo`, já vem
certo no `.env.production.example`).

O banco `database/database.sqlite` que veio no zip já está migrado (gerado
no Passo 1), então o `migrate --force` acima deve rodar sem pendências —
rode mesmo assim, para garantir que fica idempotente caso o schema mude numa
próxima versão do bundle.

---

## Passo 7 — systemd (subir no boot, reiniciar em falha)

Não encontramos publicado o conteúdo exato do arquivo `.service` que o
pacote `.deb`/`.rpm` oficial do FrankenPHP instala (o script
`install.sh` menciona que esse pacote já configura um serviço systemd usando
`/etc/frankenphp/Caddyfile`, mas o unit em si não está no repositório
público). Por isso, a unit abaixo é uma composição nossa, usando só os
comandos já confirmados acima (`frankenphp run --config`) — **trate como
rascunho e valide/ajuste na VM real**:

```ini
# /etc/systemd/system/agenda-missoes.service
[Unit]
Description=Agenda de Missoes (FrankenPHP)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/agenda-missoes
ExecStart=/usr/local/bin/frankenphp run --config /opt/agenda-missoes/Caddyfile
Restart=on-failure
RestartSec=5
AmbientCapabilities=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

`AmbientCapabilities=CAP_NET_BIND_SERVICE` permite abrir a porta 80 sem rodar
como root — alternativa ao `setcap 'cap_net_bind_service=+ep' frankenphp`
que o próprio `install.sh` oficial sugere quando não se roda como serviço.

Ativar:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now agenda-missoes
sudo systemctl status agenda-missoes
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:**
- Se o usuário `www-data` existe e tem permissão de leitura/escrita em
  `/opt/agenda-missoes` (`storage/`, `database/database.sqlite`) — ajustar
  dono dos arquivos com `chown -R www-data:www-data /opt/agenda-missoes` se
  necessário.
- Se `AmbientCapabilities` sozinho basta para abrir a porta 80, ou se também
  precisa de `CapabilityBoundingSet=CAP_NET_BIND_SERVICE`.
- Testar o restart automático (matar o processo e confirmar que o systemd
  sobe de novo) e o boot automático (reiniciar a VM).

---

## Resumo do que fica PENDENTE DE VERIFICAÇÃO NO ALVO (Debian 11 real)

- Extensão `pdo_sqlite` de fato presente no binário baixado (Passo 2 e 5).
- Se o proxy da OM libera os domínios do instalador oficial, caso optem pelo
  fallback via `apt` (Passo 4).
- Permissões de arquivo/usuário e comportamento de restart da unit systemd
  (Passo 7).
- Caminho final de instalação e valor de `APP_URL` no `.env` (Passo 3 e 6).

## Agendar o backup do SQLite na VM

O script `scripts/backup-sqlite.sh` (criado na Fase 1) já existe no bundle.
Agendar via cron do usuário que roda a aplicação:

```bash
crontab -e
# adicionar a linha (backup diário às 02h, mantendo 14 dias):
0 2 * * * /opt/agenda-missoes/scripts/backup-sqlite.sh >> /opt/agenda-missoes/storage/logs/backup-sqlite.log 2>&1
```

**PENDENTE DE VERIFICAÇÃO NO ALVO:** confirmar se o binário `sqlite3` está
disponível no Debian da VM (`which sqlite3`); se não estiver, o script cai
para `cp` simples (funciona, mas é menos seguro que `.backup` com o banco em
uso — considerar `apt install sqlite3` se o proxy permitir, ou levar o
pacote manualmente).
