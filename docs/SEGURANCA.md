# Guia de seguranca

## Principios

- Expor apenas a pasta `public/` na web.
- Manter `.env` fora do Git.
- Usar HTTPS em todos os ambientes acessiveis por utilizadores.
- Usar menor privilegio para utilizadores, base de dados e servicos externos.
- Fazer backups cifrados e testar restauracao.
- Rodar auditorias de dependencias com frequencia.

## Checklist de producao

Antes de publicar:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- `SESSION_ENCRYPT=true`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_SAME_SITE=lax`
- `CORS_ALLOWED_ORIGINS` sem `*`
- `TELCOSMS_VERIFY_SSL=true`
- `SIGA_API_VERIFY_SSL=true`
- Servidor web apontado para `public/`
- `.env` com permissoes restritas
- `storage/` e `bootstrap/cache/` com escrita apenas para o servico web
- `composer install --no-dev --optimize-autoloader`
- `npm run build`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan test`
- `composer audit --locked`

## Autenticacao

O SIGEF usa login unificado em `/login` e redireciona para o painel conforme o papel do utilizador.

Medidas existentes:

- Limite de tentativas de login por e-mail/IP.
- Regeneracao de sessao apos login.
- Logout com invalidacao de sessao e novo token CSRF.
- Sessao unica por utilizador.
- Mensagem de login generica para evitar enumeracao de contas desativadas.

Regras operacionais:

- Desativar utilizadores que sairem da instituicao.
- Nao partilhar contas.
- Rever utilizadores administrativos mensalmente.
- Trocar palavras-passe criadas por seeders no primeiro acesso.

## Palavras-passe

Politica atual:

- Minimo de 10 caracteres.
- Maiusculas e minusculas.
- Numeros.
- Simbolos.

Recomendacoes:

- Usar frases-passe longas.
- Evitar reutilizacao.
- Trocar credenciais quando houver suspeita de exposicao.
- Usar gestor de palavras-passe para contas administrativas.

## Papeis e permissoes

Tecnologias:

- Spatie Permission.
- Filament Shield.

Papeis comuns:

- `super_admin`: acesso total.
- `admin`: administracao geral.
- `escola_admin` / `escola_user`: painel de escola.
- `dpq_admin` / `dpq_user`: painel DPQ.
- `comando_admin` / `comando_user`: painel comando.

Boas praticas:

- Usar `super_admin` apenas para administradores tecnicos confiaveis.
- Dar acesso por funcao, nao por conveniencia.
- Gerar permissoes apos criar novos Resources/Pages/Widgets:

```bash
php artisan shield:generate --all
php artisan permission:cache-reset
```

## API

Rotas `/api/v1/dashboard/*` e `/api/v1/user` usam `auth:sanctum`.

Regras:

- Tokens Sanctum devem ser nominais e revogaveis.
- Nao usar token de um utilizador humano para integracoes permanentes.
- Rotacionar tokens periodicamente.
- Guardar tokens apenas em variaveis de ambiente ou cofre de segredos.

## Headers HTTP

O middleware `SecurityHeaders` aplica:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` restritivo
- `Content-Security-Policy` basico
- `Strict-Transport-Security` quando a requisicao e HTTPS e o ambiente e producao

## CSRF

Rotas web devem manter protecao CSRF. Formularios de login, logout e acoes administrativas nao devem ficar em excecoes de CSRF.

## Uploads

Uploads temporarios do Livewire:

- Devem usar disco `local`.
- Devem exigir autenticacao.
- Devem limitar tamanho e MIME.

Evitar:

- Upload de `.php`, `.phtml`, `.js`, `.html`, `.svg` quando nao for estritamente necessario.
- Servir ficheiros temporarios diretamente pela web.

## Segredos e chaves

Nunca versionar:

- `.env`
- `.env.production`
- `.env.deploy`
- `PIIPS_API_KEY`
- `SIGA_API_KEY`
- `SIGA_API_TOKEN`
- `TELCOSMS_API_KEY`
- `MAIL_PASSWORD`
- `DB_PASSWORD`
- backups SQL

O modelo `SystemSetting` cifra valores sensiveis novos como:

- `mail_password`
- `sms_api_key`
- `sms_api_secret`

Se valores antigos existirem em texto claro na base de dados, regrave-os pelo painel para que passem a ficar cifrados.

## Backups

O modulo de backup deve ser restrito a `super_admin`.

Regras:

- Guardar backups fora de `public/`.
- Cifrar backups antes de enviar para armazenamento externo.
- Testar restauracao periodicamente.
- Remover backups antigos conforme retencao definida.
- Nao enviar backups por e-mail sem cifragem.

## E-mail e CRLF

O sistema tem mitigacao para rejeitar `CR`/`LF` em campos de e-mail recebidos por request e tambem no envio legado por PHPMailer.

Estado em 2026-06-09:

- `composer audit --locked` reporta `CVE-2026-48019` no `laravel/framework v11.54.0`.
- A mitigacao local reduz o risco de injecao CRLF em campos de e-mail.
- A correcao definitiva exige migracao planejada para Laravel `12.60.0+` ou `13.10.0+`.

## Auditoria de dependencias

Rodar regularmente:

```bash
composer audit --locked
npm audit
```

Se houver vulnerabilidade:

1. Identificar pacote e versao afetada.
2. Verificar se ha patch compativel com a versao atual.
3. Atualizar em branch separada.
4. Rodar testes.
5. Fazer deploy controlado.

## Logs

Logs ficam em `storage/logs`.

Cuidados:

- Nao gravar tokens, palavras-passe ou documentos sensiveis em logs.
- Monitorar erros de autenticacao e APIs externas.
- Rotacionar logs.
- Restringir leitura dos logs ao suporte autorizado.

## Resposta a incidente

Se houver suspeita de exposicao:

1. Colocar sistema em modo manutencao se necessario:

```bash
php artisan down --secret="codigo-temporario"
```

2. Rotacionar imediatamente:

- `APP_KEY` somente com planejamento, pois afeta dados cifrados.
- Tokens SIGA/PIIPS/SMS.
- Senhas de base de dados.
- Senhas SMTP.
- Tokens Sanctum.

3. Rever logs de acesso.
4. Revogar sessoes e tokens suspeitos.
5. Restaurar backup limpo se necessario.
6. Documentar causa, impacto e correcao.

## Itens proibidos em producao

- Rotas publicas que executam `migrate`, `optimize`, `storage:link` ou comandos Artisan sensiveis.
- Scripts como `public/optimize.php` ou `public/reset-cache.php`.
- `APP_DEBUG=true`.
- `CORS_ALLOWED_ORIGINS=*`.
- `SESSION_SECURE_COOKIE=false` com HTTPS disponivel.
- `verify_ssl=false` para APIs externas em producao.
- Credenciais padrao `password`.
