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
  "total_alunos": 1250,
  "total_alistados": 450,
  "alistados": 450,
  "recrutas": 320,
  "instruendos": 480,
  "recrutas_instruendos": 800,
  "formandos": 150,
  "formandos_superior": 150,
  "em_formacao": 85,
  "formandos_concluidos": 120,
  "em_formacao_concluidos": 205,
  "formadores": 45,
  "formadores_activos": 45,
  "instituicoes_ensino": 5,
  "mapas_planos_curso": 12,
  "mapas_planos_curso_activos": 8,
  "cursos": 6,
  "disciplinas": 34
}
```

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
  "aprovados": 180,
  "pendentes": 45,
  "reprovados_notas": 22,
  "reprovados_faltas": 8,
  "reprovados_desistencia": 12,
  "baixa_curso": 5
}
```

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
  "alistado": 450,
  "recruta": 320,
  "instruendo": 280,
  "formando_superior": 50,
  "em_formacao": 85
}
```

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
    "name": "Escola Pratica de Policia \"Capolo / EPP\"",
    "acronym": "EPP",
    "total_alunos": 580
  },
  {
    "institution_id": 4,
    "name": "Academia de Policia",
    "acronym": "AP",
    "total_alunos": 420
  }
]
```

---

### 6. Formandos por curso

Retorna estatisticas de formandos agrupados por curso.

```
GET /api/v1/dashboard/students-by-course
```

**Filtros:** `institution_id`, `course_id`, `start_date`, `end_date`

**Resposta:** Array de objectos com dados por curso (delegado ao `DashboardCourseStatsService`).

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
    "id": 1234,
    "nome": "Orlando Miguel",
    "instituicao": "Academia de Policia",
    "estado": "Recruta",
    "data_inscricao": "2026-06-15"
  }
]
```

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

## Exemplo completo com cURL

```bash
# 1. Obter dados do utilizador
curl -s -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/user

# 2. Obter estatisticas da instituicao 2, no periodo de Jan-Jun 2026
curl -s -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     "https://sigef.test:8443/api/v1/dashboard/stats?institution_id=2&start_date=2026-01-01&end_date=2026-06-30"

# 3. Obter estado de aprovacao
curl -s -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/student-status

# 4. Obter formandos recentes
curl -s -H "Authorization: Bearer TOKEN" \
     -H "Accept: application/json" \
     https://sigef.test:8443/api/v1/dashboard/recent-students
```

## Exemplo com JavaScript (fetch)

```javascript
const API_BASE = 'https://sigef.test:8443/api/v1';
const TOKEN = 'SEU_TOKEN_AQUI';

async function getDashboardStats(filters = {}) {
  const params = new URLSearchParams(filters);
  const response = await fetch(`${API_BASE}/dashboard/stats?${params}`, {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Erro ${response.status}: ${response.statusText}`);
  }

  return response.json();
}

// Uso
const stats = await getDashboardStats({ institution_id: 2 });
console.log(`Total de alunos: ${stats.total_alunos}`);
```
