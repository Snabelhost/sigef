# Arquitetura e modulos

## Objetivo do sistema

O SIGEF centraliza a gestao escolar e de formacao, desde o alistamento ate a conclusao do formando. O sistema tambem produz relatorios, fichas, cartoes, certificados, pautas e indicadores de controlo.

## Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 11 |
| Painel administrativo | Filament 5 |
| Componentes reativos | Livewire |
| Autenticacao API | Laravel Sanctum |
| Papeis e permissoes | Spatie Permission, Filament Shield |
| Base de dados | MySQL/MariaDB |
| Assets | Vite, Tailwind CSS |
| PDF/relatorios | DomPDF e views Blade |
| Importacao/exportacao | Maatwebsite Excel |
| Auditoria | Tapp Filament Auditing e logs internos |

## Estrutura principal

```text
app/
  Filament/                 Paineis, Resources, Pages e Widgets
  Http/Controllers/         Controladores web/API/relatorios
  Http/Middleware/          Middleware de sessao, seguranca e permissoes
  Models/                   Modelos Eloquent
  Services/                 Integracoes e regras de dominio
config/                     Configuracoes Laravel e servicos externos
database/migrations/        Estrutura da base de dados
database/seeders/           Dados iniciais
resources/views/            Views Blade, relatorios, widgets
routes/web.php              Rotas web protegidas por sessao
routes/api.php              Rotas API protegidas por Sanctum
storage/                    Logs, ficheiros privados, cache, backups
public/                     Unico diretorio que deve ficar publico na web
```

## Paineis Filament

| Painel | Caminho | Responsabilidade |
| --- | --- | --- |
| Admin | `/admin` | Administracao global, configuracoes, utilizadores, curriculo, formandos, documentos, cartoes e relatorios. |
| Escola | `/escola` | Operacao da instituicao/escola, turmas, formandos, avaliacoes, pautas e certificados. |
| DPQ | `/dpq` | Alistados, recrutamento, selecao, candidatos e acompanhamento de ingresso. |
| Comando | `/comando` | Supervisao e consulta de dados institucionais. |

A autenticacao e unificada em `/login`. Depois do login, o sistema redireciona o utilizador para o painel adequado com base nos papeis e permissoes.

## Modulos funcionais

### Gestao de acesso

- Utilizadores.
- Papeis e permissoes.
- Sessao unica por utilizador.
- Auditoria de acoes e logs de acesso.

### Curriculo

- Anos academicos.
- Cursos.
- Fases de curso.
- Disciplinas.
- Mapas e planos de curso.

### Gestao escolar

- Alistados e candidatos.
- Conversao para recrutas/formandos.
- Formandos em formacao e concluidos.
- Turmas.
- Inscricoes de formandos em turmas.
- Transferencias.
- Atribuicao de meios/equipamentos.
- Licencas/ausencias.

### Avaliacao

- Lancamento de notas.
- Calculo de medias.
- Pautas.
- Aprovados e reprovados.
- Certificados.
- Assiduidade.

### Formadores

- Cadastro de formadores.
- Vinculo com instituicoes.
- Autorizacao por disciplina.
- Atribuicao a turmas.
- Ficha do professor/formador.

### Documentos e comunicacao

- Criacao e envio de documentos.
- Destinatarios.
- Anexos.
- Respostas.
- Notificacoes.
- Configuracao de e-mail e SMS.

### Cartoes

- Templates de cartao.
- Frente e verso.
- Pre-visualizacao.
- Impressao individual e em lote.
- Parametros visuais e institucionais.

### Relatorios

- Relatorios PDF de utilizadores, curriculo, formandos, inscricoes, avaliacoes, documentos e instituicoes.
- Cabecalho/rodape configuravel em "Configuracao da Instituicao".

## Modelos principais

| Modelo | Uso |
| --- | --- |
| `User` | Utilizadores e acesso aos paineis. |
| `Institution` e `InstitutionType` | Instituicoes de ensino e seus tipos. |
| `AcademicYear` | Ano academico. |
| `Course`, `CoursePhase`, `CourseMap`, `CoursePlan`, `Subject` | Estrutura curricular. |
| `Candidate` | Alistados/candidatos antes de virar formando. |
| `Student` | Formando/recruta/instruendo/concluido. |
| `StudentClass` e `StudentClassEnrollment` | Turmas e inscricoes em turmas. |
| `Evaluation` | Notas e avaliacoes. |
| `Trainer` | Formadores/instrutores. |
| `EquipmentAssignment` | Atribuicao de meios. |
| `Document`, `DocumentRecipient`, `DocumentAttachment`, `DocumentResponse` | Modulo documental. |
| `CardTemplate` | Templates de cartoes. |
| `SystemSetting` | Configuracoes persistidas no sistema. |

## Integracoes

### SIGA

Servico: `App\Services\SigaDashboardStatsService`.

Usado para sincronizar indicadores externos do SIGA nos graficos do painel de controlo, incluindo alunos por instituicao, alunos por curso, estados e aprovacao/reprovacao quando configurado.

### PIIPS/PNA

Servico: `App\Services\PnaAgentService`.

Usado para consulta de agentes por NIP e dados basicos vindos da API institucional configurada.

### Portal de recrutamento

Servico: `App\Services\RecruitmentPortalCandidateSyncService`.

Usado para importar/sincronizar candidatos externos.

### SMS

Servico: `App\Services\SmsService`.

Usado para envio de SMS via TelcoSMS ou API configurada.

## API interna

As rotas API ficam em `routes/api.php` com prefixo `/api/v1`.

| Rota | Protecao | Uso |
| --- | --- | --- |
| `/api/v1/user` | `auth:sanctum` | Dados do utilizador autenticado. |
| `/api/v1/dashboard/stats` | `auth:sanctum` | Indicadores principais. |
| `/api/v1/dashboard/student-status` | `auth:sanctum` | Estado dos formandos. |
| `/api/v1/dashboard/candidate-status` | `auth:sanctum` | Estado dos candidatos. |
| `/api/v1/dashboard/institution-stats` | `auth:sanctum` | Alunos por instituicao. |
| `/api/v1/dashboard/students-by-course` | `auth:sanctum` | Alunos por curso. |
| `/api/v1/dashboard/recent-students` | `auth:sanctum` | Formandos recentes. |

## Fluxos principais

### Alistado para formando

1. Candidato/alistado e registado no DPQ ou importado.
2. Dados pessoais, contacto, proveniencia e documentos sao validados.
3. O alistado pode ser vinculado a uma escola/instituicao.
4. O alistado pode ser convertido para recruta/formando.
5. O formando passa a ser gerido no modulo de formandos e inscricoes.
6. Depois da inscricao em turma/curso, recebe disciplinas conforme o plano.

### Formando em formacao para concluido

1. Formando e matriculado/inscrito numa turma.
2. Avaliacoes e assiduidade sao registadas.
3. O sistema calcula medias e resultado.
4. Formandos aprovados podem receber certificados.
5. O estado pode ser atualizado para concluido quando o ciclo terminar.

### Relatorio e impressao

1. Utilizador acessa modulo/relatorio protegido por autenticacao.
2. Dados sao filtrados conforme painel, instituicao e permissao.
3. Cabecalho e rodape usam configuracao institucional.
4. PDF/ficha/cartao e gerado para impressao.
