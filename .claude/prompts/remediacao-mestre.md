# PROMPT MESTRE — Remediação Agenda de Missões (25º BC)

> **Antes de tudo:** leia `.claude/prompts/remediacao-guardrails.md` (a "memória"
> anti-alucinação) e o estado atual em `.claude/ANDAMENTO.md`.
> Este documento diz **O QUE** fazer e **EM QUE ORDEM**. Os guardrails dizem **COMO
> não quebrar / não alucinar**.

---

## 🎯 PAPEL E POSTURA

Você é um engenheiro Laravel sênior fazendo a **remediação pós-auditoria** deste
projeto antes de produção numa intranet militar offline (Proxmox/Debian/FrankenPHP).
Trabalhe **uma fase por vez**, com **testes e prova no navegador**, **commit por fase**,
e **PARE ao fim de cada fase** para o usuário revisar (atualizando o `ANDAMENTO.md`).

## ✅ DECISÕES JÁ TOMADAS PELO USUÁRIO (não reabra)

1. **Autenticação: login completo com usuários** (tabela `users`, tela de login,
   trilha de auditoria de quem fez o quê). Papéis mínimos: `admin` vs. usuário comum.
2. **Atualizar o framework para Laravel 12.60+** (corrige os 3 advisories).
3. **Remover completamente a API REST antiga** (`/missions` CRUD + `/reset` +
   `MissionController`) — não é usada pela interface.

## 🧠 COMO TRABALHAR (regras de execução)

- Branch git dedicada (ex.: `remediacao/pos-auditoria`). Commit por fase. Nunca em `main`.
- **Leia o arquivo real antes de editar.** Reconfira números de linha.
- **Escreva o teste junto com a correção** (a suíte nasce na Fase A0).
- Rode o **CHECKLIST DE "PRONTO"** dos guardrails ao fim de cada fase.
- Prove UI no navegador (claro/escuro, 375/768/1280).
- Ao terminar a fase: atualize `ANDAMENTO.md` (log "JÁ FEITO" + próxima tarefa),
  commite, e **PARE**.
- **Não use `npm`/Vite/Breeze/Jetstream/Fortify** (quebram o offline / exigem build).
- Reaproveite `Mission::rules()` / `Mission::applyCompletion()`. Não duplique lógica.

## 🔢 ORDEM E POR QUÊ (dependências)

A0 (rede de testes) → A1 (upgrade framework) → A2 (auth + auditoria + remover API) →
A3 (lógica/validação) → A4 (offline/deploy) → A5 (performance) → A6 (UX/a11y) → A7 (fechamento).

Racional: **A0 primeiro** para nenhuma fase seguinte quebrar código sem rede de
segurança. **A1 cedo** por ser a mudança mais estrutural/arriscada (vendor). **A2**
antes das telas porque muda navegação, copy e adiciona `/login`, `/logout` e o registro
de autor. Depois, correções mais localizadas.

---

# FASES

## FASE A0 — Rede de segurança (testes + baseline) 🧪
**Objetivo:** criar a infraestrutura de testes que HOJE não existe (causa da falha
`Could not read XML from file phpunit.xml.dist`), capturando o comportamento correto
ATUAL antes de mudar qualquer coisa.

**Passos**
1. Criar `phpunit.xml.dist` (padrão Laravel 12) com ambiente de teste:
   `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
   `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`,
   `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`.
2. Criar `tests/TestCase.php` (estende `Illuminate\Foundation\Testing\TestCase`,
   bootstrap via `bootstrap/app.php`), `tests/Feature/`, `tests/Unit/`.
3. Escrever **testes de baseline do comportamento ATUAL** (usar `RefreshDatabase`):
   - `Mission::applyCompletion` (conclui → grava `completed_by`/`completed_at`/
     `previous_status`; não-conclui → limpa os três; `previous_status` só na 1ª conclusão).
   - `Livewire::test(Painel::class)`: criar missão válida; erro "Selecione ao menos um
     responsável." sem responsáveis; `changeStatus`; `reopen` nos 3 caminhos; `deleteMission`.
   - `ResponsibleSelector`: `optionsFor` exclui usados; `canAddRow`; `removeRow` mantém ≥1 linha.
   - `MilitaresManager`: criar, `toggleAtivo`, `moveUp`/`moveDown`.
4. `vendor/bin/pint` (normaliza estilo; remove o import não usado
   `WithoutModelEvents` em `database/seeders/MilitarSeeder.php`).

**Aceite:** `php artisan test` verde; `vendor/bin/pint --test` limpo.

---

## FASE A1 — Upgrade Laravel 12 + dependências 🚀
**Objetivo:** subir `laravel/framework` para **≥12.60** e zerar o `composer audit`.

**Passos**
1. Em `composer.json`, ajustar `laravel/framework` para `^12.0` (garantir ≥12.60 no
   lock). Revisar compatibilidade das demais: `laravel/tinker`, `livewire/livewire`
   (`^4` já compatível), `phpunit/phpunit`, `nunomaduro/collision`, `laravel/pint`,
   `mockery/mockery`, `fakerphp/faker`.
2. `composer update` (na máquina COM internet). Ler o guia oficial de upgrade 11→12 e
   ajustar o que o diff pedir (mudanças 11→12 são pequenas; conferir `bootstrap/app.php`,
   casts, `config/*`).
3. `composer audit` → **sem advisories** (listar residual se houver).
4. Rodar a suíte A0 (**verde**) e **smoke test no navegador** das 6 telas.

**Aceite:** `composer audit` limpo; testes verdes; app sobe e navega. Commit isolado
(para reverter fácil se preciso).

---

## FASE A2 — Autenticação + trilha de auditoria + remover API 🔐
**Objetivo:** ninguém acessa nada sem login; toda ação registra o autor; a API antiga some.

**A2.1 — Login (feito à mão, offline, sem Breeze/npm)**
- Migration: adicionar `is_admin` (boolean, default false) e, se útil, `nome_guerra`
  em `users`.
- Login em **Livewire** (`App\Livewire\Auth\Login`) com view `resources/views/auth/
  login.blade.php`, usando `Auth::attempt` + **rate limiting** (`RateLimiter`). Rota
  pública `/login`; ação de `/logout`.
- Proteger **todas** as rotas com middleware `auth`, exceto `/login` e `/up`
  (grupo em `routes/web.php`). Guest → redireciona a `/login`.
- Botão **"Sair"** na sidebar; bloco de perfil mostra o usuário logado (trocar a copy
  "Toda a seção pode editar").
- **Seed inicial de admin** (`UserSeeder`) + comando artisan **`app:create-user`**
  (criar/redefinir senha offline — não há e-mail para "esqueci a senha").

**A2.2 — Papéis e gating**
- `/militares` e a gestão de usuários **só para `is_admin`**. Usuário comum gere missões.
- Tela de **gestão de usuários** (admin) OU, no mínimo, o comando `app:create-user`.

**A2.3 — Trilha de auditoria**
- Migration `activity_log` (`id`, `user_id`, `action`, `subject` (ex.: `mission`),
  `subject_id`, `description`, `created_at`).
- Registrar autor (`auth()->id()`) em: criar/editar/excluir missão, `changeStatus`,
  `reopen`, e mudanças de militar/usuário.
- (Recomendado) Ao concluir uma missão, sugerir `completed_by` = nome do usuário logado,
  mantendo o `<select>` manual.
- (Opcional, avaliar) `SoftDeletes` em `Mission` para exclusão recuperável — se adotar,
  ajustar queries e testes.

**A2.4 — Remover a API antiga**
- Apagar as rotas `Route::prefix('missions')...` de `routes/web.php` e o arquivo
  `app/Http/Controllers/MissionController.php`. **Manter** `Mission::rules()` e
  `applyCompletion()` (usados pelo `Painel`). Confirmar que `resetDemo` (no `Painel`,
  gated a `local`) continua funcionando.

**Testes:** guest redirecionado; login OK/falha; rota protegida exige auth; rota admin
nega usuário comum; ação grava `activity_log`; `GET /missions` agora dá **404**.
Ajustar os testes Livewire existentes para `->actingAs($user)`.

**Aceite:** fluxo de login provado no navegador (claro/escuro, mobile); autor
registrado; API sumiu; testes verdes.

---

## FASE A3 — Correções de lógica e validação (pt-BR) 🧩
1. **Atraso vs. andamento** (`Painel::actualStatus`): definir política — recomendação:
   **só `pendente`** vira `atrasada`; `andamento` não conta como atrasada. Ajustar o
   card "Atrasadas", as tags e o modo monitor. **Confirmar a regra com o usuário se
   houver dúvida.**
2. **Intervalo de data** (`Mission::rules()`): adicionar limites sensatos
   (ex.: `after_or_equal:2020-01-01`, `before:2100-01-01`). Teste: `2999-12-31` rejeitada.
3. **Responsável inativado no seletor** (bug 1.3): no fluxo de edição, **unir** os nomes
   já atribuídos à missão com os militares ativos (em `Painel::people()` para edição
   e/ou `ResponsibleSelector::optionsFor()` sempre incluindo o valor atual da linha,
   marcando "(inativo)"). Teste: `optionsFor` inclui o valor atual mesmo fora de `people`.
4. **"Toda a seção" fora da carga** (`Painel::teamWorkload`): filtrar `'Toda a seção'`.
5. **Mensagens de validação em pt-BR:** criar `lang/pt_BR/validation.php` (traduções
   padrão) + definir nomes amigáveis dos campos (`validationAttributes()`/`attributes()`)
   em `Painel` e `MilitaresManager`. Teste: mensagem sai em pt-BR (não "The form.notes…").
6. **(Opcional) Lock otimista** por `updated_at` em `Painel::save` para evitar
   last-write-wins silencioso — marcar como melhoria, não bloqueia a fase.

**Aceite:** um teste por correção; prova no navegador.

---

## FASE A4 — Offline & deploy hardening 📦
1. **Auto-hospedar as fontes** (crítico p/ offline): baixar (na máquina com internet)
   os `.woff2` de **Archivo** (400–800) e **JetBrains Mono** (500/700/800) para
   `public/fonts/`, adicionar `@font-face` no topo de `public/css/app.css` apontando
   para `/fonts/...`, e **remover** os `<link>`/`preconnect` de Google Fonts de
   `resources/views/painel.blade.php` e `resources/views/militares.blade.php`.
   Teste: `preview_network` **sem** requisição a `fonts.googleapis.com`/`gstatic.com`.
2. **Ligar WAL** em `config/database.php` (bloco `sqlite`): `journal_mode => 'WAL'`,
   `synchronous => 'NORMAL'`, `busy_timeout => 5000`. Confirmar `PRAGMA journal_mode`
   = `wal`. Adicionar `*.sqlite-wal`/`*.sqlite-shm` ao `.gitignore` e garantir que o
   `scripts/backup-sqlite.sh` (usa `sqlite3 .backup`, que lida com WAL) e o
   `build-bundle.ps1` tratem esses arquivos.
3. **`build-bundle.ps1`:** mover o `Remove-Item $TempEnv` para um `finally`; incluir a
   semeadura do **admin inicial** (`UserSeeder` + `app:create-user`) junto do
   `MilitarSeeder`; confirmar que `public/fonts/` entra no bundle.
4. **Docs:** `DEPLOY.md` e `.env.production.example` — adicionar passo "criar primeiro
   usuário admin" (sem e-mail, via `app:create-user`), documentar WAL e as fontes locais.

**Aceite:** sem chamadas externas no `preview_network`; `journal_mode=wal`; docs
atualizadas. Execução real do bundle → **PENDENTE DE VERIFICAÇÃO NA VM**.

---

## FASE A5 — Performance ⚡
1. **Paginar/limitar** o histórico (`Painel::buildHistoryRows`) e a tabela "Todas as
   missões"; escopar as queries do dashboard/calendário por **janela de data** em vez
   de `Mission::orderBy(...)->get()` cru (`Painel::render`). Avaliar reduzir o escopo do
   `wire:poll` (dados leves / intervalo).
2. **Memoizar** `people()`/`completers()` e `weekData()` no ciclo de render (hoje
   `people()` roda 2× e `weekData()` 2× por render).
3. **(Opcional)** índice composto `(date, time)` nas missões.

**Aceite:** testes ainda verdes; listas funcionam no navegador; (bônus) medir contagem
de queries antes/depois com `DB::listen`. Cuidado com `wire:key` ao paginar dentro do
componentão.

---

## FASE A6 — UX / Acessibilidade ♿
1. **Modo escuro em `/militares`** (e login/gestão de usuários): extrair um **layout
   compartilhado** (`resources/views/layouts/app.blade.php`) com a ponte de tema, ou
   aplicar `.painel-root`/`theme-dark` + o script de tema também nessas páginas.
2. **Botões só-ícone com nome acessível:** `aria-label` nos botões de navegação/topbar/
   "Nova missão" (o rótulo some no mobile) e `aria-hidden="true"` no `<x-icon>`
   decorativo (adicionar prop ao componente `icon`).
3. **Contraste no escuro:** clarear `--muted` (atual `#93a49b` sobre `#1c2420` ≈ 3,7:1,
   abaixo de AA) e/ou aumentar a fonte da `.mission-meta` (10,5px). Confirmar ≥4,5:1 com
   `preview_inspect`.
4. **Truncamento de título:** `title=""` (tooltip) na versão truncada e/ou mais largura.
5. **Modal acessível:** `aria-labelledby` → `<h2>`, foco inicial no 1º campo, e prender
   o foco enquanto aberto.
6. **Comentários desatualizados:** corrigir o docblock de `ResponsibleSelector`
   ("ainda em vanilla JS até a Fase 5").

**Aceite:** contraste medido; nomes acessíveis presentes no snapshot de acessibilidade;
prova claro/escuro em 375/768/1280.

---

## FASE A7 — Fechamento ✔️
1. Rodar tudo: `php artisan test`, `vendor/bin/pint --test`, `composer audit`,
   `php artisan migrate:status`.
2. Smoke manual completo: login → 6 telas → modal → monitor → `/militares`, claro/escuro,
   3 larguras.
3. Atualizar `.claude/ANDAMENTO.md` (log "JÁ FEITO"), `DEPLOY.md`, `README.md`.
4. Commit final. Listar no `ANDAMENTO.md` o que ficou **PENDENTE DE VERIFICAÇÃO NA VM**
   (bundle real, WAL sob multi-worker, pdo_sqlite, permissões, systemd).

---

## 🗺️ RASTREABILIDADE (achado da auditoria → fase)

| Achado | Sev. | Fase |
|---|---|---|
| 2.1 Sem autenticação / sem trilha de autor | Alto | A2 |
| 2.2 API `/missions` GET vaza dados | Médio | A2 (remover) |
| 2.4 Laravel 11.54 com 3 advisories | Médio | A1 |
| 2.3 Google Fonts (quebra offline) | Médio | A4 |
| 3.1 Testes inexistentes/quebrados | Médio | A0 |
| 3.2 Validação em inglês / campo cru | Médio | A3 |
| 3.3 Pint (import não usado + scaffolding) | Baixo | A0 |
| 3.4 Comentários desatualizados | Nota | A6 |
| 1.1 "Atrasada" para missão em andamento | Baixo | A3 |
| 1.2 Data sem intervalo (aceita ano 2999) | Baixo | A3 |
| 1.3 Responsável inativado some do seletor | Médio | A3 |
| 1.4 "Toda a seção" na carga por militar | Nota | A3 |
| 1.5 Concorrência last-write-wins | Baixo | A3 (opcional) |
| 4.1 render carrega tudo / sem paginação | Médio | A5 |
| 4.2 Queries/cálculos redundantes | Baixo | A5 |
| 4.3 Índice em `time` | Nota | A5 (opcional) |
| 8.1 SQLite sem WAL | Médio | A4 |
| 5.1 `/militares` sem modo escuro | Médio | A6 |
| 5.2 Botões só-ícone sem nome acessível | Médio | A6 |
| 5.3 Contraste "muted" no escuro | Baixo | A6 |
| 5.4 Truncamento de título | Baixo | A6 |
| 5.5 Modal sem foco/rótulo acessível | Baixo | A6 |
| 7.x build-bundle `.env` no `finally` | Baixo | A4 |

> **8.2 (responsável órfão)** é resolvido por **1.3**. Migrations (Seção 8) já OK.
