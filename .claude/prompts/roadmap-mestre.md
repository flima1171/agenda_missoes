# PROMPT MESTRE — Evolução da Agenda de Missões (25º BC)

> Cole este prompt inteiro para o agente. Ele implementa, em fases verificadas,
> tudo que foi combinado: deploy offline, blindagem de produção, cadastro de
> militares, seletor de responsáveis progressivo, migração da interface para PHP
> (Livewire) e correção do bug do calendário. Foi escrito para IMPEDIR alucinação.

---

## PAPEL E POSTURA

Você é um engenheiro sênior trabalhando num projeto Laravel real que vai para a
intranet de uma unidade militar. Seja conservador: prefira mudanças pequenas,
verificadas e reversíveis. NÃO tente entregar tudo de uma vez.

## CONTEXTO REAL DO PROJETO (confirme por leitura antes de confiar)

- Laravel 11, PHP 8.2, banco **SQLite** (arquivo único). Sem npm/Vite — os assets
  são estáticos em `public/`.
- Backend: API REST interna em `routes/web.php` (`/missions` CRUD + `/missions/reset`),
  controller em `app/Http/Controllers/MissionController.php`, model `app/Models/Mission.php`.
- Frontend ATUAL: **todo em JS puro** em `public/js/app.js` (monta dashboard,
  calendário, tabelas, modal; faz polling a cada 15s) + CSS em `public/css/app.css`.
  View única `resources/views/painel.blade.php`.
- A lista de militares HOJE é um array PHP fixo `$painelPeople` em
  `resources/views/painel.blade.php` (~linha 198), injetado no JS via `window.__PAINEL__`.
- Responsáveis são renderizados como checkboxes (`.chip-option`) em `public/js/app.js`
  (~linha 499) e salvos como **array de strings** no campo `responsibles` da missão.
- O calendário só desenha o intervalo 07h–18h (`CAL_START`/`CAL_END` em
  `public/js/app.js` ~linha 16).
- `.env` está `APP_ENV=local` / `APP_DEBUG=true`.
- NÃO há autenticação.

> Números de linha são pontos de partida, não verdade absoluta. Releia os arquivos
> antes de editar; podem ter mudado.

## DECISÕES JÁ TOMADAS (não reabra a discussão)

1. **Interface migra para Livewire** (interatividade escrita em PHP/Blade),
   **reaproveitando o CSS existente** (`public/css/app.css`). Objetivo: o dono do
   projeto NÃO sabe JS e quer manter tudo em PHP, sem perder a fluidez de app nem
   o visual atual. Atualização ao vivo do painel e do modo monitor deve usar
   `wire:poll` (nada de JS escrito à mão).
2. **Deploy na VM usa FrankenPHP** (binário único, sem `apt`/dependências), porque
   a VM (Proxmox VE 7.4-3, Debian 11) fica atrás de um proxy que bloqueia a internet.

## REGRAS ANTI-ALUCINAÇÃO (obrigatórias, sem exceção)

1. **Nunca invente.** Não cite arquivo, classe, rota, método de Laravel/Livewire ou
   comando de terminal sem antes confirmar que existe (Grep/Read/`--help`/doc oficial).
   Se algo do contexto acima não bater com o código real, PARE e relate o que
   encontrou — não prossiga em cima de suposição.
2. **Não invente números de versão nem comandos de FrankenPHP/Livewire de cabeça.**
   Consulte a documentação oficial ATUAL. Se não tiver acesso à internet, diga isso
   explicitamente e marque o trecho como "confirmar versão/commando na doc oficial"
   em vez de chutar.
3. **Prove visualmente.** Toda mudança observável no navegador deve ser verificada
   com os preview tools (`preview_start`, `preview_screenshot`, `preview_inspect`,
   `preview_snapshot`, `preview_console_logs`). Nunca diga "deve funcionar" sem rodar.
4. **Uma fase por vez.** Execute as fases NA ORDEM. Ao terminar UMA fase: pare,
   mostre a prova (prints/medições), liste o que mudou e ESPERE a confirmação do
   usuário antes de começar a próxima. Nunca faça duas fases no mesmo passo.
5. **Rede de segurança.** Antes de começar, crie um branch git e faça um commit do
   estado atual. Faça commit ao fim de cada fase concluída e verificada.
6. **Não quebre o que funciona.** Nas migrações para Livewire, só remova código JS
   antigo DEPOIS que o equivalente em Livewire estiver provado funcionando. Migre
   tela por tela, não tudo de uma vez.
7. **Relate honestamente.** Se um teste falhar, mostre a saída. Se algo só pode ser
   verificado na VM real (ex: FrankenPHP no Debian), diga que ficou pendente de
   verificação no alvo — não finja que está OK.
8. **Na dúvida sobre uma decisão do usuário, pergunte** (ex: se um militar promovido
   deve reescrever missões passadas). Não decida sozinho o que muda dados.

---

## FASES

### FASE 0 — Preparação (rede de segurança)
- Confirme `git status` limpo o suficiente; crie branch `evolucao/roadmap`.
- Commit do estado atual.
- Leia: `MissionController.php`, `Mission.php`, `routes/web.php`,
  `resources/views/painel.blade.php`, `public/js/app.js`, `public/css/app.css`, `.env`.
- Suba o app com `preview_start` e confirme que a tela atual carrega.
- **Pronto quando:** branch criado, app rodando, arquivos-chave lidos.

### FASE 1 — Blindagem de produção (rápido, alto valor)
Objetivo: evitar perda de dados quando entrar em produção.
- O endpoint `/missions/reset` (apaga TODAS as missões e recarrega demo) só pode
  funcionar em ambiente `local`. Fora de `local`, deve retornar 403/404. Esconda o
  botão de reset (`#resetBtn`) quando não estiver em `local`.
- Crie `.env.production.example` com `APP_ENV=production`, `APP_DEBUG=false`,
  timezone correto, SQLite. NÃO altere o `.env` local de desenvolvimento.
- Crie um script de backup do `database/database.sqlite` (cópia com timestamp,
  mantendo N dias) + instruções de agendamento (cron na VM).
- **Pronto quando:** com `APP_ENV=production`, `/missions/reset` é bloqueado e o
  botão some; em `local` continua funcionando; script de backup testado gerando cópia.

### FASE 2 — Pipeline de deploy offline (FrankenPHP) + DEPLOY.md
Objetivo: instalar/rodar na VM sem depender da internet da OM.
- Crie `build-bundle.ps1` (Windows) que: roda `composer install --no-dev
  --optimize-autoloader`, garante o SQLite migrado, e empacota o projeto (código +
  `vendor/` + banco) num zip, EXCLUINDO `.env` local, `.git`, caches de dev.
- Crie `DEPLOY.md` com o procedimento completo e verificado:
  1. Como obter o binário do FrankenPHP numa máquina com internet e levar para a VM
     (confirme na doc oficial o nome do release e os comandos; NÃO invente versão).
  2. Fallback: como configurar o proxy da OM no `apt` e no `composer`
     (`/etc/apt/apt.conf.d/`, variáveis `http_proxy`/`https_proxy`/`no_proxy`).
  3. Como rodar a aplicação com FrankenPHP servindo o Laravel, incluindo verificar
     se a extensão **pdo_sqlite** está presente no binário (a app depende dela).
  4. Serviço systemd para subir no boot e reiniciar em falha.
  5. `php artisan migrate --force` + caches de produção (`config:cache`,
     `route:cache`, `view:cache`).
- Marque claramente cada passo que só pode ser confirmado na VM real como
  "PENDENTE DE VERIFICAÇÃO NO ALVO".
- **Pronto quando:** o zip é gerado localmente; `DEPLOY.md` está completo, coerente
  e honesto sobre o que falta testar no Debian.

### FASE 3 — Cadastro de militares (a seção muda)
Objetivo: gerenciar quem entra, sai e é promovido, sem editar código.
- Migration criando tabela `militares`: `id`, `posto_graduacao`, `nome_guerra`
  (ou nome de exibição), `ativo` (bool), `ordem` (para ordenação), e campos de
  contato OPCIONAIS já pensando no futuro (`telegram_id`, `telefone`) — podem ficar
  nulos por enquanto.
- Model `Militar` + seeder populando a partir da lista atual `$painelPeople`.
- Tela de gestão (Livewire): listar, adicionar, editar, **inativar** (não apagar,
  para preservar histórico) e reordenar. Promoção = editar `posto_graduacao`/nome.
- O seletor de responsáveis passa a ler os **militares ativos** desta tabela
  (não mais do array fixo no Blade).
- **DECISÃO DE DADOS (siga esta a menos que o usuário diga o contrário):** as missões
  continuam guardando o **nome do responsável como texto (snapshot)** no momento da
  atribuição. Assim, promover/renomear um militar NÃO reescreve missões passadas —
  o histórico preserva "quem fez, com que posto na época". Documente isso no código.
- **Pronto quando:** dá para adicionar/renomear/inativar um militar pela tela; novas
  missões escolhem da lista de ativos; missões antigas mantêm os nomes originais.
  Verificado no navegador.

### FASE 4 — Seletor de responsáveis progressivo
Objetivo: substituir a lista poluída de checkboxes por "um por vez + botão +".
- No formulário de missão (Livewire), começar com UM seletor mostrando os militares
  ativos ainda não escolhidos; um botão "+" adiciona outra linha; um "x" remove.
- Sem JS escrito à mão — usar Livewire (`wire:click`, arrays no componente).
- **Pronto quando:** verificado no preview em 3 larguras (mobile/tablet/desktop);
  adicionar e remover responsáveis funciona e salva corretamente.

### FASE 5 — Migração da interface para Livewire (a maior; incremental)
Objetivo: tirar a dependência de `public/js/app.js`, mantendo visual e fluidez.
- Instale Livewire via composer (confirme versão compatível com Laravel 11 na doc).
- Migre TELA POR TELA, reaproveitando `public/css/app.css`: visão geral, calendário,
  tabela "todas as missões", concluídas, modal de nova/editar missão, modo monitor.
- Atualização ao vivo (o polling de 15s) e a rotação do modo monitor: usar `wire:poll`.
- Só remova cada trecho de `public/js/app.js` DEPOIS de provar a paridade da tela
  correspondente em Livewire (conteúdo + sem overflow, verificado no preview).
- **Pronto quando:** todas as telas funcionam sem `app.js` (ou com o mínimo residual
  justificado), o monitor atualiza sozinho, e não há regressão visual (prints das
  telas em 3 larguras).

### FASE 6 — Bug do horário no calendário
Objetivo: missões fora de 07h–18h não podem sumir.
- Hoje missões fora dessa faixa são salvas mas não aparecem no calendário.
- Corrija: amplie a faixa, ou ajuste dinamicamente para caber as missões do dia, ou
  ao menos sinalize que há missões fora do horário exibido.
- **Pronto quando:** uma missão às 06:00 e outra às 20:00 aparecem no calendário
  (verificado no preview).

---

## AO FIM DE CADA FASE
Pare e entregue: (1) o que mudou, com caminhos de arquivo; (2) prova visual/medições;
(3) o que ficou pendente de verificar na VM, se houver; (4) pergunte se pode seguir
para a próxima fase. Faça commit. NÃO comece a próxima fase sem o "ok".
