# Documentacao do SIGEF

Este diretorio concentra a documentacao tecnica e operacional do SIGEF.

## Guias

- [Arquitetura e modulos](ARQUITETURA.md): visao geral do sistema, paineis, camadas, modelos e fluxos principais.
- [Manual funcional](FUNCIONALIDADES.md): funcionalidades de cada painel/pagina, recursos do sistema e guias de uso.
- [Instalacao](INSTALACAO.md): instalacao local, instalacao em servidor web, migracoes, seeders, assets e permissoes.
- [Configuracao](CONFIGURACAO.md): variaveis `.env`, integracoes SIGA, PIIPS/PNA, SMS, e-mail, CORS, sessoes e uploads.
- [Seguranca](SEGURANCA.md): checklist de producao, permissoes, hardening, auditoria, backups, segredos e resposta a incidentes.
- [Operacao e manutencao](OPERACAO.md): comandos de rotina, caches, filas, logs, backups, testes e deploy.

## Audiencia

Esta documentacao serve para:

- Administradores do sistema.
- Equipa de desenvolvimento.
- Suporte tecnico.
- DevOps/infraestrutura.
- Auditores internos de seguranca.

## Convencoes

- Comandos PHP assumem que `php` esta no `PATH`. No Laragon/Windows pode ser necessario usar o caminho completo, por exemplo:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan about
```

- Em producao, execute comandos com o utilizador do projeto, nunca como administrador/root sem necessidade.
- Valores sensiveis aparecem como placeholders. Nunca documente chaves reais no Git.
