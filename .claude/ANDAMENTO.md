# ANDAMENTO — Agenda de Missões (25º BC)

> Este é o **diário de bordo** do projeto. É a fonte da verdade sobre o que já foi
> feito e o que falta. O roteiro detalhado de CADA tarefa está em
> `.claude/prompts/roadmap-mestre.md` — este arquivo aqui só controla o ESTADO.

---

## ⛔ REGRAS INVIOLÁVEIS (leia antes de qualquer coisa)

1. **NÃO confie na sua memória nem no que este arquivo afirma sobre o código.**
   Antes de editar qualquer coisa, ABRA E LEIA o arquivo real (Read/Grep). Números de
   linha, nomes de classe e trechos citados aqui podem estar desatualizados.
2. **Faça APENAS a "PRÓXIMA TAREFA" abaixo.** Uma tarefa por vez. Não adiante fases.
3. **Prove no navegador** toda mudança de tela com os preview tools antes de dar por
   pronta. Nunca diga "deve funcionar" sem rodar.
4. **Trabalhe em branch git** e **faça commit** ao terminar a tarefa verificada.
5. **Ao terminar, ATUALIZE ESTE ARQUIVO** (seção "COMO ATUALIZAR" abaixo) e PARE para
   o usuário revisar. Não comece a próxima tarefa sem o "ok" dele.
6. **Seja honesto:** se algo falhou ou só pode ser testado na VM real, escreva isso
   aqui — não finja que está OK.
7. **Na dúvida sobre algo que altera dados** (ex: militar promovido reescreve missões
   passadas?), PERGUNTE ao usuário antes.

---

## 📍 ESTADO ATUAL

- **Fase em andamento:** Fase 2 concluída.
- **PRÓXIMA TAREFA:** Fase 3 — Cadastro de militares (tabela `militares` + CRUD
  Livewire, inativar em vez de apagar, missões guardam nome como snapshot).
  Detalhes em `.claude/prompts/roadmap-mestre.md` › FASE 3.
- **Depois dela:** Fase 4 — Seletor de responsáveis progressivo.

---

## ✅ JÁ FEITO (registro cronológico — só acrescente, nunca apague)

- **2026-07-04** — Corrigido overflow das caixas do calendário (texto vazava para a
  coluna vizinha). Mudanças: `public/css/app.css` (`min-width:0` em `.day-cell` e
  `.cal-mission`) e `public/js/app.js` (atributo `title` com texto completo na
  `.cal-mission`). VERIFICADO no navegador (calendário normal + modo monitor, em
  mobile/tablet/desktop).
- **2026-07-04** — **Fase 0 concluída.** Criada a branch `evolucao/roadmap` a partir
  de `main`. Commit `f8a713a` com todo o estado acumulado (fix do overflow do
  calendário, migrations `2025_01_02_000000_add_previous_status_to_missions_table` e
  `2025_01_03_000000_convert_responsible_to_responsibles`, `composer.lock` e demais
  ajustes pendentes). Lidos e conferidos contra o código real: `MissionController.php`,
  `Mission.php`, `routes/web.php`, `painel.blade.php`, `.env` — todos batem com o que
  o ANDAMENTO/roadmap descreviam (`#resetBtn` na linha 48 do blade, `$painelPeople` na
  linha 198, `CAL_START`/`CAL_END` na linha 16 de `app.js`). Subi o app com
  `preview_start` (porta 8010, pois a 8000 estava ocupada por outra sessão — ajustado
  `.claude/launch.json` para usar `--port=8010`) e confirmei visualmente que o painel
  carrega sem erros de console.
- **2026-07-04** — **Fase 1 concluída — Blindagem de produção.** Mudanças:
  `app/Http/Controllers/MissionController.php` (método `reset()` agora chama
  `abort(404)` quando `app()->environment('local')` é falso, antes de apagar
  qualquer missão); `resources/views/painel.blade.php` (botão `#resetBtn` só é
  renderizado dentro de `@if (app()->environment('local'))`); `public/js/app.js`
  (linha do `bind()` que faz `$('#resetBtn').onclick = resetData` agora checa se o
  elemento existe antes, pra não quebrar o resto do binding de eventos quando o
  botão não existe fora de `local`); criado `.env.production.example`
  (`APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` em branco pra gerar na VM,
  mesmo `DB_CONNECTION=sqlite`); criado `scripts/backup-sqlite.sh` (copia
  `database/database.sqlite` para `storage/app/backups/database-<timestamp>.sqlite`
  usando `sqlite3 .backup` quando disponível, com fallback pra `cp`; apaga backups
  com mais de `RETENTION_DAYS` dias, padrão 14; comentário no topo do script com o
  exemplo de linha de cron); `.gitignore` (ignora `/storage/app/backups`).
  **VERIFICADO:** rodei o script localmente via Git Bash e ele gerou o backup (usando
  o fallback `cp`, pois este ambiente Windows não tem `sqlite3` no PATH — em produção
  na VM Debian isso deve ser conferido, idealmente instalando `sqlite3` pra ter o
  backup consistente via `.backup` em vez do `cp` simples). Troquei `APP_ENV` no
  `.env` para `production` e testei de ponta a ponta com `curl` direto no servidor
  (depois de REINICIAR o `php artisan serve` — importante: o processo antigo não
  pega mudança de `.env` em quente, então qualquer teste de ambiente precisa
  reiniciar o servidor): confirmado `#resetBtn` ausente do HTML e
  `POST /missions/reset` retornando 404. Revertido `.env` para `local` e reiniciado
  de novo: confirmado no navegador (`preview_screenshot` + `preview_console_logs`)
  que o botão volta a aparecer e não há erros de console. Ajustado
  `.claude/launch.json` para porta `8011` (a 8010 estava em uso por outra sessão de
  chat rodando em paralelo).
  **PENDENTE:** o script de backup só foi testado com fallback `cp` neste ambiente
  Windows; na VM Debian real, confirmar se `sqlite3` está instalado (ou instalar) pra
  usar o modo `.backup` mais seguro. O agendamento via cron ainda não foi testado de
  fato (só documentado no cabeçalho do script) — só será possível validar na VM.
- **2026-07-04** — **Fase 2 concluída — Pipeline de deploy offline (FrankenPHP).**
  Antes de escrever qualquer comando, pesquisei a documentação oficial do FrankenPHP
  (frankenphp.dev/docs, github.com/php/frankenphp — inclusive lendo o `install.sh`
  oficial e o `build-static.sh` direto do repositório) para não inventar nomes de
  asset/flags. Criados: `build-bundle.ps1` (copia o projeto pra uma pasta temporária,
  roda `composer install --no-dev --optimize-autoloader` **nessa cópia** — sem tocar
  no `vendor/` nem no `.env` do checkout de dev —, recria um `database/database.sqlite`
  novo e migrado, sem os dados de demonstração, via um `.env` temporário
  (`.env.production.example` + `key:generate`) que é apagado antes de zipar, e
  compacta tudo em `build/agenda-missoes-<timestamp>.zip`); `DEPLOY.md` com o
  procedimento completo (baixar `frankenphp-linux-x86_64-gnu` das releases oficiais —
  confirmado no `install.sh` que é esse o asset certo pra Debian/glibc; `pdo_sqlite` e
  `sqlite3` confirmados na lista `defaultExtensions` do `build-static.sh` oficial;
  Caddyfile pro Laravel; fallback de proxy da OM em `apt`/variáveis `http_proxy`;
  unit systemd; `migrate --force` + `config:cache`/`route:cache`/`view:cache`;
  agendamento do `backup-sqlite.sh` via cron). Trechos que a documentação oficial não
  cobre (ex: conteúdo exato do `.service` do pacote `.deb`, se `frankenphp php-cli -m`
  funciona igual ao `php -m`) foram marcados explicitamente como "PENDENTE DE
  VERIFICAÇÃO NO ALVO" — não inventei nada em cima disso. Adicionado `/build` ao
  `.gitignore`. **VERIFICADO localmente:** rodei `build-bundle.ps1` de verdade (não só
  li o código) — gerou o zip; extraí e conferi: sem `.env`, `.git`, `.claude`,
  `tests/`; `vendor/` só com dependências de produção (sem `phpunit`); banco SQLite
  migrado com `SELECT count(*) FROM missions` = 0 (sem dados de demonstração);
  `storage/framework/{sessions,views}` e `storage/logs` só com o `.gitignore`
  placeholder. Confirmei que o `vendor/` e o `.env` deste checkout de desenvolvimento
  não foram alterados pelo script (`git status` limpo, `.env` continua `APP_ENV=local`).
  **PENDENTE (só verificável na VM real, listado também no fim do `DEPLOY.md`):**
  extensão `pdo_sqlite` de fato presente no binário baixado; se o proxy da OM libera
  os domínios do instalador oficial (fallback via `apt`); permissões/usuário e
  comportamento de restart da unit systemd (ela é uma composição nossa, não o arquivo
  oficial do pacote `.deb`, que não está publicado); caminho final de instalação e
  `APP_URL`. **Não testei rodar o binário do FrankenPHP nem o zip na VM** — esta fase
  só cobre o pipeline de preparação, que roda 100% nesta máquina Windows.

---

## 🗒️ FILA DE FASES (visão geral; detalhe no roadmap-mestre.md)

- [x] **Fase 0** — Preparação: branch git + commit do estado atual + ler arquivos-chave.
- [x] **Fase 1** — Blindagem de produção: bloquear `/missions/reset` fora de `local`,
      esconder botão `#resetBtn`, `.env.production.example`, script de backup do SQLite.
- [x] **Fase 2** — Deploy offline: `build-bundle.ps1` + `DEPLOY.md` (FrankenPHP, fallback
      de proxy, systemd, conferir extensão pdo_sqlite).
- [ ] **Fase 3** — Cadastro de militares: tabela `militares` + CRUD Livewire (inativar em
      vez de apagar; missões guardam nome como snapshot, não reescrevem histórico).
- [ ] **Fase 4** — Seletor de responsáveis progressivo (um + botão "+"), sem JS à mão.
- [ ] **Fase 5** — Migrar a interface para Livewire, tela por tela, reaproveitando o CSS.
- [ ] **Fase 6** — Corrigir bug do calendário (missões fora de 07h–18h somem).

---

## 🔑 FATOS DO PROJETO A RECONFERIR SEMPRE (mapa de onde as coisas estão)

> Confirme lendo o arquivo — não assuma que continua exato.

- Laravel 11, PHP 8.2, banco **SQLite** (`database/database.sqlite`). Sem npm/Vite.
- Rotas da API interna: `routes/web.php` (`/missions` CRUD + `/missions/reset`).
- Controller: `app/Http/Controllers/MissionController.php` (método `reset()` apaga TUDO).
- Model: `app/Models/Mission.php` (campo `responsibles` = array de strings).
- Interface ATUAL toda em JS: `public/js/app.js`. CSS: `public/css/app.css`.
- View única: `resources/views/painel.blade.php`. Lista fixa de militares em
  `$painelPeople` (~linha 198), injetada via `window.__PAINEL__`.
- Calendário desenha só 07h–18h: `CAL_START`/`CAL_END` em `public/js/app.js` (~linha 16).
- `.env`: `APP_ENV=local`, `APP_DEBUG=true`. Sem autenticação.
- **Decisões travadas:** interface → **Livewire** (reaproveitar CSS, live via `wire:poll`);
  deploy → **FrankenPHP** (binário único, VM offline atrás de proxy).
- Deploy offline: `build-bundle.ps1` (raiz do projeto) gera o zip de produção;
  `DEPLOY.md` (raiz) tem o procedimento completo pra VM.

---

## 🔁 COMO ATUALIZAR ESTE ARQUIVO (faça ao terminar cada tarefa)

1. Em **JÁ FEITO**, acrescente uma linha com a data, o que mudou (com caminhos de
   arquivo), como foi verificado e qualquer pendência.
2. Marque a fase concluída com `[x]` na **FILA DE FASES**.
3. Atualize **ESTADO ATUAL** (nova "PRÓXIMA TAREFA" e a seguinte).
4. Se descobriu que algum "FATO DO PROJETO" mudou, corrija-o.
5. Faça commit incluindo este arquivo. Depois PARE e peça revisão ao usuário.
