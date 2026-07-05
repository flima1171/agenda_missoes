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
- ⬜ pendente — **A3** — Correções de lógica e validação em pt-BR
- ⬜ pendente — **A4** — Offline & deploy: self-host das fontes, ligar WAL, revisar `build-bundle.ps1` e docs
- ⬜ pendente — **A5** — Performance: paginação/escopo de queries e memoização
- ⬜ pendente — **A6** — UX / acessibilidade
- ⬜ pendente — **A7** — Fechamento: suíte + Pint + audit + smoke completo + atualizar docs

## Log de conclusão (só acrescente, nunca apague)
- **2026-07-04 — A2 concluída.** Login à mão em Livewire (`App\Livewire\Auth\Login` + views `auth/login.blade.php` shell e `livewire/auth/login.blade.php`), `Auth::attempt` + rate limiting (5 tentativas por e-mail+IP). Migrations: `add_admin_fields_to_users_table` (`is_admin`, `nome_guerra`) e `create_activity_log_table`. Middleware `admin` (`EnsureUserIsAdmin`) registrado em `bootstrap/app.php`; `routes/web.php` reescrito: `/login`+`/logout` públicos/auth, tudo mais sob `auth`, `/militares`+`/usuarios` sob `admin`. Trilha `ActivityLog::record` gravada em login, criar/editar/excluir/mudar situação/reabrir missão, criar/editar/(in)ativar militar e criar/editar/promover usuário; `completed_by` passa a sugerir o usuário logado. Comando `app:create-user` (criar/redefinir senha offline), `UserSeeder` (admin inicial `admin@25bc.local`) no `DatabaseSeeder`, tela `App\Livewire\UsuariosManager` (só admin: criar, redefinir senha, alternar papel — sem se auto-rebaixar). API REST removida: `MissionController.php` apagado e rotas `/missions` retiradas (`Mission::rules()`/`applyCompletion()` mantidos). **15 testes novos** (Auth, UsuariosManager, CreateUserCommand) + `PainelTest`/`MilitaresManagerTest` migrados para `actingAs`: **36 testes / 99 asserções verdes**; `pint --test` limpo; `composer audit` limpo; `migrate:status` ok. **Navegador** (porta 8013): guest→/login; login válido→painel + `activity_log` de login gravado; sidebar com perfil/Sair e links de admin (admin) / sem eles (comum); logout ok; usuário comum recebe 403 em `/militares` e `/usuarios` e 404 em `/missions`; login provado claro+escuro (toggle persiste) e mobile 375px; zero erro de console. **PENDENTE/A6:** `/militares` e `/usuarios` ainda sem tema escuro e o contraste do `--muted` (achado 5.3) seguem para a A6.
- **2026-07-04 — A1 concluída.** `composer update` subiu `laravel/framework` v11.54.0 → **v12.62.0** (satisfaz todos os advisories: o "Temporary Signed URL Path Confusion" exigia `<12.61.1`, não só 12.60). Também: `nikic/php-parser` 5.7→5.8, novo `symfony/polyfill-php84`; `composer.json` ajustado (framework `^12.0`, tinker `^2.10`, collision `^8.6`, phpunit `^11.5.3`). `composer audit` → **limpo**. Suíte: **21 testes / 56 asserções verdes** — e o selo "deprecated" do PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`) **sumiu** (Laravel 12 corrigiu o config, como previsto na A0). `pint --test` limpo; `migrate:status` sem pendências. Smoke no navegador (porta 8013, 1280px): as 6 telas OK (dashboard, calendário, todas as missões, concluídas, modal, modo monitor), Livewire hidratando e round-trips funcionando, zero erro de console. Nenhuma mudança de código de app foi necessária (bootstrap/app.php, casts e config seguem compatíveis 11→12).
- **2026-07-04 — A0 concluída.** Criada a branch `remediacao/pos-auditoria` a partir de `evolucao/roadmap`. Criados `phpunit.xml.dist`, `tests/TestCase.php`, `tests/Unit/MissionTest.php`, `tests/Feature/{PainelTest,ResponsibleSelectorTest,MilitaresManagerTest}.php` (21 testes / 56 asserções, todos verdes). Removido o import não usado `WithoutModelEvents` do `MilitarSeeder`. `vendor/bin/pint` normalizou o scaffolding (5 arquivos). `vendor/bin/pint --test` limpo. Nota: PHPUnit marca os testes como "deprecated" por um aviso do PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`) disparado pelo `config/database.php` do Laravel 11 — não é falha (exit 0); deve sair na A1 (Laravel 12).
