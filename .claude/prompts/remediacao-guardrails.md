# GUARDRAILS — Remediação Agenda de Missões (25º BC)

> **Este arquivo é a "memória" anti-alucinação da remediação.** Leia-o INTEIRO
> antes de tocar em qualquer código, e releia a seção "VERDADE DE BASE" sempre
> que for citar um arquivo. O roteiro do que fazer está em
> `.claude/prompts/remediacao-mestre.md`. O diário de bordo é `.claude/ANDAMENTO.md`.
>
> Base factual verificada por auditoria em **2026-07-04** (branch `evolucao/roadmap`).
> Números de linha envelhecem — **reconfira com Read/Grep antes de citar linha**.

---

## ⛔ REGRAS INVIOLÁVEIS

1. **NÃO confie na sua memória, nem em comentários do código, nem neste arquivo**
   para afirmar COMO o código se comporta. **ABRA E LEIA o arquivo real** (Read/Grep)
   antes de cada edição e antes de cada afirmação.
2. **Uma FASE por vez** (ver `remediacao-mestre.md`). Não adiante fases. Ao terminar
   uma fase verificada, **atualize o `ANDAMENTO.md`, faça commit e PARE** para o
   usuário revisar.
3. **Trabalhe em branch git.** Nunca faça commit direto em `main`. Nunca use
   `--no-verify`. Faça um commit por fase (ou por sub-passo coeso).
4. **Prove no navegador** toda mudança de UI com os preview tools, em **tema claro E
   escuro** e nas larguras **375 / 768 / 1280 px** quando aplicável. Nunca escreva
   "deve funcionar" sem rodar.
5. **Toda mudança precisa de teste automatizado** que a cubra (a suíte é criada na
   Fase A0). Rode `php artisan test` e `vendor/bin/pint --test` ao fim de cada fase —
   ambos verdes antes de commitar.
6. **Faça backup de `database/database.sqlite`** antes de qualquer teste destrutivo e
   **restaure ao final**. `resetDemo()` só roda em `local` — não o use como atalho.
7. **Seja honesto:** se algo falhou, foi pulado, ou só pode ser testado na VM real
   (Proxmox/Debian/FrankenPHP), escreva isso no `ANDAMENTO.md`. Não finja que testou.
8. **Na dúvida sobre algo que altera dados ou muda o comportamento visível**
   (ex.: mudar a regra de "atrasada"), PERGUNTE ao usuário antes.

---

## 🧭 VERDADE DE BASE (o que o código É — verificado)

### Stack e restrições
- **Laravel** (era **11.54.0** na auditoria; a Fase A1 sobe para **12.60+**),
  **PHP 8.5** local, **FrankenPHP** embute PHP 8.5 no deploy. **Livewire v4**.
- **SEM npm / Vite / build de assets.** O CSS é **um arquivo estático único**:
  `public/css/app.css`. Não introduza pipeline de build, Tailwind, Breeze,
  Jetstream ou qualquer coisa que exija `npm`.
- **Banco: SQLite único** em `database/database.sqlite`. Nada de MySQL.
- **Deploy é OFFLINE (intranet air-gapped).** Runtime **não pode** depender de
  internet: sem CDN, sem Google Fonts remoto, sem chamadas externas.

### Arquitetura da interface
- UI **100% Livewire**. Componente principal `App\Livewire\Painel`
  (`app/Livewire/Painel.php` + `resources/views/livewire/painel.blade.php`).
- Partials em `resources/views/livewire/partials/` (`mission-row`, `next-mission`,
  `next-mission-tv`, `calendar-grid`, `tv-screen`).
- Componentes isolados: `App\Livewire\ResponsibleSelector` (seletor progressivo de
  responsáveis) e `App\Livewire\MilitaresManager` (CRUD de militares).
- Wrappers Blade (páginas): `resources/views/painel.blade.php` (rota `/`) e
  `resources/views/militares.blade.php` (rota `/militares`).
- Componentes Blade: `resources/views/components/icon.blade.php` (SVGs) e
  `live-clock.blade.php` (relógio via Alpine).
- **O painel NÃO usa API HTTP** — fala direto com o Eloquent (`Mission`). A API REST
  antiga (`/missions` + `MissionController`) **será REMOVIDA na Fase A2**.

### Modelo de dados
- `Mission` (`app/Models/Mission.php`):
  - `responsibles` é coluna **JSON** (cast `array`) guardando **NOMES DE TEXTO
    (snapshot)**, **SEM FK**. Promover/renomear/inativar um militar **NÃO** reescreve
    missões já criadas. **Nunca** transforme isso em relação/FK.
  - `date` e `time` são **strings** (`"AAAA-MM-DD"` / `"HH:MM"`), casadas 1:1 com o front.
  - Regras de validação em **`Mission::rules()`** (compartilhadas). Lógica de conclusão
    em **`Mission::applyCompletion()`**. **Mantenha a lógica centralizada no model** —
    não duplique em componentes/controllers.
- `Militar` (`app/Models/Militar.php`): tabela **`militares`**, scope `ativos()`,
  método `nomeExibicao()`. **"Toda a seção" NÃO é militar** — é uma opção fixa somada
  em `Painel::people()`.
- `User` (`app/Models/User.php`): model padrão; a tabela `users` **já existe**
  (migration `0001_01_01_000000_create_users_table.php`). Base para o login da Fase A2.

### Segurança já OK (não regredir)
- **Blade escapa tudo com `{{ }}`** — XSS testado e negativo. O **único** `{!! !!}` do
  projeto está em `components/icon.blade.php`, injetando SVGs de um **array fixo
  controlado pelo dev**. **Nunca** use `{!! !!}` com dado vindo do usuário.
- **CSRF ativo** no Livewire (writes sem token → 419). Sem `DB::raw`/`whereRaw`/
  `selectRaw` — só bindings Eloquent. Não introduza SQL cru.
- `.env` está no `.gitignore`; `.env.production.example` tem `APP_KEY` vazia e
  `APP_DEBUG=false` (correto). Não commite segredos.

### Tema e JS
- O modo escuro alterna a classe **`.theme-dark` na `.painel-root`** (um `<div>`),
  **não** no `<body>`. A página `/militares` hoje **não tem** `.painel-root` → é sempre
  clara (corrigido na Fase A6).
- **Ponte JS mínima** (em `resources/views/painel.blade.php`): tema no `localStorage` +
  Fullscreen API. `live-clock.blade.php` usa **Alpine** (embutido no Livewire) para o
  relógio. **Não adicione outros frameworks JS.**
- `wire:poll.15s="refresh"` no painel (recalcula view models); `wire:poll.12s="rotateTv"`
  no modo monitor.

### SQLite (pragmas medidos na auditoria)
- `journal_mode=delete`, `synchronous=2` (FULL), `busy_timeout=60000`; config
  (`config/database.php`) tem os três como `null`. **A Fase A4 liga WAL.**

---

## 🪤 ARMADILHAS CONHECIDAS (custaram tempo na auditoria)

1. **Livewire v4 no navegador:** o proxy `$wire` **NÃO** expõe `$wire.set/get/call`
   de forma confiável via `preview_eval` (testado: `w.set`, `w.$set`, `w.openEdit`
   todos falham). Para **asserções de estado**, use **`Livewire::test(...)`**
   (server-side, via `php artisan tinker --execute` ou nos testes). Para **simular o
   usuário** no navegador, **clique nos elementos reais** (botões/links do DOM).
2. **`required` / `type=date|time` nativos** nos inputs do modal fazem o navegador
   **bloquear o submit antes** da validação do servidor. Para exercitar a validação
   server-side, use `Livewire::test` (ou preencha campos válidos e force o caso alvo).
3. **`responsibles` é EXCLUÍDO das regras em `Painel::save()`** e revalidado à mão
   (`$this->responsibles === []` → `addError('form.responsibles', ...)`). A lista chega
   por evento `responsibles-changed` do `ResponsibleSelector`. Não quebre esse contrato.
4. **`applyCompletion` grava `previous_status` só na PRIMEIRA conclusão**; `reopen`
   usa `previous_status ?: 'pendente'` e limpa os campos de conclusão. Cobrir com
   testes os 3 caminhos: nunca teve status anterior, reaberta 2×, criada já concluída.
5. **Responsável inativado some do `<select>`** (bug 1.3): `optionsFor()` só lista de
   `people()` (ativos). O valor fica em `rows` mas sem `<option>` → select vazio. **O
   valor É preservado** ao salvar sem tocar na linha (confirmado) — é bug de EXIBIÇÃO.
6. **`.calendar-grid { min-width: 820px }`** → o **scroll horizontal do calendário é
   INTENCIONAL**. Não "conserte" isso.
7. **Worktree órfão** `.claude/worktrees/friendly-matsumoto-b93c19/` tem um `vendor/`
   inteiro e **polui buscas com Glob** (`**/*.md`, `**/*.php`). Restrinja buscas a
   `app/`, `resources/`, `routes/`, `database/`, `config/` ou exclua `.claude/**` e
   `vendor/**`.

---

## 🔒 INVARIANTES QUE NÃO PODEM QUEBRAR

- **Offline total em runtime:** nada de internet para o app rodar (fontes
  self-hosted após A4; sem CDN; sem npm/Vite).
- **pt-BR com acentuação correta** em TODA a UI, mensagens e comentários (nunca
  "missao" por "missão", "secao" por "seção").
- **`responsibles` = snapshot de texto, sem FK.** Histórico não é reescrito ao
  promover/renomear/inativar militar.
- **Lógica de missão centralizada em `Mission`** (`rules()`/`applyCompletion()`).
- **SQLite único.** **`resetDemo` gated a `local`.**
- **Sem regressão de XSS/CSRF/SQLi** (ver "Segurança já OK").

---

## ✅ CHECKLIST DE "PRONTO" (por fase)

Antes de commitar/atualizar o ANDAMENTO, TODOS devem passar:

```bash
# 1. Sintaxe dos arquivos PHP alterados
php -l caminho/do/arquivo.php

# 2. Estilo (Pint) — sem diffs pendentes
vendor/bin/pint --test

# 3. Suíte de testes verde
php artisan test

# 4. Migrations aplicadas/sem conflito (se mexeu em migration, rode migrate:fresh --seed em DEV)
php artisan migrate:status

# 5. (Só após a Fase A1) Dependências sem advisories
composer audit
```

- **UI:** subir com preview tools, provar no navegador (claro + escuro, 375/768/1280).
  Conferir contraste com `preview_inspect` (não confie só no screenshot).
- **Offline (Fase A4+):** `preview_network` **sem** nenhuma requisição a domínio externo.
- **Dados:** backup do `database.sqlite` antes de teste destrutivo; restaurar depois.

Se algo não passar, **corrija ou registre honestamente no ANDAMENTO** — não commite quebrado.
