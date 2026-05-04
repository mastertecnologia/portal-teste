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
- Erros por ticket sao tratados de forma silenciosa para nao derrubar o processamento inteiro.
