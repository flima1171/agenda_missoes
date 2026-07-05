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
- ⬜ pendente — **A1** — Upgrade Laravel 12.60+ (com `composer audit` limpo, suíte verde, smoke no navegador)
- ⬜ pendente — **A2** — Autenticação (login completo) + trilha de auditoria + remover a API REST `/missions`
- ⬜ pendente — **A3** — Correções de lógica e validação em pt-BR
- ⬜ pendente — **A4** — Offline & deploy: self-host das fontes, ligar WAL, revisar `build-bundle.ps1` e docs
- ⬜ pendente — **A5** — Performance: paginação/escopo de queries e memoização
- ⬜ pendente — **A6** — UX / acessibilidade
- ⬜ pendente — **A7** — Fechamento: suíte + Pint + audit + smoke completo + atualizar docs

## Log de conclusão (só acrescente, nunca apague)
- **2026-07-04 — A0 concluída.** Criada a branch `remediacao/pos-auditoria` a partir de `evolucao/roadmap`. Criados `phpunit.xml.dist`, `tests/TestCase.php`, `tests/Unit/MissionTest.php`, `tests/Feature/{PainelTest,ResponsibleSelectorTest,MilitaresManagerTest}.php` (21 testes / 56 asserções, todos verdes). Removido o import não usado `WithoutModelEvents` do `MilitarSeeder`. `vendor/bin/pint` normalizou o scaffolding (5 arquivos). `vendor/bin/pint --test` limpo. Nota: PHPUnit marca os testes como "deprecated" por um aviso do PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`) disparado pelo `config/database.php` do Laravel 11 — não é falha (exit 0); deve sair na A1 (Laravel 12).
