# Módulo avançado (Fase 1) — migrations e models

## O que já existia no portal (não duplicado)

| Pedido original | Situação no projeto |
|-----------------|---------------------|
| `ticket_logs` / histórico simples | `ticket_histories` + `ticketsmovs` |
| SLA em tickets | Colunas em `tickets` (migration `TicketsSlaEnterpriseColumns`) + `sla_policies` |
| `ticket_ratings` | Campo `customer_rating` em `attendance_histories` (Fase 1) |
| Anexos | `ticketsanexos` + nova `attendance_attachments` (público/privado, mime) |
| Faturamento / NF | `faturamento`, `faturamento_itens`, `faturas`, `financeiro_lancamentos` |
| Contratos de cliente (itens) | `clicontratos`, `contratos_horas` |
| Notificações | `portal_internal_notifications` |
| Permissões | RBAC (`rbac_*`) |

## O que foi criado (PostgreSQL)

**Migration:** `config/Migrations/20260405130000_PortalAdvancedAttendanceContractsInvoicesAudit.php`

| Tabela | Função |
|--------|--------|
| `contracts` | Contrato comercial avançado (mensalidade, SLA, franquia horas, renovação) |
| `contract_services` | Serviços/itens do contrato |
| `contract_documents` | Documentos anexos |
| `contract_consumptions` | Consumo por mês / ticket / OS |
| `attendance_histories` | Snapshot por ticket (cliente, SLA resumido, rating) |
| `attendance_timeline` | Linha do tempo (nota pública / interna, `metadata` JSONB) |
| `attendance_attachments` | Anexos do módulo avançado |
| `invoices` | Fatura vinculada a `contracts` (código único, mês ref.) |
| `invoice_items` | Itens da fatura |
| `invoice_payments` | Pagamentos |
| `audit_logs` | Auditoria genérica (`old_data` / `new_data` JSONB) |

Convenções de FK alinhadas ao ERP: **`idcliente`**, **`idempresa`** (não `client_id` / `company_id` nas colunas físicas).

## Models (CakePHP Table)

Em `src/Model/Table/`: `Contracts`, `ContractServices`, `ContractDocuments`, `ContractConsumptions`, `Invoices`, `InvoiceItems`, `InvoicePayments`, `AttendanceHistories`, `AttendanceTimeline`, `AttendanceAttachments`, `AuditLogs`.

## Como validar

1. **Migrar (PostgreSQL):**  
   `bin/cake migrations migrate`  
   Confirmar tabelas com `\dt` no `psql` ou cliente SQL.

2. **ORM:** no `bin/cake console`:  
   `TableRegistry::get('Contracts')->find()->count();`  
   (esperado `0` sem dados.)

3. **Próximas fases:** controllers, portal ABAC, integração pontual em `TicketsController` (chamadas aos services), testes automatizados.

## Fase 2 — Services (`App\Service\PortalAdvanced`)

| Classe | Função |
|--------|--------|
| `AuditLogService` | `log()` / `isAvailable()` — grava `audit_logs` (JSON). |
| `ReportExportService` | `writeCsv()`, `writeCsvToTmp()` — exportação CSV sem depender de controller. |
| `InvoiceGenerationService` | `generateMonthly(YYYY-MM, ?idempresa, $notify)` — fatura por contrato ativo (`active`/`ativo`), consumo do mês, itens mensalidade + excedente; idempotente; auditoria + notificação staff opcional. |
| `ContractSlaIntegrationService` | Prazos `data_limite_resposta` / `data_limite_resolucao` a partir de `contracts.sla_hours` (complementa `App\Service\Ticket\SlaService` por política). `recordFirstResponseIfEmpty()`, `refreshSlaAfterStatusChange()` delega a `SlaRecalculationService`. |
| `AdvancedNotificationService` | `notifyEmpresaStaff()` — wrapper para `PortalNotificationService`. |

**Shell (cron):** `bin/cake portal_advanced gerar_faturas_mes --mes=YYYY-MM [--empresa=ID] [--no-notify]`

**Integração recomendada (ainda manual):** após criar ticket com contrato avançado resolvido, chamar `ContractSlaIntegrationService::applyContractHoursToTicket($ticket, $contract)` antes do save; ao mudar situação para fechado, `refreshSlaAfterStatusChange($ticket)`.

## Fase 3 — telas, rotas e RBAC

- **ERP (role 0):** `AdvancedContracts`, `AdvancedInvoices`, `AdvancedReports`. Rotas explícitas em `config/routes.php` sob `/modulo-avancado/…` (listagem, detalhe, export CSV de faturas/indicadores, POST `marcar-paga` para fatura). Histórico de atendimentos no ERP permanece em `Tickets/historico` (sem duplicata PG no menu interno).
- **Portal cliente (`C_RoleCliente`):** `PortalAdvancedContracts`, `PortalAdvancedInvoices`, `PortalAdvancedAttendance` — URLs `/cliente/contratos-avancados`, `/cliente/faturas-avancadas`, `/cliente/historico-atendimento-avancado` (+ `view/*`, export de faturas).
- **Catálogo:** `config/permissions_registry.php` — códigos `erp.advanced.*` (equipe) e `portal.advanced.*` (portal).
- **PostgreSQL:** migration `20260406140000_PortalAdvancedRbacPermissions.php` grava as permissões em `rbac_permissions` e associa `portal.advanced.*` ao papel `cliente_portal`. As permissões `erp.advanced.*` não são atribuídas automaticamente; vincule-as aos papéis internos desejados na matriz RBAC.
- **Menu lateral:** ERP — submenu «Módulo avançado» em `src/Template/Element/sidebar.ctp` (só `role === 0`). Portal — submenu «Contratos & faturas» em `src/Template/Element/sidebarcli.ctp`.

## Ambiente não-PostgreSQL

A migration retorna sem executar DDL (igual a outras migrations só-PG do repositório). Para MySQL seria necessária migration espelho, se houver requisito.
