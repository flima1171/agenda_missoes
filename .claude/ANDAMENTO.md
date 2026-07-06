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

- **Fase em andamento:** Remediação pós-auditoria — **A5 concluída**. Branch de
  trabalho: `remediacao/pos-auditoria`.
- **PRÓXIMA TAREFA:** **A6** — UX/acessibilidade: tema escuro em `/militares` e
  `/usuarios`, `aria-label` em botões só-ícone, contraste do `--muted` no escuro,
  truncamento de título com tooltip, modal acessível (foco/rótulo). Detalhe em
  `.claude/prompts/remediacao-mestre.md`.
- **Depois dela:** A7 (fechamento: suíte + Pint + audit + smoke completo + docs).

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
- **2026-07-04** — **Fase 4 concluída — Seletor de responsáveis progressivo.**
  **Inconsistência encontrada e resolvida ANTES de codar (perguntei ao
  usuário em vez de decidir sozinho, regra 1/8 anti-alucinação):** o roadmap
  descreve a Fase 4 como algo que roda dentro de "o formulário de missão
  (Livewire)", mas conferi de verdade `painel.blade.php` e `public/js/app.js`
  e o modal "Nova missão" continua 100% em JS puro — a migração dele pra
  Livewire só está prevista na Fase 5 (que vem DEPOIS da 4 no próprio
  roadmap). Perguntei ao usuário como resolver; ele escolheu a opção
  "componente Livewire isolado" (mesmo padrão da Fase 3: uma ilha de Livewire
  dentro da página ainda majoritariamente JS, sem adiantar a Fase 5).
  Antes de mexer, li de verdade `painel.blade.php` (`#f-responsible` linha
  183, bloco `$painelPeople` perto do fim do arquivo) e `public/js/app.js`
  (`getResponsibles()`/`setResponsibles()` linha 275-276, injeção de chips
  `#f-responsible` linha 499, evento `submitForm` linha 311-329) pra entender
  o contrato exato entre o JS do formulário e o campo de responsáveis. Também
  li o código-fonte do pacote `livewire/livewire` dentro de `vendor/` (não
  documentação externa, já que não há acesso à internet neste ambiente) pra
  confirmar sem chutar: `Livewire\Attributes\On` existe
  (`vendor/livewire/livewire/src/Attributes/On.php`); despacho de evento do
  JS puro pro Livewire é `window.Livewire.dispatch(nome, {chave: valor})`
  (`dispatchGlobal` em `vendor/livewire/livewire/dist/livewire.js`); os
  parâmetros do evento chegam ao método PHP por NOME do parâmetro, não
  posição (`SupportEvents.php`: `wrap($this->component)->$method(...$params)`
  com `$params` associativo — spread de array associativo vira argumento
  nomeado a partir do PHP 8+, e o projeto é PHP 8.2); e que bind de array por
  índice (`wire:model.live="rows.0"`) é suportado nativamente (dot-path em
  `HandleComponents.php`).
  Criado componente `App\Livewire\ResponsibleSelector`
  (`app/Livewire/ResponsibleSelector.php` + `resources/views/livewire/
  responsible-selector.blade.php`, via `php artisan make:livewire
  ResponsibleSelector --class`): array `$rows` (uma posição por linha
  escolhida, sempre começando com uma vazia), `addRow()`/`removeRow()`,
  `optionsFor($i)` (esconde de cada `<select>` quem já foi escolhido em OUTRA
  linha, mas mantém a opção atual da própria linha pra não sumir), e
  `canAddRow()` (só mostra "+ Adicionar responsável" quando todas as linhas
  atuais estão preenchidas e ainda sobra gente pra escolher). Escuta o evento
  `set-responsibles` (`#[On('set-responsibles')]`) pra ser resetado toda vez
  que o modal abre — necessário porque o componente Livewire persiste na
  página entre uma abertura de modal e outra (SPA-like), então sem isso a
  seleção de uma missão vazaria pra próxima.
  **Ponte com o JS existente (documentada em comentário no código, só até a
  Fase 5 migrar o modal inteiro):** a view do componente renderiza, pra cada
  responsável escolhido (valor não vazio), um `<input type="checkbox" checked
  hidden>` dentro do próprio `id="f-responsible"` — isso preserva o contrato
  que `getResponsibles()` já lia (`#f-responsible input:checked`) SEM
  precisar tocar nessa função. Já `setResponsibles(list)` mudou: em vez de
  marcar `.checked` em checkboxes estáticos (que não existem mais), agora
  despacha `Livewire.dispatch('set-responsibles', { list })`, chamado tanto
  em `openNew()` (lista vazia) quanto em `openEdit()` (responsáveis da missão).
  `painel.blade.php`: `@livewireStyles`/`@livewireScripts` adicionados; bloco
  `@php $painelPeople = ...` movido pra antes do modal (antes ficava perto do
  `<script>` no fim do arquivo, tarde demais pro `<livewire:responsible-
  selector :people="$painelPeople" />` que agora fica dentro do campo
  "Responsável(is)"); `<div class="chip-group" id="f-responsible"></div>`
  virou `<livewire:responsible-selector :people="$painelPeople" />`. `app.js`:
  removida a linha que injetava os checkboxes de chip em `#f-responsible` no
  `init()` (o Livewire renderiza agora). `app.css`: removidas as regras
  `.chip-group`/`.chip-option` (ficaram órfãs, sem nenhum uso no projeto
  depois da troca — confirmado com grep antes de apagar) e criadas
  `.resp-rows`/`.resp-row`/`.resp-remove`/`.resp-add` (+ variante
  `body.theme-dark`), reaproveitando as variáveis de cor existentes
  (`var(--red)` etc.), conforme a decisão travada de reaproveitar o CSS atual.
  **VERIFICADO no navegador** (`preview_start`, porta 8011): abri "Nova
  missão", escolhi um militar no primeiro seletor, cliquei "+ Adicionar
  responsável" (só aparece depois que a linha atual está preenchida e ainda
  sobra gente), a segunda linha excluiu corretamente quem já tinha sido
  escolhido na primeira; removi a segunda linha com o "x" e confirmei que o
  checkbox escondido ficou só com o valor certo. Salvei a missão de teste e
  conferi a resposta da API: `responsibles: ["Cb Luide"]` — persistiu certo.
  Abri para EDITAR uma missão existente com 2 responsáveis ("Inspeção das
  instalações") e confirmei que o componente populou as DUAS linhas com os
  valores certos (sem vazar a seleção do teste anterior, prova de que o
  evento `set-responsibles` reresetando o componente a cada abertura
  funciona). Testado em mobile/tablet/desktop (`preview_resize`): 1 e 2 linhas
  cabem sem overflow em 375px; achei uma peculiaridade da ferramenta de
  automação do preview (um clique via `preview_click` no botão "+" não
  disparou o `wire:click` logo depois de um resize de viewport, enquanto
  `.click()` via JS no mesmo elemento funcionou imediatamente) — **não é bug
  do app**, confirmei rodando a mesma ação via JS puro com sucesso. Testado
  também modo escuro: `.resp-remove` aplica as cores corretas
  (`background: rgb(34,43,38)`, conferido via `preview_inspect`). Sem erros
  de console em nenhum momento. Ao final, restaurei os dados de demonstração
  (`resetData`/`#resetBtn`, ambiente `local`) pra não deixar a missão de teste
  no banco. Rodei `vendor/bin/pint --test` nos arquivos novos (passou) e
  `php -l` no componente (sem erros de sintaxe).
  **PENDENTE:** nenhuma. `php artisan test` continua quebrado
  (`Could not read XML from file phpunit.xml.dist`) mas confirmei via `git
  stash` que isso já falhava ANTES desta fase (pré-existente, fora do escopo
  daqui).
- **2026-07-04** — **Fase 5 concluída — Migração completa da interface para
  Livewire.** Antes de mexer, li de verdade `painel.blade.php` (234 linhas) e
  `public/js/app.js` (518 linhas) inteiros pra entender TODO o contrato: render
  do dashboard/calendário/tabelas/modal/TV, cálculo de "atrasada", contagem de
  progresso da semana, avatares, countdown da próxima missão, rotação do modo
  monitor (12s) e do modo monitor do calendário — nada foi migrado sem antes
  achar a função/trecho correspondente no JS antigo.
  **Arquitetura escolhida:** UM componente Livewire full-page-like,
  `App\Livewire\Painel` (`app/Livewire/Painel.php` + `resources/views/
  livewire/painel.blade.php`), embutido via `<livewire:painel />` dentro de
  `resources/views/painel.blade.php` (que virou um shell finíssimo, só
  `<head>`/`@livewireStyles`/`@livewireScripts` + a ponte de JS descrita
  abaixo) — segue o mesmo padrão de `militares.blade.php` (Fase 3), não uma
  rota-componente Livewire nativa, por consistência com o que já existia.
  `render()` pré-calcula TUDO em "view models" (arrays PHP simples: stats,
  linhas de missão, grade do calendário, dados do TV etc.) e a view só
  exibe — nenhum método é chamado de dentro de `@include`, pra não depender de
  `$this` estar disponível em partials inclusos (não tinha certeza se o
  binding do Livewire propaga pra `@include`s e não quis arriscar). Partials
  novos: `resources/views/livewire/partials/{mission-row,next-mission,
  next-mission-tv,calendar-grid,tv-screen}.blade.php`. Componente Blade novo
  `<x-icon name="...">` (`resources/views/components/icon.blade.php`)
  reaproveita os MESMOS paths SVG que estavam em `ICONS` no `app.js` (copiados,
  não reinventados). Componente `<x-live-clock>` (`resources/views/
  components/live-clock.blade.php`) é o ÚNICO lugar com expressão Alpine.js
  pra atualizar hora/data a cada 1s/30s sem round-trip ao servidor (Alpine já
  vem embutido no bundle do Livewire v4 — confirmei com
  `grep -c Alpine vendor/livewire/livewire/dist/livewire.js`, não instalei nada
  novo). Outras 3 pontes inevitáveis de JS (documentadas em comentário no
  próprio `painel.blade.php`, nenhuma é lógica de negócio): 1) tema escuro lido
  do `localStorage` e mandado pro componente via `Livewire.dispatch('set-
  initial-theme', ...)`; 2) persistir tema no `localStorage` quando o
  componente avisa (`Livewire.on('theme-changed', ...)`); 3) pedir/sair da
  tela cheia (Fullscreen API não existe em PHP) e avisar o componente se o
  navegador sair sozinho (Esc nativo) via evento `fullscreenchange`.
  **Refatoração pra não duplicar regra de negócio:** `MissionController`
  tinha `rules()`/`applyCompletion()` privados; como o Livewire agora
  manipula `Mission` diretamente (sem passar pela API JSON interna), essas
  duas viraram métodos ESTÁTICOS em `App\Models\Mission`
  (`Mission::rules()`/`Mission::applyCompletion()`), e o controller e o
  `Painel` chamam a mesma implementação — a API `/missions` continua existindo
  e funcionando (não removi rotas), só não é mais usada pela UI.
  `App\Livewire\ResponsibleSelector` (Fase 4): a ponte de checkbox escondido
  pra JS ler (`#f-responsible input:checked`) foi REMOVIDA — agora o
  componente dispara `responsibles-changed` (via `$this->dispatch()`) toda vez
  que uma linha muda (`updated()`) ou uma linha é adicionada/removida, e
  `Painel` escuta com `#[On('responsibles-changed')]` pra manter
  `$this->responsibles` sincronizado — zero leitura de DOM.
  **`public/js/app.js` foi APAGADO** (`git rm`, 518 linhas) — confirmei antes,
  com grep, que nenhum blade referenciava mais `<script src=".../app.js">`
  nem `window.__PAINEL__`. `public/css/app.css`: as regras `body.monitor-mode`,
  `body.calendar-monitor-mode` e `body.theme-dark` viraram
  `.painel-root.monitor-mode` etc. (renomeadas com `sed`, não reescritas à
  mão) porque essas classes agora ficam num `<div class="painel-root">`
  dentro do `<body>` (raiz do componente Livewire), não mais no `<body>` em
  si — o `<body>` virou só o wrapper estático do shell.
  **3 bugs reais encontrados e corrigidos DURANTE a verificação no navegador
  (não escondidos, registrando por honestidade):**
  1) `$this->validate()` com regras `"form.campo"` devolve os dados já
     ANINHADOS de volta (`['form' => [...]]`), não com a chave literal
     `"form.campo"` — meu primeiro código fazia
     `str_replace('form.', '', $chave)` num array que já vinha aninhado,
     então `Mission::create()` recebia um `$data` sem `title`/`date`/etc.
     Descoberto criando uma missão de teste pelo calendário: deu
     `SQLSTATE... NOT NULL constraint failed: missions.title`. Corrigido pra
     `$data = $this->validate()['form'];`.
  2) `changeStatus()` (troca de situação pelo select do dashboard) não
     incluía a chave `completed_by` no array passado pra
     `Mission::applyCompletion()`, que faz `$data['completed_by'] ?? ...` —
     PHP/Laravel converte "Undefined array key" em exceção fatal por padrão.
     Descoberto trocando a situação de uma missão pra "Concluída" no
     dashboard. Corrigido adicionando a chave (replicando exatamente o que o
     `app.js` antigo já calculava no payload antes de mandar pro back-end).
  3) Modo escuro: ao mover `.theme-dark` do `<body>` pro `<div
     class="painel-root">`, textos e fundos que dependiam de HERANÇA de
     `body { color: var(--text); background: var(--bg) }` ficaram
     "congelados" no valor claro (propriedades CSS custom são reavaliadas por
     elemento, mas `color`/`background` já resolvidos são herdados como valor
     fixo, não como variável). Resultado: título "Bom dia, Seção." e o
     relógio ficavam BRANCO SOBRE BRANCO (invisíveis) no modo escuro.
     Descoberto testando o toggle de tema e comparando screenshots. Corrigido
     adicionando `.painel-root { color: var(--text); background: var(--bg); }`
     em `app.css`, reafirmando as variáveis no escopo certo.
  4) (bug de timing, não de renderização) O tema inicial era mandado via
     `Livewire.dispatch('set-initial-theme', ...)` dentro do listener
     `livewire:init`, que dispara ANTES do componente terminar de hidratar no
     cliente — o dispatch se perdia. Corrigido: registrar os `Livewire.on(...)`
     em `livewire:init` (continua certo), mas só DISPARAR o tema inicial em
     `livewire:initialized` (evento que o próprio bundle do Livewire expõe
     pra sinalizar que os componentes já hidrataram — confirmei com
     `grep -o "livewire:[a-zA-Z-]*"` no bundle antes de usar, não inventei o
     nome do evento).
  **VERIFICADO no navegador** (`preview_start`, porta **8012** — a 8011 estava
  em uso por outra sessão; ajustei `.claude/launch.json`): as 6 telas do
  escopo da fase, uma por uma — visão geral (stats, missões de hoje com select
  de situação, próximos dias, próxima missão, progresso da semana, carga por
  militar), calendário (navegação de semana, duplo-clique em célula vazia
  cria missão na data/hora certas, clique numa missão abre edição), "todas as
  missões" (filtros segmentados, badges, botão editar), "concluídas" (reabrir
  restaura a `previous_status` certa), modal (criar, editar, excluir com
  `confirm()`, seletor de responsáveis progressivo integrado sem bridge de
  checkbox), modo monitor (TV: rotação automática 12s comprovada, tela cheia,
  sair), modo monitor do calendário (sempre semana atual, independente da
  navegação do calendário normal — confirmado que NÃO usa `$calMonday`
  navegado). Testado tema escuro em TODAS as telas + modal (contraste
  conferido com `preview_inspect`, não só olhando print). Testado responsivo
  em mobile (375px) e tablet (768px) além de desktop — sidebar vira barra
  inferior de ícones, formulário empilha em 1 coluna, tabela esconde colunas
  extras, tudo igual ao comportamento antigo do `app.js`. Restaurei os dados
  de demonstração (`resetDemo`/`#resetBtn`) ao final. `vendor/bin/pint --test`
  limpo nos arquivos tocados (`Painel.php` precisou de `vendor/bin/pint`
  pra ordenar imports — rodei e conferi de novo). `php -l` sem erros em todos
  os arquivos PHP novos/editados.
  **Decisão consciente, não uma omissão:** o atalho de teclado "n" (abrir nova
  missão) do `app.js` antigo NÃO foi recriado — exigiria uma expressão Alpine
  checando `document.activeElement`/classe de kiosk, que é lógica de UI "à
  mão" de novo, e não está entre as 6 telas listadas como escopo desta fase.
  Sinalizando aqui pra decisão do usuário, não decidi remover em definitivo.
  **Nota sobre a ferramenta de teste (não é bug do app):** em 2 momentos um
  `preview_click` por seletor CSS não disparou o `wire:click` (o app não
  mudou de estado), mas o MESMO elemento via `.click()` direto por
  `preview_eval` funcionou imediatamente — mesma peculiaridade já registrada
  na Fase 4. Confirmado rodando a ação de novo com sucesso.
  **PENDENTE:** nenhuma pendência de VM (tudo local/navegador). `php artisan
  test` continua quebrado, pré-existente (mesma causa já registrada na Fase 4).
- **2026-07-04** — **Fase 6 concluída — Bug do horário no calendário.** Antes
  de mexer, li de verdade `app/Livewire/Painel.php` (não confiei na memória
  do que a Fase 5 tinha deixado): `buildWeekGrid()` (linha ~639) usa
  `range(self::CAL_START, self::CAL_END - 1)` (7..17) como a ÚNICA fonte das
  linhas do grid, e o loop de células (`$cells[$h][...] = ...`) só percorre
  essas horas — uma missão salva às 06:00 ou 20:00 nunca cai em nenhuma
  célula, some visualmente (mas continua no banco). Confirmado lendo também
  `resources/views/livewire/partials/calendar-grid.blade.php` (usa só
  `$grid['hours']`, sem hardcode de linhas) e `public/css/app.css`
  (`.calendar-grid` só define `grid-template-columns`, as linhas são
  implícitas — logo aumentar a quantidade de horas não quebra o CSS).
  **Escolhi a opção "ajuste dinamicamente" do roadmap** (não "amplie a faixa"
  fixo, pra não desperdiçar espaço vertical em semanas normais): mudei
  `buildWeekGrid()` (`app/Livewire/Painel.php`) pra calcular o menor/maior
  horário das missões que caem nos 7 dias exibidos (`$weekIsos`/
  `$missionHours`) e usar `min(CAL_START, ...)`/`max(CAL_END, ...)` pra
  expandir a faixa 07h–18h só quando necessário — semana sem nada fora do
  range continua exibindo exatamente 07h–18h como antes.
  **VERIFICADO no navegador** (`preview_start`, porta **8013** — a 8012
  estava em uso por outra sessão; ajustei `.claude/launch.json`): criei pela
  UI uma missão de teste às 06:00 e outra às 20:00 no dia de hoje (sábado,
  04/jul) — as duas apareceram corretamente no calendário normal (grid
  passou a ir de 06:00 até 20:00 automaticamente) E no modo monitor do
  calendário (`enterCalendarMonitor`, que chama a mesma `buildWeekGrid()`
  pra semana atual). Testado em mobile (375px): grid continua com scroll
  horizontal (`min-width:820px` em `.calendar-grid`, comportamento
  pré-existente, não uma regressão desta fase). Sem erros no console em
  nenhum momento. Restaurei os dados de demonstração (`resetDemo`) ao final
  pra não deixar as missões de teste no banco. `vendor/bin/pint --test` em
  `app/Livewire/Painel.php` passou; `php -l` sem erros de sintaxe.
  **PENDENTE:** nenhuma. `php artisan test` continua quebrado, pré-existente
  (mesma causa já registrada nas Fases 4/5).

- **2026-07-04** — **Remediação A0 concluída — Rede de segurança (testes +
  baseline).** Criada a branch `remediacao/pos-auditoria` a partir de
  `evolucao/roadmap` (não trabalho em `main`). O `php artisan test` HOJE só
  falhava por não existir `phpunit.xml.dist` nem `tests/` — confirmei lendo o
  diretório (não havia nenhum dos dois). Antes de escrever qualquer teste, li de
  verdade os arquivos reais que eles cobrem: `app/Models/Mission.php`
  (`rules()`/`applyCompletion()`), `app/Livewire/Painel.php` (save/changeStatus/
  reopen/deleteMission/openEdit), `app/Livewire/ResponsibleSelector.php`
  (`optionsFor`/`canAddRow`/`removeRow`), `app/Livewire/MilitaresManager.php`
  (save/toggleAtivo/moveUp/moveDown), `app/Models/Militar.php` e as migrations de
  `missions`/`militares`. Criados: `phpunit.xml.dist` (padrão Laravel 12 —
  `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
  `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`,
  `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`); `tests/TestCase.php` (mínimo, o
  `createApplication()` do framework já resolve via `bootstrap/app.php` —
  confirmei em `vendor/.../Foundation/Testing/TestCase.php`); `tests/Unit/
  MissionTest.php` (7 testes cobrindo `applyCompletion` nos 3 caminhos: 1ª
  conclusão grava `completed_by`/`completed_at`/`previous_status`; não-conclusão
  limpa os três; reconclusão preserva a 1ª — só teve status anterior, `completed_at`
  e `completed_by`; + `rules()`); `tests/Feature/PainelTest.php` (8 testes:
  criar missão válida, erro "Selecione ao menos um responsável." sem responsáveis
  + não salva, `changeStatus`→concluída, `reopen` nos caminhos com e sem
  `previous_status`, `deleteMission`, editar existente); `tests/Feature/
  ResponsibleSelectorTest.php` (`optionsFor` exclui usados/mantém o atual,
  `canAddRow`, `removeRow` mantém ≥1 linha); `tests/Feature/
  MilitaresManagerTest.php` (criar com `ativo`+`ordem` no fim, `toggleAtivo` sem
  apagar, `moveUp`/`moveDown`, editar). Removido o import não usado
  `WithoutModelEvents` de `database/seeders/MilitarSeeder.php` (achado 3.3 da
  auditoria). Rodei `vendor/bin/pint` (fix): normalizou 5 arquivos de scaffolding
  pré-existentes (`bootstrap/providers.php`, `config/{auth,database,logging}.php`,
  `database/factories/UserFactory.php`) — só estilo, conferi o diff (10 inserções/
  6 remoções, nada funcional); `vendor/bin/pint --test` depois: **limpo**.
  Adicionado `/.phpunit.result.cache` ao `.gitignore`.
  **VERIFICADO:** `php artisan test` → **21 testes / 56 asserções, exit 0**;
  `vendor/bin/pint --test` → passed; `php -l` limpo em todos os arquivos novos.
  Rodei a suíte de novo APÓS o Pint para garantir que a normalização não quebrou
  nada (continua verde). Fase sem UI → sem prova de navegador (correto).
  **PENDENTE / observação honesta:** o PHPUnit marca os 21 testes com o selo
  "deprecated" por causa de um aviso do **PHP 8.5** (`Constant
  PDO::MYSQL_ATTR_SSL_CA is deprecated`) disparado quando o `config/database.php`
  do Laravel 11 avalia o bloco `mysql` (o `pdo_mysql` está carregado neste
  ambiente). **Não é falha** — a suíte sai com exit 0 e `failOnDeprecation` está
  desligado; é ruído de ambiente e some quando a **A1** subir para o Laravel 12.
  Não mexi em `config/database.php` além do que o Pint normalizou, pra não
  antecipar a A1.

- **2026-07-04** — **Remediação A1 concluída — Upgrade Laravel 12 +
  dependências.** Antes de mexer, confirmei o estado real: `php artisan --version`
  = 11.54.0 e `composer audit` = 3 advisories em `laravel/framework`. Li os 3
  advisories de verdade (não confiei na memória): o mais restritivo, "Temporary
  Signed URL Path Confusion" (`GHSA-crmm-hgp2-wgrp`), exige **`<12.61.1`** — ou
  seja, 12.60 não bastaria, mirei o 12.x mais recente. Testei conectividade
  (`composer diagnose` → packagist OK) porque o log da Fase 4 dizia "sem internet"
  (o ambiente varia; hoje tinha). Backup de `composer.json`/`composer.lock` e de
  `database/database.sqlite` (para `%TEMP%`) antes de qualquer coisa.
  Editei `composer.json`: `laravel/framework` `^11.9`→`^12.0`, `laravel/tinker`
  `^2.9`→`^2.10`, `nunomaduro/collision` `^8.1`→`^8.6`, `phpunit/phpunit`
  `^11.0.1`→`^11.5.3` (alinhado ao skeleton do Laravel 12, para o resolver não
  escolher versões antigas). `composer update --with-all-dependencies`:
  `laravel/framework` v11.54.0 → **v12.62.0**, `nikic/php-parser` 5.7→5.8, novo
  `symfony/polyfill-php84`. Nenhuma mudança de código de app foi necessária
  (`bootstrap/app.php`, casts do Mission/Militar e `config/*` seguem compatíveis
  no salto 11→12 — a suíte e o smoke provaram).
  **VERIFICADO:** `composer audit` → **"No security vulnerability advisories
  found."**; `php artisan test` → **21 testes / 56 asserções, exit 0** (e o selo
  "deprecated" do PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA`, registrado na A0, **sumiu** —
  o Laravel 12 guarda o constante no `config/database.php`); `vendor/bin/pint
  --test` → passed; `php artisan migrate:status` → tudo "Ran", sem pendências.
  **Smoke no navegador** (`preview_start` porta 8013, viewport forçado a 1280px):
  as 6 telas do painel, uma a uma via `wire:click` real no DOM — visão geral
  (stats/próxima missão/carga por militar renderizados), calendário (grid com as
  6 missões da semana), todas as missões (tabela com 8 linhas), concluídas (com
  botão "Reabrir"), modal "Nova missão" (9 campos + seletor de responsáveis
  progressivo), modo monitor (`.painel-root.monitor-mode`, tela de TV com 18
  elementos — screenshot conferido). **Zero erro de console** em todas.
  Observação de ferramenta (não é bug do app): o botão "Ativar modo monitor" fica
  em `.sidebar-bottom`, escondido abaixo de ~1024px; precisei forçar 1280px para
  ele ficar visível — comportamento responsivo pré-existente, não regressão.
  **Dados:** não houve escrita (só naveguei e abri/fechei o modal); a demonstração
  seguiu intacta, mas mantive o backup do `.sqlite` em `%TEMP%` por precaução.
  **PENDENTE:** nenhuma. `composer update` roda na máquina de DEV (com internet);
  o deploy na VM offline continua usando o bundle já migrado (fora do escopo da A1).

- **2026-07-04** — **Remediação A2 concluída — Autenticação + trilha de
  auditoria + remoção da API REST.** Antes de codar, li de verdade os arquivos
  reais: `routes/web.php`, `MissionController.php`, `User.php`, a migration de
  `users`, `Painel.php`, `MilitaresManager.php`, os shells `painel.blade.php`/
  `militares.blade.php`, `bootstrap/app.php`, `DatabaseSeeder.php` e os 4 arquivos
  de teste da A0. **Backup** de `database/database.sqlite` no scratchpad antes de
  qualquer `migrate`. **Decisões já travadas** (não reabri): login completo à mão,
  remover a API antiga por inteiro.
  **A2.1 Login:** migration `2026_07_04_160000_add_admin_fields_to_users_table`
  (`is_admin` bool default false, `nome_guerra` nullable); `User` ganhou `HasFactory`,
  os campos no `fillable`, cast `is_admin=>boolean` e `nomeExibicao()`. Componente
  `App\Livewire\Auth\Login` (`Auth::attempt` + `RateLimiter`, 5 tentativas por
  e-mail+IP, `session()->regenerate()`), view shell `resources/views/auth/login.blade.php`
  (aplica tema salvo do localStorage antes do paint + botão de alternar tema) e
  view do componente `resources/views/livewire/auth/login.blade.php`. Rotas: `/login`
  (`guest`) e `POST /logout` (`auth`); **todas as demais sob `auth`**; guest cai em
  `/login`. Sidebar do painel: bloco de perfil agora mostra o usuário logado
  (iniciais + papel) e um botão **"Sair"** (form POST + `@csrf`, ícone `logout` novo
  no `<x-icon>`); a antiga copy "Toda a seção pode editar" saiu. CSS de login
  (`.auth-*`) e ajuste do `.profile` (classe `.profile-info` + `.logout-btn`,
  corrigindo a regra mobile que dependia de `div:last-child`) em `public/css/app.css`.
  **A2.2 Papéis/gestão:** middleware `admin` (`EnsureUserIsAdmin`) registrado em
  `bootstrap/app.php`; `/militares` e `/usuarios` só admin (e `mount()` de ambos os
  componentes aborta 403 — defesa em profundidade). Comando `app:create-user`
  (criar/redefinir senha offline — sem "esqueci a senha"), `UserSeeder` (admin
  inicial `admin@25bc.local` / senha `admin1234` a trocar no deploy) adicionado ao
  `DatabaseSeeder`. Tela `App\Livewire\UsuariosManager` + views (criar usuário,
  redefinir senha, alternar papel; impede o próprio usuário de se auto-rebaixar).
  **A2.3 Auditoria:** migration `create_activity_log_table` (`user_id` nullable
  `nullOnDelete`, `action`, `subject`, `subject_id`, `description`, só `created_at`);
  model `ActivityLog` com `record()` estático (pega `Auth::id()`). Registro em:
  login, criar/editar/excluir/mudar-situação/reabrir missão (`Painel`), criar/editar/
  (in)ativar militar (`MilitaresManager`), criar/editar/promover/rebaixar usuário
  (`UsuariosManager`). `fallbackCompleter()` do `Painel` passa a **sugerir o usuário
  logado** como quem concluiu (segue editável no `<select>`). **A2.4 Remover API:**
  `git rm app/Http/Controllers/MissionController.php`; rotas `/missions` (+`/reset`)
  retiradas de `routes/web.php`; `Mission::rules()`/`applyCompletion()` mantidos
  (comentários que citavam "API JSON/MissionController" atualizados). `resetDemo()`
  do `Painel` (gated a `local`) segue existindo.
  **Testes:** `PainelTest` e `MilitaresManagerTest` migrados para `actingAs`
  (o painel exige login; militares exige admin). Novos: `AuthTest` (guest→login,
  login ok+auditoria, senha errada, logout, comum 403 em /militares+/usuarios,
  admin 200, `/missions` agora 404), `UsuariosManagerTest` (criar, hash, não se
  auto-rebaixar, promover/rebaixar outro, comum não monta o componente),
  `CreateUserCommandTest` (criar admin, redefinir senha sem duplicar, recusar senha
  curta). **VERIFICADO:** `php artisan test` → **36 testes / 99 asserções, exit 0**;
  `vendor/bin/pint --test` → limpo (Pint importou `EnsureUserIsAdmin`/`UserFactory`);
  `composer audit` → limpo; `migrate:status` → todas "Ran". `php -l` limpo em todos
  os PHP tocados. **Navegador** (`preview_start`, porta 8013): guest→`/login`; login
  do admin → painel, com `activity_log` "login" gravado (conferido no tinker); sidebar
  com perfil "A"/Sair e os 2 links de admin; criei um usuário comum pela tela
  `/usuarios` (auditado); logout pelo botão → `/login`; logado como comum: sidebar
  **sem** links de admin e `fetch` retornando **403** em `/militares` e `/usuarios`
  e **404** em `/missions`; login provado em **claro E escuro** (o toggle da tela de
  login alterna e persiste `theme-dark` — conferido com `preview_inspect`) e em
  **mobile 375px**; **zero erro de console** em todas as telas. Ao final, limpei os
  dados de teste do banco de dev (removido o usuário comum e os `activity_log` de
  teste; o admin semeado permaneceu). **Dados:** backup do `.sqlite` mantido no
  scratchpad; o schema migrado do dev NÃO foi revertido (o backup era pré-migration,
  restaurá-lo quebraria o dev) — só limpei as linhas de teste.
  **PENDENTE (vai para a A6, como o mestre já previa):** as páginas `/militares` e
  `/usuarios` ainda não têm tema escuro, e o contraste do `--muted` no escuro
  (achado 5.3) segue abaixo de AA — ambos no escopo declarado da Fase A6. Nenhuma
  pendência de VM nesta fase (tudo local/navegador).

- **2026-07-05** — **Remediação A3 concluída — Correções de lógica e validação
  em pt-BR.** Antes de codar, li de verdade `Mission.php` (`rules()`),
  `Painel.php` (`actualStatus`/`teamWorkload`/`rules()`), `ResponsibleSelector.php`
  (`optionsFor`) + a view, `MilitaresManager.php` e os 3 arquivos de teste tocados,
  e confirmei que NÃO existia `lang/` (mensagens saíam em inglês). Backup do
  `.sqlite` no `%TEMP%` antes do teste no navegador.
  **(1) Atrasada vs. andamento (achado 1.1):** perguntei ao usuário (muda
  comportamento visível — regra 8). Resposta: "é possível estar em andamento, mas
  mesmo assim estar atrasada" → as duas coexistem. **Mantive `Painel::actualStatus`
  como está** (qualquer não-concluída vencida = atrasada), só adicionei comentário
  registrando a decisão e um teste travando: pendente vencida E andamento vencida
  contam como atrasada; concluída vencida não. Nenhuma mudança de comportamento —
  achado 1.1 rebaixado a "intencional".
  **(2) Intervalo de data (achado 1.2):** `Mission::rules()` no campo `date` ganhou
  `after_or_equal:2020-01-01` + `before:2100-01-01` (casado com `date_format:Y-m-d`).
  Teste: `2999-12-31` rejeitada, não grava.
  **(3) Responsável inativado no seletor (achado 1.3):** `ResponsibleSelector::
  optionsFor()` agora adiciona o valor atual da linha à lista de opções mesmo quando
  ele não está em `people()` (só ativos) — sem isso o `<select>` da linha ficaria
  sem a opção e o responsável seria perdido ao salvar. Novo método `isInactive()` e
  a view (`responsible-selector.blade.php`) marca "(inativo)" na opção fora de
  `people`. Teste: `optionsFor` inclui o valor atual fora de `people`; some das
  OUTRAS linhas.
  **(4) "Toda a seção" fora da carga (achado 1.4):** `Painel::teamWorkload` pula
  `'Toda a seção'` (não é militar). Teste: `team` não lista "Toda a seção", mas
  lista o militar real.
  **(5) Validação em pt-BR (achado 3.2):** criado `lang/pt_BR/validation.php`
  (traduções padrão do Laravel 12) + `validationAttributes()` em `Painel`
  (`form.title`→"título" etc.) e `MilitaresManager` (`nome_guerra`→"nome de guerra"
  etc.). Adicionado `APP_LOCALE=pt_BR`/`APP_FALLBACK_LOCALE=en` ao `phpunit.xml.dist`
  para o teste de mensagem não depender do `.env`. Testes: mensagens saem "O campo
  título é obrigatório." / "O campo nome de guerra é obrigatório." (sem "field is
  required" nem chave crua "form.title").
  **VERIFICADO:** `php artisan test` → **42 testes / 123 asserções, exit 0** (era
  36/99); `vendor/bin/pint --test` → passed; `composer audit` → limpo;
  `migrate:status` → todas "Ran"; `php -l` limpo em todos os arquivos tocados.
  **Navegador** (`preview_start`, porta 8013): criei via tinker uma missão com
  responsável "Cb Fantasma (ex-membro)" (não está entre os ativos), logei como admin
  e abri a edição — o `<select>` da 1ª linha exibiu "Cb Fantasma (ex-membro)
  (inativo)" com o valor SELECIONADO e preservado, a 2ª linha "Cb Luide" sem
  marcador, e a exclusão cruzada de opções entre linhas continuou certa. Provado em
  tema **claro E escuro** e nas larguras **375/768/1280px** (sem overflow do
  `.resp-rows`). Validação: com o título vazio, "Salvar missão" mostrou
  "O campo título é obrigatório." em pt-BR (claro e escuro). **Zero erro de
  console.** Ao final removi a missão de teste (voltou a 8 missões de demonstração);
  o backup do `.sqlite` segue no `%TEMP%`. As chamadas ao componente Livewire no
  navegador foram feitas via `$wire.call(...)` porque o `preview_click`/`.click()`
  no `wire:click` voltou a não disparar de forma confiável (mesma peculiaridade da
  ferramenta já registrada nas Fases 4/5 — não é bug do app).
  **PENDENTE:** nenhuma pendência de VM (tudo local/navegador). O contraste do erro
  de validação e o tema escuro em `/militares`/`/usuarios` seguem para a A6, como já
  previsto.

- **2026-07-06** — **Remediação A4 concluída — Offline & deploy hardening.**
  Antes de codar, li de verdade: `public/css/app.css` (topo, `:root`, `--mono`),
  `resources/views/painel.blade.php`/`militares.blade.php` (os `<link>` do Google
  Fonts) e as shells `auth/login.blade.php`/`usuarios.blade.php` (que já só usam
  `app.css`, sem link de fonte), `config/database.php` (bloco `sqlite` com
  `journal_mode/synchronous/busy_timeout = null`), `.gitignore`,
  `scripts/backup-sqlite.sh`, `build-bundle.ps1`, `.env.production.example`,
  `DEPLOY.md`, `database/seeders/UserSeeder.php` e `app/Console/Commands/CreateUser.php`.
  Backup de `database/database.sqlite` no `%TEMP%` antes de subir o app (o WAL
  converte o arquivo).
  **(1) Fontes self-hosted (achado 2.3):** com internet nesta máquina, baixei do
  Google Fonts (UA de navegador) os 4 `.woff2` — Archivo `latin`/`latin-ext` e
  JetBrains Mono `latin`/`latin-ext` — para `public/fonts/` (validados por magic
  bytes `wOF2`). São fontes VARIÁVEIS: a URL `latin` é a mesma para todos os pesos,
  logo 1 arquivo por subset cobre a faixa inteira. Adicionei o bloco `@font-face` no
  topo de `public/css/app.css` (`font-weight: 100 900` p/ Archivo, `100 800` p/
  JetBrains; `unicode-range` copiados do próprio CSS do Google; `src: url("/fonts/…")`).
  Removi os `<link rel="preconnect">` + o `<link>` de `css2?family=…` de
  `painel.blade.php` e `militares.blade.php` (login/usuarios não tinham link e agora
  também herdam as fontes via `app.css`). **Prova offline:** `preview_network`
  registrou SÓ requisições a `localhost:8013` (inclusive `/fonts/archivo-latin.woff2`
  e `/fonts/jetbrains-mono-latin.woff2`) — ZERO a `fonts.googleapis.com`/`gstatic.com`.
  **(2) WAL (achado 8.1):** `config/database.php` bloco `sqlite` → `journal_mode` =
  `env('DB_JOURNAL_MODE','WAL')`, `synchronous` = `env('DB_SYNCHRONOUS','NORMAL')`,
  `busy_timeout` = `env('DB_BUSY_TIMEOUT',5000)`. Confirmei no framework
  (`vendor/…/Connectors/SQLiteConnector.php`) que ele roda `pragma journal_mode=…`
  quando a chave está `isset` (por isso saiu de `null` p/ valor). `.gitignore` passou
  a ignorar `/database/*.sqlite-wal` e `-shm`. `scripts/backup-sqlite.sh`: no fallback
  `cp` (sem `sqlite3`) agora copia também `-wal`/`-shm` p/ o backup ficar consistente.
  **(3) `build-bundle.ps1` (achado 7.x):** `$TempEnv` definido ANTES do `try` e
  removido no `finally` (não vaza `.env`/APP_KEY se algo falhar no meio); força
  `DB_JOURNAL_MODE=DELETE` no `.env` temporário do build p/ o banco do zip ser um
  arquivo único (sem `-wal`/`-shm`; converte p/ WAL na VM no 1º uso); semeia o admin
  (`UserSeeder`) além do `MilitarSeeder`; assertiva de que `public/fonts/*.woff2`
  entrou no staging (falha o build se sumir).
  **(4) Docs:** `.env.production.example` (bloco WAL comentado — já é default no config
  — + nota de troca de senha do admin) e `DEPLOY.md` (Passo 1: bundle agora semeia
  admin e inclui as fontes; nota-blockquote sobre WAL/`-wal`/`-shm`/backup; Passo 6:
  nova seção "Trocar a senha do administrador inicial" com o cuidado de que o papel
  `is_admin` vem SÓ da flag `--admin`, então redefinir a senha do admin sem `--admin`
  o rebaixaria — li o `CreateUser.php` p/ confirmar esse comportamento).
  **Teste novo:** `tests/Feature/DatabaseConfigTest.php` — checa os 3 valores de config
  e prova o COMPORTAMENTO num banco em ARQUIVO temporário (`journal_mode` vira `wal`),
  já que o banco `:memory:` dos testes não vira WAL.
  **VERIFICADO:** `php artisan test` → **44 testes / 127 asserções, exit 0** (era
  42/123; +2 da A4); `vendor/bin/pint --test` → passed; `php -l` limpo em
  `config/database.php` e no teste novo. **Navegador** (`preview_start` 8013, logado
  como admin `admin@25bc.local`): fontes carregadas do próprio servidor,
  `document.fonts.check` confirma Archivo 700 e JetBrains Mono 800 (cobrindo
  `Seção nº ção ã õ ç`); `body` computa `Archivo…`, `.mono` computa `JetBrains Mono…`;
  provado em CLARO e ESCURO e em mobile 375px; `preview_network` sem NENHUM domínio
  externo; zero erro de console. WAL provado end-to-end no banco de dev rodando
  (`pragma journal_mode`=wal, `synchronous`=1=NORMAL, `busy_timeout`=5000).
  **`build-bundle.ps1` reexecutado de fato** (não só lido): zip gerado, extraído e
  conferido — sem `.env`, 4 fontes em `public/fonts/`, só `database.sqlite` (sem
  `-wal`/`-shm`), `militares`=6, `users`=1 (admin@25bc.local `is_admin=1`),
  `missions`=0, `journal_mode=delete`. Removi o zip/extração temporária ao final; o
  banco de dev ficou em WAL (estado novo pretendido) e o backup pré-WAL segue no
  `%TEMP%`.
  **PENDENTE DE VM (Debian/FrankenPHP real):** conversão/estabilidade do WAL sob
  multi-worker do FrankenPHP; e o backup via `sqlite3 .backup` com o binário `sqlite3`
  de verdade (nesta máquina Windows ele não está no PATH — o fallback `cp`+`-wal`/`-shm`
  é o que roda aqui).

- **2026-07-06** — **Remediação A5 concluída — Performance (paginação, escopo de
  queries e memoização).** Antes de mexer, li `app/Livewire/Painel.php` (817 linhas)
  inteiro e os partials `calendar-grid.blade.php`/`tv-screen.blade.php` — confirmei que
  o calendário e a TV mostram missões CONCLUÍDAS também (classe `done`), não só
  pendentes, o que definiu como escopar as consultas sem quebrar a exibição.
  **(1) Escopo de queries por janela de data (achado 4.1):** `render()` trocou o único
  `Mission::orderBy(...)->get()` (carregava a tabela INTEIRA a cada requisição,
  inclusive anos de histórico já concluído, mesmo com `wire:poll` a cada 15s) por 4
  consultas focadas: `$open` (não concluídas, qualquer data — necessário porque uma
  missão "atrasada" pode ser antiga, confirmado pelo teste da A3 com data `2020-01-02`),
  `$todayAny` (só hoje, qualquer situação), `$weekMissions` (só a semana ATUAL) e
  `$calWindow` (só a semana NAVEGADA do calendário; reaproveita `$weekMissions` quando
  coincidem). `buildStats()`/`buildTvData()` passaram a receber essas coleções já
  escopadas em vez da coleção gigante.
  **(2) Paginação/limite (achado 4.1):** "Todas as missões" e "Concluídas" ganharam
  `$tableLimit`/`$historyLimit` (50 cada, constante `LIST_PAGE_SIZE`) com botão
  "Carregar mais" (`loadMoreTable`/`loadMoreHistory`, +50 por clique, CSS `.load-more`
  reaproveitando `.text-btn`); trocar o filtro segmentado reinicia o limite da tabela.
  O histórico agora busca do banco já `orderByDesc` + `take($historyLimit)`.
  **(3) Memoização (achado 4.2):** `people()`, `completers()` e `weekData()` cacheiam o
  resultado num campo privado durante o MESMO `render()` (a instância do Livewire é
  recriada a cada requisição, então o cache não vaza entre requisições) — eliminava a
  consulta duplicada de `Militar::ativos()` (rodava 2× por render) e o recálculo de
  `weekData` (2-3× por render).
  **(4) Índice composto opcional (achado 4.3):** migration
  `2026_07_06_191845_add_date_time_index_to_missions_table` — `index(['date','time'])`.
  **(5) `wire:poll` avaliado, não alterado:** os intervalos (15s dashboard / 12s TV)
  já eram adequados; o ganho real veio de reduzir o TAMANHO da consulta, não a
  frequência — mudar isso alteraria comportamento visível sem necessidade.
  **Teste novo** `tests/Feature/PainelPerformanceTest.php` (5 testes): paginação de
  "Todas as missões"/"Concluídas" com "carregar mais", reset do limite ao trocar
  filtro, calendário só traz missões da semana exibida, e uma única consulta a
  `militares` por render (via `DB::enableQueryLog()` — sem a memoização dava 2).
  **VERIFICADO:** `php artisan test` → **49 testes / 139 asserções, exit 0** (era
  44/127); `vendor/bin/pint --test` → passed; `composer audit` → limpo;
  `migrate:status` → todas "Ran" (migration nova aplicada). **Navegador**
  (`preview_start` 8013): criei via tinker 55 missões pendentes + 55 concluídas extras
  pra forçar o limite de 50 a aparecer de verdade — "Carregar mais missões" e "Carregar
  mais missões concluídas" confirmados em tema ESCURO/mobile (375px) e DESKTOP (1280px)
  claro; cliquei nos dois botões e confirmei mais linhas aparecendo e o botão sumindo
  quando não há mais itens. Naveguei pro calendário e voltei uma semana (`prevWeek` —
  2 cliques via `preview_click` não dispararam o `wire:click`, mesma peculiaridade de
  ferramenta já registrada nas Fases 4/5/A2; o mesmo botão via `.click()` em JS
  funcionou de imediato) e confirmei visualmente que as missões "atrasadas" de julho
  daquela semana aparecem lá e NÃO vazam pra semana atual — prova de que o escopo de
  data do calendário está correto. Zero erro de console em toda a sessão. Ao final,
  apaguei as 110 missões de teste e resemeei `MissionSeeder` (voltou a 8 missões de
  demonstração); backup do `.sqlite` pré-fase ficou em `%TEMP%`.
  **PENDENTE:** nenhuma pendência de VM (é tudo consulta/lógica local). O item
  "(bônus)" de medir contagem de queries antes/depois foi coberto de forma
  direcionada (teste da memoização de `militares`), não uma medição exaustiva de
  todas as queries do `render()` — suficiente pra travar a regressão que importava.

- [x] **Fase 0** — Preparação: branch git + commit do estado atual + ler arquivos-chave.
- [x] **Fase 1** — Blindagem de produção: bloquear `/missions/reset` fora de `local`,
      esconder botão `#resetBtn`, `.env.production.example`, script de backup do SQLite.
- [x] **Fase 2** — Deploy offline: `build-bundle.ps1` + `DEPLOY.md` (FrankenPHP, fallback
      de proxy, systemd, conferir extensão pdo_sqlite).
- [x] **Fase 3** — Cadastro de militares: tabela `militares` + CRUD Livewire (inativar em
      vez de apagar; missões guardam nome como snapshot, não reescrevem histórico).
- [x] **Fase 4** — Seletor de responsáveis progressivo (um + botão "+"), sem JS à mão.
- [x] **Fase 5** — Migrar a interface para Livewire, tela por tela, reaproveitando o CSS.
- [x] **Fase 6** — Corrigir bug do calendário (missões fora de 07h–18h somem).

---

## 🔑 FATOS DO PROJETO A RECONFERIR SEMPRE (mapa de onde as coisas estão)

> Confirme lendo o arquivo — não assuma que continua exato.

- **Laravel 12.62.0** (subido na Remediação A1; era 11.54.0), PHP 8.5 local /
  `require` `^8.2`, banco **SQLite** (`database/database.sqlite`). Sem npm/Vite.
  `composer audit` limpo desde a A1.
- **Autenticação (Remediação A2):** login à mão em Livewire (`App\Livewire\Auth\Login`,
  rota `/login` guest, `POST /logout`). **Todas** as rotas exigem `auth`, exceto
  `/login` e o health `/up`. Middleware `admin` (`App\Http\Middleware\EnsureUserIsAdmin`,
  alias em `bootstrap/app.php`) protege `/militares` e `/usuarios`. `users` ganhou
  `is_admin` e `nome_guerra`. Admin inicial: `UserSeeder` (`admin@25bc.local`). Criar/
  redefinir senha offline: `php artisan app:create-user`. Trilha de auditoria:
  tabela `activity_log` + `App\Models\ActivityLog::record()`, gravada nas ações de
  missão/militar/usuário/login.
- **API REST antiga REMOVIDA na A2:** `MissionController.php` apagado e as rotas
  `/missions` (+`/reset`) retiradas de `routes/web.php`. A interface (Livewire) já
  manipulava `Mission` direto desde a Fase 5. `App\Models\Mission::rules()`/
  `Mission::applyCompletion()` (estáticos) permanecem — única fonte da regra de missão.
- Model: `app/Models/Mission.php` (campo `responsibles` = array de strings).
- **Interface (desde a Fase 5): 100% Livewire/Blade, `public/js/app.js` foi
  APAGADO.** Componente principal: `App\Livewire\Painel`
  (`app/Livewire/Painel.php` + `resources/views/livewire/painel.blade.php`),
  embutido em `resources/views/painel.blade.php` (shell fino) via
  `<livewire:painel />`. `render()` pré-calcula "view models" (arrays) pra
  view só exibir. Partials: `resources/views/livewire/partials/*.blade.php`.
  Componentes Blade: `<x-icon name="...">` (ícones SVG) e `<x-live-clock>`
  (relógio/data ao vivo). CSS: `public/css/app.css` (classes de modo
  monitor/tema escuro migraram de `body.*` pra `.painel-root.*`, já que
  `.painel-root` é a raiz do componente Livewire dentro do `<body>`).
  Único JS restante: expressões Alpine.js (empacotado no bundle do Livewire
  v4, nada instalado à parte) no `<x-live-clock>`, e uma ponte pequena em
  `painel.blade.php` pra 3 coisas que só o navegador faz (tema no
  localStorage, Fullscreen API, sincronizar saída de tela cheia nativa) —
  nenhuma regra de negócio em JS.
  O campo "Responsável(is)" do modal continua sendo
  `App\Livewire\ResponsibleSelector` (Fase 4), mas a ponte de checkbox
  escondido foi REMOVIDA na Fase 5: agora ele dispara o evento
  `responsibles-changed` e `Painel` escuta com `#[On(...)]`.
- View do painel: `resources/views/livewire/painel.blade.php` (não mais
  `painel.blade.php`, que virou só o shell). A lista de pessoas
  (`$people`, método privado `people()` em `Painel.php`) vem de
  `\App\Models\Militar::ativos()` + `push('Toda a seção')`, igual antes.
- Calendário desenha 07h–18h por padrão (constantes `CAL_START`/`CAL_END` em
  `App\Livewire\Painel`), mas desde a **Fase 6** a faixa se expande
  dinamicamente em `buildWeekGrid()` quando alguma missão da semana exibida
  cai fora dela — nenhuma missão some do grid por causa do horário.
- **Performance (Remediação A5):** `Painel::render()` NÃO usa mais uma única query
  "traga tudo" — usa 4 consultas escopadas por janela de data (`$open` = não
  concluídas de qualquer data, `$todayAny`/`$weekMissions`/`$calWindow` = janelas
  específicas). "Todas as missões" e "Concluídas" são paginadas (`$tableLimit`/
  `$historyLimit`, botão "Carregar mais"). `people()`/`completers()`/`weekData()`
  são memoizados por render (campo privado, não persiste entre requisições).
- `.env`: `APP_ENV=local`, `APP_DEBUG=true`. Autenticação ativa desde a A2 (ver acima).
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
