# Evolução Service Desk (PASSO 3 — base estrutural)

## Migrations (PostgreSQL + Phinx genérico)

| Arquivo | Conteúdo |
|---------|----------|
| `20260321140000_QueuesEnterpriseFields.php` | `queues`: `nivel_da_fila`, `tipo_fila`, `ativo`, `fila_pai_id`, `proxima_fila_id`; backfill; filas extras (`n2_remoto`, `n2_field`, `n3_infra`, `n3_especialistas`, `mudanca`) por empresa |
| `20260321140100_SlaPolicies.php` | Tabela `sla_policies` + seed P1–P4 por empresa |
| `20260321140200_TicketHistories.php` | Tabela `ticket_histories` (auditoria tipada) |
| `20260321140300_TicketsSlaEnterpriseColumns.php` | Colunas SLA / tipo / prioridade / impacto / urgência / prazos em `tickets` |

Rodar: `bin/cake migrations migrate`

## Mapeamento legado ↔ enterprise

- **Título/descrição:** continuam `assunto` + `solicitacao` (sem renomear colunas).
- **Cliente / solicitante / atendente:** `idcliente`, `idautor`, `idtecnico_responsavel`.
- **Fila:** `queue_id` + workflow `fila_suporte` / `nivel_atendimento` inalterados.
- **Status:** `situacao` (int) permanece canônico até fase de workflow enterprise.
- **Novos:** `tipo_ticket`, `prioridade` (P1–P4), `impacto`, `urgencia`, `origem_ticket`, campos SLA e `ticket_histories`.

## Serviços (`src/Service/Ticket/`)

- `TicketClassificationService` — matriz impacto × urgência + derivação a partir de `severidade`.
- `SlaService` — aplica política ao criar ticket (prazos e flags iniciais).
- `TicketHistoryLogger` — grava em `ticket_histories` (falha silenciosa).
- `TicketWorkflowService` — esqueleto de transições enterprise (expansão futura).
- `SlaRecalculationService` — recalcula `sla_percentual_consumido` e `sla_status` (tickets com `sla_resolucao_minutos` e `data_limite_resolucao`).
- `DashboardService` — snapshot operacional (backlog, resolvidos hoje, agregados, alertas SLA violado).

## Integração atual

`TicketsController::add` chama `_applyEnterpriseTicketOnCreate()` após `_syncTicketQueueAfterCreate()` (somente se a coluna `prioridade` existir).

### CLI — recálculo de SLA

```bash
bin/cake tickets_sla recalculate
bin/cake tickets_sla recalculate -e 1
```

### API — dashboard operacional (técnico, `role` 0)

`GET /tickets/api-dashboard-operacional` — JSON `{ ok, dashboard }` (empresa da sessão). Rota explícita em `config/routes.php`; action liberada no `Security` como as demais APIs de tickets.

### UI — tela dedicada (Service Desk)

`/servicedesk/operacional` — React (`screen: tech_operacional`), consome o endpoint acima. Links **Fila** / **Painel operacional** no topbar do layout `servicedesk` (só técnico). Em desenvolvimento Vite: rota `/tecnico/operacional` (mock).

### UI — fluxo clássico (layout portal)

`/tickets/operacional` — mesmo React e API; layout `default` como `/tickets/index`. Na listagem técnica (fora do Service Desk), botão **Painel operacional** no cabeçalho (`paths.ticketsOperacional`).

## Próximos passos sugeridos

- Pausa de SLA ao mapear `situacao` → `aguardando_cliente` (além de `sla_resolucao_pausado`).
- React: widgets consumindo `api-dashboard-operacional`.
