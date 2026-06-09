# Guia de configuracao

## Ficheiros de ambiente

O sistema usa `.env` para configuracao local e de servidor. O ficheiro `.env.example` e o modelo seguro que pode estar no Git.

Nunca versionar:

- `.env`
- `.env.production`
- `.env.deploy`
- ficheiros SQL de backup
- tokens exportados
- logs

## Aplicacao

```env
APP_NAME=SIGEF
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://sigef.seu-dominio.ao
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
```

Regras:

- `APP_DEBUG=false` em qualquer servidor acessivel por terceiros.
- `APP_KEY` deve ser unica por ambiente.
- Nao reutilizar a mesma chave entre desenvolvimento e producao.

## Base de dados

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigef
DB_USERNAME=sigef_user
DB_PASSWORD=senha_forte
```

Recomendacoes:

- Criar utilizador de base de dados dedicado.
- Evitar utilizador `root` em producao.
- Permitir acesso apenas do servidor da aplicacao.
- Fazer backups regulares.

## Sessao

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Em ambiente local sem HTTPS, `SESSION_SECURE_COOKIE=false` pode ser necessario. Em producao deve ser `true`.

## Cache, filas e logs

```env
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=daily
LOG_LEVEL=warning
```

Para operacao simples, `database` e suficiente. Para maior volume, avaliar Redis e workers dedicados.

## E-mail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.exemplo.ao
MAIL_PORT=587
MAIL_USERNAME=noreply@exemplo.ao
MAIL_PASSWORD=senha_smtp
MAIL_FROM_ADDRESS=noreply@exemplo.ao
MAIL_FROM_NAME="${APP_NAME}"
```

Tambem e possivel configurar e-mail pelo painel administrativo em "Servidor de E-mail". A palavra-passe SMTP persistida em `system_settings` e cifrada pelo sistema.

## SMS

```env
TELCOSMS_API_KEY=
TELCOSMS_API_URL=https://telcosms.co.ao/send_message
TELCOSMS_VERIFY_SSL=true
```

Regras:

- Nunca colocar chave real no Git.
- `TELCOSMS_VERIFY_SSL=true` em producao.
- Validar saldo/estado no fornecedor antes de assumir erro do SIGEF.

## SIGA API

```env
SIGA_API_BASE_URL=https://siga.urbtech.shop
SIGA_API_KEY=
SIGA_API_TOKEN=
SIGA_API_VERIFY_SSL=true
SIGA_API_TIMEOUT=15
SIGA_API_CACHE_TTL=300
SIGA_API_MAX_PAGES=25
SIGA_API_STUDENTS_ENDPOINT=/api/v1/students
SIGA_API_PROGRAMS_ENDPOINT=/api/v1/catalog/programs
SIGA_API_INSTITUTION_STUDENTS_ENDPOINT=
SIGA_API_STUDENTS_BY_COURSE_ENDPOINT=
```

Uso:

- Alimenta graficos e indicadores do painel de controlo.
- Pode consultar totais por instituicao e curso.
- Usa cache para reduzir carga na API externa.

Quando os dados do SIGA nao refletem:

1. Confirme `SIGA_API_BASE_URL`.
2. Confirme `SIGA_API_KEY` e `SIGA_API_TOKEN`.
3. Confirme endpoints.
4. Reduza temporariamente `SIGA_API_CACHE_TTL=0` para teste.
5. Execute `php artisan optimize:clear`.
6. Verifique logs em `storage/logs/laravel.log`.

## PIIPS/PNA

```env
PIIPS_API_URL=http://localhost:3333/api/v1/integration
PIIPS_API_KEY=
PIIPS_STORAGE_URL=http://localhost:3333
PIIPS_CACHE_TTL=3600
```

Uso:

- Consulta de agente por NIP.
- Dados basicos e fotografia/ficheiros quando a API disponibiliza.

## Portal de recrutamento

```env
RECRUITMENT_PORTAL_CANDIDATES_URL=http://10.110.2.18/api/candidates
RECRUITMENT_PORTAL_TIMEOUT=25
```

Uso:

- Sincronizacao/importacao de candidatos externos.
- Validar conectividade de rede antes de executar importacoes em massa.

## CORS

```env
CORS_ALLOWED_ORIGINS=https://sigef.seu-dominio.ao
```

Se houver mais de uma origem confiavel:

```env
CORS_ALLOWED_ORIGINS=https://sigef.seu-dominio.ao,https://app.seu-dominio.ao
```

Nao usar `*` em producao.

## Uploads temporarios

```env
LIVEWIRE_TEMP_UPLOAD_DISK=local
```

O disco `local` evita expor uploads temporarios no diretorio publico. Tipos aceites devem ficar limitados a documentos e imagens necessarias.

## Configuracao institucional por instituicao

No painel, configure dados usados em relatorios em `Configuracoes > Configurar Instituicao`.

Cada instituicao pode ter a sua propria identidade de relatorio. Ao abrir a pagina, selecione a instituicao antes de editar os campos:

- Nome da instituicao.
- Sigla.
- Republica/ministerio/orgao.
- Diretor/responsavel.
- NIF, telefone, e-mail, website.
- Provincia, municipio e endereco.
- Logotipo.
- Texto de rodape.

Estes dados alimentam cabecalhos e rodapes de relatorios, fichas e documentos emitidos para a instituicao selecionada.

Regras de fallback:

1. Se existir configuracao personalizada da instituicao, ela e usada.
2. Se ainda nao existir configuracao personalizada, o SIGEF usa os dados cadastrados na propria instituicao, como nome, sigla, contactos, endereco e logotipo.
3. Se o relatorio nao tiver uma instituicao unica, o sistema usa a configuracao global/padrao.

Boas praticas:

- Configure primeiro os dados basicos em `Instituicoes > Instituicoes`.
- Depois ajuste cabecalho, responsavel e rodape em `Configurar Instituicao`.
- Revise uma ficha ou PDF antes de emitir documentos oficiais em massa.

## Configuracao de cartoes

O modulo de templates permite:

- Frente e verso.
- Cores de fallback.
- Imagem de fundo quando existir.
- Logotipo e dados institucionais.
- Pre-visualizacao antes de imprimir.

Evite incluir codigos de barras ou campos sensiveis sem necessidade operacional.
