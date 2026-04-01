# Documento 1 — Visão Geral do Projeto

## 1. Contexto

Sistema de gestão e atendimento ao cliente desenvolvido pela **PGM Sistemas**.
Composto por dois portais distintos que compartilham o mesmo backend CakePHP 3.7:

| Portal | Público | URL base | Layout |
|--------|---------|----------|--------|
| **Portal PGM** | Equipe interna (técnicos, gestores, financeiro) | `/` | `default.ctp` / `servicedesk.ctp` |
| **Portal do Cliente** | Usuários das empresas clientes | `/` (role = 1) | `client.ctp` |

---

## 2. Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP ≥ 5.6 / **CakePHP 3.7.x** |
| Banco de dados | **PostgreSQL** (servidor 10.0.2.23) |
| Frontend principal | HTML + Bootstrap 3 + jQuery + DataTables |
| Frontend complementar | **React** (pasta `dashboard-react/`) — interface Service Desk operacional |
| Geração de PDF | **mPDF** |
| Detecção mobile | `mobiledetectlib` |
| Bundler React | Vite |

---

## 3. Infraestrutura

```
┌─────────────────────────────────────────────┐
│  Portal (Linux)  10.0.2.25                  │
│  /var/www/portal/public  ← Document root    │
│  CakePHP 3.7 + PHP                          │
└─────────────────┬───────────────────────────┘
                  │ TCP/IP
┌─────────────────┴───────────────────────────┐
│  PostgreSQL  10.0.2.23                      │
└─────────────────────────────────────────────┘
                  │ HTTP (REST)
┌─────────────────┴───────────────────────────┐
│  ERP Windows  10.0.2.7  (ECS-MASTER)        │
│  http://10.0.2.7:85/WebGridPGM/             │
│  Integrador GridERP + Web (WSO)             │
└─────────────────────────────────────────────┘
```

Deploy via script `deploy-portal.sh`. Integração com ERP legado Windows via chamadas HTTP REST.

---

## 4. ERP Interno (Portal PGM)

### 4.1 Objetivo

Ferramenta de back-office da equipe PGM para gestão de clientes, contratos, tickets de suporte, faturamento e relatórios gerenciais.

### 4.2 Módulos existentes

| Módulo | Controller | Descrição |
|--------|-----------|-----------|
| **Dashboard** | `Users::dashboard` | Visão geral: tickets, agenda, pendências |
| **Clientes** | `ClientesController` | Cadastro completo de clientes PF/PJ com consulta CNPJ/IE |
| **Contratos** | `ClicontratosController` | Contratos de clientes, cobertura e vigência |
| **Ordens de Serviço** | `OrdensservicoController` | OS com horas, parcelas e movimentos |
| **Orçamentos** | `OrcamentosController` | Orçamentos com itens, movimentos, catálogo e PDF |
| **Pré-faturamento** | `PrefaturamentoController` | Conferência prévia de OS para faturamento |
| **Faturamento** | `FaturamentoController` | Emissão de documentos/cobranças a partir de OS |
| **Financeiro** | `FinanceiroController` | Dashboard financeiro, contas a receber |
| **Faturas / Locação** | `FaturasController` | Locação de equipamentos, aprovação, recibo |
| **Tickets** | `TicketsController` | Central de suporte (listagem técnica, painel operacional) |
| **Service Desk** | `ServicedeskController` | Interface React dedicada para técnicos |
| **Agenda** | `VisitasController` | Agendamento de visitas técnicas, calendário |
| **Produtos** | `ProdutosController` | Estoque, precificação, PDF estoque |
| **Banco de Senhas** | `BancosenhasController` | Cofre de credenciais (vault com reveal) |
| **Áreas** | `AreasController` | Áreas de atendimento |
| **Problemas** | `ProblemasController` | Categorização de problemas |
| **Filas** | `QueuesController` | Filas de atendimento N1/N2/N3 |
| **Normas de Empresa** | `NormasempresaController` | Normas e regulamentos internos |
| **Notificações** | `NotificacoesController` | Notificações push internas |
| **Empresas** | `EmpresasController` | Cadastro de empresas (PGM e empresas-cliente) |
| **Usuários Empresa** | `EmpresasusersController` | Vinculação usuário ↔ empresa |
| **Permissões** | `PermissoesController` | RBAC: gestão de papéis e permissões |
| **Config** | `ConfigController` | Configurações globais do sistema |
| **Feriados** | `FeriadosController` | Cadastro de feriados para SLA |

---

## 5. Portal do Cliente

### 5.1 Objetivo

Interface self-service para usuários das empresas clientes acompanharem atendimentos, abrirem tickets, consultarem contratos/faturas e solicitarem orçamentos.

### 5.2 Módulos disponíveis para o cliente

| Módulo | Ações permitidas |
|--------|----------------|
| **Tickets** | Abrir, visualizar, cancelar, fazer download de anexo, imprimir |
| **Orçamentos** | Solicitar orçamento, acompanhar status, catálogo de produtos |
| **Agenda** | Ver visitas agendadas |
| **Contratos** | Consultar contratos e vigência |
| **Faturas** | Consultar cobranças e efetuar download |

---

## 6. Módulos Novos (em especificação)

| Módulo | Público-alvo | Objetivo |
|--------|-------------|---------|
| **Histórico de Atendimentos** | Cliente + Técnico | Listagem completa de tickets com timeline, SLA, anexos e auditoria |
| **Contratos e Faturas** | Cliente + Financeiro | Contratos com cobertura/vigência + faturas com download e cobrança |
| **Relatórios** | Técnico + Gestor + Cliente | Indicadores, filtros avançados, gráficos e exportações |

---

## 7. Integrações

| Integração | Protocolo | Direção | Finalidade |
|-----------|----------|---------|-----------|
| ERP Windows (GridERP) | HTTP REST | Portal ← ERP | Importar OS, produtos, clientes |
| ERP Windows (GridERP) | HTTP REST | Portal → ERP | Atualizar situação de OS |
| Receita Federal | HTTP | Portal → Receita | Consulta CNPJ |
| SEFAZ/SINTEGRA | HTTP | Portal → SEFAZ | Consulta IE |
| SMTP | TLS/SSL | Portal → SMTP | E-mails de tickets, notificações |

---

## 8. Autenticação e Sessão

- Login por `username` + `password` + `idempresa` (multi-empresa)
- `role = 0` → usuário PGM (técnico/gestor)
- `role = 1` → usuário cliente
- `admin = true` → acesso às rotas `/admin/*`
- Troca de empresa via dropdown (AJAX, sem relogin)
- Autenticação dupla disponível (`loginduasetapas`)
