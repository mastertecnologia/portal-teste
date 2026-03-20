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

## Patch PostgreSQL: `queues` incompleta (`20250320120000_PostgreSQLQueuesSchemaPatch.php`)

Se as tabelas `queues` / `queues_users` já existiam **sem** `name`, `idempresa`, `codigo`, `sort_order`, etc., rode as migrations de novo **ou** execute o SQL manual:

```bash
psql -U USUARIO -d pgm -f config/schema/postgres_queues_patch_idempotent.sql
```

**Antes:** garanta que exista pelo menos um registro em **`empresas`** (a migration/SQL usa `MIN(id)` e, em último caso, `idempresa = 1`). Se não existir `empresas.id = 1`, ajuste os dados em `queues` antes de criar a FK.

## Responsável no ticket: `idtecnico_responsavel` vs `owner_id`

- **Referência canônica:** `idtecnico_responsavel` (filtros, regras, legado).
- **`owner_id`:** espelho persistido, preenchido em `TicketsTable::beforeSave` a partir do canônico (API JSON / FK). Evite gravar só `owner_id` na aplicação.

### Teste sugerido (sem 503)

1. Abrir **GET** `queues/api-index` (admin técnico) ou criar fila via fluxo existente.
2. **POST** `queues/api-ensure-defaults` se a empresa ainda não tiver filas.
3. No cadastro de usuário, vincular **Filas de Atendimento** (`queues_users`).
4. Criar/editar ticket com `queue_id` e responsável via **`idtecnico_responsavel`** (ou ações “Iniciar atendimento” / assumir) — conferir que `owner_id` no banco acompanha após o save.
5. **GET** `tickets/api-index` e **GET** `queues/api-for-ticket/{id}` / `queues/get-available-queues/{id}`.

## Níveis de suporte (`20250321140000_SupportLevelsQueuesTickets.php`)

- Cria **`support_levels`** (N1, N2, N3, NOC, Serviço) e colunas **`support_level_id`** em `queues`, `queues_users`, `users`, `tickets` (+ `description` em `queues` no PostgreSQL).
- Depois de migrar, rode **`bin/cake migrations migrate`** também no servidor.

### Se “as alterações não aparecem” no servidor

1. Na **máquina de desenvolvimento**: `git status` deve estar limpo; `git log -1` deve mostrar commits recentes (`feat(admin): painel Filas/técnicos`, `feat(tickets): filas…`, etc.).
2. No **servidor** (`/var/www/portal` ou equivalente): `git fetch origin && git pull origin main` (ou a branch usada em produção).
3. Confirme que estes caminhos **existem** no disco do servidor:
   - `config/Migrations/20250321140000_SupportLevelsQueuesTickets.php`
   - `src/Controller/QueuesController.php` (ações `adminIndex`, `adminEdit`, …)
   - `src/Template/Queues/admin_index.ctp` (e `admin_edit.ctp`, `admin_technicians.ctp`)
   - `src/Model/Table/SupportLevelsTable.php` (deve conter `parent::initialize($config);`)
4. Se o deploy **não** copia `config/` ou `src/Template/`, ajuste o pipeline ou faça deploy pelo **mesmo repositório Git** completo.
5. Reinicie **PHP-FPM** / **Apache** após o pull se usar OPcache agressivo.

### Painel web (admin)

- Menu lateral (**admin**): **Filas / técnicos** → `queues/admin-index`.
- Ou **Configurações** → cartão **Filas / técnicos**.
