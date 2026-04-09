# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Identity

PGM Portal — CakePHP 3.10 ERP for managing clients, contracts, tickets, orders, invoicing, and a client portal. Database: **PostgreSQL** (not MySQL, despite the cursorrules saying MySQL). PHP ≥ 7.4. PDF via **mPDF**, Excel via **PhpSpreadsheet**.

## Infrastructure (3 servers)

| Role | IP |
|------|----|
| Portal (Linux, CakePHP) | 10.0.2.25 |
| PostgreSQL | 10.0.2.23 |
| ERP/Grid (Windows IIS, WebGridPGM) | 10.0.2.7:85 |

The ERP URL is stored in `empresas.urlerp` in the database, not in config files. Integration with the ERP uses SOAP calls to `.wso` services.

## Commands

```bash
# CakePHP shell / Cake CLI
bin/cake server -p 8765                   # built-in dev server
bin/cake routes                           # list all registered routes
bin/cake cache clear_all                  # clear all cache
bin/cake migrations migrate               # apply pending migrations
bin/cake bake migration NomeDaMigration   # scaffold a new migration
bin/cake bake model NomeDoModel          # scaffold model + entity + table
bin/cake bake controller NomeDoController
bin/cake bake template NomeDoController

# RBAC diagnostic shell (src/Shell/RbacRolloutShell.php)
bin/cake rbac_rollout stats
bin/cake rbac_rollout unassigned_equipe
bin/cake rbac_rollout report
bin/cake rbac_rollout audit_purge --days 30

# Tests
./vendor/bin/phpunit                          # all tests
composer test-rbac                            # rbac + rbac-integration + rbac-http (bootstrap tests/bootstrap_http.php; URLs alinham a App.base / APP_BASE)
composer rbac-verify-noninteractive           # alias do anterior (sem prompts)
composer rbac-verify-with-pre-deploy          # bin/cake rbac_rollout pre_deploy (requer PostgreSQL)
# Scripts: bin/rbac_verify_noninteractive.ps1 | .sh — idem test-rbac; RBAC_RUN_PRE_DEPLOY=1 acrescenta pre_deploy
# GitHub Actions: .github/workflows/rbac-phpunit.yml (push/PR nos paths do YAML ou workflow_dispatch)

./vendor/bin/phpunit tests/TestCase/Utility/  # specific directory
./vendor/bin/phpunit --filter RbacCheckerTest # specific test class

# Dependencies
composer install
```

## Architecture

### Request flow
`AppController::beforeFilter` runs on every request: loads RBAC/ABAC components, sets menu state for the sidebar, sets global template vars (user info, skin, empresa name), then delegates to `RbacComponent::checkRequest()` which may short-circuit with a redirect/403.

### Auth system
- **Legacy**: `users.admin` (boolean) + `users.role` (0 = equipe/staff, 1 = portal/client)
- **RBAC** (layered on top): `rbac_users_roles` → `rbac_roles_permissions` → `rbac_permissions`. Controlled by `config/rbac.php` with `mode: off|warn|enforce` (repo default `enforce` when `RBAC_MODE` unset; override per env). Optional `RBAC_*` for `.env`: `config/rbac.env.example`. Root `.env.example` is tracked (`.gitignore` excepts `!.env.example`). Rollout priorities P0–P3: `IMPLEMENTATION_LOG.md` (index at top + anchors `rbac-priority-p0` … `rbac-priority-p3`).
- **ABAC**: `config/abac.php` + `src/Controller/Component/AbacComponent.php` — attribute-based scope narrowing (empresa|cliente|own) evaluated after RBAC allows.
- `AppController::isAuthorized()` is the last fallback; prefix `admin` requires `users.admin = true`.
- ERP API endpoints (`listAPI`, `refreshAPI`, `addAPI`) bypass session auth; they use a token in the request header.

### User roles
- `role = 0` — equipe (internal staff); can access ERP modules
- `role = 1` — portal (client); limited to `/cliente/*` routes and client-facing views

### Templates
- `.ctp` files in `src/Template/` (CakePHP 3 convention; NOT `.php` in `templates/`)
- Layout: `src/Template/Layout/`; shared partials in `src/Template/Element/`
- For "Advanced" module controllers (`Advanced*`, `PortalAdvanced*`, `ContractManagement`, `PortalContratos`), the variable `pgmAdvancedModuleStylesheet = true` is set so views load premium CSS.

### Visual identity
- Primary color: `#1D9E75` (teal)
- CSS premium files served via `PgmAssetsController` at `/pgm-assets/css/:name` (avoids static 404 with APP_BASE=/portal)

### Key modules
- **Tickets**: hybrid — classic CakePHP views + React UI (`src/Template/Tickets/operacional.ctp`); JSON APIs at `/tickets/api-*` use session auth but are unlocked from CSRF (`Security` component `unlockedActions`).
- **Contratos/Contracts**: `ContractManagement` (ERP staff) + `PortalContratos` (client). Autentique webhook at `/modulo-contratos/webhook/autentique`.
- **Orcamentos**: `OrcamentosController`; client can request via `solicitar` action; PDF via mPDF; catalog suggestions via `catalogoSugestoes` (GET).
- **Notifications**: `PortalNotificationsController` — JSON endpoints for the bell icon in the sidebar.

### Config files
- `config/rbac.php` — RBAC runtime config (mode, phases, feature flags)
- `config/abac.php` — ABAC rules
- `config/permissions_registry.php` — canonical permission codes registry
- `config/routes.php` — all routes explicitly defined (do not rely solely on DashedRoute fallback for new endpoints)

### Database migrations
`config/Migrations/` — timestamped migration files. Run `bin/cake migrations migrate` after pulling. New migrations must follow the `YYYYMMDDHHMMSS_PascalCaseName.php` naming convention.

## Absolute rules (from cursorrules)

1. Always use CakePHP ORM (`$this->Table->find()`); never write raw SQL.
2. Validation belongs in Table classes, not controllers.
3. Use `$this->request->getData()` / `getQuery()` — never `$_POST` / `$_GET`.
4. Escape output with `h()` in templates.
5. Tokens for security must use `random_bytes()` or `random_int()` — never `rand()` or `uniqid()`.
6. New public portal routes (no auth) must disable `Auth` middleware for those actions.
7. CSRF protection is active — always use `FormHelper` or add the action to `unlockedActions`.
8. Monetary values: `DECIMAL(15,2)`; format as BRL in views.
