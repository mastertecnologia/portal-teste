# AUTH_AUDIT — Diagnóstico de autenticação e autorização (Fase 1)

**Projeto:** portal-teste (CakePHP)  
**Data do diagnóstico:** 2026-04-07  
**Escopo:** mapeamento do estado atual, riscos, compatibilidade obrigatória e plano de migração incremental (sem alteração de comportamento nesta fase).

**Atualização 2026-04-08:** o repositório passou a `config/rbac.php` com **`enforce`** por defeito (sem `RBAC_MODE`), filtros de menu, auditoria de negações e políticas ativas; ver **`IMPLEMENTATION_LOG.md`** (índice **P0–P3** no topo, âncoras `rbac-priority-p0` …), `docs/AUTH_MODEL.md`, `docs/TEST_CHECKLIST_RBAC.md` e **`composer test-rbac`** (suite `rbac` em `phpunit.xml.dist`; CI: `.github/workflows/rbac-phpunit.yml`). O texto abaixo conserva o diagnóstico da Fase 1 onde ainda útil; conclusões que citavam “RBAC sempre off” foram corrigidas no resumo e na secção 12.

---

## 1. Resumo executivo

| Área | Situação |
|------|----------|
| Autenticação | `AuthComponent` (Form) em `AppController`; sessão com `users.email` / `password`. |
| Autorização legada | `Controller` authorize + `isAuthorized()`: prefixo `admin` exige `user['admin']`; fora disso, **por padrão permite** rotas não-admin. |
| Autorização ERP/API | Ações `*api` / `*API` explícitas em `AppController::isAuthorized` e CORS; integração por token (não RBAC de sessão). |
| RBAC novo | Tabelas `rbac_permissions`, `rbac_roles`, `rbac_roles_permissions`, `rbac_users_roles`; `RbacComponent::checkRequest` em `beforeFilter` quando o modo RBAC ≠ `off` (neste repo: **`enforce`** por defeito se `RBAC_MODE` não estiver definida). |
| ABAC | `AbacQuery` + `AbacComponent::applyToQuery`; mapa em `config/abac.php` (`enabled` true); escopo derivado de `rbacAbacScope` ou role portal. |
| Grupos (organização de usuários) | Tabelas Fase 3 + `expand_group_roles` no `RbacComponent`. CRUD de grupos em `PermissoesController` (Fase 4 parcial). |
| Catálogo de permissões | `config/permissions_registry.php` + sync `PermissoesController::adminSyncRegistry`. |
| Painel RBAC | `PermissoesController` (somente `admin` + `role===0`): catálogo, matriz, usuários da equipe ↔ papéis, **grupos RBAC** (membros e papéis por grupo). |
| Frontend menus | `sidebar.ctp` / `sidebarcli.ctp`: `role` (0=equipe, 1=cliente), `permissaoacesso`, flags como `canClienteSolicitarOrcamento`. |

**Conclusão (Fase 1, revista em 2026-04-08):** existe **base RBAC + ABAC** integrada; o repositório adotou **modo `enforce`** por defeito em `config/rbac.php` (sobrepor com `RBAC_MODE=warn|off` se necessário). Quem **não** tem papéis em `rbac_users_roles` continua no **híbrido** (legado / `isAuthorized`), salvo `enforce_block_without_roles` após backfill — ver `IMPLEMENTATION_LOG` Fase 8 e checklist RBAC. Evolução “ERP profissional” segue **incremental** em matriz, políticas, testes e documentação (Fases 3–6, 9–11).

---

## 2. Modelos e entidades

| Recurso | Local | Observação |
|---------|--------|------------|
| Usuário | `src/Model/Entity/User.php`, `UsersTable` | Campos de negócio incluem `admin`, `role`, `permissaoacesso`, `idcliente`, `idempresa` (via sessão após login). |
| RBAC permissão | `RbacPermission` | `code`, `module`, `controller`, `action`, `perm_type`, `abac_scope`, etc. |
| RBAC papel | `RbacRole` | `slug`, `is_system`, `active`, `sort_order`, `hierarchy_level` (Fase 3). |
| Vínculos | `RbacRolesPermission`, `RbacUsersRole` | PKs compostas nas migrations. |
| Grupos RBAC | `RbacGroup`, `RbacUserGroup`, `RbacGroupRole` | Grupo ↔ utilizador ↔ papel (herança de `role_id` na checagem). |
| Políticas / campos / auditoria (schema) | Tabelas `rbac_permission_policies`, `rbac_field_permissions`, `rbac_audit_authorizations` | Criadas na Fase 3; motor e UI — roadmap Fases 5 / 4 / 9. |

---

## 3. Migrations relacionadas

| Arquivo | Conteúdo relevante |
|---------|---------------------|
| `config/Migrations/20260327140000_RbacPermissionsFoundation.php` | Cria `rbac_permissions`, `rbac_roles`, `rbac_roles_permissions`, `rbac_users_roles`. |
| `config/Migrations/20260330130000_ClientPortalRbacPapel.php` | Papel `cliente_portal` + vínculos (PostgreSQL). |
| `config/Migrations/20260402103000_PortalRelatoriosRbacPermission.php` | Permissões portal relatórios. |
| `config/Migrations/20260406140000_PortalAdvancedRbacPermissions.php` | Permissões módulos avançados. |
| `config/Migrations/20260415120000_ContractCanonicalRbacPermissions.php` | Contratos canónicos. |
| `config/Migrations/20260410120000_RemoveErpAdvancedAttendancePermission.php` | Ajuste de catálogo. |
| `config/Migrations/20260416100000_RbacPhase3GroupsPoliciesAudit.php` | Grupos RBAC, políticas/campos/auditoria (schema), `hierarchy_level` em `rbac_roles`. |

**Seeders dedicados RBAC:** não há seeds nomeados `*Rbac*`; papéis padrão são garantidos em `PermissoesController::_ensureDefaultRoles()` e parte dos dados em migrations SQL.

---

## 4. Configuração em runtime

| Arquivo | Função |
|---------|--------|
| `config/rbac.php` | `mode`, `bypass_legacy_super`, `whitelist`, `skip_action_prefixes`, `expand_legacy_aliases`, `legacy_permission_log`, `expand_group_roles`, `audit_decisions_db`, `evaluate_permission_policies` (Fase 5: políticas em `rbac_permission_policies`); rollout (Fase 8): `log_unassigned_rbac_users`, `enforce_block_without_roles`, `enforce_block_without_roles_equipe_only`. |
| `config/abac.php` | `Abac.enabled` e mapa `tables` → colunas `empresa_column`, `cliente_column`, etc. |
| `config/permissions_registry.php` | Lista mestre de permissões para inserção idempotente no sync. |

---

## 5. Middleware / componentes / helpers

| Componente | Papel |
|------------|--------|
| `AppController::initialize` | Carrega `Rbac`, `Abac`, `Auth` com `authorize => Controller`. |
| `AppController::beforeFilter` | Chama `Rbac->checkRequest($controller, $action)` após montar view vars. |
| `RbacComponent` | Com papéis RBAC efetivos e modo ≠ off: nega se não houver permissão que case controller/ação ou se `evaluate_permission_policies` e nenhuma linha ativa em `rbac_permission_policies` satisfizer `conditions_json` (OR por linha). Seta `rbacAbacScope` / `rbacAbacPermissionCode`; `rbacDenyReason` pode ser `policy_denied`. Opcional: `audit_decisions_db`. |
| `RbacPermissionResolver` | `expandPermissionIds()`: antes do match de rota, expande os IDs do papel com permissões canónicas cujo `legacy_code` está entre os códigos atribuídos; `isLegacyBundleCode()` para diagnóstico. |
| `RbacUserRolesResolver` | `effectiveRoleIds(userId)`: papéis diretos + grupos (espelha o `RbacComponent`). |
| `RbacPolicyConditions` | Avaliação de `conditions_json`; usada pelo `RbacComponent` quando `evaluate_permission_policies` está ativo. |
| `RbacChecker` | `matchAction()` para casar permissão; `clientePodeSolicitarOrcamento()` integra portal + RBAC. |
| `AbacComponent` | Delega a `AbacQuery::apply()`. |

**Não há** função global única `hasPermission($code)` usada em toda a app; a checagem efetiva de rota é **controller#action** via catálogo RBAC quando ativo.

---

## 6. `isAuthorized` e controllers

Padrão recorrente:

- **Admin:** `PermissoesController` e vários módulos restringem a `admin` + critérios adicionais (`role === 0`, etc.).
- **Demais controllers:** muitos delegam `return parent::isAuthorized($user)` → fora `admin`, **true** por padrão.

**Implicação:** com `Rbac.mode=off` ou, em `enforce`, utilizadores **sem** papéis RBAC (híbrido), o acesso a actions não-admin continua a depender sobretudo de `isAuthorized` / legado. Utilizadores **com** papéis RBAC em `enforce` precisam de permissões no catálogo que casem a rota; ver `enforce_block_without_roles` no checklist para fechar a excepção híbrida da equipa após backfill.

---

## 7. Menus e navegação

- **`src/Template/Element/sidebar.ctp`:** `$roleNav === 0` controla blocos do menu PGM (equipe vs cliente).
- **`src/Template/Element/sidebarcli.ctp`:** menu cliente condicionado a `permissaoacesso`; submenus (ex.: orçamentos) a `canClienteSolicitarOrcamento`.
- **`AppController::beforeFilter`:** define `role`, `admin`, `permissaoacesso`, estados ativos de menu, `canClienteSolicitarOrcamento`.

**Gap (atenuado na Fase 6b):** com `menu_filter_sidebar` ativo (padrão no repositório), a sidebar equipe respeita `menu_sidebar_gates` e códigos atómicos; persistem entradas e fluxos condicionados só por `role` / `admin` / `permissaoacesso` onde ainda não houver gate.

---

## 8. Frontend: telas, botões, campos

Padrões encontrados:

- **`isEditing` / readonly:** exemplo documentado em `Clientes/edit.ctp` (modo leitura via readonly + JS, sem `disabled` no cadastro).
- **Tickets:** `TicketsController` expõe flags JSON tipo `canEditDescricao` para UI React; regras misturam `role` e `admin`.
- **dashboard-react:** referências a flags de edição (ex.: `canEditDescricao`).

**Gap:** não há camada única “permission code → visibilidade de botão/campo” alinhada ao catálogo `rbac_permissions.code`.

---

## 9. Permissões existentes (catálogo)

Fonte: `config/permissions_registry.php` (sincronizado para `rbac_permissions`).

**Padrões de código hoje (amostra por família):**

- **Config / admin:** `config.*`, `permissoes.admin`
- **Empresas / equipe:** `empresas.manage`, `users.equipe`, `users.equipe_*`, `empresasusers.manage`, `queues.admin`
- **Portal clientes:** `clientes.manage`, `users.clientes_*`, `orcamentos.*`, `tickets.portal_cliente`, `portal.*`, `clientes.portal_edit`
- **Menu principal:** `dashboard.view`, `produtos.manage`, `ordensservico.*`, `servicedesk.tickets`, `tickets.api`
- **Operações:** `orcamentos.manage`, `faturas.locacao`, `prefaturamento.*`, `agenda.*`, `bancosenhas.manage`, contratos/faturas avançados, `erp.contracts.*`
- **Conta:** `users.profile`, `users.password`, `users.twofactor*`
- **API integração:** `api.ordensservico`, `api.produtos`, `api.clientes`
- **ABAC-only catalogado:** `portal.cliente_dashboard` (`perm_type` abac)

**Observação:** muitos códigos usam sufixo **`.manage`** ou nomes **`users.equipe_*`** em vez do padrão alvo estrito `modulo.recurso.acao` do prompt master. Convivem com códigos já mais granulares (`ordensservico.list`, `ordensservico.create`).

---

## 10. Papéis (roles) existentes

| Origem | Papéis |
|--------|--------|
| Campo `users.role` | `0` equipe (PGM), `1` cliente (portal). |
| `users.admin` | Acesso rotas prefixo `admin`; bypass RBAC se `bypass_legacy_super` e `role===0`. |
| Tabela `rbac_roles` | Criados/atualizados via `_ensureDefaultRoles()`: `super_admin`, `admin_equipe`, `operacao`, `financeiro`, `leitura`, `cliente_portal` (+ migration portal). |

**Não há** hierarquia numérica de papel na tabela `rbac_roles` (ex.: `level`) — requisito futuro para “não atribuir role acima do operador”.

---

## 11. Estruturas N:N

| Tabela | Relação |
|--------|---------|
| `rbac_roles_permissions` | papel ↔ permissão |
| `rbac_users_roles` | usuário ↔ papel RBAC (adicional ao legado) |

**Confirmado:** existem `user_role` (via `rbac_users_roles`) e `role_permission` (via `rbac_roles_permissions`) no sentido funcional do prompt.

---

## 12. Gaps e riscos

| Gap / risco | Detalhe |
|-------------|---------|
| RBAC `off` só por política de ambiente | Sem `RBAC_MODE`, o repo usa **`enforce`**; ambientes antigos podem definir `RBAC_MODE=off` até migração. |
| `isAuthorized` permissivo | Fora `admin`, default true — risco onde o híbrido (sem papéis RBAC) ainda se aplica ou rotas não mapeadas no catálogo. |
| Permissões “macro” | Muitos `*.manage` e `controller/*` — granularidade fina e matriz campo-a-campo ainda em evolução. |
| Grupos / políticas / campos | Schema Fase 3 presente; adoção UI e `rbac_permission_policies` em runtime — ver roadmap Fases 4–5. |
| ABAC e políticas | `AbacQuery` por dados; `conditions_json` em políticas RBAC quando `evaluate_permission_policies` está ativo (padrão no repo); sem motor genérico IP/MFA/horário. |
| Duplicação de lógica | `permissaoacesso`, `role`, `admin`, RBAC, ABAC em fluxos paralelos (tickets, portal, orçamentos). |
| Frontend vs backend | Menu e flags muitas vezes por `role`; backend deve continuar a validar sempre. |
| APIs | Whitelist explícita; qualquer mudança em `Rbac`/`Security` unlocked actions exige regressão de integrações. |
| Documentação vs schema | `docs/DOC3_RBAC_ABAC.md` descreve códigos exemplificativos nem sempre iguais ao `permissions_registry.php`. |

---

## 13. Pontos de compatibilidade obrigatória

1. Manter rotas e prefixos atuais; não remover `permissions_registry` nem sync idempotente sem plano de transição.
2. Preservar `AppController::isAuthorized` para APIs (`ordensservico`, `clientes`, `produtos`, `clicontratos`) e `pgmassets`.
3. Manter `config/rbac.php` whitelist e `bypass_legacy_super` até migração de utilizadores admin para papéis RBAC.
4. Preservar `skip_action_prefixes` para actions `api*` usadas pelo grid/React.
5. Manter campo `users.role`, `users.admin`, `permissaoacesso` e fluxo portal (`sidebarcli.ctp`).
6. `RbacComponent`: utilizadores **sem** papéis em `rbac_users_roles` não devem perder acesso abruptamente ao ativar enforce (estratégia: backfill de papéis ou modo híbrido com fallback legado — **Roadmap Fase 8** em `IMPLEMENTATION_LOG.md`).

---

## 14. Plano de migração (alinhado ao Roadmap em `IMPLEMENTATION_LOG.md`)

Estado consolidado na tabela **Roadmap** no topo de `IMPLEMENTATION_LOG.md`. Resumo:

| Fase | Foco | Estado (2026-04-07) |
|------|------|---------------------|
| 1 | Diagnóstico | Concluída (`AUTH_AUDIT`, `IMPLEMENTATION_LOG`). |
| 2 | Catálogo + aliases em BD + seeds | Concluída (2–2f). |
| 7 | Compat runtime: expandir IDs com `rbac_permission_legacy_aliases`, log opcional | Concluída (`RbacPermissionResolver`). *Fallback enforce / sem papel → Fase 8.* |
| 3 | Dados: grupos, políticas, campos, auditoria (schema) | Parcial — migration + ORM grupos + `expand_group_roles`; políticas/campos/auditoria só schema |
| 4 | Admin: grupos, matriz efetiva | Parcial — CRUD grupos em `PermissoesController`; matriz efetiva unificada pendente |
| 5 | ABAC engine / políticas JSON | Parcial — `RbacPolicyConditions` + `evaluate_permission_policies` no `RbacComponent`; UI CRUD políticas / prioridade avançada pendente |
| 6 | UI menu/campos por permissão | Pendente |
| 8 | Rollout enforce, backfill `rbac_users_roles`, modo híbrido | Parcial — flags + `RbacComponent` + `bin/cake rbac_rollout stats|unassigned_equipe`; backfill manual/UI |
| 9 | Auditoria em ações críticas | Parcial — decisões RBAC em `rbac_audit_authorizations` (`audit_decisions_db`); UI/retention/críticos alargados pendente |
| 10 | Testes | Pendente |
| 11 | Docs (`AUTH_MODEL`, matriz, legado, checklist) + alinhar DOC3 | Pendente |

---

## 15. Arquivos-chave (referência rápida)

```
config/rbac.php
config/abac.php
config/permissions_registry.php
config/Migrations/20260327140000_RbacPermissionsFoundation.php
src/Controller/AppController.php
src/Controller/PermissoesController.php
src/Controller/Component/RbacComponent.php
src/Controller/Component/AbacComponent.php
src/Utility/RbacChecker.php
src/Utility/RbacPermissionResolver.php
src/Utility/RbacUserRolesResolver.php
src/Utility/RbacPolicyConditions.php
src/Model/Table/RbacPermissionPoliciesTable.php
src/Utility/AbacQuery.php
src/Shell/RbacRolloutShell.php
src/Model/Table/RbacGroupsTable.php
src/Model/Table/RbacUserGroupsTable.php
src/Model/Table/RbacGroupRolesTable.php
src/Model/Table/RbacAuditAuthorizationsTable.php
src/Template/Element/sidebar.ctp
src/Template/Element/sidebarcli.ctp
docs/DOC3_RBAC_ABAC.md
docs/DOC2_MAPA_MENUS.md
```

---

*Fim do relatório da Fase 1.*
