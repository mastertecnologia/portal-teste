# Documento 6 — Especificação do Módulo: Relatórios

## 1. Objetivo

Centralizar indicadores operacionais, financeiros e de atendimento em relatórios com filtros avançados, gráficos interativos e exportação em múltiplos formatos, tanto para a equipe interna (ERP) quanto para o cliente (portal).

---

## 2. Personas e Acessos

| Persona | Relatórios disponíveis | Exportação |
|---------|----------------------|-----------|
| Gestor PGM | Todos | Sim (todos os formatos) |
| Técnico PGM | Atendimento, SLA, Agenda | Sim (CSV/Excel) |
| Financeiro PGM | Financeiro, Contratos, Inadimplência | Sim (todos) |
| Cliente | Atendimento da empresa, Consumo contrato | Sim (PDF/Excel limitado) |

---

## 3. Estrutura do Módulo

```
/relatorios/
  ├── index           → Painel de relatórios disponíveis
  ├── tickets         → Relatório de atendimentos
  ├── sla             → Relatório de SLA
  ├── contratos       → Relatório de contratos
  ├── financeiro      → Relatório financeiro
  ├── agenda          → Relatório de visitas técnicas
  └── exportar        → Endpoint de exportação (POST)

/portal/relatorios/   → Versão cliente (dados filtrados por empresa)
```

---

## 4. Indicadores — Painel Principal

### 4.1 Indicadores de Atendimento

| Indicador | Cálculo | Período |
|-----------|---------|---------|
| Total de tickets abertos | COUNT(*) WHERE situacao IN (pendente, em_andamento) | Hoje / Mês / Ano |
| Total de tickets encerrados | COUNT(*) WHERE situacao IN (resolvido, respondido) | Mês / Ano |
| Tempo médio de resolução | AVG(datafinalizado - created) | Mês / Trimestre |
| Tempo médio 1ª resposta | AVG(data_primeira_resposta - created) | Mês |
| Taxa de reabertura | tickets_reabertos / tickets_resolvidos × 100 | Mês |
| Tickets por técnico | GROUP BY idtecnico_responsavel | Mês |
| Tickets por fila | GROUP BY queue_id | Mês |
| Tickets por cliente | GROUP BY idcliente | Mês / Ano |
| Distribuição por prioridade | GROUP BY prioridade | Mês |
| Distribuição por tipo | GROUP BY tipo_ticket | Mês |

### 4.2 Indicadores de SLA

| Indicador | Cálculo |
|-----------|---------|
| Taxa de cumprimento SLA | tickets_ok / total × 100 |
| Tickets em atenção | COUNT WHERE sla_status = 'atencao' |
| Tickets violados | COUNT WHERE sla_status = 'violado' |
| % violação por prioridade | GROUP BY prioridade, sla_status |
| Tempo médio antes da violação | AVG(sla_percentual_consumido) WHERE violado |
| Top 5 clientes com mais violações | GROUP BY idcliente ORDER BY violacoes DESC |

### 4.3 Indicadores Financeiros

| Indicador | Cálculo |
|-----------|---------|
| Faturamento total do mês | SUM(valor_final) WHERE competencia = mês atual |
| Faturamento por cliente | GROUP BY idcliente |
| Inadimplência | SUM(valor) WHERE situacao = 'vencida' |
| Taxa de inadimplência | inadimplencia / faturamento × 100 |
| Recebido no mês | SUM WHERE situacao = 'paga' AND data_pagamento ≥ início mês |
| Previsão de receita | SUM contratos ativos × valor_mensal |
| Horas faturadas vs. contratadas | SUM ticketshoras vs. SUM clicontratos.horas_incluidas |

### 4.4 Indicadores de Contratos

| Indicador | Cálculo |
|-----------|---------|
| Contratos ativos | COUNT WHERE situacao = 'ativo' |
| Contratos a vencer (30 dias) | COUNT WHERE vigencia_fim BETWEEN hoje e hoje+30 |
| Contratos encerrados no mês | COUNT WHERE vigencia_fim NO mês atual |
| Receita recorrente mensal (MRR) | SUM valor_mensal WHERE situacao = 'ativo' |
| Consumo médio de horas | AVG(horas_consumidas / horas_incluidas) × 100 |

---

## 5. Filtros Disponíveis

### 5.1 Filtros Globais (todos os relatórios)

| Filtro | Tipo | Campo base |
|--------|------|-----------|
| Período | Date range | `created` / `datafinalizado` / `competencia` |
| Empresa | Select | `idempresa` |
| Cliente | Select (busca) | `idcliente` |

### 5.2 Filtros por Relatório

#### Relatório de Atendimentos

| Filtro | Opções |
|--------|--------|
| Status | Todos / Abertos / Encerrados / Cancelados |
| Prioridade | P1 / P2 / P3 / P4 |
| Tipo de ticket | Incidente / Requisição / Problema / Mudança |
| Técnico responsável | Select (usuários role=0) |
| Fila | Select múltiplo |
| SLA | OK / Atenção / Violado |
| Agrupamento | Por dia / semana / mês / técnico / cliente |

#### Relatório de SLA

| Filtro | Opções |
|--------|--------|
| Política SLA | Select (sla_policies) |
| Status SLA | OK / Atenção / Violado |
| Prioridade | P1–P4 |
| Fila | Select |

#### Relatório Financeiro

| Filtro | Opções |
|--------|--------|
| Competência | Mês/Ano (range) |
| Status fatura | Pendente / Paga / Vencida |
| Tipo | Mensalidade / Avulso / OS / Locação |
| Faixa de valor | Min / Max |

#### Relatório de Contratos

| Filtro | Opções |
|--------|--------|
| Status | Ativo / Suspenso / Encerrado / Em renovação |
| Tipo | Suporte / Locação / Desenvolvimento / Misto |
| Vigência | Range de datas |
| Consumo de horas | Abaixo / Acima de X% |

---

## 6. Gráficos

### 6.1 Biblioteca

Usar **C3.js** (já disponível no projeto via `public/assets/node_modules/c3-master/`) ou **Chartist** (também disponível). Alternativa: Chart.js injetado via CDN para os novos módulos.

### 6.2 Gráficos por Relatório

#### Relatório de Atendimentos

| Gráfico | Tipo | Eixos |
|---------|------|-------|
| Volume de tickets no tempo | Linha | X: data, Y: qtd tickets |
| Tickets por status | Pizza / Donut | status, count |
| Tickets por prioridade | Barra horizontal | prioridade, count |
| Tickets por técnico | Barra vertical | técnico, count |
| Tempo médio de resolução | Linha | X: semana/mês, Y: horas |

#### Relatório de SLA

| Gráfico | Tipo | Eixos |
|---------|------|-------|
| Taxa de cumprimento SLA (meses) | Linha | X: mês, Y: % cumprimento |
| Violações por prioridade | Barra empilhada | prioridade, ok/atencao/violado |
| SLA por cliente | Barra horizontal | cliente, % cumprimento |

#### Relatório Financeiro

| Gráfico | Tipo | Eixos |
|---------|------|-------|
| Faturamento mensal | Barra | X: mês, Y: R$ |
| Recebido vs. Emitido | Barra agrupada | X: mês, Y: R$ |
| Inadimplência no tempo | Linha | X: mês, Y: % |
| Distribuição por tipo | Pizza | tipo, R$ |

#### Relatório de Contratos

| Gráfico | Tipo | Eixos |
|---------|------|-------|
| Contratos por status | Pizza | status, count |
| MRR no tempo | Linha | X: mês, Y: R$ |
| Consumo de horas por cliente | Barra | cliente, % consumido |

---

## 7. Exportações

### 7.1 Formatos suportados

| Formato | Biblioteca | Uso |
|---------|-----------|-----|
| **CSV** | PHP nativo (`fputcsv`) | Dados brutos para análise |
| **Excel (XLSX)** | PHPSpreadsheet | Dados formatados com cabeçalho |
| **PDF** | mPDF | Relatório visual para impressão/envio |

### 7.2 Endpoint de exportação

```
POST /relatorios/exportar
Content-Type: application/json

{
  "relatorio": "tickets",
  "formato": "xlsx",
  "filtros": {
    "periodo_inicio": "2026-01-01",
    "periodo_fim": "2026-03-31",
    "idcliente": 42,
    "situacao": ["resolvido", "respondido"]
  }
}
```

**Resposta:** download direto do arquivo gerado.

### 7.3 Permissões por formato

| Formato | Quem pode exportar |
|---------|------------------|
| CSV | gestor, financeiro, admin |
| Excel | gestor, financeiro, admin |
| PDF | todos (com permissão `relatorios.*`) |
| PDF cliente | cliente_full |

---

## 8. Visão ERP

### 8.1 Painel `/relatorios/index`

```
┌─────────────────────────────────────────────────────┐
│  Indicadores rápidos (cards)                        │
│  [Tickets abertos] [SLA hoje] [Faturamento mês]     │
│  [Contratos a vencer] [Inadimplência]               │
├───────────────────┬─────────────────────────────────┤
│  Relatórios       │  Área de visualização           │
│  ─────────────    │                                 │
│  > Atendimento    │  [Selecione um relatório]       │
│  > SLA            │                                 │
│  > Financeiro     │                                 │
│  > Contratos      │                                 │
│  > Agenda         │                                 │
└───────────────────┴─────────────────────────────────┘
```

### 8.2 Relatório de Atendimentos (`/relatorios/tickets`)

Seções:
1. Barra de filtros (colapsável)
2. Cards de totalizadores
3. Gráfico de volume no tempo
4. Gráfico de distribuição por status/prioridade
5. Tabela detalhada (DataTables, server-side)
6. Botões de exportação

### 8.3 Relatório Financeiro (`/relatorios/financeiro`)

Seções:
1. Filtros (competência, cliente, status)
2. Cards: Total emitido / Total recebido / Inadimplência / MRR
3. Gráfico de faturamento mensal (barras)
4. Gráfico recebido vs. emitido
5. Tabela de faturas (DataTables)
6. Bloco de inadimplentes (top clientes com faturas vencidas)
7. Exportação

---

## 9. Visão Cliente

### 9.1 Painel `/portal/relatorios`

**Filtros obrigatórios (automáticos, não editáveis pelo cliente):**
- `idcliente = sessao.idcliente`
- `idempresa = sessao.idempresa`

**Relatórios disponíveis:**

| Relatório | Descrição |
|-----------|-----------|
| Histórico de atendimentos | Tickets do próprio cliente com filtros de período/status |
| Consumo de SLA | Indicadores de cumprimento de prazos |
| Consumo do contrato | Horas utilizadas vs. contratadas |
| Faturas | Histórico de cobranças e status de pagamento |

### 9.2 Restrições na visão cliente

- Sem acesso a dados de outros clientes
- Sem indicadores de valor de contrato (R$)
- Sem dados de outros técnicos (somente nome do responsável)
- Exportação em PDF ou Excel básico (sem fórmulas/macros)
- Gráficos de consumo SLA e horas sempre referenciados à política do contrato do cliente

### 9.3 Gráficos da visão cliente

| Gráfico | Tipo | Descrição |
|---------|------|-----------|
| Tickets por mês | Barra | Abertos vs. encerrados |
| Distribuição por status | Donut | Situação atual dos tickets |
| Consumo de horas | Gauge / Barra | % das horas contratadas usadas no mês |
| SLA cumprimento | Linha | % nos últimos 6 meses |

---

## 10. Agendamento de Relatórios

*Funcionalidade futura — spec para planejamento.*

| Feature | Descrição |
|---------|-----------|
| Envio por e-mail | Relatório gerado e enviado automaticamente |
| Periodicidade | Semanal / Mensal |
| Formato | PDF ou Excel |
| Destinatários | E-mails configurados por empresa |
| Gatilho | `FechamentoMensalCommand` ou cron dedicado |

---

## 11. Performance e Boas Práticas

| Ponto | Recomendação |
|-------|-------------|
| Queries pesadas | Usar `LIMIT` + paginação server-side; nunca carregar todos os registros |
| Índices | Garantir índices em `created`, `datafinalizado`, `idcliente`, `idempresa`, `situacao` |
| Cache | Totalizadores do painel com cache de 5 min (memcached ou tabela de snapshots) |
| Exportação assíncrona | Para relatórios > 10k linhas, gerar em background e notificar por e-mail |
| Segurança | Sempre aplicar filtro de empresa na query; nunca confiar em parâmetro GET para `idempresa` |
