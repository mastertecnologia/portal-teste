# Documento 3 — RBAC e ABAC

**Catálogo e fases:** o código-fonte de verdade do catálogo é `config/permissions_registry.php`; o estado das fases de evolução (incl. aliases e resolver em runtime) está em [`IMPLEMENTATION_LOG.md`](../IMPLEMENTATION_LOG.md): **Índice P0–P3** no topo, **Roadmap**, tabela **Próximas fases (executável)** e checklists com modelos de issues (**P3** inclui fecho documental Fase 11 e este DOC3). **Runtime:** sem `RBAC_MODE`, `config/rbac.php` usa **`enforce`**, filtros de menu, auditoria de negações e políticas ativas (sobrepor com env — ver `LEGACY_COMPATIBILITY.md`). **Testes automatizados:** `composer test-rbac` corre também a suite **`rbac-http`** (integração HTTP Auth+Rbac: `Permissoes` admin, `Areas::index`, `Empresasusers::index`, `Problemas::index`, `Feriados::index`, `ContratosHoras::index`, `Normasempresa::index` / `::acessoremoto`, `Financeiro::index`, SQLite em memória; bootstrap `tests/bootstrap_http.php` — ver `TEST_CHECKLIST_RBAC.md`). Checklist manual: [`TEST_CHECKLIST_RBAC.md`](TEST_CHECKLIST_RBAC.md). Resumo do stack: [`AUTH_MODEL.md`](AUTH_MODEL.md). Legado vs. RBAC: [`LEGACY_COMPATIBILITY.md`](LEGACY_COMPATIBILITY.md). Matriz e export: [`RBAC_ABAC_MATRIX.md`](RBAC_ABAC_MATRIX.md). Políticas extra por permissão (`rbac_permission_policies` + `Rbac.evaluate_permission_policies` em `config/rbac.php`) — ver `RbacPolicyConditions`. Este documento mantém visão conceitual; exemplos de códigos nas secções abaixo podem estar desatualizados face ao registry — validar sempre em `permissions_registry.php` e no painel **Permissões**.

## 1. Visão Geral do Modelo de Permissões

O sistema usa dois mecanismos complementares:

| Mecanismo | Classe | Finalidade |
|----------|--------|-----------|
| **RBAC** (Role-Based Access Control) | `RbacComponent` + `RbacChecker` | Controla **quais ações** um perfil pode executar |
| **ABAC** (Attribute-Based Access Control) | `AbacComponent` | Controla **quais registros** o usuário pode acessar (escopo por empresa / cliente / próprio) |

O `AppController` expõe dois campos para rastreabilidade:
- `$rbacAbacScope` → escopo que autorizou (`empresa` | `cliente` | `own`)
- `$rbacAbacPermissionCode` → código da permissão que autorizou (ex.: `clientes.manage`)

---

## 2. Tabelas do Banco

```sql
rbac_roles              -- papéis (perfis)
  id  |  name  |  description

rbac_permissions        -- permissões individuais
  id  |  code  |  description  |  controller  |  action

rbac_roles_permissions  -- N:N papel ↔ permissão
  id  |  rbac_role_id  |  rbac_permission_id

rbac_users_roles        -- N:N usuário ↔ papel
  id  |  user_id  |  rbac_role_id
```

### Tabelas de suporte ABAC

```sql
empresasusers           -- usuário ↔ empresa (multi-empresa)
  id  |  iduser  |  idempresa

clientes                -- clientes vinculados ao usuário
  id  |  iduser (FK)
```

---

## 3. Perfis (Roles) Padrão

| `role` (campo users) | Descrição | Portal |
|---------------------|-----------|--------|
| `0` | Técnico / Gestor PGM | Portal PGM |
| `1` | Usuário Cliente | Portal do Cliente |
| `admin = true` | Administrador do sistema | Rotas `/admin/*` |

### Papéis RBAC (tabela `rbac_roles`)

| Nome sugerido | Descrição |
|--------------|-----------|
| `admin` | Acesso total — criação e gestão de todos os módulos |
| `gestor` | Visualização completa + relatórios; sem exclusão de registros críticos |
| `tecnico` | Acesso a tickets, OS, agenda, atendimento |
| `financeiro` | Acesso a faturamento, faturas, financeiro; sem tickets |
| `comercial` | Acesso a orçamentos, clientes, produtos |
| `cliente_basico` | Portal cliente — visualização de tickets e contratos |
| `cliente_full` | Portal cliente — solicitar orçamentos + download de faturas |

---

## 4. Permissões por Módulo

### 4.1 Atendimento / Tickets

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `tickets.view` | Visualizar tickets | tecnico, gestor, admin |
| `tickets.manage` | Criar/editar/fechar tickets | tecnico, admin |
| `tickets.assign` | Transferir tickets entre filas/técnicos | gestor, admin |
| `tickets.delete` | Excluir tickets | admin |
| `tickets.export` | Exportar listagem | gestor, admin |
| `tickets.sla_view` | Ver painel SLA | tecnico, gestor, admin |
| `tickets.historico_view` | Ver histórico completo (módulo novo) | tecnico, gestor, admin |
| `tickets.cliente_view` | Cliente visualiza seus tickets | cliente_basico, cliente_full |
| `tickets.cliente_add` | Cliente abre ticket | cliente_basico, cliente_full |

### 4.2 Ordens de Serviço

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `os.view` | Visualizar OS | tecnico, gestor, admin |
| `os.manage` | Criar/editar OS | tecnico, admin |
| `os.faturar` | Gerar faturamento a partir de OS | financeiro, admin |

### 4.3 Orçamentos

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `orcamentos.view` | Visualizar orçamentos | comercial, gestor, admin |
| `orcamentos.manage` | Criar/editar orçamentos | comercial, admin |
| `orcamentos.aprovar` | Aprovar/rejeitar orçamentos | gestor, admin |
| `orcamentos.cliente_solicitar` | Cliente solicita orçamento | cliente_full |

### 4.4 Faturamento e Financeiro

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `faturamento.view` | Visualizar cobranças | financeiro, gestor, admin |
| `faturamento.manage` | Criar/editar faturamento | financeiro, admin |
| `faturamento.alterar_status` | Alterar status do faturamento | financeiro, admin |
| `financeiro.dashboard` | Ver dashboard financeiro | financeiro, gestor, admin |
| `financeiro.receber` | Registrar recebimentos | financeiro, admin |

### 4.5 Contratos (módulo novo)

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `contratos.view` | Visualizar contratos | financeiro, gestor, admin |
| `contratos.manage` | Criar/editar contratos | financeiro, admin |
| `contratos.cliente_view` | Cliente visualiza seus contratos | cliente_basico, cliente_full |
| `faturas.cliente_download` | Cliente baixa faturas/boletos | cliente_full |

### 4.6 Relatórios (módulo novo)

| Código | Descrição | Papéis |
|--------|-----------|--------|
| `relatorios.tickets` | Relatórios de atendimento | tecnico, gestor, admin |
| `relatorios.contratos` | Relatórios de contratos | financeiro, gestor, admin |
| `relatorios.financeiro` | Relatórios financeiros | financeiro, admin |
| `relatorios.exportar` | Exportar relatórios (CSV/Excel/PDF) | gestor, admin |
| `relatorios.cliente_view` | Cliente acessa relatórios da empresa | cliente_full |

---

## 5. Regras ABAC

### 5.1 Escopos disponíveis

| Escopo | Descrição |
|--------|-----------|
| `empresa` | Usuário acessa apenas registros da(s) empresa(s) vinculada(s) a ele |
| `cliente` | Usuário acessa apenas registros do cliente vinculado ao seu usuário |
| `own` | Usuário acessa apenas registros que ele mesmo criou |
| `*` (admin) | Acesso irrestrito — sem filtro de escopo |

### 5.2 Regras por Empresa

- Cada usuário está vinculado a uma ou mais empresas via `empresasusers`.
- O campo `idempresa` na sessão define a empresa ativa.
- Troca de empresa via dropdown AJAX (action `alteraempresa`) recarrega o contexto da sessão.
- Consultas SQL sempre filtram por `idempresa` da sessão, exceto para `admin = true`.

```
Regra: usuário.idempresa_ativa → WHERE idempresa = :idempresa_ativa
```

### 5.3 Regras por Contrato

- Contratos pertencem a um cliente (`clicontratos.idcliente`).
- Um técnico (`role = 0`) acessa contratos de todos os clientes da empresa ativa.
- Um cliente (`role = 1`) acessa somente contratos de seu `idcliente`.

```
role = 0: WHERE clicontratos.idcliente IN (clientes da empresa ativa)
role = 1: WHERE clicontratos.idcliente = sessao.idcliente
```

### 5.4 Regras por Ticket

| Condição | Regra de acesso |
|---------|----------------|
| `role = 0` (técnico) | Todos os tickets da empresa ativa (queue filtra por fila do técnico) |
| `role = 1` (cliente) | Apenas `WHERE tickets.idcliente = sessao.idcliente` |
| Técnico responsável | Escopo `own`: `WHERE idtecnico_responsavel = sessao.iduser` |
| Fila específica | `WHERE queue_id IN (filas do técnico via queues_users)` |

### 5.5 Regras por Nível de Suporte (SLA)

| Nível | Acesso |
|-------|--------|
| `support_level_id` | Define SLA policy aplicada ao ticket |
| `sla_policy_id` | FK para `sla_policies.idempresa` — sempre da empresa ativa |
| Prioridade P1–P4 | Derivada da matriz impacto × urgência via `TicketClassificationService` |

---

## 6. Fluxo de Verificação (Request Lifecycle)

```
Request
  │
  ▼
AppController::beforeFilter()
  │
  ├─ Auth::user() → obtém role, iduser, admin, idempresa
  │
  ├─ RbacComponent::checkRequest(controller, action)
  │     ├─ Consulta rbac_users_roles → papéis do usuário
  │     ├─ Consulta rbac_roles_permissions → permissões dos papéis
  │     └─ Retorna null (permitido) | Response redirect (negado)
  │
  ├─ AbacComponent (chamada explícita na action)
  │     └─ Aplica filtro de escopo (empresa | cliente | own)
  │
  └─ isAuthorized($user) — verificação legado (role 0/1)
        └─ Retorna true/false
```

---

## 7. Permissão Especial: `canClienteSolicitarOrcamento`

Verificada em `AppController::beforeFilter` via `RbacChecker::clientePodeSolicitarOrcamento()`:
- Exige `role = 1` (cliente)
- Exige `permissaoacesso` ativo no cadastro do usuário
- Disponibilizada como `$canClienteSolicitarOrcamento` em todas as views

---

## 8. APIs de Integração ERP (sem sessão)

As APIs de integração com o ERP Windows são autenticadas por **token no header HTTP**, sem sessão CakePHP:

| Header | Descrição |
|--------|-----------|
| `empresa` | Código da empresa |
| `token` | Token de autenticação do integrador |

`isAuthorized()` retorna `true` diretamente para esses endpoints, ignorando RBAC/ABAC.

---

## 9. Gestão de Papéis e Permissões (UI)

| Tela | URL | Permissão necessária |
|------|-----|---------------------|
| Listagem de papéis | `/permissoes/index` | admin |
| Editar papel | `/permissoes/edit/:id` | admin |
| Matriz usuário ↔ papel | `/permissoes/admin-matrix` | admin |
| Conceder super-admin | `/permissoes/admin-grant-super-all` | admin |
