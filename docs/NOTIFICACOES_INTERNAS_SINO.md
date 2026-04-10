# Notificações internas (sino) — Portal PGM

Documentação operacional e técnica do módulo de **notificações in-app** para utilizadores da **equipe** (`users.role = 0`): sino na sidebar, API JSON, preferências e alertas agendáveis (contratos).

---

## 1. Visão geral

- **Quem vê o sino:** apenas equipe (`role = 0`). O elemento fica em `src/Template/Element/sidebar.ctp`, condicionado ao gate `sidebar_notifications_bell` (RBAC menu lateral) e `roleNav === 0`.
- **O que mostra:** contagem de não lidas e lista recente, lidos a partir de `portal_internal_notifications`, filtrado por `user_id` da sessão.
- **URLs canónicas:** `/pgm-notifications/*` (com prefixo `App.base`, ex.: `/portal/pgm-notifications/...`). Existe alias legado `/portal-notifications/*`.
- **Stack:** CakePHP 3, PostgreSQL, jQuery no elemento do sino.

---

## 2. Base de dados (PostgreSQL)

| Tabela | Função |
|--------|--------|
| `portal_internal_notifications` | **Obrigatória** para o sino: uma linha por notificação por utilizador (`user_id`, `title`, `message`, `type`, `is_read`, `action_url`, etc.). |
| `portal_notification_preferences` | Preferências por `user_id` + `event_type` (`send_in_app`, `send_email`). Sem linha para um tipo → **in-app assume ligado** (default). |
| `client_domain_events` | Histórico de eventos de domínio cliente (auditoria). Usada pelo cron de contratos para **dedupe** (ver secção 6). |

**Migrations de referência:** `config/Migrations/20260403140000_PortalClienteDomainNotifications.php` (PG), `20260404120000_PortalClienteDomainNotificationsMysql.php` (MySQL, se aplicável).

**Prontidão da infraestrutura:** `App\Service\ClienteDomain\InfrastructureGuard::isReady()` considera o módulo ativo se existir a tabela **`portal_internal_notifications`** (não exige `client_domain_events` para o sino).

---

## 3. Rotas e middleware

- **Definição:** `config/routes.php` — rotas nomeadas `unread-count`, `list`, `mark-read/:id` (POST), `mark-all-read` (POST), `preferences`, `save-preferences` (POST).
- **`PortalNotificationsBasePathMiddleware`:** em subpasta (`APP_BASE=/portal`), redireciona pedidos GET/HEAD sem prefixo para a URL com base. A deteção usa **`REQUEST_URI`** para evitar loop de 302 quando o path interno já está correto (ver commit `fix(middleware): evitar redirect loop...`).
- **Ordem em `src/Application.php`:** `CollapseDuplicatePortalPathMiddleware` → `PortalNotificationsBasePathMiddleware` → `RoutingMiddleware`.

---

## 4. Código principal

| Peça | Caminho |
|------|---------|
| Controller API + prefs | `src/Controller/PortalNotificationsController.php` |
| Sino (HTML + JS) | `src/Template/Element/portal_notification_bell.ctp` |
| URLs com `App.base` | `src/View/Helper/PgmPortalNotifHelper.php` (carregado em `src/View/AppView.php`) |
| Emissão unificada de eventos | `src/Service/ClienteDomain/ClienteDomainBridge.php` |
| Gravação in-app + prefs | `src/Service/ClienteDomain/PortalNotificationService.php` |
| Guard de tabelas | `src/Service/ClienteDomain/InfrastructureGuard.php` |
| Cron contratos | `src/Service/ClienteDomain/ClienteDomainCronService.php` |
| Shell CLI | `src/Shell/ClienteDomainShell.php` — `bin/cake cliente_domain alertas_contratos` |
| Tickets → sino | `src/Service/Ticket/TicketInternalNotificationHelper.php` |
| Tipos de evento / labels prefs | `src/Utility/ClienteDomainEventType.php` |

**Autorização:** `PortalNotificationsController::isAuthorized` exige utilizador com `role === 0` (equipe).

---

## 5. RBAC

- As ações do `PortalNotifications` estão na **whitelist** de `config/rbac.php` (`portalnotifications#unreadcount`, `listjson`, `markread`, `markallread`, `preferences`, `savepreferences`) para que pedidos **JSON** não recebam redirect HTML em modo `enforce` (o que quebrava o `$.ajax` / `getJSON` do sino).
- O acesso efetivo continua limitado por **`isAuthorized`** (só equipe) e pelos dados **escopados por `user_id`**.

---

## 6. O que gera notificações “reais”

| Origem | Entrada |
|--------|---------|
| Clientes | `ClientesController` — criar / editar / ativar / inativar / token (`ClienteDomainBridge::emit`) |
| Contratos | `ClicontratosController` |
| Tickets | `TicketsController`, `TicketcomentariosController` → `TicketInternalNotificationHelper` |
| Utilizadores | `UsersController` — vínculo a cliente |
| ERP | `ClienteErpSyncService` — erros de integração |
| **Cron** | `bin/cake cliente_domain alertas_contratos` — contratos **vencidos** / **prestes a vencer** (`ClienteDomainCronService`) |
| Agenda | `AgendaReminderService` |
| Faturas avançadas | `InvoiceGenerationService` / `AdvancedNotificationService` |

Cada `emit` com `idempresa > 0` notifica **toda a equipe** ligada à empresa (`empresasusers` + `users.role = 0` ativos), respeitando `send_in_app` em `portal_notification_preferences`.

### Cron e dedupe

- Comando: `sudo -u www-data /var/www/portal/bin/cake cliente_domain alertas_contratos`
- Opções: `--dias=30` (janela “vencendo”), `--dedupe=7` (dias sem repetir o mesmo alerta para o mesmo contrato, via `client_domain_events` + `metadata_json`).
- Segunda execução imediata pode mostrar **tudo ignorado**: comportamento esperado (dedupe).

**Produção:** agendar cron diário (ex. utilizador `www-data`):

```cron
0 6 * * * cd /var/www/portal && bin/cake cliente_domain alertas_contratos >> /var/log/pgm-cliente-domain-cron.log 2>&1
```

---

## 7. Deploy em produção (Linux)

1. `cd /var/www/portal`
2. Atualizar código: `git pull origin main` (ou fluxo acordado).
3. `chown -R www-data:www-data /var/www/portal` se o pull foi como root.
4. `sudo -u www-data bin/cake cache clear_all`
5. Migrações, se ainda não aplicadas: `sudo -u www-data bin/cake migrations migrate`
6. Confirmar `.env`: `APP_BASE` (ex. `/portal`), `APP_FULL_BASE_URL` coerente com o URL público.

---

## 8. Diagnóstico rápido

### SQL (substituir host/user/base)

```bash
psql -h <HOST> -p 5432 -U <USER> -d pgm -c \
  "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN \
   ('portal_internal_notifications','portal_notification_preferences','client_domain_events') ORDER BY 1;"

psql ... -c "SELECT COUNT(*) FROM portal_internal_notifications;"
psql ... -c "SELECT COUNT(*) FROM portal_internal_notifications WHERE user_id = <ID>;"

psql ... -c "SELECT user_id, event_type, send_in_app FROM portal_notification_preferences WHERE send_in_app = 0 LIMIT 20;"
```

### Teste manual de UI

Inserir **um** registo para um `user_id` de equipe (número inteiro, não placeholder):

```sql
INSERT INTO portal_internal_notifications (user_id, type, title, message, is_read, created, modified)
VALUES (<ID>, 'info', 'Teste', 'Mensagem de teste.', 0, NOW(), NOW());
```

### Logs

- `logs/error.log` — erros; procurar `ClienteDomainBridge`, `PortalNotificationService`, `PortalInternalNotifications`.

### Rede (browser)

- Pedidos GET a `/portal/pgm-notifications/unread-count` e `/portal/pgm-notifications/list` devem responder **200** e corpo **JSON** (sessão equipe).

---

## 9. Problemas conhecidos e correções aplicadas

| Sintoma | Causa provável | Nota |
|---------|-----------------|------|
| Loop de redirect em `/pgm-notifications/preferences` | Middleware usava só `getUri()->getPath()` sem prefixo | Corrigido com path a partir de `REQUEST_URI` |
| Notice “String offset cast” em `Mask()` | `strpos` sem `#` na máscara | Corrigido em `Clientes/index.ctp`, `edit.ctp`, `search.ctp` |
| Sino vazio / “Indisponível” com RBAC enforce | Redirect HTML em vez de JSON | Whitelist `portalnotifications#*` + `isAuthorized` equipe |
| Tabela vazia sem eventos | Nenhum fluxo disparou `emit` / cron não agendado | Normal até haver ação ou cron |

---

## 10. Referências

- Catálogo RBAC / permissões: `config/permissions_registry.php` (`portal.notifications*`).
- Testes de matching de ações: `tests/TestCase/Utility/RbacCheckerTest.php`.
- Índice do projeto: `CLAUDE.md`, `docs/DOC3_RBAC_ABAC.md`.

---

*Última revisão do documento: alinhado ao repositório após correções de middleware, RBAC JSON, `InfrastructureGuard` e validação em produção (cron contratos + sino).*
