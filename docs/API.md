# Documentacao da API REST do SIGEF

Este documento descreve a API REST do SIGEF, incluindo endpoints, autenticacao, filtros e exemplos de uso.

## Visao geral

A API do SIGEF fornece acesso programatico a dados do dashboard e estatisticas do sistema. Inclui autenticacao via login e endpoints de consulta (GET), desenhada para alimentar paineis de controlo, dashboards externos ou integracao com outros sistemas.

- **Base URL:** `https://{dominio}/api/v1`
- **Formato:** JSON
- **Autenticacao:** Laravel Sanctum (Bearer Token)
- **Versao:** v1

## Autenticacao

### Como funciona

A API usa **Laravel Sanctum** para autenticacao. Todos os endpoints requerem um token de acesso valido enviado no header `Authorization`.

Sanctum suporta dois modos de autenticacao:

| Modo | Uso | Como funciona |
|------|-----|---------------|
| **Token Bearer** | Aplicacoes externas, scripts, integracao | Token pessoal de API gerado por utilizador |
| **Cookie/Sessao** | SPA no mesmo dominio | Usa a sessao web existente (stateful) |

### Obter um token de acesso

#### Opcao 1: Via endpoint de login (recomendado)

Envie um `POST` para `/api/v1/login` com as credenciais:

```bash
curl -X POST https://sigef.test:8443/api/v1/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"email": "utilizador@exemplo.ao", "password": "senha123", "device_name": "app-parceiro"}'
```

**Resposta de sucesso (200):**

```json
{
  "message": "Autenticação realizada com sucesso.",
  "token": "1|abc123xyz...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Nome do Utilizador",
    "email": "utilizador@exemplo.ao"
  }
}
```

**Resposta de erro (422):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["As credenciais fornecidas estão incorrectas."]
  }
}
```

> **IMPORTANTE:** Guarde o token retornado num local seguro. Use-o em todas as requisicoes seguintes.

#### Opcao 2: Via Tinker (administradores)

Para gerar tokens manualmente sem passar pelo login:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::find(1);
$token = $user->createToken('nome-do-token')->plainTextToken;
echo $token;
```

### Usar o token nas requisicoes

Adicione o token no header `Authorization` de cada requisicao:

```bash
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/user
```

### Resposta de erro de autenticacao

Se o token for invalido ou estiver ausente:

```json
{
  "message": "Unauthenticated."
}
```

**HTTP Status:** `401 Unauthorized`

### Configuracoes de seguranca

| Parametro | Valor |
|-----------|-------|
| Expiracao do token | Nunca (configuravel em `config/sanctum.php`) |
| Dominios stateful | Configurados via `SANCTUM_STATEFUL_DOMAINS` no `.env` |
| CORS origens permitidas | Configuradas via `CORS_ALLOWED_ORIGINS` no `.env` |
| Credenciais CORS | Desativado (`supports_credentials: false`) |

---

## Endpoints

### 0. Login (autenticacao)

Autenticar e obter token de acesso. **Este e o unico endpoint publico (nao requer token).**

```
POST /api/v1/login
```

**Body (JSON):**

| Campo | Tipo | Obrigatorio | Descricao |
|-------|------|-------------|----------|
| `email` | string | Sim | Email do utilizador |
| `password` | string | Sim | Password do utilizador |
| `device_name` | string | Nao | Nome do dispositivo/app (default: `api-token`) |

**Resposta:** Ver secao "Obter um token de acesso" acima.

---

### 0b. Logout

Revogar o token actual.

```
POST /api/v1/logout
Authorization: Bearer TOKEN
```

**Resposta:**

```json
{
  "message": "Sessão encerrada com sucesso. Token revogado."
}
```

### 0c. Logout de todas as sessoes

Revogar todos os tokens do utilizador.

```
POST /api/v1/logout-all
Authorization: Bearer TOKEN
```

**Resposta:**

```json
{
  "message": "Todas as sessões foram encerradas. Todos os tokens revogados."
}
```

---

### 1. Utilizador autenticado

Retorna os dados do utilizador associado ao token.

```
GET /api/v1/user
```

**Resposta:**

```json
{
  "id": 1,
  "name": "Administrador",
  "email": "admin@sigef.ao",
  "email_verified_at": "2026-01-01T00:00:00.000000Z",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-06-20T12:00:00.000000Z"
}
```

---

### 2. Estatisticas gerais do dashboard

Retorna contadores globais do sistema (alistados, recrutas, instruendos, formandos, formadores, escolas, cursos e disciplinas).

```
GET /api/v1/dashboard/stats
```

**Filtros (query params):**

| Parametro | Tipo | Descricao |
|-----------|------|-----------|
| `institution_id` | int | Filtrar por instituicao |
| `course_id` | int | Filtrar por curso |
| `start_date` | date (Y-m-d) | Data inicio do periodo |
| `end_date` | date (Y-m-d) | Data fim do periodo |

**Exemplo:**

```bash
curl -H "Authorization: Bearer TOKEN" \
     "https://sigef.test:8443/api/v1/dashboard/stats?institution_id=2"
```

**Resposta:**

```json
{
    "total_alunos": 30,
    "total_alistados": 20,
    "alistados": 20,
    "recrutas": 6,
    "instruendos": 4,
    "recrutas_instruendos": 10,
    "formandos": 1,
    "formandos_superior": 1,
    "em_formacao": 5,
    "formandos_concluidos": 0,
    "em_formacao_concluidos": 5,
    "formadores": 6,
    "formadores_activos": 6,
    "instituicoes_ensino": 11,
    "mapas_planos_curso": 4,
    "mapas_planos_curso_activos": 4,
    "cursos": 3,
    "disciplinas": 12
}
```

**Descricao dos campos:**

| Campo | Descricao |
|-------|-----------|
| `total_alunos` | Total geral (alistados + recrutas + instruendos) |
| `alistados` | Candidatos com tipo "Alistado" |
| `recrutas` | Formandos na 1a fase (Recruta) |
| `instruendos` | Formandos na 2a fase (Instruendo/Cadete) |
| `recrutas_instruendos` | Soma de recrutas + instruendos |
| `formandos` | Candidatos com tipo "Formando" |
| `formandos_superior` | Formandos do ensino superior |
| `em_formacao` | Formandos em processo de formacao |
| `formandos_concluidos` | Formandos que concluiram a formacao |
| `em_formacao_concluidos` | Soma em formacao + concluidos |
| `formadores` | Total de formadores activos |
| `formadores_activos` | Formadores com estado activo |
| `instituicoes_ensino` | Total de escolas/instituicoes |
| `mapas_planos_curso` | Total de mapas/planos de curso |
| `mapas_planos_curso_activos` | Mapas/planos de curso activos |
| `cursos` | Total de cursos |
| `disciplinas` | Total de disciplinas |

---

### 3. Estado de aprovacao dos formandos

Retorna contadores de aprovados, reprovados (por notas, faltas, desistencia) e baixas de curso.

```
GET /api/v1/dashboard/student-status
```

**Filtros:** `institution_id`, `start_date`, `end_date`

**Resposta:**

```json
{
    "aprovados": 2,
    "pendentes": 3,
    "reprovados_notas": 2,
    "reprovados_faltas": 0,
    "reprovados_desistencia": 1,
    "baixa_curso": 2
}
```

**Descricao dos campos:**

| Campo | Descricao |
|-------|-----------|
| `aprovados` | Formandos com nota media >= 10 |
| `pendentes` | Formandos com avaliacoes incompletas |
| `reprovados_notas` | Reprovados por nota insuficiente |
| `reprovados_faltas` | Reprovados por excesso de faltas |
| `reprovados_desistencia` | Reprovados por desistencia |
| `baixa_curso` | Formandos que deram baixa do curso |

---

### 4. Distribuicao por estado de formacao

Retorna a distribuicao dos formandos por tipo/fase: alistado, recruta, instruendo, formando superior, em formacao.

```
GET /api/v1/dashboard/candidate-status
```

**Filtros:** `institution_id`, `start_date`, `end_date`

**Resposta:**

```json
{
    "alistado": 20,
    "recruta": 6,
    "instruendo": 4,
    "formando_superior": 0,
    "em_formacao": 5
}
```

**Descricao dos campos:**

| Campo | Descricao |
|-------|-----------|
| `alistado` | Candidatos na fase de alistamento |
| `recruta` | Formandos na 1a fase (Recruta) |
| `instruendo` | Formandos na 2a fase (Instruendo) |
| `formando_superior` | Formandos do ensino superior |
| `em_formacao` | Formandos em processo de formacao activa |

---

### 5. Estatisticas por instituicao

Retorna o total de alunos por escola/instituicao (top 10), ordenado do maior para o menor.

```
GET /api/v1/dashboard/institution-stats
```

**Filtros:** `institution_id`, `start_date`, `end_date`

**Resposta:**

```json
[
    {
        "institution_id": 2,
        "name": "ESCOLA PRÁTICA DE POLÍCIA",
        "acronym": "EPP",
        "total_alunos": 21
    },
    {
        "institution_id": 4,
        "name": "ACADEMIA DE POLÍCIA",
        "acronym": "ACADEPOL",
        "total_alunos": 5
    },
    {
        "institution_id": 7,
        "name": "COLÉGIO DE POLÍCIA COMANDANTE JOSÉ ALFREDO",
        "acronym": "EKUIKUI",
        "total_alunos": 2
    },
    {
        "institution_id": 12,
        "name": "CENTRO DE FORMAÇÃO REGIONAL NORTE",
        "acronym": "C.F.R.N-MALANJE",
        "total_alunos": 2
    }
]
```

**Descricao dos campos:**

| Campo | Descricao |
|-------|-----------|
| `institution_id` | ID da instituicao |
| `name` | Nome completo da instituicao |
| `acronym` | Sigla/acronimo da instituicao |
| `total_alunos` | Total de alunos (candidatos + formandos) |

---

### 6. Formandos por curso

Retorna estatisticas de formandos agrupados por curso.

```
GET /api/v1/dashboard/students-by-course
```

**Filtros:** `institution_id`, `course_id`, `start_date`, `end_date`

**Resposta:** Array de objectos com dados por curso.

---

### 7. Formandos recentes

Retorna os 10 formandos mais recentes do sistema.

```
GET /api/v1/dashboard/recent-students
```

**Filtros:** `institution_id`

**Resposta:**

```json
[
    {
        "id": 27,
        "nome": "Orlando Miguel",
        "instituicao": "ACADEMIA DE POLÍCIA",
        "estado": "1ª Fase - Recruta",
        "data_inscricao": "2026-06-12"
    },
    {
        "id": 26,
        "nome": "Filomena Justina Bastos",
        "instituicao": "ESCOLA PRÁTICA DE POLÍCIA",
        "estado": "1ª Fase - Recruta",
        "data_inscricao": "2026-06-11"
    },
    {
        "id": 25,
        "nome": "Manuel Pedro",
        "instituicao": "ESCOLA PRÁTICA DE POLÍCIA",
        "estado": "Em Formação",
        "data_inscricao": "2026-06-11"
    }
]
```

**Descricao dos campos:**

| Campo | Descricao |
|-------|-----------|
| `id` | ID do formando |
| `nome` | Nome completo |
| `instituicao` | Nome da escola/instituicao |
| `estado` | Fase actual (Recruta, Instruendo, Em Formacao, etc.) |
| `data_inscricao` | Data de inscricao no formato YYYY-MM-DD |

---

## Codigos de resposta HTTP

| Codigo | Significado |
|--------|------------|
| `200` | Sucesso |
| `401` | Nao autenticado (token invalido ou ausente) |
| `403` | Sem permissao |
| `404` | Recurso nao encontrado |
| `500` | Erro interno do servidor |

## Limites e restricoes

| Restricao | Valor |
|-----------|-------|
| Rate limiting | Nao configurado (sem limite) |
| Metodos HTTP | Apenas GET (somente leitura) |
| Paginacao | Nao implementada (dados retornados por inteiro) |
| Escrita (POST/PUT/DELETE) | Nao disponivel via API |

## Integracoes externas (saida)

O SIGEF tambem consome APIs externas, configuradas no `.env`:

| Servico | Variavel `.env` | Funcao |
|---------|----------------|--------|
| SIGA | `SIGA_API_URL`, `SIGA_API_TOKEN` | Dados de formandos do sistema SIGA |
| PIIPS/PNA | `PIIPS_API_URL`, `PIIPS_API_TOKEN` | Integracao com sistema PIIPS |
| TelcoSMS | `TELCOSMS_API_URL`, `TELCOSMS_API_KEY` | Envio de SMS |
| Portal de Recrutamento | `RECRUITMENT_PORTAL_URL` | Dados de candidatos |
| Consulta de BI | `IDENTITY_CARD_LOOKUP_URL` | Verificacao de bilhete de identidade |

## Guia de teste rapido

### Credenciais de teste

| Campo | Valor |
|-------|-------|
| Email | `api-teste@sigef.ao` |
| Password | `Api@Teste2026!` |

> **AVISO:** Estas credenciais sao apenas para ambiente de teste/desenvolvimento. Em producao, crie utilizadores dedicados com permissoes adequadas.

### Passo 1: Obter o token via login

```bash
curl -k -X POST https://sigef.test:8443/api/v1/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d "{\"email\": \"api-teste@sigef.ao\", \"password\": \"Api@Teste2026!\"}"
```

A resposta incluira o token:

```json
{
  "message": "Autenticação realizada com sucesso.",
  "token": "3|xYz123AbCdEfGh...",
  "token_type": "Bearer",
  "user": {
    "id": 34,
    "name": "API Teste",
    "email": "api-teste@sigef.ao"
  }
}
```

### Passo 2: Usar o token para consultar a API

Substitua `TOKEN` pelo valor recebido no passo anterior:

```bash
# Dados do utilizador autenticado
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/user

# Estatisticas gerais
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/stats

# Estatisticas filtradas por instituicao e periodo
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     "https://sigef.test:8443/api/v1/dashboard/stats?institution_id=2&start_date=2026-01-01&end_date=2026-06-30"

# Estado de aprovacao
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/student-status

# Distribuicao por fase
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/candidate-status

# Estatisticas por escola
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/institution-stats

# Formandos por curso
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/students-by-course

# Formandos recentes
curl -k -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/recent-students
```

### Passo 3: Encerrar sessao (opcional)

```bash
# Revogar o token actual
curl -k -X POST -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/logout

# Ou revogar TODOS os tokens
curl -k -X POST -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/logout-all
```

---

## Exemplo com JavaScript (fetch)

```javascript
const API_BASE = 'https://sigef.test:8443/api/v1';

// Passo 1: Fazer login e obter token
async function login(email, password) {
  const response = await fetch(`${API_BASE}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email, password, device_name: 'minha-app' }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Erro de autenticação');
  }

  const data = await response.json();
  return data.token; // Guardar este token
}

// Passo 2: Consultar a API com o token
async function getStats(token, filters = {}) {
  const params = new URLSearchParams(filters);
  const response = await fetch(`${API_BASE}/dashboard/stats?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Erro ${response.status}: ${response.statusText}`);
  }

  return response.json();
}

// Exemplo de uso completo
async function main() {
  try {
    // Login
    const token = await login('api-teste@sigef.ao', 'Api@Teste2026!');
    console.log('Token obtido com sucesso!');

    // Consultar estatisticas
    const stats = await getStats(token, { institution_id: 2 });
    console.log('Total de alunos:', stats.total_alunos);
    console.log('Recrutas:', stats.recrutas);
    console.log('Formadores:', stats.formadores);
  } catch (error) {
    console.error('Erro:', error.message);
  }
}

main();
```

## Exemplo com Python (requests)

```python
import requests

API_BASE = 'https://sigef.test:8443/api/v1'

# Passo 1: Fazer login
response = requests.post(f'{API_BASE}/login', json={
    'email': 'api-teste@sigef.ao',
    'password': 'Api@Teste2026!',
    'device_name': 'python-script',
}, verify=False)

data = response.json()
token = data['token']
print(f"Token: {token}")

# Passo 2: Consultar a API
headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json',
}

# Estatisticas gerais
stats = requests.get(f'{API_BASE}/dashboard/stats', headers=headers, verify=False).json()
print(f"Total de alunos: {stats['total_alunos']}")
print(f"Recrutas: {stats['recrutas']}")

# Formandos recentes
students = requests.get(f'{API_BASE}/dashboard/recent-students', headers=headers, verify=False).json()
for s in students:
    print(f"  - {s['nome']} ({s['estado']})")
```

## Testar com Postman

1. **Importar a coleccao:** Crie uma nova coleccao no Postman chamada "SIGEF API".

2. **Configurar o login:**
   - Metodo: `POST`
   - URL: `https://sigef.test:8443/api/v1/login`
   - Body (raw JSON):
     ```json
     {
       "email": "api-teste@sigef.ao",
       "password": "Api@Teste2026!"
     }
     ```
   - Desative a verificacao SSL em Settings > SSL certificate verification.

3. **Copiar o token** da resposta do login.

4. **Configurar os endpoints protegidos:**
   - Em Authorization, seleccione "Bearer Token".
   - Cole o token recebido.
   - Teste os endpoints GET como `/api/v1/dashboard/stats`.

## Resumo dos endpoints

| Metodo | Endpoint | Auth | Descricao |
|--------|----------|------|-----------|
| `POST` | `/api/v1/login` | Nao | Autenticar e obter token |
| `POST` | `/api/v1/logout` | Sim | Revogar token actual |
| `POST` | `/api/v1/logout-all` | Sim | Revogar todos os tokens |
| `GET` | `/api/v1/user` | Sim | Dados do utilizador |
| `GET` | `/api/v1/dashboard/stats` | Sim | Estatisticas gerais |
| `GET` | `/api/v1/dashboard/student-status` | Sim | Aprovados/reprovados |
| `GET` | `/api/v1/dashboard/candidate-status` | Sim | Distribuicao por fase |
| `GET` | `/api/v1/dashboard/institution-stats` | Sim | Estatisticas por escola |
| `GET` | `/api/v1/dashboard/students-by-course` | Sim | Formandos por curso |
| `GET` | `/api/v1/dashboard/recent-students` | Sim | 10 formandos recentes |

