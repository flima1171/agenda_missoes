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

- **Fase em andamento:** Fase 3 concluída.
- **PRÓXIMA TAREFA:** Fase 4 — Seletor de responsáveis progressivo (um seletor +
  botão "+", sem JS à mão). Detalhes em `.claude/prompts/roadmap-mestre.md` › FASE 4.
- **Depois dela:** Fase 5 — Migrar a interface para Livewire, tela por tela.

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
- **2026-07-04** — **Fase 3 concluída — Cadastro de militares.** Antes de mexer,
  li de verdade `MissionController.php`, `Mission.php`, `routes/web.php` e
  `painel.blade.php` (confirmei `$painelPeople` na linha 200, não 198 como o
  ANDAMENTO antigo dizia — número de linha realmente pode desatualizar, como a
  regra avisa) e `public/js/app.js` (`PEOPLE`/`COMPLETERS` na linha 8-9, uso em
  `#f-responsible`/`#f-completed_by` na linha 499-500). Pesquisei a doc oficial
  ATUAL do Livewire antes de instalar — hoje (jul/2026) o composer resolve
  `livewire/livewire` para a **v4.3.3** (major mudou de v3 pra v4 desde que o
  roadmap foi escrito; segui a doc atual em livewire.laravel.com/docs, não a
  da v3 que eu lembrava). Instalado `composer require livewire/livewire`.
  Criados: migration `2026_07_04_144412_create_militares_table` (`posto_graduacao`,
  `nome_guerra`, `ativo` bool, `ordem`, `telegram_id`/`telefone` opcionais);
  Model `App\Models\Militar` (`$table = 'militares'` explícito — o pluralizer do
  Eloquent não sabe português e ia gerar "militars"; scope `ativos()`; método
  `nomeExibicao()`); `MilitarSeeder` (popula os 6 militares que hoje estavam
  fixos em `$painelPeople`; "Toda a seção" **não** virou registro — não é um
  militar, não pode ser promovido/inativado, continua uma opção fixa somada na
  view) — registrado em `DatabaseSeeder` (roda antes do `MissionSeeder`, que
  continua só sendo chamado explicitamente pelo `reset()` em `local`). Criado o
  componente Livewire `App\Livewire\MilitaresManager` (classe +
  `resources/views/livewire/militares-manager.blade.php`, via
  `php artisan make:livewire MilitaresManager --class`) com listar, adicionar,
  editar, inativar/reativar (nunca apagar — sem método de delete no componente,
  de propósito) e reordenar (botões ↑/↓ trocando o campo `ordem` com o vizinho;
  sem drag-and-drop, mas 100% Livewire, sem JS escrito à mão). Nova rota
  `GET /militares` (`routes/web.php`) renderizando `resources/views/militares.blade.php`
  (página própria, com `@livewireStyles`/`@livewireScripts`, reaproveitando
  `public/css/app.css` — decidi NÃO reaproveitar a estrutura `.sidebar`/`.nav`
  do painel principal porque o CSS responsivo dela é talhado especificamente
  pro nav de 4 ícones do dashboard; tentei reaproveitar e quebrou em mobile,
  então troquei por um layout mais simples, só com `.card`/`.form-grid`/
  `.all-table` etc.). `resources/views/painel.blade.php`: troquei o array fixo
  `$painelPeople` por uma query (`\App\Models\Militar::ativos()->get()->map(...)
  ->push('Toda a seção')`) e adicionei o link "Militares" na sidebar (nova seção
  "Administração"). **DECISÃO DE DADOS** (já estava travada no roadmap, só
  confirmando que segui): missões continuam guardando o nome como texto
  (snapshot); promover/renomear um militar não reescreve missões antigas — não
  há nenhuma FK de missão pra militar, é só texto solto no campo `responsibles`.
  Também ajustei `build-bundle.ps1` e `DEPLOY.md` (Passo 1): o bundle de deploy
  agora roda `db:seed --class=MilitarSeeder` depois do `migrate` (sem isso, o
  próximo deploy real subiria sem NENHUM militar cadastrado, já que essa tabela
  é nova) — `MissionSeeder` continua de fora do bundle, de propósito, pra não
  levar missões de demonstração pra produção.
  **VERIFICADO no navegador** (`preview_start`, porta 8011): criei, editei
  (renomeei), inativei/reativei e reordenei militares de verdade pela tela
  `/militares`, conferindo o resultado direto no banco (`php artisan tinker`)
  a cada passo — bug encontrado e corrigido no meio do caminho: meu primeiro
  seletor de teste (`button:not(.danger-btn)`) sem querer clicava no botão
  ↑ em vez de "Editar" (os dois não têm a classe `danger-btn` quando o militar
  está inativo) — **não é bug do app**, era só a minha automação de teste
  mirando errado; refiz com seletor mais específico e confirmei que
  editar/inativar/reordenar funcionam certinho isoladamente. Confirmei que a
  lista de responsáveis no modal "Nova missão" bate exatamente com a antiga
  (`Asp Araújo, 3º Sgt Rodrigues Silva, Cb Luide, Sd EP Jones, Sd EP Ferreira
  Lima, Sd EP Edilson, Toda a seção`) e que inativar um militar o remove do
  seletor imediatamente. Testado em mobile/tablet/desktop — achei e corrigi
  uma quebra visual real: a tela `/militares` reaproveitava `.sidebar`/`.nav`
  do painel e, no breakpoint mobile (`@media max-width:760px`), a regra
  `.primary-btn { width:42px }` (pensada pro botão "Nova missão" virar
  ícone) espremia meu botão "Adicionar militar" num quadrado ilegível —
  corrigi com layout mais simples (sem sidebar) + uma regra aditiva
  `#militares-page .primary-btn { width:auto }` em `public/css/app.css`
  (não toquei na regra original, só sobrepus com mais especificidade só
  nesta página nova). Sem erros no console em nenhuma tela testada.
  Rodei `build-bundle.ps1` de novo de ponta a ponta e conferi o zip: banco
  com `militares`=6 (todos ativos, ordem 1-6) e `missions`=0.
  **PENDENTE:** nenhuma pendência de VM aqui (é tudo local/navegador); a
  mensagem de erro de validação (campo obrigatório) aparece em inglês
  ("The posto graduacao field is required.") porque o projeto nunca teve
  arquivos de tradução em `lang/pt_BR` — não é regressão minha, já era assim
  para as validações do `MissionController` também; não mexi nisso por estar
  fora do escopo da Fase 3.
  **À parte (fora do escopo desta fase, sinalizado como tarefa em segundo
  plano):** rodando `composer audit` depois de instalar o Livewire, apareceram
  3 advisories de segurança em `laravel/framework` (a instalada, v11.54.0, está
  na faixa afetada por um CVE de CRLF injection na regra de validação `email`
  padrão) — não é usado ativamente neste app (sem autenticação), mas vale
  avaliar upgrade; não fiz nada a respeito aqui, só registrei a tarefa.

---

## 🗒️ FILA DE FASES (visão geral; detalhe no roadmap-mestre.md)

- [x] **Fase 0** — Preparação: branch git + commit do estado atual + ler arquivos-chave.
- [x] **Fase 1** — Blindagem de produção: bloquear `/missions/reset` fora de `local`,
      esconder botão `#resetBtn`, `.env.production.example`, script de backup do SQLite.
- [x] **Fase 2** — Deploy offline: `build-bundle.ps1` + `DEPLOY.md` (FrankenPHP, fallback
      de proxy, systemd, conferir extensão pdo_sqlite).
- [x] **Fase 3** — Cadastro de militares: tabela `militares` + CRUD Livewire (inativar em
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
- View do painel: `resources/views/painel.blade.php`. `$painelPeople` (~linha 200)
  NÃO é mais um array fixo — vem de `\App\Models\Militar::ativos()` + `push('Toda a
  seção')`, injetado via `window.__PAINEL__` do mesmo jeito de antes.
- Calendário desenha só 07h–18h: `CAL_START`/`CAL_END` em `public/js/app.js` (~linha 16).
- `.env`: `APP_ENV=local`, `APP_DEBUG=true`. Sem autenticação.
- **Cadastro de militares (Fase 3):** tabela `militares` (migration
  `2026_07_04_144412_create_militares_table`), Model `App\Models\Militar`
  (`$table='militares'` explícito, scope `ativos()`, método `nomeExibicao()`).
  Tela de gestão: rota `GET /militares` → `resources/views/militares.blade.php`
  → componente Livewire `App\Livewire\MilitaresManager` (+ view em
  `resources/views/livewire/militares-manager.blade.php`). Sem delete — só
  inativa (`ativo=false`). Reordena trocando o campo `ordem` com o vizinho.
  "Toda a seção" NÃO é um registro em `militares`, é só um valor fixo somado
  na query do painel (não pode ser promovido/inativado).
- **Livewire instalado é a v4** (não v3) — `composer.json` já resolveu pra
  v4.3.3 em jul/2026. A doc oficial atual (livewire.laravel.com/docs) usa
  convenções diferentes da v3 (ex.: single-file components por padrão,
  `Route::livewire()`); usamos `make:livewire NomeDoComponente --class`
  (estilo clássico, classe separada) por ser mais previsível — confirme na
  doc oficial ATUAL antes de assumir qualquer sintaxe da v3/v4 em fases futuras.
- **Decisões travadas:** interface → **Livewire** (reaproveitar CSS, live via `wire:poll`);
  deploy → **FrankenPHP** (binário único, VM offline atrás de proxy).
- Deploy offline: `build-bundle.ps1` (raiz do projeto) gera o zip de produção
  (agora também roda `db:seed --class=MilitarSeeder`, sem isso o bundle subiria
  sem nenhum militar cadastrado — `MissionSeeder` continua fora, de propósito);
  `DEPLOY.md` (raiz) tem o procedimento completo pra VM.

---

## 🔁 COMO ATUALIZAR ESTE ARQUIVO (faça ao terminar cada tarefa)

1. Em **JÁ FEITO**, acrescente uma linha com a data, o que mudou (com caminhos de
   arquivo), como foi verificado e qualquer pendência.
2. Marque a fase concluída com `[x]` na **FILA DE FASES**.
3. Atualize **ESTADO ATUAL** (nova "PRÓXIMA TAREFA" e a seguinte).
4. Se descobriu que algum "FATO DO PROJETO" mudou, corrija-o.
5. Faça commit incluindo este arquivo. Depois PARE e peça revisão ao usuário.
