# Documento 2 — Mapa de Menus

## 1. Menu ERP (Portal PGM — role = 0)

```
Dashboard
│
├── Atendimento
│   ├── Tickets                      /tickets/index
│   ├── Service Desk (React)         /servicedesk
│   ├── Painel Operacional (React)   /servicedesk/operacional
│   ├── Filas de Atendimento         /queues
│   └── [NOVO] Histórico Atend.      /tickets/historico  ← Módulo novo Doc 4
│
├── Comercial / OS
│   ├── Ordens de Serviço            /ordensservico/index
│   ├── Orçamentos                   /orcamentos/index
│   ├── Pré-faturamento              /prefaturamento/index
│   └── Faturamento                  /faturamento/index
│
├── Financeiro
│   ├── Dashboard Financeiro         /financeiro/index
│   ├── Contas a Receber             /financeiro/contas-receber
│   ├── Faturas / Locação            /locacao/index
│   └── [NOVO] Contratos e Faturas   /clicontratos/portal  ← Módulo novo Doc 5
│
├── Cadastros
│   ├── Clientes                     /clientes/index
│   ├── Produtos                     /produtos/index
│   ├── Precificação                 /produtos/precificacao
│   └── Estoque PDF                  /produtos/estoque-pdf
│
├── Agenda
│   └── Visitas / Calendário         /agenda/index  |  /agenda/calendario
│
├── Relatórios                                        ← Módulo novo Doc 6
│   ├── [NOVO] Indicadores           /relatorios/indicadores
│   ├── [NOVO] Tickets               /relatorios/tickets
│   ├── [NOVO] Contratos             /relatorios/contratos
│   └── [NOVO] Financeiro            /relatorios/financeiro
│
└── Configurações
    ├── Usuários                     /users/index
    ├── Empresas                     /empresas/index
    ├── Usuários por Empresa         /empresasusers/index
    ├── Áreas                        /areas/index
    ├── Problemas                    /problemas/index
    ├── Feriados                     /feriados/index
    ├── Normas de Empresa            /normasempresa/index
    ├── Banco de Senhas              /bancosenhas/index
    ├── Permissões (RBAC)            /permissoes/index
    └── Config                       /config/index
```

---

## 2. Menu Portal do Cliente (role = 1)

```
Dashboard do Cliente
│
├── Atendimento
│   ├── Meus Tickets                 /tickets/indexcliente
│   ├── Abrir Ticket                 /tickets/add
│   └── [NOVO] Histórico Atend.      /tickets/historico-cliente  ← Módulo novo Doc 4
│
├── Orçamentos
│   ├── Meus Orçamentos              /orcamentos/index
│   ├── Solicitar Orçamento          /orcamentos/solicitar
│   └── Catálogo                     /orcamentos/catalogo
│
├── [NOVO] Contratos e Faturas       ← Módulo novo Doc 5
│   ├── Meus Contratos               /portal/contratos
│   ├── Detalhe do Contrato          /portal/contratos/:id
│   └── Minhas Faturas               /portal/faturas
│
└── Agenda
    └── Minhas Visitas               /agenda/indexcliente
```

---

## 3. Onde cada módulo novo se encaixa

### Módulo: Histórico de Atendimentos (Doc 4)

| Contexto | URL sugerida | Entrada de menu |
|---------|-------------|----------------|
| ERP — técnico/gestor | `/tickets/historico` | Atendimento → Histórico de Atendimentos |
| Portal cliente | `/tickets/historico-cliente` | Atendimento → Histórico de Atendimentos |

**Diferença de visão:**
- Técnico vê todos os tickets das empresas que gerencia, com SLA, auditoria e filtros avançados.
- Cliente vê somente tickets da sua empresa, com timeline simplificada.

---

### Módulo: Contratos e Faturas (Doc 5)

| Contexto | URL sugerida | Entrada de menu |
|---------|-------------|----------------|
| ERP — financeiro | `/faturamento/index` (existente) + novas abas | Financeiro → Contratos e Faturas |
| Portal cliente | `/portal/contratos` e `/portal/faturas` | Contratos e Faturas (menu próprio) |

**Diferença de visão:**
- ERP: gestão completa, ações de cobrança, geração de boleto, relatório de inadimplência.
- Cliente: visualização somente-leitura, download de PDF/boleto.

---

### Módulo: Relatórios (Doc 6)

| Contexto | URL sugerida | Entrada de menu |
|---------|-------------|----------------|
| ERP — gestor/técnico | `/relatorios/*` | Relatórios (menu principal) |
| Portal cliente | `/portal/relatorios` | Relatórios (acesso restrito) |

**Diferença de visão:**
- ERP: todos os indicadores, sem filtro de empresa.
- Cliente: somente dados da própria empresa; gráficos de consumo SLA e histórico.

---

## 4. Layouts por Contexto

| Layout | Arquivo | Usado em |
|--------|---------|---------|
| Portal PGM padrão | `default.ctp` | Todas as telas internas do ERP |
| Service Desk | `servicedesk.ctp` | `/servicedesk/*` — interface React técnicos |
| Portal do cliente | `client.ctp` | Telas do cliente (`role = 1`) |
| Impressão | `print.ctp` | Tickets impressos, PDFs |
| Login | `login.ctp` | Tela de autenticação |
| Orçamentos | `orcamentos.ctp` | Catálogo e solicitação de orçamentos |

---

## 5. Estados de Menu (variáveis de view)

O `AppController::beforeFilter` define as seguintes variáveis injetadas em todos os layouts:

| Variável | Controller ativo |
|---------|----------------|
| `$dashboard` | Users::dashboard |
| `$clientesActive` | Clientes |
| `$ordensActive` | Ordensservico |
| `$ticketsActive` | Tickets |
| `$orcamentosActive` | Orcamentos |
| `$faturamentoActive` | Faturamento |
| `$financeiroActive` | Financeiro |
| `$faturasActive` | Faturas |
| `$prefaturamentoActive` | Prefaturamento |
| `$senhasActive` | Bancosenhas |
| `$relActive` | Relatorios |
| `$queuesAtendimentoActive` | Queues |
