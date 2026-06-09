# Operacao e manutencao

## Rotina diaria

- Verificar disponibilidade do sistema.
- Verificar `storage/logs/laravel.log`.
- Confirmar execucao de backups.
- Validar integracoes criticas: SIGA, PIIPS/PNA, SMS e e-mail.
- Verificar espaco em disco.

## Rotina semanal

- Rodar testes automatizados.
- Rodar auditoria de dependencias.
- Verificar utilizadores administrativos.
- Verificar falhas de login repetidas.
- Testar download de relatorios essenciais.

## Rotina mensal

- Testar restauracao de backup.
- Rever papeis/permissoes.
- Rever tokens e chaves de API.
- Atualizar dependencias em ambiente de teste.
- Validar certificados TLS.

## Comandos Laravel uteis

### Estado da aplicacao

```bash
php artisan about
php artisan route:list
php artisan migrate:status
```

### Cache

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Permissoes

```bash
php artisan shield:generate --all
php artisan permission:cache-reset
php artisan shield:super-admin
```

### Testes

```bash
php artisan test
composer audit --locked
```

### Manutencao

```bash
php artisan down
php artisan up
```

Com acesso secreto temporario:

```bash
php artisan down --secret="codigo-temporario"
```

## Deploy recomendado

1. Fazer backup da base de dados.
2. Fazer backup de `.env`.
3. Atualizar codigo.
4. Instalar dependencias:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

5. Rodar migracoes:

```bash
php artisan migrate --force
```

6. Limpar e reconstruir caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

7. Verificar:

```bash
php artisan about
php artisan route:list
php artisan test
```

8. Testar login, painel de controlo, relatorios e integracoes.

## Backups

Backups devem cobrir:

- Base de dados.
- `storage/app/public`.
- `storage/app/private`, se usado.
- Configuracoes externas relevantes.

Nao incluir:

- `node_modules`.
- `vendor`, se puder ser reconstruido por Composer.
- caches temporarios.
- logs antigos sem necessidade legal.

## Restauracao

Procedimento base:

1. Colocar sistema em manutencao.
2. Restaurar base de dados.
3. Restaurar ficheiros de `storage`.
4. Confirmar `.env`.
5. Executar:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan storage:link
```

6. Testar login, pesquisa, relatorios, cartoes e integracoes.
7. Retirar manutencao.

## Monitorizacao

Monitorar:

- HTTP 500/403/419.
- Tempo de resposta do dashboard.
- Erros em APIs externas.
- Falhas de envio de SMS/e-mail.
- Crescimento de logs.
- Falta de espaco em disco.
- Backups nao executados.

## Integracao SIGA

Quando os graficos divergirem:

1. Confirmar se a origem SIGA tem dados atualizados.
2. Confirmar `SIGA_API_BASE_URL`.
3. Confirmar token/chave.
4. Temporariamente usar `SIGA_API_CACHE_TTL=0`.
5. Executar `php artisan optimize:clear`.
6. Consultar logs do Laravel.
7. Reativar cache apos teste.

## Importacoes

Antes de importacao em massa:

- Fazer backup.
- Testar com poucos registos.
- Validar campos obrigatorios.
- Confirmar duplicados.
- Verificar logs apos importacao.

## Cartoes e relatorios

Quando imagem/logotipo nao aparece:

1. Confirmar ficheiro em storage.
2. Confirmar `php artisan storage:link`.
3. Confirmar `APP_URL`.
4. Confirmar permissoes de leitura.

Quando PDF falha:

1. Verificar logs.
2. Confirmar dados institucionais.
3. Confirmar extensoes PHP de imagem/DOM.
4. Reduzir imagens muito pesadas.

## Cuidados com Git

Antes de commit:

```bash
git status --short
git diff --stat
php artisan test
```

Nao adicionar:

- `.env`
- `.env.production`
- `.env.deploy`
- `storage/`
- `node_modules/`
- backups SQL
- ficheiros temporarios
- credenciais reais

Commit recomendado:

```bash
git add README.md docs/
git commit -m "docs: document SIGEF installation and security"
git pull --rebase --autostash
```
