# Manual funcional do SIGEF

Este documento descreve o SIGEF do ponto de vista de utilizacao: para que serve o sistema, quais recursos existem, o que cada painel faz, quais paginas estao disponiveis e como executar os principais fluxos de trabalho.

## Visao geral do sistema

O SIGEF e um Sistema Integrado de Gestao Escolar e Formacao. Ele acompanha o ciclo completo de uma pessoa no processo formativo:

1. Alistamento ou registo inicial.
2. Validacao de dados pessoais, documentos, contactos e proveniencia.
3. Vinculacao a instituicao de ensino.
4. Conversao para recruta, instruendo ou formando.
5. Inscricao em turma, curso, fase e disciplinas.
6. Acompanhamento de presencas, avaliacoes, faltas, licencas e transferencias.
7. Emissao de fichas, cartoes, pautas, certificados e relatorios.
8. Consulta de indicadores no painel de controlo.

O sistema foi construido em Laravel, Filament, Livewire e MySQL/MariaDB. Usa papeis e permissoes para separar responsabilidades entre administracao geral, escola, DPQ e comando.

## Publico-alvo

- Administradores do SIGEF.
- Direccao ou comando com necessidade de supervisao.
- DPQ ou equipa responsavel por alistamento/recrutamento.
- Escolas/instituicoes de ensino.
- Secretarias academicas.
- Formadores/instrutores autorizados.
- Suporte tecnico e equipa de infraestrutura.

## Recursos principais

| Recurso | O que permite fazer |
| --- | --- |
| Login unificado | Entrar no sistema e ser redirecionado para o painel correto conforme o papel do utilizador. |
| Painel de controlo | Ver indicadores de alistados, formandos, recrutas, cursos, disciplinas, instituicoes e dados vindos da API SIGA. |
| Gestao de acesso | Criar utilizadores, gerir papeis, permissoes e acessos. |
| Instituicoes | Registar escolas, faculdades, unidades organicas e dados usados em relatorios. |
| Curriculo | Gerir anos academicos, cursos, fases, disciplinas, mapas e planos de curso. |
| Alistados/Formandos | Registar, importar, editar, filtrar, transferir, vincular e converter pessoas no fluxo formativo. |
| Gestao de formandos | Inscrever formandos em turmas/cursos, promover fases e concluir formacao. |
| Turmas | Criar turmas, associar cursos, fases, disciplinas, formadores e formandos. |
| Avaliacoes | Lancar notas, calcular resultados, emitir mini pautas e pauta geral. |
| Presencas | Registar pontos, presencas, faltas e assiduidade. |
| Licencas e baixas | Registar ausencias, desistencias, reprovacoes por faltas e baixas de curso. |
| Transferencias | Mover alistados/formandos entre instituicoes e consultar historico. |
| Formadores | Registar formadores, autorizar disciplinas, atribuir turmas e imprimir fichas/cartoes. |
| Atribuicao de meios | Registar meios/equipamentos entregues aos formandos. |
| Documentos | Criar documentos, anexar ficheiros, enviar para destinatarios e acompanhar respostas. |
| Cartoes | Criar modelos de cartao, visualizar frente/verso e imprimir. |
| Relatorios | Gerar documentos PDF com cabecalho/rodape institucional. |
| Integracoes | Consultar dados externos do SIGA, PIIPS/PNA, portal de recrutamento, SMS e e-mail. |

## Acesso ao sistema

### Login

URL: `/login`

Funcionalidade:

- Autenticacao unica para todos os perfis.
- Redirecionamento automatico para `/admin`, `/escola`, `/dpq` ou `/comando`.
- Sessao unica por utilizador.
- Protecao contra tentativas repetidas de login.

Como usar:

1. Abrir `/login`.
2. Informar e-mail e palavra-passe.
3. Confirmar se entrou no painel correto.
4. Trocar a palavra-passe inicial quando for primeiro acesso.

## Painel Admin

URL: `/admin`

O painel Admin e o painel mais completo. Ele centraliza configuracoes globais, gestao de acesso, curriculo, instituicoes, formandos, formadores, documentos, relatorios, cartoes e indicadores.

### Painel de Controlo

Pagina: `Painel de Controlo`

Funcionalidades:

- Indicadores de alistados, formandos, recrutas, instruendos, formadores, cursos, disciplinas, instituicoes e mapas de curso.
- Grafico "Alunos por Instituicao de Ensino".
- Grafico "Total de Alunos por Curso".
- Grafico "Estado de Formandos".
- Grafico "Formandos Aprovados e Reprovados".
- Integracao com API SIGA para trazer dados externos quando configurada.
- Filtros por instituicao, curso e periodo.

Como usar:

1. Entrar em `/admin`.
2. Usar filtros no topo para restringir por instituicao, curso ou data.
3. Comparar dados do sistema com dados da API SIGA nos graficos.
4. Clicar nos cartoes/indicadores quando houver detalhe disponivel.

Observacao operacional:

- Os graficos usam cache para evitar lentidao quando a API externa oscila.
- Se os dados externos parecerem antigos, verificar configuracao SIGA e cache em `docs/CONFIGURACAO.md`.

### Gestao de Acesso

#### Utilizadores

Pagina: `Utilizadores`

Funcionalidades:

- Criar utilizadores.
- Editar dados de acesso.
- Ativar/desativar contas.
- Associar utilizador a instituicao.
- Atribuir papeis/permissoes.
- Consultar detalhes do utilizador.

Como usar:

1. Abrir `Gestao de Acesso > Utilizadores`.
2. Criar ou editar um utilizador.
3. Definir e-mail, nome, instituicao e estado.
4. Associar papel adequado.
5. Guardar e testar acesso com o perfil criado.

#### Acessos

Pagina: `Acessos`

Funcionalidades:

- Gerir papeis do Filament Shield/Spatie Permission.
- Definir permissoes por Resource, Page e Widget.
- Ver, criar, editar e consultar papeis.

Como usar:

1. Abrir `Gestao de Acesso > Acessos`.
2. Selecionar um papel.
3. Marcar as permissoes necessarias.
4. Guardar.
5. Limpar cache de permissoes quando necessario:

```bash
php artisan permission:cache-reset
```

### Instituicoes

#### Instituicoes

Pagina: `Instituicoes`

Funcionalidades:

- Criar escolas/instituicoes.
- Editar dados institucionais.
- Definir tipo, provincia, municipio, endereco e contactos.
- Associar instituicoes a cursos, formandos, formadores e relatorios.
- Excluir instituicoes conforme regras do sistema.

Como usar:

1. Abrir `Instituicoes > Instituicoes`.
2. Criar a instituicao antes de cadastrar formandos/turmas.
3. Preencher nome, sigla, tipo, provincia, municipio e dados de contacto.
4. Guardar.

#### Tipos de Instituicao

Pagina: `Tipos de Instituicao`

Funcionalidades:

- Cadastrar categorias de instituicoes.
- Padronizar classificacao usada em filtros e relatórios.

### Curriculo

#### Anos Academicos

Funcionalidades:

- Criar anos academicos.
- Definir periodo letivo.
- Marcar ano ativo.
- Usar ano academico em turmas, cursos e inscricoes.

#### Cursos

Funcionalidades:

- Criar cursos.
- Associar curso a instituicao.
- Definir nome, descricao e estado.
- Usar curso em mapas, turmas e inscricoes.

#### Fases do Curso

Funcionalidades:

- Organizar o curso por fases, etapas ou periodos.
- Permitir ligacao entre curso, disciplinas e turma.

#### Disciplinas

Funcionalidades:

- Criar disciplinas.
- Associar disciplina a curso/fase/instituicao.
- Usar disciplina em avaliacoes, turmas e planos de curso.

#### Mapas e Planos de Curso

Funcionalidades:

- Criar o mapa curricular de uma instituicao/curso.
- Associar curso, ano academico, fases e disciplinas.
- Definir estrutura que sera usada para inscricao de formandos.

#### Planos de Curso

Funcionalidades:

- Detalhar plano de ensino.
- Relacionar disciplinas, cargas e fases.
- Apoiar inscricoes e avaliacao.

### Gestao Escolar

#### Formandos

Pagina: `Formandos`

Funcionalidades:

- Criar formando/alistado.
- Editar identificacao pessoal, filiacao, telefone, e-mail, provincia, municipio e endereco.
- Importar por Excel.
- Baixar modelo de importacao.
- Sincronizar candidatos do portal de recrutamento.
- Imprimir ficha do formando.
- Transferir alistado/formando entre instituicoes.
- Vincular alistado e converter em recruta.
- Atribuir escola em massa.
- Enviar SMS de apresentacao.
- Excluir registos conforme permissoes.

Como usar para criar:

1. Abrir `Gestao Escolar > Formandos`.
2. Clicar em `Criar`.
3. Preencher identificacao pessoal.
4. Preencher contactos e localizacao.
5. Informar proveniencia/documentos quando aplicavel.
6. Guardar.

Como usar para converter alistados:

1. Filtrar ou selecionar alistados.
2. Usar acao `Vincular e Converter`.
3. Escolher instituicao/escola.
4. Confirmar.
5. Verificar os novos recrutas na gestao de formandos.

#### Cadetes

Funcionalidades:

- Consultar e gerir cadetes/formandos avancados.
- Importar dados por Excel.
- Baixar modelo de importacao.
- Vincular e iniciar formacao.
- Transferir instituicao.
- Enviar SMS.

#### Gestao de Formandos

Pagina: `Gestao de Formandos`

Funcionalidades:

- Gerir inscricoes em turmas/cursos.
- Promover recrutas para instruendos.
- Mudar formandos em formacao para concluidos.
- Editar inscricao, curso, turma e fase.
- Aplicar acoes em massa respeitando o estado selecionado.
- Consultar dados academicos vinculados ao formando.

Como usar:

1. Abrir `Gestao Escolar > Gestao de Formandos`.
2. Filtrar por instituicao, curso, turma ou estado.
3. Selecionar apenas os registos do estado que deseja alterar.
4. Executar a acao em massa adequada.
5. Confirmar resultado na listagem.

#### Turmas

Funcionalidades:

- Criar turmas por curso, fase e ano academico.
- Associar formandos.
- Associar formadores e disciplinas.
- Editar dados da turma.

#### Pontos / Presencas

Funcionalidades:

- Registar presencas e faltas.
- Consultar assiduidade.
- Apoiar decisao de reprovacao por faltas.

#### Avaliacoes

Funcionalidades:

- Lancar notas por formando/disciplina.
- Definir tipo de avaliacao.
- Calcular medias e resultados.
- Apoiar geracao de pautas.

#### Mini Pautas e Pauta Geral

Funcionalidades:

- Gerar mini pauta por turma/disciplina.
- Gerar pauta geral.
- Consultar notas, aprovados, reprovados e pendentes.
- Imprimir ou exportar conforme configuracao.

#### Certificados

Funcionalidades:

- Gerar certificado individual.
- Gerar certificados em massa.
- Usar dados institucionais no cabecalho/rodape.
- Emitir certificados para formandos concluidos/aprovados.

#### Licencas, desistencias e baixas

Funcionalidades:

- Registar licenca ou ausencia.
- Registar reprovacao por faltas.
- Registar desistencias ou baixa de curso.
- Adicionar ocorrencias relacionadas.
- Consultar historico.

#### Historicos de Transferencia

Paginas:

- `Historico Alistados`
- `Historico Formandos`
- `Historico de Transferencias`

Funcionalidades:

- Consultar origem, destino, data e responsavel pela transferencia.
- Acompanhar movimentacao entre instituicoes.
- Auditar mudancas institucionais.

#### Atribuicao de Meios

Funcionalidades:

- Registar meios/equipamentos entregues.
- Informar data, quantidade, estado e responsavel.
- Consultar devolucoes ou atribuicoes em aberto.

### Recursos Humanos

#### Formadores

Funcionalidades:

- Criar formador.
- Importar por Excel.
- Baixar modelo.
- Consultar ficha do formador.
- Imprimir cartao.
- Associar a instituicao.
- Autorizar disciplinas.
- Definir estado ativo/inativo.

Como usar:

1. Abrir `Recursos Humanos > Formadores`.
2. Criar ou importar formadores.
3. Preencher dados pessoais e profissionais.
4. Autorizar disciplinas que o formador pode lecionar.
5. Associar a turmas quando necessario.

#### Atribuicao de Turmas

Funcionalidades:

- Atribuir formador a turma.
- Editar atribuicoes.
- Gerir disciplinas do formador na turma.
- Adicionar disciplina autorizada.

#### Efectivos

Funcionalidades:

- Consultar efetivos ligados ao sistema.
- Apoiar controlo administrativo e recursos humanos.

### Comunicacao e Documentos

#### Documentos

Funcionalidades:

- Criar documento.
- Definir destinatarios.
- Anexar ficheiros.
- Visualizar documento.
- Editar documento.
- Responder documento.
- Acompanhar respostas e estado.

Como usar:

1. Abrir `Comunicacao > Documentos`.
2. Clicar em `Criar`.
3. Informar assunto, conteudo e destinatarios.
4. Anexar ficheiros se necessario.
5. Guardar/enviar.
6. Acompanhar respostas na visualizacao do documento.

### Configuracoes

#### Cartoes

Funcionalidades:

- Criar template de cartao.
- Definir frente e verso.
- Configurar cores de fallback.
- Usar imagem de fundo quando existir.
- Pre-visualizar antes de imprimir.
- Editar e eliminar modelos.

Como usar:

1. Abrir `Configuracoes > Cartoes`.
2. Criar modelo.
3. Configurar dimensoes, textos, cores e imagens.
4. Pre-visualizar frente e verso.
5. Ajustar ate ficar pronto para impressao.

#### Configurar Instituicao

Funcionalidades:

- Definir dados institucionais para relatorios.
- Informar nome, sigla, orgao, NIF, telefone, e-mail, endereco, logotipo e rodape.
- Alimentar cabecalhos e rodapes de fichas, relatorios, certificados e cartoes.

#### Servidor de E-mail

Funcionalidades:

- Configurar SMTP.
- Testar envio.
- Guardar configuracoes sensiveis cifradas.

#### Backup da BD

Funcionalidades:

- Configurar/gerir backup da base de dados.
- Apoiar rotina de seguranca e recuperacao.
- Deve ser usado apenas por administradores autorizados.

#### Patentes

Funcionalidades:

- Cadastrar patentes.
- Usar patentes em formadores, efetivos, candidatos ou formandos conforme o fluxo.

#### Orgaos de Proveniencia

Funcionalidades:

- Cadastrar orgaos/unidades de origem.
- Usar proveniencia no cadastro de candidatos/formandos.

#### Tipos de Alunos

Funcionalidades:

- Cadastrar categorias como alistado, recruta, instruendo, formando e concluido.
- Definir cores/rotulos quando aplicavel.

#### Tipos de Recrutamento

Funcionalidades:

- Cadastrar formas de recrutamento/ingresso.
- Usar classificacao no cadastro de candidatos.

### Relatorios

Pagina: `Relatorios`

Funcionalidades:

- Emitir relatorios administrativos.
- Gerar PDFs com dados filtrados.
- Usar cabecalho e rodape da configuracao institucional.
- Apoiar auditoria, impressao e arquivo.

## Painel Escola

URL: `/escola`

O painel Escola e focado na operacao diaria de uma instituicao. Normalmente mostra apenas dados relacionados a escola/instituicao do utilizador.

### Painel de Controlo da Escola

Funcionalidades:

- Indicadores da escola.
- Estado dos formandos.
- Aprovados e reprovados.
- Ultimos formandos.
- Visao operacional sem configuracoes globais desnecessarias.

### Paginas e recursos do painel Escola

| Area | Paginas | Uso |
| --- | --- | --- |
| Curriculo | Anos Academicos, Cursos, Fases do Curso, Disciplinas, Mapas de Curso, Planos de Curso, Turmas | Configurar estrutura academica da escola. |
| Gestao Escolar | Formandos, Gestao de Formandos, Cadetes, Pontos/Presencas, Avaliacoes, Mini Pautas, Pauta Geral, Certificados, Licencas, Atribuicao de Meios, Historico de Transferencias | Executar a rotina escolar e acompanhar formandos. |
| Recursos Humanos | Formadores, Atribuicao de Turmas | Gerir formadores e vinculo com turmas/disciplinas. |
| Documentos | Documentos | Criar, receber, responder e acompanhar documentos. |
| Instituicoes | Instituicoes | Consultar dados da instituicao conforme permissao. |
| Relatorios | Relatorios | Emitir relatorios da escola. |

Como usar no dia a dia:

1. Conferir indicadores no painel.
2. Atualizar formandos e inscricoes.
3. Registrar presencas e avaliacoes.
4. Emitir pautas quando houver notas.
5. Gerar certificados para concluidos/aprovados.
6. Consultar relatorios para direccao.

## Painel DPQ

URL: `/dpq`

O painel DPQ e voltado para alistamento, recrutamento e acompanhamento inicial antes da entrada plena na escola.

### Recursos do painel DPQ

| Pagina | Uso |
| --- | --- |
| Formandos/Candidatos | Registar alistados, editar dados, importar, consultar e acompanhar candidatos. |
| Instituicoes | Consultar ou gerir instituicoes relacionadas ao fluxo de recrutamento. |
| Tipos de Recrutamento | Cadastrar categorias de recrutamento. |
| Testes de Selecao | Registar e acompanhar provas/testes de selecao. |
| Cadetes/Formandos | Acompanhar candidatos ja convertidos quando aplicavel. |

Fluxo recomendado:

1. Registar ou importar candidatos.
2. Validar documentos e dados pessoais.
3. Registar teste de selecao quando aplicavel.
4. Atribuir instituicao/escola.
5. Converter para recruta/formando quando aprovado.
6. Acompanhar transferencia para gestao escolar.

## Painel Comando

URL: `/comando`

O painel Comando e voltado para supervisao institucional. Ele deve permitir consulta e acompanhamento sem expor configuracoes operacionais desnecessarias.

### Recursos do painel Comando

| Pagina | Uso |
| --- | --- |
| Instituicoes | Consultar instituicoes e sua estrutura. |
| Formandos/Cadetes | Acompanhar dados de formandos/cadetes. |
| Formadores | Consultar formadores e efetivos docentes. |
| Avaliacoes | Consultar informacao academica e resultados conforme permissao. |

Uso recomendado:

1. Entrar em `/comando`.
2. Consultar indicadores e listagens.
3. Usar filtros por instituicao, curso ou estado.
4. Exportar/solicitar relatorios quando necessario.

## Fluxos de trabalho detalhados

### 1. Configuracao inicial do sistema

1. Criar/validar instituicoes.
2. Criar tipos de instituicao, patentes, proveniencias e tipos de aluno.
3. Criar ano academico ativo.
4. Criar cursos, fases e disciplinas.
5. Criar mapas/planos de curso.
6. Configurar instituicao para relatorios.
7. Criar utilizadores e papeis.
8. Testar login por perfil.

### 2. Registar alistado/formando

1. Abrir `Formandos`.
2. Clicar em `Criar`.
3. Preencher identificacao pessoal.
4. Preencher filiacao.
5. Preencher telefone, e-mail, provincia, municipio e endereco.
6. Preencher proveniencia e documentos.
7. Guardar.
8. Imprimir ficha quando necessario.

### 3. Importar candidatos ou formadores por Excel

1. Abrir o recurso correspondente.
2. Baixar modelo.
3. Preencher o ficheiro sem alterar nomes de colunas obrigatorias.
4. Fazer upload em `Importar Excel`.
5. Validar resumo da importacao.
6. Corrigir duplicados ou erros indicados.

### 4. Vincular alistado e converter em recruta

1. Abrir `Formandos`.
2. Filtrar por alistados.
3. Selecionar registos.
4. Usar `Vincular e Converter`.
5. Escolher escola/instituicao.
6. Confirmar.
7. Consultar os recrutas em `Gestao de Formandos`.

### 5. Inscrever formando em curso/turma

1. Abrir `Gestao de Formandos`.
2. Selecionar formando.
3. Editar inscricao.
4. Escolher instituicao, curso, turma e fase.
5. Confirmar disciplinas conforme plano.
6. Guardar.

### 6. Promover ou concluir formandos

1. Abrir `Gestao de Formandos`.
2. Filtrar pelo estado correto.
3. Selecionar apenas recrutas para promover a instruendo, ou apenas em formacao para concluir.
4. Executar acao em massa correspondente.
5. Confirmar.
6. Validar a listagem apos a acao.

### 7. Registar avaliacoes e emitir pautas

1. Garantir que turma, disciplinas e formandos estao configurados.
2. Abrir `Avaliacoes`.
3. Lancar notas por disciplina/formando.
4. Abrir `Mini Pautas` ou `Pauta Geral`.
5. Filtrar por turma/curso/fase.
6. Conferir notas e resultado.
7. Imprimir ou exportar.

### 8. Emitir certificados

1. Garantir que o formando esta aprovado/concluido.
2. Confirmar dados institucionais em `Configurar Instituicao`.
3. Abrir `Certificados`.
4. Gerar certificado individual ou em massa.
5. Conferir PDF antes de imprimir.

### 9. Criar e imprimir cartoes

1. Abrir `Configuracoes > Cartoes`.
2. Criar template com frente e verso.
3. Configurar fundo, cores, logotipo e campos.
4. Pre-visualizar.
5. Abrir o recurso de pessoa/formador/formando.
6. Usar acao de impressao de cartao.

### 10. Gerar relatorios

1. Configurar dados institucionais.
2. Abrir `Relatorios`.
3. Escolher tipo de relatorio.
4. Aplicar filtros.
5. Gerar PDF.
6. Arquivar ou imprimir.

## Integracoes externas

### SIGA

Uso:

- Trazer dados externos para graficos do painel.
- Comparar alunos por instituicao e curso.
- Trazer estados de cadetes/pos-laboral, aprovados e reprovados quando a API disponibiliza.

Configuracao:

- Ver `SIGA_API_BASE_URL`.
- Ver `SIGA_API_KEY`.
- Ver `SIGA_API_TOKEN`.
- Ver endpoints em `docs/CONFIGURACAO.md`.

Operacao:

- O SIGEF usa cache para nao travar o painel.
- Quando a API falha, o painel usa o ultimo resultado valido.
- Para testes controlados, limpar caches e verificar logs.

### SMS

Uso:

- Enviar SMS de apresentacao.
- Avisar alistados/formandos.
- Apoiar comunicacao em massa.

Cuidados:

- Validar telefones antes do envio.
- Confirmar saldo/estado no fornecedor.
- Nao enviar mensagens sem autorizacao operacional.

### E-mail

Uso:

- Envio de mensagens e notificacoes.
- Configuravel em `.env` ou painel `Servidor de E-mail`.

### PIIPS/PNA

Uso:

- Consulta por NIP.
- Apoio a preenchimento/validacao de dados institucionais quando a API estiver disponivel.

### Portal de recrutamento

Uso:

- Sincronizar candidatos externos.
- Importar dados para o fluxo do DPQ/SIGEF.

## Boas praticas de uso

- Criar primeiro instituicoes, anos, cursos, fases e disciplinas.
- Evitar duplicar candidatos/formandos; pesquisar antes de criar.
- Usar importacao por Excel apenas com modelos oficiais.
- Validar filtros antes de aplicar acoes em massa.
- Nao misturar estados diferentes em acoes de promocao/conclusao.
- Conferir dados institucionais antes de gerar relatorios/certificados.
- Manter papeis e permissoes atualizados.
- Rever logs quando uma integracao externa nao responder.
- Fazer backup antes de importacoes grandes.

## Permissoes recomendadas por perfil

| Perfil | Acesso recomendado |
| --- | --- |
| `super_admin` | Acesso total, configuracoes, backups, permissoes e manutencao. |
| `admin` | Administracao geral sem tarefas tecnicas sensiveis quando possivel. |
| `escola_admin` | Painel Escola, formandos, turmas, avaliacoes, pautas, certificados e relatorios da instituicao. |
| `escola_user` | Operacao limitada da escola conforme funcao. |
| `dpq_admin` | Painel DPQ, candidatos, recrutamento, testes e atribuicao inicial. |
| `dpq_user` | Operacao limitada de alistamento/recrutamento. |
| `comando_admin` | Consulta/supervisao ampla. |
| `comando_user` | Consulta restrita conforme permissao. |

## Glossario rapido

| Termo | Significado |
| --- | --- |
| Alistado | Pessoa registada no processo inicial. |
| Recruta | Alistado convertido para primeira fase de formacao. |
| Instruendo | Formando promovido para etapa/fase de instrucao. |
| Formando | Pessoa em gestao escolar/formativa. |
| Cadete | Categoria de formando usada em alguns fluxos do sistema/SIGA. |
| Pos-laboral | Tipo/turno de formacao vindo da API SIGA quando aplicavel. |
| Curso | Programa formativo. |
| Fase | Etapa de um curso. |
| Disciplina | Unidade curricular avaliada. |
| Turma | Grupo de formandos em curso/fase/ano. |
| Pauta | Documento de notas/resultados. |
| Certificado | Documento emitido apos conclusao/aprovacao. |
| Proveniencia | Orgao/unidade de origem do candidato/formando. |

## Onde encontrar mais informacao

- Instalacao: `docs/INSTALACAO.md`.
- Configuracao: `docs/CONFIGURACAO.md`.
- Arquitetura: `docs/ARQUITETURA.md`.
- Seguranca: `docs/SEGURANCA.md`.
- Operacao e manutencao: `docs/OPERACAO.md`.
