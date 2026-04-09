# Modelo de autorização (stack)

Resumo operacional do portal **CakePHP 3**: como convivem autenticação, autorização legada, RBAC (por defeito **enforce** neste repositório, salvo `RBAC_MODE`) e ABAC por dados. Detalhe de fases e roadmap: [`IMPLEMENTATION_LOG.md`](../IMPLEMENTATION_LOG.md). Visão mais ampla (incl. exemplos de códigos): [`DOC3_RBAC_ABAC.md`](DOC3_RBAC_ABAC.md).

## Autenticação

- **`AuthComponent`** (config em `AppController`): login por formulário (`Users`, campo `email`), sessão, `loginRedirect` para dashboard.
- Multi-empresa: `idempresa` e vínculos em `empresasusers` após login.

## Autorização em camadas

1. **`Controller::isAuthorized($user)`** — por controller; muitos módulos permitem equipe (`role === 0`) ou regras específicas.
2. **Legado global** — `users.admin` (booleano), `users.role` (`0` equipe PGM, `1` portal cliente), `permissaoacesso` onde ainda aplicável (ex.: cliente e orçamentos).
3. **`RbacComponent`** (`AppController::beforeFilter`) — controlado por `config/rbac.php` (`mode`: `off` | `warn` | `enforce`; neste repo o ficheiro define **`enforce`** se `RBAC_MODE` não estiver definida). Compara **controller** e **action** do pedido às linhas em `rbac_permissions` associadas aos papéis do utilizador (`rbac_users_roles` + opcionalmente grupos `rbac_user_groups` / `rbac_group_roles`). Modo `off` desliga a negação RBAC de rota.
4. **`AbacComponent` + `AbacQuery`** — restringe **quais linhas** de dados entram em queries (escopo `empresa`, `cliente`, `own`, etc.) conforme `config/abac.php` e uso nos controllers.

## Catálogo RBAC

- **Fonte declarativa:** `config/permissions_registry.php` (códigos `modulo.recurso.acao`, `controller`, `action`, `abac_scope`).
- **Base:** `rbac_permissions`, `rbac_roles`, `rbac_roles_permissions`, `rbac_users_roles`; Fase 3+: grupos, `rbac_permission_policies`, `rbac_audit_authorizations`.
- **Sincronização:** painel **Permissões → Sincronizar catálogo** insere códigos novos; não altera linhas já existentes.

## Utilitários e estado no pedido

- **`RbacChecker`** — `matchAction`, `userHasPermissionCode`, `shouldShowConfigAdminHub`, regras pontuais (ex.: cliente e orçamento).
- **`RbacPermissionResolver`** — expande IDs de permissão via `rbac_permission_legacy_aliases` quando `expand_legacy_aliases` está ativo.
- **`RbacUserRolesResolver`** — papéis efetivos (diretos + herdados de grupos).
- **`RbacPolicyConditions`** — avalia `conditions_json` das políticas quando `evaluate_permission_policies` está ativo.
- No controller: `$this->rbacAbacScope`, `$this->rbacAbacPermissionCode`, `$this->rbacDenyReason` (quando aplicável).

## Onde configurar

| Ficheiro | Conteúdo |
|----------|----------|
| `config/rbac.php` | Modo RBAC, whitelist, bypass legado, políticas, menu Config, auditoria |
| `config/permissions_registry.php` | Catálogo de permissões |
| `config/abac.php` | Regras ABAC por recurso |

## Testes e checklist

- PHPUnit: `composer test-rbac` (suite **`rbac`** em `phpunit.xml.dist`)
- Manual: [`TEST_CHECKLIST_RBAC.md`](TEST_CHECKLIST_RBAC.md)
- Matriz papel × permissão (UI + SQL): [`RBAC_ABAC_MATRIX.md`](RBAC_ABAC_MATRIX.md)
