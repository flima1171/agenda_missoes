# Agenda de Missões — 25º BC

Painel para a seção acompanhar e planejar as missões do dia e da semana. Feito para ficar **exposto em um monitor/TV** na seção e também ser usado no computador pelo Chefe, Adjunto ou por qualquer militar autenticado, que cadastra as missões e marca como concluídas.

Construído em **PHP + Laravel 12** com **Livewire v4**, banco **SQLite** (arquivo único) e **sem npm/Vite** (o CSS é um arquivo estático único). Pensado para rodar **totalmente offline** na intranet da OM (ex.: uma VM no Proxmox, servida por FrankenPHP — ver [DEPLOY.md](DEPLOY.md)).

---

## O que ele faz

- **Login obrigatório** — ninguém acessa o painel sem autenticação; toda ação relevante (criar/editar/excluir missão, mudar situação, reabrir, gerenciar militares e usuários) fica registrada numa **trilha de auditoria** (`activity_log`) com o autor e a data/hora.
- **Papéis**: usuário comum gerencia missões; **administrador** (`is_admin`) também acessa `/militares` (quadro de militares da seção) e `/usuarios` (criar contas, redefinir senha, promover/rebaixar).
- **Visão geral** — missões de hoje, próximos dias, próxima missão com contagem regressiva, progresso da semana e carga por militar.
- **Calendário semanal** — grade por horário; duplo clique numa célula cria a missão já naquele dia/hora.
- **Todas as missões** — lista filtrável (pendentes, em andamento, atrasadas), paginada.
- **Concluídas** — histórico de **quem concluiu e quando**, com opção de reabrir.
- **Modo monitor (TV)** — tela cheia, letras grandes, relógio e data, alternando sozinho entre "missões de hoje" e "visão da semana". Tecla `Esc` sai.
- **Tema claro/escuro** em todas as telas (persistido no navegador), acessibilidade cuidada (nomes acessíveis em botões só-ícone, foco preso no modal, contraste AA).

Militares e usuários **não** vêm mais fixos no código: são cadastrados pela própria interface (`/militares`, `/usuarios` — só admin). O nome do responsável em cada missão é um **snapshot de texto** no momento da criação — promover/renomear/inativar um militar não reescreve missões antigas.

---

## Requisitos

- PHP **8.2+** com as extensões: `pdo_sqlite`, `mbstring`, `openssl`, `ctype`, `fileinfo`, `tokenizer`, `xml`.
- [Composer](https://getcomposer.org/) 2+.

> Não precisa de MySQL, Node/npm nem servidor externo — o banco é um arquivo SQLite dentro do projeto e o front-end é Livewire (sem build de assets).

---

## Instalação (desenvolvimento / teste rápido)

```bash
# 1. Instalar dependências
composer install

# 2. Preparar o ambiente
cp .env.example .env
php artisan key:generate

# 3. Criar o banco SQLite e as tabelas + dados de exemplo
touch database/database.sqlite      # no Windows: type nul > database\database.sqlite
php artisan migrate --seed

# 4. Subir o servidor
php artisan serve
```

Acesse **http://localhost:8000** — você será redirecionado para `/login`.

O `db:seed` cria um administrador inicial (`admin@25bc.local`) além do quadro de militares e das missões de demonstração. Veja a senha padrão e como trocá-la (comando `app:create-user`) em [DEPLOY.md](DEPLOY.md#trocar-a-senha-do-administrador-inicial-obrigatório).

Para começar sem os dados de exemplo, rode `php artisan migrate --seed --class=Database\\Seeders\\MilitarSeeder` (ou ajuste o `DatabaseSeeder`). Dentro do painel, em ambiente `local`, o botão de recarregar (ícone ↻) restaura os dados de demonstração a qualquer momento — esse botão não existe fora de `local`.

### Criar/gerenciar usuários pela linha de comando

Sem internet não há "esqueci a senha" por e-mail — use o comando artisan:

```bash
php artisan app:create-user --name="Fulano de Tal" --email=fulano@25bc.local          # usuário comum
php artisan app:create-user --name="Ciclano" --email=ciclano@25bc.local --admin       # administrador
```

---

## Colocando em produção na intranet (VM offline, Proxmox/Debian)

O caminho suportado é o pipeline com **FrankenPHP** (PHP embutido, binário único, sem precisar de `apt`/Docker na VM) descrito passo a passo em **[DEPLOY.md](DEPLOY.md)**: gerar o bundle numa máquina com internet (`build-bundle.ps1`), levar o zip + o binário para a VM, configurar o `Caddyfile`, migrar o banco (SQLite em modo **WAL**), trocar a senha do admin inicial e subir como serviço `systemd`.

---

## Backup

Todo o banco de dados é o arquivo **`database/database.sqlite`**. Use o script pronto (lida com o modo WAL corretamente via `sqlite3 .backup`, com fallback para `cp`):

```bash
scripts/backup-sqlite.sh
```

Agendamento via cron e retenção de backups: ver [DEPLOY.md](DEPLOY.md#agendar-o-backup-do-sqlite-na-vm).

---

## Estrutura

```
app/Livewire/Painel.php                      Componente principal (dashboard, calendário, tabelas, modal, modo TV)
app/Livewire/ResponsibleSelector.php         Seletor progressivo de responsáveis da missão
app/Livewire/MilitaresManager.php            CRUD de militares (só admin)
app/Livewire/UsuariosManager.php             Gestão de usuários (só admin)
app/Livewire/Auth/Login.php                  Tela de login
app/Models/Mission.php                       Model da missão (rules()/applyCompletion() centralizados)
app/Models/Militar.php                       Model do militar (snapshot em texto nas missões, sem FK)
app/Models/ActivityLog.php                   Trilha de auditoria
database/migrations/                         Estrutura das tabelas
database/seeders/                            Admin inicial + quadro de militares + missões de exemplo
resources/views/livewire/                    Views dos componentes Livewire
resources/views/painel.blade.php             Shell da página do painel (ponte de tema/fullscreen)
public/css/app.css                           Estilos (arquivo único, sem build)
public/fonts/                                Fontes self-hosted (Archivo, JetBrains Mono — sem Google Fonts)
routes/web.php                               Rotas (tudo sob `auth`; /militares e /usuarios sob `admin`)
```

## Tecnologias

Laravel 12 · Livewire v4 (com Alpine embutido) · SQLite (WAL) · Blade · CSS puro (sem build/npm) · Archivo + JetBrains Mono (self-hosted) · FrankenPHP (deploy).

---

Licença MIT.
