# Documento 4 — Especificação do Módulo: Histórico de Atendimentos

## 1. Objetivo

Oferecer uma visão completa e cronológica de todos os atendimentos (tickets) de um cliente ou da empresa, com suporte a filtros avançados, timeline de eventos, indicadores de SLA, anexos e trilha de auditoria.

Complementa o módulo de Tickets existente: onde `/tickets/index` é uma lista operacional de trabalho ativo, o **Histórico de Atendimentos** é o registro histórico permanente e consultável.

---

## 2. Personas e Acessos

| Persona | Acesso | Filtro padrão |
|---------|--------|--------------|
| Técnico PGM | Histórico de todos os tickets da empresa ativa | Todos os status |
| Gestor PGM | Idem + exportações e relatórios de SLA | Todos os status |
| Cliente | Somente tickets da sua empresa (`idcliente`) | Encerrados/Respondidos |

---

## 3. Listagem Principal

### 3.1 URL

| Contexto | URL |
|---------|-----|
| ERP (técnico/gestor) | `/tickets/historico` |
| Portal cliente | `/tickets/historico-cliente` |

### 3.2 Colunas da listagem

| Coluna | Campo BD | Descrição |
|--------|---------|-----------|
| Nº Ticket | `tickets.id` | Identificador único |
| Data Abertura | `tickets.created` | Data/hora de criação |
| Data Encerramento | `tickets.datafinalizado` | Data/hora de fechamento |
| Assunto | `tickets.assunto` | Resumo do chamado |
| Cliente | `clientes.razaosocial` | Empresa do solicitante |
| Solicitante | `users.name` (idautor) | Quem abriu o ticket |
| Técnico | `users.name` (idtecnico_responsavel) | Técnico responsável |
| Fila | `queues.nome` | Fila de atendimento |
| Prioridade | `tickets.prioridade` | P1 / P2 / P3 / P4 |
| Tipo | `tickets.tipo_ticket` | Incidente / Requisição / Problema / Mudança |
| Status | `tickets.situacao` | Encerrado / Respondido / Cancelado |
| SLA | `tickets.sla_status` | OK / Atenção / Violado |
| Horas | soma `ticketshoras.minutos` | Total em horas:min |

### 3.3 Filtros disponíveis

| Filtro | Tipo | Campo |
|--------|------|-------|
| Período (abertura) | Date range | `tickets.created` |
| Período (encerramento) | Date range | `tickets.datafinalizado` |
| Cliente | Select (busca) | `tickets.idcliente` |
| Técnico | Select | `tickets.idtecnico_responsavel` |
| Fila | Select múltiplo | `tickets.queue_id` |
| Prioridade | Checkbox | `tickets.prioridade` |
| Tipo de ticket | Select | `tickets.tipo_ticket` |
| Status | Checkbox | `tickets.situacao` |
| SLA | Select | `tickets.sla_status` |
| Texto livre | Search | `tickets.assunto` + `tickets.solicitacao` |

### 3.4 Ordenação padrão

`tickets.datafinalizado DESC` → tickets mais recentemente encerrados primeiro.

### 3.5 Paginação

DataTables server-side. Tamanho de página configurável por usuário (`users.pagelength`).

---

## 4. Tela de Detalhe

### URL

```
/tickets/view/:id   (existente — estendida com as seções abaixo)
```

### 4.1 Cabeçalho do Ticket

| Campo | Fonte |
|-------|-------|
| Número e assunto | `tickets.id` + `tickets.assunto` |
| Status atual | `tickets.situacao` (badge colorido) |
| Prioridade | `tickets.prioridade` (badge P1–P4) |
| SLA | `tickets.sla_status` + percentual consumido |
| Data abertura / encerramento | `tickets.created` / `tickets.datafinalizado` |
| Tempo total | soma de `ticketshoras` |

### 4.2 Seções do Detalhe

```
┌─────────────────────────────────────────────┐
│  Cabeçalho (status, prioridade, SLA, datas) │
├─────────────────────────────────────────────┤
│  Solicitação original (tickets.solicitacao) │
├─────────────────────────────────────────────┤
│  Timeline de Eventos                        │  ← Seção 4.3
├─────────────────────────────────────────────┤
│  Comentários                                │  ← Seção 4.4
├─────────────────────────────────────────────┤
│  Indicadores de SLA                         │  ← Seção 4.5
├─────────────────────────────────────────────┤
│  Anexos                                     │  ← Seção 4.6
├─────────────────────────────────────────────┤
│  Auditoria                                  │  ← Seção 4.7
└─────────────────────────────────────────────┘
```

---

## 5. Timeline de Eventos

### 5.1 Fontes de dados

| Tabela | Tipo de evento |
|--------|---------------|
| `ticket_histories` | Todas as transições tipadas (campo `tipo_evento`) |
| `ticketsmovs` | Movimentações legadas de status |
| `ticketcomentarios` | Comentários (público e interno) |
| `ticketsanexos` | Upload de arquivos |
| `ticketshoras` | Registro de horas técnicas |
| `tickets` (created/updated) | Criação e última edição |

### 5.2 Tipos de evento na timeline

| Tipo (`tipo_evento`) | Ícone | Descrição exibida |
|---------------------|-------|------------------|
| `created` | ➕ | Ticket aberto por [usuário] |
| `status_changed` | 🔄 | Status alterado de [A] para [B] |
| `assigned` | 👤 | Atribuído para [técnico] |
| `queue_changed` | 📋 | Movido para fila [fila] |
| `priority_changed` | ⚡ | Prioridade alterada para [P1–P4] |
| `comment_added` | 💬 | Comentário adicionado |
| `attachment_added` | 📎 | Arquivo [nome] anexado |
| `hours_logged` | ⏱ | [X]h registradas por [técnico] |
| `sla_warning` | ⚠️ | SLA atingiu [%] de consumo |
| `sla_violated` | 🚨 | SLA violado |
| `resolved` | ✅ | Resolvido por [técnico] |
| `closed` | 🔒 | Ticket encerrado |
| `reopened` | 🔓 | Ticket reaberto |

### 5.3 Estrutura do card na timeline

```
[ícone] [data/hora]  [usuário responsável]
        [descrição do evento]
        [detalhes expandíveis — valor anterior → novo valor]
```

### 5.4 Filtros na timeline

- Tipo de evento (checkboxes)
- Apenas eventos públicos (ocultar internos para role = 1)

---

## 6. Comentários

### 6.1 Tabela: `ticketcomentarios`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idticket` | FK | Ticket pai |
| `idautor` | FK users | Autor do comentário |
| `comentario` | text | Conteúdo |
| `interno` | boolean | Visível apenas para técnicos |
| `created` | timestamp | Data/hora |

### 6.2 Regras de exibição

- `interno = true` → exibido somente para `role = 0` (técnicos)
- `interno = false` → visível para cliente e técnico
- Cliente (`role = 1`) pode adicionar comentários públicos apenas em tickets abertos da sua empresa

---

## 7. Indicadores de SLA

### 7.1 Campos do ticket (tabela `tickets`)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `sla_policy_id` | FK `sla_policies` | Política aplicada |
| `prioridade` | varchar | P1 / P2 / P3 / P4 |
| `impacto` | varchar | Baixo / Médio / Alto / Crítico |
| `urgencia` | varchar | Baixa / Média / Alta / Crítica |
| `data_limite_primeira_resposta` | timestamp | Prazo primeira resposta |
| `data_primeira_resposta` | timestamp | Quando foi respondido |
| `data_limite_resolucao` | timestamp | Prazo de resolução |
| `sla_resolucao_minutos` | integer | SLA em minutos |
| `sla_percentual_consumido` | decimal | % do prazo usado |
| `sla_status` | varchar | `ok` / `atencao` / `violado` |
| `sla_resolucao_pausado` | boolean | SLA pausado (aguardando cliente) |

### 7.2 Exibição visual do SLA

```
Prazo de resolução:  ████████████░░░░░  75%  — Atenção
Data limite:  31/03/2026 17:00
Tempo restante:  4h 30min
```

| Status | Cor | Threshold |
|--------|-----|-----------|
| `ok` | Verde | < 50% consumido |
| `atencao` | Amarelo | 50–85% consumido |
| `violado` | Vermelho | > 85% ou prazo expirado |

### 7.3 Políticas SLA (tabela `sla_policies`)

| Prioridade | Primeira resposta | Resolução |
|-----------|-----------------|-----------|
| P1 — Crítico | 30 min | 4h |
| P2 — Alto | 2h | 8h |
| P3 — Médio | 8h | 24h |
| P4 — Baixo | 24h | 72h |

*Valores por empresa — configuráveis em `sla_policies`.*

---

## 8. Anexos

### 8.1 Tabela: `ticketsanexos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idticket` | FK | Ticket pai |
| `idautor` | FK users | Quem fez upload |
| `arquivo` | varchar | Nome do arquivo no disco |
| `nome_original` | varchar | Nome exibido |
| `tamanho` | integer | Bytes |
| `mimetype` | varchar | Tipo MIME |
| `created` | timestamp | Data upload |

### 8.2 Ações disponíveis

| Ação | URL | Permissão |
|------|-----|-----------|
| Download | `/tickets/download-anexo/:id` | tickets.view |
| Upload | `POST /tickets/api-anexo-upload/:idticket` | tickets.manage |
| Excluir | `POST /tickets/api-anexo-delete/:id` | tickets.manage |

### 8.3 Regras para cliente

- Cliente pode fazer upload somente em tickets abertos da própria empresa
- Download permitido para anexos de seus próprios tickets
- Visualização inline para imagens e PDFs (Magnific Popup)

---

## 9. Auditoria

### 9.1 Tabela: `ticket_histories`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `ticket_id` | FK | Ticket auditado |
| `usuario_id` | FK users | Quem executou a ação |
| `tipo_evento` | varchar | Tipo (ver seção 5.2) |
| `campo_alterado` | varchar | Nome do campo modificado |
| `valor_anterior` | text | Valor antes da alteração |
| `valor_novo` | text | Valor após a alteração |
| `created` | timestamp | Data/hora do evento |
| `metadata` | jsonb | Dados adicionais |

### 9.2 Serviços que gravam auditoria

- `TicketHistoryLogger` (falha silenciosa — não bloqueia a operação)
- Acionado pelo `TicketsController` nas ações: `add`, `edit`, `alterarStatus`, `apiSaveTicket`

### 9.3 Visibilidade

| Perfil | Vê auditoria completa | Vê apenas eventos públicos |
|--------|----------------------|--------------------------|
| admin / gestor | ✅ | — |
| tecnico | ✅ | — |
| cliente | — | ✅ |

---

## 10. Exportações

| Formato | Conteúdo | Filtros aplicados |
|---------|---------|-----------------|
| CSV | Todos os campos da listagem | Sim |
| Excel (XLSX) | Idem + formatação | Sim |
| PDF | Relatório formatado A4 | Sim |

Disponível apenas para `role = 0` com permissão `tickets.export`.

---

## 11. APIs JSON (React / AJAX)

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /tickets/api-index` | GET | Listagem técnica (paginada, filtrada) |
| `GET /tickets/api-index-cliente` | GET | Listagem cliente |
| `GET /tickets/api-view/:id` | GET | Detalhe do ticket |
| `GET /tickets/api-comments/:id` | GET | Comentários do ticket |
| `POST /tickets/api-save/:id` | POST | Salvar alterações |
| `POST /tickets/api-anexo-upload/:id` | POST | Upload de anexo |
| `POST /tickets/api-anexo-delete/:id` | POST | Excluir anexo |
