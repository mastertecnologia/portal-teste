# Service Desk v1 (mockup) — escopo e backlog

Documento de fronteira para a migração alinhada a `webroot/pgm-servicedesk-v2-navegacao.html`.

## Incluído na v1 (implementado nesta onda)

- Shell com topbar (marca + links) no layout Service Desk
- `paths.ticketsOperacional` apontando para `/servicedesk/operacional`
- Atalho "Painel operacional" visível no contexto Service Desk (fila técnica), com `role` técnico
- Paridade de classes de fila: `pgm-card`, `filter-toolbar`, `pgm-table` + regras CSS no tema claro
- Rebuild de `webroot/tickets-app` a partir de `dashboard-react`

## Fora de escopo (backlog / decisão de produto)

- Página única com badges demo, grelha de estados, nav flutuante estilo mockup
- Unificar KPIs e fila num único scroll (KPIs permanecem em `/servicedesk/operacional`)
- Replicar blocos estáticos: chat, modal demo, form demo, login card na mesma vista da fila
- Tema escuro (Service Desk em produção permanece claro, como hoje)

## Atualização (fase 4)

KPIs: **rota separada** mantida; nenhum embed de dashboard operacional na fila (reduz carga e escopo). Melhorias futuras: lazy embed opcional e mesma API `api-dashboard-operacional`.

## Atualização (fase 5)

Removido o widget flutuante de timer (`TICKET #...`) da fila técnica (`TechDashboard`) para simplificar a interface e manter foco na grade principal.
