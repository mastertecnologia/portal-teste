# Migrations (CakePHP / Phinx)

## Filas e `owner_id` (`20250319120000_QueuesQueuesUsersTicketsQueueOwner.php`)

- **`queues`**: `name`, **`idempresa`** (equivale a *company_id* do domínio; FK para `empresas.id`), `codigo`, `sort_order`, `created`, `modified`.
- **`queues_users`**: N:N entre `users` e `queues`, com `created` / `modified`.
- **`tickets`**: `queue_id` (FK para `queues`), **`owner_id`** (responsável; espelha `idtecnico_responsavel` no aplicativo).

Rodar na raiz do projeto (com PHP no PATH):

```bash
bin/cake migrations migrate
```

Bases que já receberam o SQL em `config/schema/queues_queues_users_tickets_queue_id.sql` continuarão ok: a migration só acrescenta o que faltar (ex.: `owner_id`, timestamps).
