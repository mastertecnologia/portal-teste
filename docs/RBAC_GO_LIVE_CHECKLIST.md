# RBAC / IAM — GO LIVE checklist (atualizado)

## Pré-requisitos

1. `bin/cake migrations migrate` — tabelas `rbac_access_requests`, `rbac_access_grants`, `rbac_change_audit_logs`, índice único `rbac_users_roles (user_id, role_id)`.
2. `bin/cake rbac_rollout sync_registry` (ou fluxo oficial) alinhado a `config/permissions_registry.php`.
3. `config/rbac.php`: blocos `diagnostics`, `notifications` (incl. `max_retries`, `max_notifications_per_minute`), `access_expiration`; variáveis `.env` opcionais (`RBAC_SLACK_WEBHOOK_URL`, `RBAC_FROM_EMAIL`).

## Cron / rotinas

4. Agendar:
   - `bin/cake rbac_access_expiry_notify` (lembretes `notify_before_days`, dedupe em `expiry_notifications_sent_json`)
   - `bin/cake rbac_access_expire` (expira + `auto_revoke_enabled` apenas com `applied_role_assignment` e sem outro grant ativo user+role)

## Validação automatizada

5. `scripts/validate-iam-go-live.ps1` (Windows) ou `scripts/validate-iam-go-live.sh` (Linux) — invoca `rbac_go_live_check` e dry-runs de expiração.
6. `bin/cake rbac_go_live_check` — relatório **OK** (0), **WARNING** (2), **ERROR** (3).

## Funcional (homologação manual)

7. Fluxo: access denied → solicitar acesso → manager → admin → preview → grant (incl. permissão crítica com política esperada).
8. Concorrência: dois POST de grant no mesmo pedido / lock transacional; dois grants ativos user+role impedem auto-revoke equivocado.
9. Expiração: grant vencido marca `expired`, audita `access_expired` / `access_revoked` quando aplicável.
10. Rate limit de pedidos (`access_request_rate_limit_per_hour`) + notificações (`max_notifications_per_minute`).

## UI

11. Dashboard `/permissoes/dashboard-acessos` (CSV resumo, pendentes `?tipo=pendentes`, grants `?tipo=grants`).
12. Rotas esperadas: `/users/access-denied`, `/permissoes/meus-pedidos-acesso`, `/permissoes/dashboard-acessos`, `/permissoes/matriz-visual`.

## Status final (orientação)

| Condição | Decisão |
|----------|---------|
| `rbac_go_live_check` = ERROR ou migrações pendentes críticas | **NO-GO** |
| WARNINGS de índice / rotas / ambiente corrigíveis em janela curta | **GO condicionado** |
| Check OK + fluxo manual validado + SMTP/Slack definidos | **GO** |
