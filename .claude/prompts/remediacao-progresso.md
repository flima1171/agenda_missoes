# PROGRESSO — Remediação Agenda de Missões (25º BC)

> **Fonte da verdade sobre QUAL FASE executar agora.** O agent lê este arquivo,
> pega a **primeira fase ainda `⬜ pendente`**, executa-a seguindo o detalhe em
> `.claude/prompts/remediacao-mestre.md`, e ao concluí-la (verificada + commitada)
> troca `⬜ pendente` por `✅ concluída (AAAA-MM-DD)` e registra no log abaixo.
> Se TODAS estiverem `✅`, a remediação acabou — relate isso e pare.

## Estado
- **Branch de trabalho:** `remediacao/pos-auditoria` (criar a partir de `evolucao/roadmap` se ainda não existir; nunca trabalhar em `main`).
- **Regra:** uma fase por vez. Ao concluir uma fase: marcar aqui, `commit`, e **PARAR** para revisão do usuário.

## Fases (ordem obrigatória)
- ✅ concluída (2026-07-04) — **A0** — Rede de segurança: `phpunit.xml.dist` + `tests/` + testes de baseline do comportamento atual + Pint
- ✅ concluída (2026-07-04) — **A1** — Upgrade Laravel 12.60+ (com `composer audit` limpo, suíte verde, smoke no navegador)
- ✅ concluída (2026-07-04) — **A2** — Autenticação (login completo) + trilha de auditoria + remover a API REST `/missions`
- ✅ concluída (2026-07-05) — **A3** — Correções de lógica e validação em pt-BR
- ✅ concluída (2026-07-06) — **A4** — Offline & deploy: self-host das fontes, ligar WAL, revisar `build-bundle.ps1` e docs
- ✅ concluída (2026-07-06) — **A5** — Performance: paginação/escopo de queries e memoização
- ✅ concluída (2026-07-06) — **A6** — UX / acessibilidade
- ⬜ pendente — **A7** — Fechamento: suíte + Pint + audit + smoke completo + atualizar docs

## Log de conclusão (só acrescente, nunca apague)
- **2026-07-06 — A6 concluída.** Antes de mexer, li de verdade `painel.blade.php` (shell +
  componente), `militares.blade.php`/`usuarios.blade.php`, `icon.blade.php`, `app.css`
  (variáveis `--muted`, regras de `.theme-dark`) e todos os partials com botões só-ícone
  ou texto truncado. **(1) Tema escuro em `/militares` e `/usuarios` (achado 5.1):** essas
  páginas não tinham `.painel-root` nem ponte de tema — criados 2 novos partials
  (`resources/views/partials/theme-preload.blade.php`, reaproveitando o mesmo script que
  o login já usava para aplicar o tema ANTES do paint, e `theme-sync.blade.php`) + um
  componente `<x-theme-toggle root="...">` (`resources/views/components/theme-toggle.blade.php`)
  com botão fixo no canto superior direito (`.theme-toggle-btn`, nova regra CSS). As duas
  páginas ganharam um wrapper `<div class="painel-root" id="...-theme-root">`; o toggle é
  100% client-side (localStorage), sem tocar nos componentes Livewire `MilitaresManager`/
  `UsuariosManager` (que não têm noção de tema). **(2) Nomes acessíveis em botões só-ícone
  (achado 5.2):** `aria-label` adicionado em todos os botões cujo rótulo visível pode sumir
  (nav da sidebar e "Nova missão" no mobile — o texto vira `display:none`, que NÃO conta
  para o nome acessível) ou que nunca tiveram texto (tema, reset demo, editar linha da
  tabela, fechar modal "×", `‹`/`›` do calendário, remover responsável "×", mover militar
  ↑/↓, tema do login). O componente `<x-icon>` ganhou um prop `decorative` (default `true`)
  que sempre renderiza `aria-hidden="true"` — todo ícone do app é decorativo, o nome vem do
  botão/aria-label, nunca do SVG. **(3) Contraste do `--muted` no escuro (achado 5.3):**
  medi de verdade com `getComputedStyle` DENTRO do navegador rodando (não só matemática
  offline) — `.mission-meta` (10.5px) no tema escuro dá **6,07:1** contra o cartão
  (`rgb(147,164,155)` sobre `rgb(28,36,32)`) e no claro **4,92:1** — ambos folgadamente
  acima do mínimo AA (4,5:1). A estimativa da auditoria original ("≈3,7:1, abaixo de AA")
  estava desatualizada/imprecisa; **nenhuma mudança de cor foi necessária** — documentando
  para não repetir o mesmo achado por engano numa fase futura. **(4) Truncamento de título
  (achado 5.4):** `title="..."` com o texto completo adicionado no `<strong>` truncado de
  `mission-row.blade.php`, `tv-screen.blade.php` (missão do dia no modo monitor) e no nome
  do usuário no `.profile-info` da sidebar (o calendário já tinha isso desde a Fase 0).
  **(5) Modal acessível (achado 5.5):** `id="modal-title"` no `<h2>` + `aria-labelledby`
  no `.modal` (já tinha `role="dialog"`/`aria-modal`); `Painel::openNew()`/`openEdit()`
  passaram a disparar `$this->dispatch('modal-opened')`; o shell (`painel.blade.php`)
  ganhou um 4º item na ponte de JS (documentado no comentário): foca `#f-title` no
  `livewire:init` → `Livewire.on('modal-opened', ...)`, e um `keydown` global que PRENDE o
  foco (Tab/Shift+Tab) dentro do `.modal-backdrop.open .modal` enquanto ele existir — sem
  precisar de plugin extra do Alpine (offline, sem npm). **(6) Comentário desatualizado
  (achado 3.4):** o docblock de `ResponsibleSelector::setResponsibles()` dizia "recebe do
  JS, ainda em vanilla JS até a Fase 5" como se ainda fosse verdade — corrigido para
  descrever a realidade atual (recebe de `Painel::openNew()/openEdit()`, 100% Livewire
  desde a Fase 5).
  **Teste novo** `tests/Feature/AccessibilityTest.php` (8 testes): wrapper de tema em
  `/militares` e `/usuarios`, `<x-icon>` decorativo por padrão e desligável via prop,
  `aria-label` nos botões da sidebar/modal via `Livewire::test(Painel::class)`,
  `openNew`/`openEdit` disparam `modal-opened`, `aria-labelledby="modal-title"` presente,
  `aria-label` no botão de remover responsável e nos botões de reordenar militar.
  **57 testes / 161 asserções verdes** (era 49/139); `pint --test` limpo; `php -l` limpo
  nos arquivos PHP tocados; `migrate:status` sem mudança (fase não mexeu em schema).
  **Navegador** (porta 8013, logado como admin): modal "Nova missão" — foco inicial
  confirmado indo para `#f-title` (`document.activeElement.id`), trap de foco confirmado
  nos dois sentidos (Tab do último campo volta ao primeiro `.close-btn`; Shift+Tab do
  primeiro vai ao último `.primary-btn`) via `KeyboardEvent` sintético (a mesma
  peculiaridade de sempre: `preview_click` não disparava `wire:click` no botão "Nova
  missão"; `.click()` via JS funcionou). `/militares` e `/usuarios` testados em tema
  ESCURO e CLARO (toggle + persistência após reload via `localStorage`), e em mobile
  (375px — botão de tema não colide com o link "Voltar ao painel" nem com o formulário),
  tablet (768px, modal também testado nessa largura) e desktop (1280px). Zero erro de
  console em toda a sessão; `preview_network` sem nenhuma requisição a domínio externo
  (só `localhost:8013`, confirmando que a fase não regrediu o invariante offline da A4).
  Nenhum dado foi alterado (só toggles de tema e abrir/fechar modal sem salvar) — banco
  seguiu com `missions=8, militares=6, users=1` (mesma baseline de demonstração), sem
  necessidade de restaurar backup.
  **PENDENTE:** nenhuma pendência de VM nesta fase (é tudo CSS/Blade/JS local). Segue para
  a A7 (fechamento).
- **2026-07-06 — A5 concluída.** Antes de mexer, li `app/Livewire/Painel.php` (817 linhas) inteiro e os partials `calendar-grid`/`tv-screen` para confirmar que a TV e o calendário mostram missões concluídas também (classe `done`), não só as pendentes — isso definiu o desenho da fase. **(1) Escopo de queries por janela de data** (achado 4.1): `render()` trocou o único `Mission::orderBy(...)->get()` (carregava a tabela inteira, incluindo anos de histórico) por 4 consultas focadas: `$open` (não concluídas, qualquer data — necessário porque uma missão "atrasada" pode ser antiga, confirmado pelo teste da A3 com data de 2020), `$todayAny` (só hoje, qualquer situação), `$weekMissions` (só a semana ATUAL, qualquer situação — usada por `weekData`/TV) e `$calWindow` (só a semana NAVEGADA do calendário; reaproveita `$weekMissions` quando coincidem). `buildStats`/`buildTvData` foram ajustados para receber essas coleções já escopadas em vez da coleção gigante. **(2) Paginação/limite** (achado 4.1): "Todas as missões" e "Concluídas" ganharam `$tableLimit`/`$historyLimit` (50 cada, constante `LIST_PAGE_SIZE`) com botão "Carregar mais" (`loadMoreTable`/`loadMoreHistory`, +50 por clique); trocar o filtro segmentado reinicia o limite da tabela. O histórico agora busca do banco já ORDER BY DESC + LIMIT (`historyLimit`), não mais a tabela inteira. **(3) Memoização** (achado 4.2): `people()`, `completers()` e `weekData()` cacheiam o resultado num campo privado durante o MESMO `render()` (o componente Livewire é recriado a cada request, então o cache não vaza entre requisições) — eliminava a query duplicada de `Militar::ativos()` (rodava 2× por render) e o recálculo de `weekData` (rodava 2-3× por render). **(4) Índice composto opcional** (achado 4.3): migration nova `add_date_time_index_to_missions_table` — `index(['date','time'])`. **(5) `wire:poll` avaliado, não alterado:** os intervalos (15s dashboard / 12s TV) já eram adequados e mudar a frequência alteraria comportamento visível sem necessidade — o ganho real veio de reduzir o TAMANHO da consulta, não a frequência do poll.
  **Teste novo** `tests/Feature/PainelPerformanceTest.php` (5 testes): paginação de "Todas as missões" e "Concluídas" com "carregar mais", reset do limite ao trocar filtro, calendário só traz missões da semana exibida (escopo de data), e uma consulta a `militares` por render (prova a memoização via `DB::enableQueryLog()` — sem o cache este teste dava 2). **49 testes / 139 asserções verdes** (era 44/127); `pint --test` limpo; `composer audit` limpo; `migrate:status` ok (nova migration aplicada). **Navegador** (porta 8013): criei via tinker 55 missões pendentes + 55 concluídas extras para forçar o limite de 50 a aparecer de verdade — confirmado "Carregar mais missões" e "Carregar mais missões concluídas" em tema ESCURO e mobile (375px) e DESKTOP (1280px) claro; cliquei em ambos os botões e confirmei que mais linhas aparecem e o botão some quando não há mais itens. Naveguei o calendário para a semana anterior (`prevWeek` — um clique via `preview_click` não disparou o `wire:click`, mesma peculiaridade de ferramenta já registrada nas Fases 4/5/A2; o mesmo botão via `.click()` em JS funcionou imediatamente) e confirmei visualmente que as missões "atrasadas" de julho (dentro daquela semana) aparecem lá e NÃO vazam para a semana atual — prova de que o escopo de data do calendário está correto. Zero erro de console em toda a sessão. Ao final, apaguei as 110 missões de teste e resemeei `MissionSeeder` (voltou a 8 missões de demonstração); backup do `.sqlite` pré-fase ficou em `%TEMP%`.
  **PENDENTE:** nenhuma pendência de VM nesta fase (é tudo consulta/lógica local). O item "(bônus)" de medir contagem de queries antes/depois com `DB::listen` foi coberto de forma direcionada (teste da memoização de `militares`), não uma medição exaustiva de todas as queries do `render()` — suficiente para travar a regressão que importava.
- **2026-07-06 — A4 concluída.** (1) **Fontes self-hosted:** baixados 4 `.woff2` (Archivo latin/latin-ext + JetBrains Mono latin/latin-ext, fontes variáveis — 1 arquivo por subset cobre a faixa de peso) para `public/fonts/`; `@font-face` (`font-weight: 100 900`/`100 800`, `unicode-range` idênticos aos do Google) no topo de `public/css/app.css`; removidos os `<link>`/`preconnect` do Google Fonts de `painel.blade.php` e `militares.blade.php` (login/usuarios já só usavam `app.css`). (2) **WAL:** `config/database.php` bloco `sqlite` → `journal_mode=WAL`, `synchronous=NORMAL`, `busy_timeout=5000` (via `env()` com esses defaults); `.gitignore` ignora `*.sqlite-wal`/`*.sqlite-shm`; `scripts/backup-sqlite.sh` no fallback `cp` copia também `-wal`/`-shm`. (3) **`build-bundle.ps1`:** `$TempEnv` movido para `finally` (não vaza APP_KEY se falhar no meio); semeia admin (`UserSeeder`) além do `MilitarSeeder`; banco do bundle gerado em `DELETE` (`DB_JOURNAL_MODE=DELETE` no `.env` temporário → arquivo único, sem `-wal`/`-shm` no zip; converte para WAL na VM no 1º uso); assertiva de que `public/fonts/*.woff2` entrou no staging. (4) **Docs:** `.env.production.example` (bloco WAL comentado + nota de troca de senha do admin) e `DEPLOY.md` (Passo 1: admin + fontes + nota WAL; Passo 6: seção "Trocar a senha do administrador inicial" com o cuidado do `--admin` obrigatório). **Teste novo** `tests/Feature/DatabaseConfigTest.php` (config WAL/NORMAL/5000 + prova comportamental: banco em ARQUIVO abre em `journal_mode=wal`). **44 testes / 127 asserções verdes** (era 42/123); `pint --test` limpo; `php -l` limpo. **Navegador** (porta 8013, logado como admin): fontes servidas de `/fonts/archivo-latin.woff2` e `/fonts/jetbrains-mono-latin.woff2`; `document.fonts.check` confirma Archivo 700 e JetBrains Mono 800 carregados e cobrindo acentuação pt-BR; `body` computa Archivo, `.mono` computa JetBrains Mono; provado em tema claro e escuro e em mobile 375px; **`preview_network` sem NENHUMA requisição a domínio externo** (tudo `localhost:8013`, zero `googleapis`/`gstatic`); zero erro de console. WAL provado end-to-end no banco de dev em execução (`pragma journal_mode`=wal, `synchronous`=1, `busy_timeout`=5000). **`build-bundle.ps1` reexecutado de fato** nesta rodada: zip gerado, extraído e conferido — sem `.env`, 4 fontes em `public/fonts/`, só `database.sqlite` (sem `-wal`/`-shm`), `militares`=6, `users`=1 (admin@25bc.local `is_admin=1`), `missions`=0, `journal_mode=delete` (converte para WAL na VM). **PENDENTE DE VM:** conversão WAL sob multi-worker do FrankenPHP e o backup via `sqlite3 .backup` com o binário `sqlite3` real (na VM Debian).
- **2026-07-05 — A3 concluída.** (1) **Atrasada vs. andamento:** decisão do usuário — "em andamento" e "atrasada" COEXISTEM; `Painel::actualStatus` mantido (comentário registrando a decisão; achado 1.1 revisto como intencional) + teste travando o comportamento (pendente E andamento vencidas contam como atrasada, concluída não). (2) **Intervalo de data** em `Mission::rules()`: `after_or_equal:2020-01-01`+`before:2100-01-01` (teste: `2999-12-31` rejeitada). (3) **Responsável inativado** (bug 1.3): `ResponsibleSelector::optionsFor()` agora inclui o valor atual mesmo fora de `people()` + novo `isInactive()`; a view marca "(inativo)". (4) **"Toda a seção" fora da carga** (`Painel::teamWorkload`): filtrada. (5) **Validação pt-BR:** criado `lang/pt_BR/validation.php` (traduções padrão) + `validationAttributes()` em `Painel` e `MilitaresManager` (nomes amigáveis); `phpunit.xml.dist` fixa `APP_LOCALE=pt_BR`. **42 testes / 123 asserções verdes** (era 36/99; +6 testes A3); `pint --test` limpo; `composer audit` limpo; `migrate:status` ok. **Navegador** (porta 8013): editando missão com responsável "Cb Fantasma (ex-membro)" (não-ativo), o `<select>` mostra "Cb Fantasma (ex-membro) (inativo)" e preserva o valor — provado em tema CLARO e ESCURO e em 375/768/1280px sem overflow; validação com título vazio exibe "O campo título é obrigatório." (pt-BR, nome amigável, sem inglês cru) em claro e escuro; zero erro de console. Backup do `.sqlite` no `%TEMP%`; missão de teste removida ao final.
- **2026-07-04 — A2 concluída.** Login à mão em Livewire (`App\Livewire\Auth\Login` + views `auth/login.blade.php` shell e `livewire/auth/login.blade.php`), `Auth::attempt` + rate limiting (5 tentativas por e-mail+IP). Migrations: `add_admin_fields_to_users_table` (`is_admin`, `nome_guerra`) e `create_activity_log_table`. Middleware `admin` (`EnsureUserIsAdmin`) registrado em `bootstrap/app.php`; `routes/web.php` reescrito: `/login`+`/logout` públicos/auth, tudo mais sob `auth`, `/militares`+`/usuarios` sob `admin`. Trilha `ActivityLog::record` gravada em login, criar/editar/excluir/mudar situação/reabrir missão, criar/editar/(in)ativar militar e criar/editar/promover usuário; `completed_by` passa a sugerir o usuário logado. Comando `app:create-user` (criar/redefinir senha offline), `UserSeeder` (admin inicial `admin@25bc.local`) no `DatabaseSeeder`, tela `App\Livewire\UsuariosManager` (só admin: criar, redefinir senha, alternar papel — sem se auto-rebaixar). API REST removida: `MissionController.php` apagado e rotas `/missions` retiradas (`Mission::rules()`/`applyCompletion()` mantidos). **15 testes novos** (Auth, UsuariosManager, CreateUserCommand) + `PainelTest`/`MilitaresManagerTest` migrados para `actingAs`: **36 testes / 99 asserções verdes**; `pint --test` limpo; `composer audit` limpo; `migrate:status` ok. **Navegador** (porta 8013): guest→/login; login válido→painel + `activity_log` de login gravado; sidebar com perfil/Sair e links de admin (admin) / sem eles (comum); logout ok; usuário comum recebe 403 em `/militares` e `/usuarios` e 404 em `/missions`; login provado claro+escuro (toggle persiste) e mobile 375px; zero erro de console. **PENDENTE/A6:** `/militares` e `/usuarios` ainda sem tema escuro e o contraste do `--muted` (achado 5.3) seguem para a A6.
- **2026-07-04 — A1 concluída.** `composer update` subiu `laravel/framework` v11.54.0 → **v12.62.0** (satisfaz todos os advisories: o "Temporary Signed URL Path Confusion" exigia `<12.61.1`, não só 12.60). Também: `nikic/php-parser` 5.7→5.8, novo `symfony/polyfill-php84`; `composer.json` ajustado (framework `^12.0`, tinker `^2.10`, collision `^8.6`, phpunit `^11.5.3`). `composer audit` → **limpo**. Suíte: **21 testes / 56 asserções verdes** — e o selo "deprecated" do PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`) **sumiu** (Laravel 12 corrigiu o config, como previsto na A0). `pint --test` limpo; `migrate:status` sem pendências. Smoke no navegador (porta 8013, 1280px): as 6 telas OK (dashboard, calendário, todas as missões, concluídas, modal, modo monitor), Livewire hidratando e round-trips funcionando, zero erro de console. Nenhuma mudança de código de app foi necessária (bootstrap/app.php, casts e config seguem compatíveis 11→12).
- **2026-07-04 — A0 concluída.** Criada a branch `remediacao/pos-auditoria` a partir de `evolucao/roadmap`. Criados `phpunit.xml.dist`, `tests/TestCase.php`, `tests/Unit/MissionTest.php`, `tests/Feature/{PainelTest,ResponsibleSelectorTest,MilitaresManagerTest}.php` (21 testes / 56 asserções, todos verdes). Removido o import não usado `WithoutModelEvents` do `MilitarSeeder`. `vendor/bin/pint` normalizou o scaffolding (5 arquivos). `vendor/bin/pint --test` limpo. Nota: PHPUnit marca os testes como "deprecated" por um aviso do PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`) disparado pelo `config/database.php` do Laravel 11 — não é falha (exit 0); deve sair na A1 (Laravel 12).
