# Service Desk - Cron de Auto-Escalonamento SLA

Comando CakePHP:

```bash
bin/cake CheckSlaEscalation
```

Execucao recomendada (a cada 2 minutos):

```bash
*/2 * * * * /usr/bin/php /var/www/app/bin/cake CheckSlaEscalation
```

Observacoes:

- O job so executa escalonamento quando as feature flags estao habilitadas:
  - `WORKFLOW_ENABLED=true`
  - `WORKFLOW_SLA_ENABLED=true`
  - `WORKFLOW_AUTO_ESCALATION_ENABLED=true`
- O comando ignora tickets fechados/resolvidos/cancelados.
- **Candidatos ao batch** (alem do filtro de situacao): `sla_escalated_at` vazio, `data_limite_resolucao` preenchido, SLA de resolucao nao pausado (`sla_resolucao_pausado` falso/nulo). Limite 1000 tickets por execucao, ordenados pelo prazo de resolucao.
- Erros por ticket sao tratados de forma silenciosa para nao derrubar o processamento inteiro.
- Os minutos configurados em `escalate_after_minutos` (workflow_sla_policies) são somados sobre `data_limite_resolucao` em **horario util** (`BusinessHoursService`), alinhado ao calculo principal de prazos de SLA — nao sao apenas minutos corrido de relogio.
- **Politica de auto-escalonamento** pode definir, alem de `escalate_to_state_id`: `escalate_to_queue_id`, `escalate_to_support_level_id`, e flags `notify_manager`, `notify_customer`, `notify_technician`. Com `auto_escalar` ativo e prazo ultrapassado (+ tolerancia), e obrigatorio haver **pelo menos** um destino (estado, fila ou nivel) ou uma notificacao. Gestor: variavel de ambiente `WORKFLOW_SLA_MANAGER_EMAIL` (lista separada por virgula); se vazia, usa `Config.emailtickets` e depois `empresas.email`.
- **Registo**: cada escalonamento aplicado grava `workflow_sla_escalation_logs` com `event_type=escalated`, `reason_code=escalated` e `payload` JSON (estado/fila/nivel, notificacoes enviadas); evento espelhado em `ticket_sla_events` / historico quando o motor de ciclos SLA esta ativo (`sla_auto_escalated`).
