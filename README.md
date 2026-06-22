# SIGEF

SIGEF e o Sistema Integrado de Gestao Escolar e Formacao usado para gerir alistados, formandos, instrutores, turmas, cursos, avaliacoes, documentos, cartoes, relatorios e indicadores de controlo.

O sistema e construido em Laravel 11, Filament 5, Livewire, MySQL/MariaDB, Vite e Tailwind CSS. Tambem integra servicos externos como SIGA, PIIPS/PNA, portal de recrutamento e servidor SMS.

## Documentacao

- [Indice da documentacao](docs/README.md)
- [Arquitetura e modulos](docs/ARQUITETURA.md)
- [Manual funcional](docs/FUNCIONALIDADES.md)
- [API REST](docs/API.md)
- [Guia de instalacao](docs/INSTALACAO.md)
- [Guia de configuracao](docs/CONFIGURACAO.md)
- [Guia de seguranca](docs/SEGURANCA.md)
- [Operacao e manutencao](docs/OPERACAO.md)

## Paineis

- `/login`: autenticacao unificada.
- `/admin`: administracao geral.
- `/escola`: gestao operacional da escola/instituicao.
- `/dpq`: gestao de alistamento, selecao e recrutamento.
- `/comando`: consulta e supervisao institucional.

## Stack principal

- PHP `>=8.2 <8.5` (`8.2`, `8.3` ou `8.4`), testado localmente com PHP `8.3.30`.
- Laravel `11.54.0`.
- Filament `~5.0`.
- Laravel Sanctum para API autenticada.
- Spatie Permission e Filament Shield para papeis/permissoes.
- MySQL/MariaDB em ambiente operacional.
- Node/Vite para assets frontend.

## Arranque rapido

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SystemSettingsSeeder
php artisan db:seed --class=AngolaAdministrativeDivisionsSeeder
php artisan db:seed --class=ProvenanceSeeder
php artisan db:seed --class=AdminSeeder
php artisan shield:generate --all
php artisan storage:link
npm run build
php artisan optimize:clear
php artisan app:server-check
```

Depois aceda a `/login` e altere imediatamente as credenciais criadas por seeder.

## Testes

```bash
php artisan test
composer audit --locked
```

## Nota de seguranca

Nunca publique `.env`, `.env.production`, `.env.deploy`, backups SQL, `storage/`, `vendor/` com alteracoes manuais, ou qualquer chave/tokens reais. O `DocumentRoot` do servidor deve apontar para a pasta `public/`.
