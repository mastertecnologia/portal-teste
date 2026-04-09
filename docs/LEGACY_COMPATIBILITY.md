# Compatibilidade com autorização legada

Este documento descreve como o **RBAC novo** convive com **`users.admin` / `users.role`** e macros antigas, sem quebrar ambientes em migração. Roadmap: [`IMPLEMENTATION_LOG.md`](../IMPLEMENTATION_LOG.md). Modelo geral: [`AUTH_MODEL.md`](AUTH_MODEL.md).

## Flags e comportamento híbrido (`config/rbac.php`)

- **`mode === off`** — `RbacComponent` não nega; decisões ficam em `isAuthorized`, checks manuais e legado.
- **`bypass_legacy_super`** — Se `true` (padrão), utilizador com `admin` e `role === 0` **não** é avaliado pelo RBAC de rota (atalho para administradores PGM durante o rollout).
- **Sem papéis em `rbac_users_roles`** — Por omissão o pedido **não** é negado pelo RBAC (híbrido): continua a valer legado. Exceção: `enforce_block_without_roles` em modo `enforce` (e `enforce_block_without_roles_equipe_only` para limitar a equipe).
- **`expand_legacy_aliases`** — Papéis que só têm permissões **macro** (ex.: `clientes.manage`) passam a considerar também os **códigos canónicos** ligados em `rbac_permission_legacy_aliases` na mesma verificação de rota.
- **`menu_filter_config`** — Se `true`, o atalho ao hub **Config** e o acesso ao `ConfigController` para equipe seguem `RbacChecker::shouldShowConfigAdminHub` (exige `config.manage` quando já existem papéis RBAC; quem ainda não tem papéis mantém o atalho).
- **`skip_action_prefixes`** — Ex.: `api` — actions cujo nome começa por esse prefixo **não** passam pelo `RbacComponent` (APIs JSON / React); continuam sujeitas a `isAuthorized` e a outros gates.

## Aliases de rota e controller no catálogo

- **`PortalContratos` ↔ `PortalAdvancedContracts`** — `RbacChecker::matchAction` aceita o par para URLs canónicas vs. nome legado no catálogo.
- **`Visitas` ↔ `Agenda`** — Rotas `/agenda/*` disparam o controller **Visitas**; linhas antigas do catálogo com `controller = Agenda` e `action = *` ainda casam. Novas instalações e migration `20260418120000` alinham `agenda.alias` a **Visitas**.

## Painel Permissões

- **`PermissoesController`** — Continua a exigir `isAuthorized`: admin equipe (`admin` e `role === 0`), independentemente do modo RBAC. Atribuição de papéis RBAC é feita aqui (e telas relacionadas).

## Sincronização do catálogo

- Entradas em `permissions_registry.php` só entram na tabela `rbac_permissions` após **Sincronizar catálogo**; correções a linhas já existentes podem exigir **migration** ou SQL pontual (documentado por alteração).

## Variáveis de ambiente (sobrepõem literais em `config/rbac.php`)

- **`RBAC_MODE`** — `off` | `warn` | `enforce` (ex.: `warn` ou `off` em desenvolvimento quando o ficheiro está em `enforce`).
- **`RBAC_MENU_FILTER_CONFIG`**, **`RBAC_MENU_FILTER_SIDEBAR`** — `0`/`1` ou `true`/`false` / `on`/`off`.
- **`RBAC_LOG_UNASSIGNED_USERS`** — log de pedidos de utilizadores autenticados sem `rbac_users_roles` (útil em piloto).
- **`RBAC_AUDIT_DECISIONS_DB`** — `0` | `1` | `all` (gravar decisões em `rbac_audit_authorizations`).
- **`RBAC_ENFORCE_BLOCK_WITHOUT_ROLES`** — `1` só **após** backfill completo da equipa em `rbac_users_roles`.
- **`RBAC_EVALUATE_POLICIES`** — políticas `rbac_permission_policies` em runtime.
- **`RBAC_WARN_FLASH`** — flash em modo `warn`.

Lista completa e semântica: docblock de `config/rbac.php`. Modelo geral de `.env`: **`.env.example`** na raiz (versionado; `.gitignore` tem excepção `!.env.example`). Variáveis só RBAC: `config/rbac.env.example`.

## Checklist

- [`TEST_CHECKLIST_RBAC.md`](TEST_CHECKLIST_RBAC.md)
