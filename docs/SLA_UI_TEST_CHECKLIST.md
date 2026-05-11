# Checklist de testes SLA via UI (homologação / pós-deploy)

Marcar **OK** / **FALHA** e anotar data, ambiente e utilizador. Ajustar o prefixo da app (`APP_BASE`, ex.: `/portal`) nas URLs.

## Pré-requisitos

- [ ] Migrações aplicadas até `20260507190000` (ciclos, eventos, colunas opcionais).
- [ ] Utilizador **equipe** (`role = 0`) com permissão que inclui `Servicedesk::slaRelatorio` (ex.: `servicedesk.view` conforme RBAC).
- [ ] `Workflow.workflowEnabled`, `Workflow.workflowSlaEnabled` ativos para a empresa em teste (e escalonamento, se testar escalação).
- [ ] Pelo menos um **estado de workflow** e políticas em **Servicedesk → Workflow SLA** (global, empresa, cliente e/ou contrato, conforme cenários).

---

## A. Service Desk — edição de ticket (React)

Abrir um ticket real: **Service Desk** → detalhe / edição técnica onde o `TicketSlaPanel` aparece.

- [ ] **Painel SLA** visível quando há feature SLA / ciclos / eventos (ou mensagem coerente quando não há schema).
- [ ] Mostra **política aplicada** (ou identificador útil) alinhada ao contexto do ticket (cliente/contrato quando existir).
- [ ] Prazos **resposta** / **resolução** (minutos ou datas) coerentes com a política esperada para esse ticket.
- [ ] **Pausar SLA**: estado passa a pausado, sem erro; após refresh, estado mantém-se.
- [ ] **Retomar SLA**: retoma sem erro; **deadline** de resolução (e resposta, se houver) **aumenta** em relação ao valor antes da pausa (extensão pelo tempo pausado).
- [ ] Links **logs** / **admin SLA** (se mostrados) abrem sem 403 para quem tem permissão.

**Regressão**

- [ ] Guardar alterações normais ao ticket (assunto, fila, técnico) não bloqueia por erro do painel SLA.

---

## B. Ciclo e fecho (UI + verificação indireta)

- [ ] Alterar **fila** ou **técnico responsável**: painel / dados SLA recarregam com ciclo coerente (novo ciclo no backend — pode constatar por logs ou relatório).
- [ ] **Fechar / resolver** o ticket: não deve ficar erro no ecrã; em suporte, confirmar que o ciclo aberto foi encerrado (BD ou relatório).

---

## C. Políticas por escopo (validação funcional via ticket)

Preparar tickets ou políticas de teste e confirmar na UI **um screenshot mental** de prazos/minutos corretos:

- [ ] Ticket **sem contrato**, só empresa/global → prazos iguais à política **legada/global** esperada para o estado.
- [ ] Ticket com **cliente** e política “só cliente” → prazos da política de cliente.
- [ ] Ticket com **contrato** → prazos da política de contrato (tier mais largo).
- [ ] Ticket com **contrato + serviço + problema** (+ fila/nível se política tier 1) → prazos da **política mais específica** (comparar com linha no admin Workflow SLA).

---

## D. Relatório SLA (CakePHP, não React)

Menu equipe: **Relatório SLA** (sidebar; `ServicedeskController::slaRelatorio`).

- [ ] Página abre sem 403/500.
- [ ] Filtro **mês** (`YYYY-MM`) e **cliente** alteram o resultado.
- [ ] KPIs e listagens carregam sem erro de SQL visível.
- [ ] (Opcional) Filtro “somente SLA estourado” reduz linhas de forma credível.

---

## E. Admin Workflow SLA (React / servicedesk)

- [ ] Criar ou editar política com **escopo** (cliente, contrato, serviço, problema, fila, nível).
- [ ] Guardar e reabrir: campos persistem.
- [ ] Política **inativa** (`ativo = false`), se existir coluna: não deve ser aplicada ao ticket de teste.

---

## F. Contratos — separador SLA (se usado)

**Módulo contratos** → vista de contrato → separador **SLA / service desk** (quando existir).

- [ ] Lista ou resumo carrega sem erro.
- [ ] Alterações administrativas (se a UI permitir) respeitam permissões.

---

## G. Dashboard operacional (opcional)

**Service Desk operacional** / **Tech Dashboard**: blocos **SLA** (violados, perto do prazo, pausados, KPIs).

- [ ] Dados aparecem ou mensagem clara se colunas SLA não existirem.
- [ ] Contadores mudam após pausar/retomar ou fechar ticket de teste (pode exigir refresh).

---

## H. Autoescalonamento (UI limitada)

A escalação é orientada por **cron** / comando; na UI validar só o **efeito** depois do job:

- [ ] Com política de teste `auto_escalar` + prazo estourado (ambiente de teste): após correr o job, ticket mostra **fila/estado/nível** atualizados conforme política **mesma** usada nos prazos (comparar política no admin).

---

## I. Permissões e portal cliente

- [ ] Utilizador **portal** (`role = 1`) **não** acede a Relatório SLA nem a pausa/retoma se a API devolver 403 (comportamento esperado).
- [ ] Sem regressão na listagem/detalhe de tickets do cliente.

---

## Registo de execução

| Data | Ambiente | Executor | Notas |
|------|----------|----------|-------|
|      |          |          |       |
