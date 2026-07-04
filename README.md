# Agenda de Missões — 25º BC

Painel para a seção acompanhar e planejar as missões do dia e da semana. Feito para ficar **exposto em um monitor/TV** na seção e também ser usado no computador pelo Chefe, Adjunto ou por qualquer militar, que cadastra as missões e marca como concluídas.

Construído em **PHP + Laravel 11** com banco **SQLite** (arquivo único), pensado para rodar **offline** na intranet da OM (ex.: uma VM no Proxmox).

---

## O que ele faz

- **Visão geral** — missões de hoje, próximos dias, próxima missão com contagem regressiva, progresso da semana e carga por militar.
- **Calendário semanal** — grade por horário; duplo clique numa célula cria a missão já naquele dia/hora.
- **Todas as missões** — lista filtrável (pendentes, em andamento, atrasadas).
- **Concluídas** — histórico de **quem concluiu e quando**, com opção de reabrir.
- **Modo monitor (TV)** — tela cheia, letras grandes, relógio e data, alternando sozinho entre "missões de hoje" e "visão da semana". Tecla `Esc` sai.
- Qualquer militar da seção pode cadastrar e atualizar a situação das missões.

Militares pré-cadastrados: Asp Araújo, 3º Sgt Rodrigues Silva, Cb Luide, Sd EP Jones, Sd EP Ferreira Lima, Sd EP Edilson.

---

## Requisitos

- PHP **8.2+** com as extensões: `pdo_sqlite`, `mbstring`, `openssl`, `ctype`, `fileinfo`, `tokenizer`, `xml`.
- [Composer](https://getcomposer.org/) 2+.

> Não precisa de MySQL nem de servidor externo — o banco é um arquivo SQLite dentro do projeto.

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

Acesse **http://localhost:8000**.

Para começar sem os dados de exemplo, rode `php artisan migrate` (sem `--seed`). Dentro do painel, o botão de recarregar (ícone ↻) restaura os dados de demonstração a qualquer momento.

---

## Colocando em produção na intranet (VM no Proxmox)

Exemplo com **Debian/Ubuntu + Nginx + PHP-FPM**. Ajuste caminhos e usuário conforme sua VM.

```bash
# Dependências
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl unzip git

# Clonar o projeto (ou copiar os arquivos)
cd /var/www
sudo git clone https://github.com/flima1171/agenda_missoes.git
cd agenda_missoes

# Dependências PHP em modo produção
composer install --no-dev --optimize-autoloader

# Ambiente
cp .env.example .env
php artisan key:generate
# edite o .env: APP_ENV=production e APP_DEBUG=false

# Banco
touch database/database.sqlite
php artisan migrate --seed --force

# Permissões (usuário do PHP-FPM costuma ser www-data)
sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache

# Cache de configuração/rotas (opcional, deixa mais rápido)
php artisan config:cache
php artisan route:cache
```

### Nginx — exemplo de site

```nginx
server {
    listen 80;
    server_name missoes.intranet;                 # ou o IP da VM
    root /var/www/agenda_missoes/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/agenda_missoes /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Aponte o monitor da seção para `http://missoes.intranet` (ou o IP da VM) e clique em **Ativar modo monitor**.

---

## Backup

Todo o banco de dados é o arquivo **`database/database.sqlite`**. Para backup, basta copiá-lo:

```bash
cp database/database.sqlite ~/backup-missoes-$(date +%F).sqlite
```

---

## Estrutura

```
app/Http/Controllers/MissionController.php   API das missões (CRUD + reset)
app/Models/Mission.php                       Model da missão
database/migrations/                         Estrutura das tabelas
database/seeders/MissionSeeder.php           Dados de exemplo
resources/views/painel.blade.php             Página única (painel + modo TV)
public/css/app.css                           Estilos
public/js/app.js                             Lógica da interface (consome a API)
routes/web.php                               Rotas
```

## Tecnologias

Laravel 11 · SQLite · Blade · JavaScript puro (sem build/npm) · Archivo + JetBrains Mono.

---

Licença MIT.
