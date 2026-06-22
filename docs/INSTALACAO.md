# Guia de instalacao

## Requisitos

### Servidor

- PHP `8.2+`, recomendado `8.3`.
- Extensoes PHP comuns do Laravel: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `intl`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.
- Composer 2.
- Node.js 20+ e npm.
- MySQL 8 ou MariaDB compativel.
- Servidor web Apache/Nginx/IIS com HTTPS.

### Local Windows com Laragon

- Laragon com PHP 8.3.
- MySQL/MariaDB ativo.
- Virtual host apontando para `C:\laragon\www\sigef\public`.
- Exemplo de URL local: `https://sigef.test:8443`.

## Instalacao local

1. Clonar o repositorio:

```bash
git clone <URL_DO_REPOSITORIO> sigef
cd sigef
```

2. Instalar dependencias PHP:

```bash
composer install
```

3. Instalar dependencias frontend:

```bash
npm install
```

4. Criar ficheiro de ambiente:

```bash
cp .env.example .env
```

No Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

5. Gerar chave da aplicacao:

```bash
php artisan key:generate
```

6. Configurar base de dados no `.env`:

```env
APP_NAME=SIGEF
APP_ENV=local
APP_DEBUG=false
APP_URL=https://sigef.test:8443

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigef
DB_USERNAME=root
DB_PASSWORD=
```

7. Executar migracoes:

```bash
php artisan migrate --force
```

8. Executar seeders iniciais:

```bash
php artisan db:seed --class=SystemSettingsSeeder
php artisan db:seed --class=AngolaAdministrativeDivisionsSeeder
php artisan db:seed --class=ProvenanceSeeder
php artisan db:seed --class=AdminSeeder
```

9. Gerar permissoes do Shield:

```bash
php artisan shield:generate --all
php artisan shield:super-admin
```

Se o comando pedir o utilizador, selecione o utilizador administrador criado pelo seeder. Depois, confirme no painel se o utilizador tem papel `super_admin`.

10. Criar link de storage:

```bash
php artisan storage:link
```

11. Compilar assets:

```bash
npm run build
```

12. Limpar caches:

```bash
php artisan optimize:clear
```

13. Aceder ao sistema:

```text
https://sigef.test:8443/login
```

## Credenciais iniciais

O seeder `AdminSeeder` cria acessos de apoio:

```text
Super Admin: admin@sigef.com / password
Escola Admin: escola@sigef.com / password
```

Altere estas palavras-passe imediatamente depois do primeiro login.

## Instalacao em producao

1. Configurar o servidor web com `DocumentRoot` para a pasta `public/`.

Apache:

```apache
DocumentRoot /var/www/sigef/public
<Directory /var/www/sigef/public>
    AllowOverride All
    Require all granted
</Directory>
```

Nginx:

```nginx
root /var/www/sigef/public;
index index.php;
try_files $uri $uri/ /index.php?$query_string;
```

2. Preparar o `.env` de producao:

```env
APP_NAME=SIGEF
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sigef.seu-dominio.ao

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CORS_ALLOWED_ORIGINS=https://sigef.seu-dominio.ao
```

3. Instalar dependencias sem pacotes de desenvolvimento:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

4. Executar migracoes:

```bash
php artisan migrate --force
```

5. Preparar storage e caches:

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

6. Corrigir permissoes de escrita:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
```

Em Windows/IIS ou Laragon, garanta permissao de escrita para o utilizador do servico web nas pastas `storage` e `bootstrap/cache`.

## Comandos uteis de verificacao

```bash
php artisan app:server-check
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
composer audit --locked
```

## Problemas comuns

### Erro 500 apos deploy

1. Execute `php artisan app:server-check` e corrija todos os itens `FAILED`.
2. Verifique `.env` e `APP_KEY`.
3. Use PHP 8.2, 8.3 ou 8.4 com as extensoes indicadas pelo diagnostico.
4. Verifique permissoes de `storage` e `bootstrap/cache`.
5. Execute `php artisan optimize:clear`.
6. Consulte `storage/logs/laravel.log` e o log de erros do PHP-FPM/Apache.

### Fotos/documentos nao aparecem

1. Confirme `php artisan storage:link`.
2. Confirme `APP_URL`.
3. Confirme se o ficheiro existe em `storage/app/public`.
4. Confirme que o servidor web serve `public/storage`.

### Sem acesso ao painel

1. Confirme se o utilizador esta ativo.
2. Confirme papel `super_admin`, `admin`, `escola_admin`, `dpq_admin` ou `comando_admin`.
3. Execute `php artisan shield:generate --all`.
4. Limpe cache de permissoes:

```bash
php artisan permission:cache-reset
```
