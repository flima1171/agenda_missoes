# Prompt mestre — corrigir texto cortado e layout quebrado (Agenda de Missões)

Cole este prompt inteiro para o agente quando quiser caçar e corrigir problemas de
texto cortado / layout quebrado no projeto. Ele foi escrito para impedir que o
agente "alucine" (invente arquivos, classes ou frameworks que não existem, ou
declare sucesso sem provar visualmente).

---

## Objetivo

Encontrar e corrigir, em todo o projeto `agenda_missoes`, os casos em que texto
aparece cortado de forma ilegível ou elementos vazam para fora de seus
contêineres (cards, colunas do calendário, células de tabela). O projeto é
Laravel (Blade) no backend e JS puro + CSS puro no frontend — **não** é
React/Vue/Tailwind. Não presuma bibliotecas ou arquivos que não foram
confirmados por leitura direta.

## Contexto já levantado (verifique antes de confiar, o código pode ter mudado)

- `public/js/app.js` função `calendarGridHTML()` (por volta da linha 178-192)
  gera o HTML das missões do calendário e é usada tanto na view normal do
  calendário quanto no "modo monitor" (tela cheia).
- `public/css/app.css`:
  - `.calendar-grid` (~linha 180): `grid-template-columns: 64px repeat(7, minmax(104px,1fr))`
  - `.tv-calendar-grid` (~linha 306): `grid-template-columns: 72px repeat(7, minmax(0,1fr))`
  - `.cal-mission strong`, `.cal-mission span` (~linha 199-200 e ~324):
    `white-space: nowrap; overflow: hidden; text-overflow: ellipsis;`
  - Mesmo padrão nowrap+ellipsis aparece em `.mission-main strong` (~122),
    `.mission-meta` (~123), `.responsible span` (~125), `.tv-mission .m strong` (~274).
- `resources/views/painel.blade.php` contém as views: `view-dashboard`,
  `view-calendar` (`#calendarGrid`), `view-missions` (`.all-table`),
  `view-history`, além do modo monitor (`body.calendar-monitor-mode`).

Estes números de linha e nomes são um ponto de partida, **não uma verdade
absoluta** — releia os arquivos antes de editar, pois podem ter mudado.

## Regras anti-alucinação (obrigatórias)

1. **Nunca edite um seletor, classe ou arquivo sem antes confirmar que ele
   existe** — use Grep/Read para localizar a ocorrência exata (arquivo + linha)
   antes de qualquer Edit. Se não encontrar o que o prompt menciona, pare e
   diga o que encontrou de fato, não invente.
2. **Não presuma stack incompatível.** Não sugira soluções de React, Vue,
   Tailwind, styled-components etc. Este projeto é Blade + CSS + JS vanilla.
3. **Não declare um problema resolvido sem prova visual.** Toda correção deve
   ser confirmada com os preview tools (`preview_start`, `preview_screenshot`,
   `preview_inspect`, `preview_snapshot`) rodando o app de verdade — nunca
   apenas "pela leitura do CSS deveria funcionar".
4. **Não misture achados de telas diferentes.** O projeto tem pelo menos duas
   renderizações do calendário (view normal e modo monitor/TV) que usam o
   mesmo HTML gerado por JS mas CSS parcialmente diferente — teste as duas
   separadamente e diga qual tela cada print corresponde.
5. **Diferencie truncamento intencional de layout quebrado de verdade:**
   - Truncamento intencional sem forma de ver o texto completo (ellipsis sem
     `title`) é um bug de usabilidade — a correção é adicionar `title="..."`
     com o texto completo e/ou permitir quebra de linha quando há espaço
     vertical.
   - Elemento que ultrapassa fisicamente a borda do card/coluna (overflow real,
     não é apenas `...`) é bug de layout — normalmente causado por falta de
     `min-width: 0` em filho de flex/grid, `box-sizing` incorreto, ou largura
     fixa maior que o contêiner pai.
   - Não aplique a mesma correção para os dois casos.
6. **Uma correção por vez, revalidada antes da próxima.** Não faça uma
   varredura de "corrigir tudo de uma vez" sem checar cada mudança
   isoladamente — isso é como se introduzem regressões silenciosas.

## Passo a passo

1. **Levantamento (somente leitura):**
   - Rode Grep por `white-space:\s*nowrap`, `text-overflow`, `overflow:\s*hidden`,
     `position:\s*absolute`, larguras fixas em `px` dentro de grids/flex, em
     `public/css/app.css` e em qualquer outro `.css`/`.blade.php` do projeto
     (não assuma que só existe esse um arquivo CSS — confirme com Glob).
   - Liste cada ocorrência como `arquivo:linha` + o elemento HTML/classe
     correspondente (procure no Blade/JS onde essa classe é usada).

2. **Reprodução com dados de pior caso:**
   - Suba o servidor com `preview_start`.
   - Se possível, insira/edite dados de teste com textos propositalmente
     longos (título de missão longo, nome de responsável composto longo,
     várias missões no mesmo horário/dia) — ou use os dados que já aparecem
     cortados nos prints do usuário ("Conferência de material carga",
     "Reunião com o chefe da seção") como casos de teste reais.
   - Abra cada tela relevante (visão geral, calendário normal, todas as
     missões, concluídas, modo monitor, modo monitor do calendário) e capture
     `preview_screenshot` + `preview_snapshot` **antes** de mexer no código.
   - Teste em pelo menos 3 larguras de viewport (`mobile`, `tablet`, `desktop`
     via `preview_resize`) e, se o app tiver dark mode, também em `dark`.

3. **Classificação:** para cada ocorrência encontrada no passo 1, marque qual
   dos três tipos da regra 5 se aplica, com base no que foi visto no passo 2
   (não classifique sem ter visto o resultado real na tela).

4. **Correção incremental:**
   - Aplique a correção de um problema por vez (ex: um seletor CSS ou um
     componente).
   - Prefira soluções que preservem a densidade de informação do painel
     (ex: `title` para tooltip com texto completo, `word-break: break-word`
     combinado com `-webkit-line-clamp` para 2 linhas, ajustar
     `grid-template-columns`/`minmax` para dar mais espaço mínimo às colunas)
     em vez de simplesmente aumentar fontes ou remover truncamento por completo.
   - Depois de cada mudança, recarregue o preview e confira com
     `preview_inspect` (propriedades CSS computadas) e `preview_screenshot`
     que o problema sumiu e que nada mais quebrou ao lado.

5. **Varredura final:** repita a busca do passo 1 para confirmar que não
   sobrou nenhuma ocorrência do padrão problemático fora do que foi
   intencionalmente mantido (ex: truncamento com `title` é aceitável e não
   precisa virar quebra de linha).

## Critério de conclusão

Só declare a tarefa concluída quando, para cada tela testada, houver print
mostrando:
- Nenhum texto cortado sem forma de acessar o conteúdo completo (tooltip ou
  quebra de linha), **e**
- Nenhum elemento (caixa de missão, card, célula) ultrapassando visualmente os
  limites do seu contêiner, **e**
- O mesmo teste feito em pelo menos 3 larguras de tela, com print como prova.

Se algo não puder ser verificado visualmente (ex: exportação de PDF, impressão),
diga isso explicitamente em vez de assumir que está correto.
