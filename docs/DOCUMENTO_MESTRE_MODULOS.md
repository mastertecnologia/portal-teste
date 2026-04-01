# Documento Mestre — Três Novos Módulos do ERP de Suporte

**Projeto:** Portal PGM / Portal do Cliente  
**Stack:** CakePHP 3.7 · PostgreSQL · Bootstrap 3 · React (Service Desk)  
**Data:** 2026-03-31  
**Escopo:** Histórico de Atendimentos · Contratos e Faturas · Relatórios e Indicadores

---

## 1. Visão Geral

O sistema já possui dois portais compartilhando o mesmo backend:

| Portal | Público | Role | Layout |
|--------|---------|------|--------|
| **ERP PGM** | Técnicos, gestores, financeiro | `role = 0` | `default.ctp` |
| **Portal do Cliente** | Usuários das empresas clientes | `role = 1` | `client.ctp` |

Os três novos módulos **não substituem** nenhum módulo existente. Eles **complementam** o que já existe, adicionando camadas de consulta histórica, gestão contratual e visibilidade gerencial que hoje não existem de forma estruturada.

### Resumo dos módulos novos

| # | Módulo | Problema que resolve |
|---|--------|---------------------|
| 1 | **Histórico de Atendimentos** | Hoje só existe a lista operacional de tickets abertos. Não há visão histórica navegável, timeline de eventos nem auditoria consolidada. |
| 2 | **Contratos e Faturas** | `clicontratos` e `faturas` existem no banco, mas não há módulo de portal para o cliente consultar nem uma visão unificada contrato ↔ fatura no ERP. |
| 3 | **Relatórios e Indicadores** | Não há módulo de relatórios. Gestores e clientes não têm como extrair métricas de SLA, financeiro ou consumo de contrato. |

---

## 2. Onde Cada Módulo Entra no Menu do ERP

O `AppController::beforeFilter` já define o mapa de estados de menu via `$controllerToMenuMap`. Os novos módulos seguem o mesmo padrão — cada um recebe sua variável `*Active` e seu controller é registrado no mapa.

### Menu ERP — estrutura atualizada

```
Dashboard
│
├── ATENDIMENTO
│   ├── Tickets                       /tickets/index          (existente)
│   ├── Service Desk                  /servicedesk            (existente)
│   ├── Painel Operacional            /servicedesk/operacional (existente)
│   ├── Filas                         /queues/index           (existente)
│   └── ► Histórico de Atendimentos   /historico/index        ← NOVO #1
│
├── COMERCIAL / OS
│   ├── Ordens de Serviço             /ordensservico/index    (existente)
│   ├── Orçamentos                    /orcamentos/index       (existente)
│   ├── Pré-faturamento               /prefaturamento/index   (existente)
│   └── Faturamento                   /faturamento/index      (existente)
│
├── FINANCEIRO
│   ├── Dashboard Financeiro          /financeiro/index       (existente)
│   ├── Contas a Receber              /financeiro/contas-receber (existente)
│   ├── Faturas / Locação             /locacao/index          (existente)
│   └── ► Contratos e Faturas         /contratos/index        ← NOVO #2
│
├── ► RELATÓRIOS                                              ← NOVO #3
│   ├── Painel de Indicadores         /relatorios/index
│   ├── Atendimentos                  /relatorios/tickets
│   ├── SLA                           /relatorios/sla
│   ├── Contratos                     /relatorios/contratos
│   └── Financeiro                    /relatorios/financeiro
│
└── CONFIGURAÇÕES
    └── (inalterado)
```

### Variáveis de menu a criar

```php
// AppController.php — $controllerToMenuMap
'historico'   => 'historicoActive',
'contratos'   => 'contratosActive',
'relatorios'  => 'relActive',   // relActive já existe mas sem controller associado
```

---

## 3. Onde Cada Módulo Entra no Menu do Portal do Cliente

O portal do cliente usa `client.ctp` e limita o que é exibido com base em `role = 1` e na permissão `canClienteSolicitarOrcamento`.

```
Dashboard do Cliente
│
├── ATENDIMENTO
│   ├── Meus Tickets                  /tickets/indexcliente   (existente)
│   ├── Abrir Ticket                  /tickets/add            (existente)
│   └── ► Histórico de Atendimentos   /historico/cliente      ← NOVO #1
│
├── ORÇAMENTOS                        (existente)
│
├── ► CONTRATOS E FATURAS                                      ← NOVO #2
│   ├── Meus Contratos                /contratos/cliente
│   └── Minhas Faturas                /contratos/faturas
│
├── ► RELATÓRIOS                                               ← NOVO #3
│   └── Indicadores da Empresa        /relatorios/cliente
│
└── AGENDA
    └── Minhas Visitas                /agenda/indexcliente    (existente)
```

---

## 4. Objetivo de Cada Módulo

### Módulo 1 — Histórico de Atendimentos

> **Dar visibilidade completa e auditável de tudo que aconteceu em um ticket, do início ao fim, para técnicos e clientes.**

O módulo de Tickets existente (`/tickets/index`) é uma **fila de trabalho ativo**. O Histórico é o **arquivo consultável** de atendimentos encerrados e em andamento, com:

- Listagem com filtros avançados (período, técnico, fila, SLA, tipo, prioridade)
- Detalhe com timeline cronológica de cada evento do ticket
- Indicadores de SLA (% consumido, prazo, violação)
- Seção de anexos com download
- Trilha de auditoria completa (`ticket_histories`)

**O que muda para o cliente:** em vez de ver só "Ticket #42 — Resolvido", o cliente vê exatamente o que aconteceu, quando cada resposta foi dada e se o SLA foi cumprido.

---

### Módulo 2 — Contratos e Faturas

> **Unificar em uma única tela a relação contrato → cobranças → pagamentos, com download de documentos para o cliente e gestão completa para o financeiro.**

As tabelas `clicontratos` e `faturas`/`faturamento` já existem no banco, mas:
- Não há portal do cliente para consultar contratos ou baixar faturas
- No ERP não há visão cruzada contrato ↔ histórico de faturas ↔ consumo de horas

O módulo entrega:
- Cadastro e gestão de contratos com cobertura, vigência e SLA
- Listagem e detalhe de faturas com download de PDF e boleto
- Alertas de vencimento de contrato e inadimplência
- Para o cliente: visão somente-leitura de contratos e histórico de cobranças

---

### Módulo 3 — Relatórios e Indicadores

> **Transformar os dados já existentes em indicadores acionáveis para gestores, técnicos e clientes, com gráficos e exportações.**

Atualmente não existe nenhuma tela de relatórios estruturada. Os dados estão no banco mas só são acessíveis via consulta direta ou exportação manual.

O módulo entrega:
- Painel com cards de KPIs principais (tickets, SLA, financeiro, contratos)
- Relatórios por área: atendimento, SLA, financeiro, contratos, agenda
- Gráficos interativos (C3.js — já presente no projeto)
- Exportação em CSV, Excel e PDF (mPDF — já presente)
- Visão do cliente: consumo do contrato, histórico de atendimentos e SLA da empresa

---

## 5. Regras de Negócio

### 5.1 Módulo 1 — Histórico de Atendimentos

| Regra | Descrição |
|-------|-----------|
| **RN-H01** | Técnico vê todos os tickets da empresa ativa. Cliente vê apenas tickets da própria empresa (`WHERE idcliente = sessao.idcliente`). |
| **RN-H02** | Comentários marcados como `interno = true` são invisíveis para `role = 1`. |
| **RN-H03** | A timeline monta eventos de 5 fontes: `ticket_histories`, `ticketsmovs`, `ticketcomentarios`, `ticketsanexos`, `ticketshoras`. |
| **RN-H04** | SLA é calculado a partir de `sla_policies` vinculada ao ticket. Pause de SLA quando `sla_resolucao_pausado = true`. |
| **RN-H05** | Download de anexo exige verificação de que o ticket pertence ao cliente logado (quando `role = 1`). |
| **RN-H06** | Exportação da listagem disponível apenas para `role = 0` com permissão `tickets.export`. |
| **RN-H07** | Registro em `ticket_histories` é gravado com falha silenciosa — nunca bloqueia a operação principal. |

### 5.2 Módulo 2 — Contratos e Faturas

| Regra | Descrição |
|-------|-----------|
| **RN-C01** | Cliente visualiza apenas contratos onde `clicontratos.idcliente = sessao.idcliente`. Nunca expor contratos de outros clientes. |
| **RN-C02** | Campos financeiros (valor mensal, valor total, margem) nunca aparecem na visão do cliente. |
| **RN-C03** | Contrato com `vigencia_fim < hoje` bloqueia a aplicação automática de SLA a novos tickets. |
| **RN-C04** | Alerta de vencimento gerado quando `vigencia_fim` estiver entre `hoje` e `hoje + 30 dias`. |
| **RN-C05** | Fatura com `situacao = pendente` e `vencimento < hoje` é promovida automaticamente para `vencida` (via CLI ou consulta lazy). |
| **RN-C06** | Download de PDF/boleto requer que a fatura pertença ao `idcliente` da sessão quando `role = 1`. |
| **RN-C07** | Geração de fatura a partir de OS (`/faturamento/gerar-de-os/:idordem`) usa fluxo existente — o módulo novo apenas adiciona a visão unificada. |
| **RN-C08** | Observações internas do contrato são visíveis apenas para `role = 0`. |

### 5.3 Módulo 3 — Relatórios

| Regra | Descrição |
|-------|-----------|
| **RN-R01** | Todo relatório aplica filtro de `idempresa = sessao.idempresa` como condição não removível. Admin pode selecionar empresa livremente. |
| **RN-R02** | Cliente acessa apenas dados da própria empresa. Parâmetros de `idcliente` ou `idempresa` na URL são ignorados — somente o valor da sessão é usado. |
| **RN-R03** | Exportações > 10.000 linhas devem ser processadas em background e enviadas por e-mail (evitar timeout). |
| **RN-R04** | Gráficos usam apenas dados já persistidos no banco — nunca cálculo em tempo real sobre conjunto completo. |
| **RN-R05** | Cliente não vê valores financeiros de outros clientes nem dados de custo/margem. |
| **RN-R06** | Relatório de SLA exibe violações com identificação do ticket para que o técnico possa agir. |
| **RN-R07** | Indicadores do painel têm cache de 5 minutos para evitar queries pesadas a cada reload. |

---

## 6. Diferença entre Visão Interna e Visão do Cliente

### Módulo 1 — Histórico de Atendimentos

| Aspecto | ERP (role = 0) | Portal Cliente (role = 1) |
|---------|---------------|--------------------------|
| Escopo | Todos os tickets da empresa ativa | Apenas tickets do `idcliente` |
| Filtros | Técnico, fila, tipo, prioridade, SLA, período | Período, status, assunto |
| Timeline | Todos os eventos, incluindo internos | Apenas eventos públicos |
| Comentários | Públicos + internos | Apenas públicos |
| Horas registradas | Visíveis (campo + total) | Total em horas (sem detalhe por técnico) |
| SLA | Detalhado (prazos, %, política) | Simplificado ("No prazo" / "Atrasado") |
| Auditoria | Completa (campo por campo) | Resumida (abertura, resposta, encerramento) |
| Exportação | CSV · Excel · PDF | PDF (simplificado) |
| Ações | Reabrir, transferir, editar | Somente leitura |

### Módulo 2 — Contratos e Faturas

| Aspecto | ERP (role = 0) | Portal Cliente (role = 1) |
|---------|---------------|--------------------------|
| Escopo | Todos os clientes da empresa ativa | Apenas contratos/faturas do `idcliente` |
| Valores | Valor mensal, total, margem, custo | Oculto |
| Observações internas | Visíveis | Ocultas |
| Cobertura | Campo a campo (checkboxes detalhados) | Lista legível de itens cobertos |
| Ações | CRUD completo + alterar status + cobrar | Somente leitura |
| Downloads | PDF · Boleto · Excel gerencial | PDF da fatura · Boleto |
| Horas | Detalhe por OS e por técnico | Consumido vs. incluso (sem detalhe) |
| Alertas | Vencimento, inadimplência, renovação | Vencimento de fatura |

### Módulo 3 — Relatórios

| Aspecto | ERP (role = 0) | Portal Cliente (role = 1) |
|---------|---------------|--------------------------|
| Escopo | Empresa ativa (todos os clientes) | Apenas dados da empresa do cliente |
| Relatórios disponíveis | Atendimento · SLA · Financeiro · Contratos · Agenda | Atendimento · SLA · Consumo contrato |
| Valores financeiros | Receita, inadimplência, MRR, custo | Ocultos |
| Dados de técnicos | Todos (nome, performance, filas) | Apenas "técnico responsável" |
| Gráficos | Todos os tipos (linha, barra, pizza, gauge) | Volume tickets · SLA · Consumo de horas |
| Exportação | CSV · Excel completo · PDF gerencial | Excel básico · PDF simplificado |
| Agendamento | Envio por e-mail configurável | Não disponível |

---

## 7. Dependências entre Módulos

### 7.1 Dependências de banco (tabelas existentes necessárias)

```
Módulo 1 — Histórico
  ├── tickets              ✅ existe
  ├── ticket_histories     ✅ existe (migration 20260321140200)
  ├── ticketcomentarios    ✅ existe
  ├── ticketsanexos        ✅ existe
  ├── ticketshoras         ✅ existe
  ├── ticketsmovs          ✅ existe
  ├── sla_policies         ✅ existe (migration 20260321140100)
  ├── queues               ✅ existe
  └── users, clientes      ✅ existem

Módulo 2 — Contratos e Faturas
  ├── clicontratos         ✅ existe
  ├── faturas              ✅ existe
  ├── faturamento          ✅ existe
  ├── faturamento_itens    ✅ existe
  ├── clientes             ✅ existe
  └── sla_policies         ✅ existe (dependência de Módulo 1 ou independente)

Módulo 3 — Relatórios
  ├── tickets + ticket_histories  ← depende do Módulo 1 estar populando histories
  ├── clicontratos + faturas      ← depende do Módulo 2
  ├── ticketshoras                ✅ existe
  └── todos os demais             ✅ existem
```

### 7.2 Dependências funcionais

```
Módulo 3 (Relatórios)
    └── depende de ──► Módulo 1 (para relatório de SLA ter histórico rico)
    └── depende de ──► Módulo 2 (para relatório financeiro ter dados de contratos)

Módulo 2 (Contratos e Faturas)
    └── depende de ──► sla_policies (já criada na evolução do Service Desk)
    └── fornece para ──► Módulo 1 (contrato define SLA do ticket)
    └── fornece para ──► Módulo 3 (dados financeiros e de cobertura)

Módulo 1 (Histórico)
    └── independente dos outros dois para funcionar
    └── alimenta ──► Módulo 3 (dados de SLA e timeline)
```

### 7.3 Dependências de RBAC/ABAC

Todos os três módulos dependem de:
- `RbacComponent` + `AbacComponent` (já existentes em `AppController`)
- Perfis e permissões registrados em `rbac_roles` e `rbac_permissions`
- Escopo de empresa via `empresasusers` (já existe)

Nenhum módulo novo introduz mecanismo de autorização próprio — todos usam o que já existe.

---

## 8. Ordem Recomendada de Desenvolvimento

### Critério de ordenação

1. **Valor imediato** — o que o cliente e o gestor vão usar no dia 1
2. **Complexidade** — começar pelo que reutiliza mais código existente
3. **Dependências** — módulos base antes dos que dependem deles

---

### Fase 1 — Módulo 2: Contratos e Faturas *(2–3 semanas)*

**Por que primeiro:**
- As tabelas já existem (`clicontratos`, `faturas`, `faturamento`)
- É o módulo com maior valor imediato para o cliente (ele precisa ver seus contratos e boletos)
- Não depende dos outros dois módulos
- Cria a base de dados que alimenta o Módulo 3

**Entregas da Fase 1:**

| Semana | Entrega |
|--------|---------|
| 1 | `ContratosController` + views ERP (listagem + detalhe) |
| 1 | Rota `/contratos/*` no `routes.php` + variável `contratosActive` no AppController |
| 2 | Visão cliente (`/contratos/cliente` e `/contratos/faturas`) no layout `client.ctp` |
| 2 | Download de PDF de fatura e boleto com validação de ownership |
| 3 | Alertas de vencimento de contrato (notificação interna) |
| 3 | Testes: escopo ABAC (cliente não acessa dados de outro cliente) |

**Permissões RBAC a criar:**
```
contratos.view · contratos.manage · contratos.cliente_view
faturas.view · faturas.manage · faturas.cliente_download
```

---

### Fase 2 — Módulo 1: Histórico de Atendimentos *(2–3 semanas)*

**Por que segundo:**
- Depende de `ticket_histories` (já existe) e `sla_policies` (já existe)
- Não depende do Módulo 2
- Depois de pronto, alimenta diretamente o Módulo 3

**Entregas da Fase 2:**

| Semana | Entrega |
|--------|---------|
| 1 | `HistoricoController` + listagem ERP com filtros (DataTables server-side) |
| 1 | Rota `/historico/*` + variável `historicoActive` no AppController |
| 2 | Detalhe do ticket: timeline montada a partir das 5 fontes |
| 2 | Painel de SLA (barra de progresso, status, prazos) |
| 3 | Visão cliente (`/historico/cliente`) com eventos públicos apenas |
| 3 | Exportação CSV/Excel/PDF da listagem |
| 3 | Testes: comentários internos invisíveis para cliente |

**Permissões RBAC a criar:**
```
tickets.historico_view · tickets.export
tickets.cliente_historico_view
```

---

### Fase 3 — Módulo 3: Relatórios e Indicadores *(3–4 semanas)*

**Por que por último:**
- Depende dos dados gerados pelos Módulos 1 e 2
- É o mais complexo (múltiplos relatórios, gráficos, exportações)
- Tem maior valor para gestão — mas não bloqueia operação do sistema

**Entregas da Fase 3:**

| Semana | Entrega |
|--------|---------|
| 1 | `RelatoriosController` + painel index com cards de KPIs |
| 1 | Rota `/relatorios/*` + `relActive` (variável já existe — apenas mapear o controller) |
| 2 | Relatório de atendimentos (filtros + DataTables + gráfico de volume) |
| 2 | Relatório de SLA (taxa de cumprimento + violações) |
| 3 | Relatório financeiro (faturamento mensal + inadimplência) |
| 3 | Relatório de contratos (MRR + consumo de horas) |
| 4 | Visão cliente (`/relatorios/cliente`) com gráficos de consumo |
| 4 | Exportações CSV · Excel · PDF em todos os relatórios |

**Permissões RBAC a criar:**
```
relatorios.tickets · relatorios.sla · relatorios.contratos
relatorios.financeiro · relatorios.exportar · relatorios.cliente_view
```

---

### Visão geral do cronograma

```
Semana  1  2  3  4  5  6  7  8  9  10
        ├──────────────┤               Módulo 2 — Contratos e Faturas
                       ├──────────────┤ Módulo 1 — Histórico
                                      ├──────────────────────┤ Módulo 3 — Relatórios
```

**Total estimado: 7–10 semanas** (desenvolvimento + testes de escopo ABAC por módulo)

---

## 9. Checklist de Arquitetura por Módulo

Para cada módulo novo, seguir exatamente o padrão existente no projeto:

```
src/
  Controller/
    ContratosController.php     ← estende AppController
    HistoricoController.php
    RelatoriosController.php

  Template/
    Contratos/
      index.ctp                 ← layout default (ERP)
      view.ctp
      cliente.ctp               ← layout client
      faturas.ctp               ← layout client
    Historico/
      index.ctp
      view.ctp
      cliente.ctp
    Relatorios/
      index.ctp
      tickets.ctp
      sla.ctp
      financeiro.ctp
      contratos.ctp
      cliente.ctp

config/
  routes.php                    ← adicionar rotas explícitas
```

**AppController — alterações necessárias:**

```php
// $controllerToMenuMap — adicionar:
'historico'   => 'historicoActive',
'contratos'   => 'contratosActive',
// 'relatorios' => 'relActive'  ← já existe a variável, mapear o controller

// $menuStates — adicionar:
'historicoActive'  => '',
'contratosActive'  => '',
```

**Security::unlockedActions — nenhuma alteração** — os três módulos usam FormHelper padrão com CSRF, sem AJAX dinâmico que exija desbloqueio.

---

## 10. Referência Rápida — Tabelas por Módulo

| Tabela | Módulo 1 | Módulo 2 | Módulo 3 |
|--------|:--------:|:--------:|:--------:|
| `tickets` | ✅ lê | — | ✅ agrega |
| `ticket_histories` | ✅ lê/grava | — | ✅ agrega |
| `ticketcomentarios` | ✅ lê | — | — |
| `ticketsanexos` | ✅ lê | — | — |
| `ticketshoras` | ✅ lê | — | ✅ agrega |
| `sla_policies` | ✅ lê | ✅ lê | ✅ agrega |
| `clicontratos` | — | ✅ lê/grava | ✅ agrega |
| `faturas` | — | ✅ lê | ✅ agrega |
| `faturamento` | — | ✅ lê | ✅ agrega |
| `faturamento_itens` | — | ✅ lê | ✅ agrega |
| `queues` | ✅ lê | — | ✅ agrega |
| `clientes` | ✅ lê | ✅ lê | ✅ agrega |
| `users` | ✅ lê | — | ✅ agrega |
| `rbac_*` | ✅ usa | ✅ usa | ✅ usa |
| `empresasusers` | ✅ usa | ✅ usa | ✅ usa |
