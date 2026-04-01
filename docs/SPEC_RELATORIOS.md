# Especificação Funcional — Módulo: Relatórios e Indicadores

**Versão:** 1.0  
**Data:** 2026-03-31  
**Status:** Aprovada para desenvolvimento  
**Referência cruzada:** DOCUMENTO_MESTRE_MODULOS.md · DOC6_RELATORIOS.md · SPEC_HISTORICO_ATENDIMENTOS.md · SPEC_CONTRATOS_FATURAS.md

---

## Sumário

1. [Objetivo do Módulo](#1-objetivo-do-módulo)  
2. [Relatórios Disponíveis no ERP](#2-relatórios-disponíveis-no-erp)  
3. [Relatórios Disponíveis no Portal](#3-relatórios-disponíveis-no-portal)  
4. [Filtros Globais](#4-filtros-globais)  
5. [Indicadores Principais](#5-indicadores-principais)  
6. [Gráficos Sugeridos](#6-gráficos-sugeridos)  
7. [Permissões RBAC](#7-permissões-rbac)  
8. [Regras ABAC](#8-regras-abac)  
9. [Exportações](#9-exportações)  
10. [Critérios de Aceite](#10-critérios-de-aceite)  
11. [Edge Cases e Tratamentos](#11-edge-cases-e-tratamentos)

---

## 1. Objetivo do Módulo

Centralizar indicadores operacionais, de atendimento, de SLA e financeiros em relatórios com filtros avançados, gráficos interativos (Chart.js 4.4.1) e exportação em CSV/Excel/PDF — tanto para a equipe interna (ERP, `role = 0`) quanto para o cliente (portal, `role = 1`).

**Premissas de design:**

- Os dados da equipe PGM cobrem **todas as empresas** do `idempresa` da sessão; o cliente vê **somente os próprios dados**.
- Nenhum valor monetário de contrato (custo, margem) é exposto ao cliente.
- As queries pesadas usam paginação server-side; totalizadores usam cache de 5 min via tabela `relatorios_cache` (snapshot — detalhado na Seção 5).
- A camada de serviço segue o padrão de `DashboardService::operationalSnapshot()`: um método principal retorna array PHP pronto para `json_encode`, reaproveitável no ERP e no portal.
- O menu `relActive` já está mapeado em `AppController::$controllerToMenuMap` para o controller `RelatoriosController`. **Nenhuma alteração em AppController é necessária** além de adicionar o controller.

---

## 2. Relatórios Disponíveis no ERP

### 2.1 Painel `/relatorios/index`

Ponto de entrada. Exibe cards de indicadores rápidos e links para cada relatório.

```
┌──────────────────────────────────────────────────────────────┐
│  [Tickets abertos]  [SLA hoje]  [Faturamento mês]            │
│  [Contratos a vencer]  [Inadimplência]                       │
├────────────────────┬─────────────────────────────────────────┤
│  Menu lateral      │  Área de visualização                   │
│  ─────────────     │                                         │
│  > Atendimentos    │  Selecione um relatório à esquerda.     │
│  > SLA             │                                         │
│  > Financeiro      │                                         │
│  > Contratos       │                                         │
│  > Agenda/Visitas  │                                         │
└────────────────────┴─────────────────────────────────────────┘
```

Os 5 cards do topo reaproveitam dados já calculados por `DashboardService` e `FinanceiroController`; **não devem disparar novas queries** — consultar a tabela de cache (Seção 5.6).

---

### 2.2 Relatório de Atendimentos — `/relatorios/tickets`

**Objetivo:** Volume, distribuição e evolução dos tickets no período.

**Seções da página:**

| # | Seção | Implementação |
|---|-------|---------------|
| 1 | Barra de filtros (colapsável) | Form GET, campos definidos na Seção 4 |
| 2 | Cards de totalizadores | PHP COUNT(*) com filtros aplicados |
| 3 | Gráfico: volume no tempo | Chart.js linha — dados via AJAX `/relatorios/dadosTickets` |
| 4 | Gráfico: distribuição status/prioridade | Chart.js donut + barra horizontal |
| 5 | Tabela detalhada | DataTables server-side, `/relatorios/ticketsJson` |
| 6 | Botões de exportação | Form POST para `/relatorios/exportar` |

**Colunas da tabela:**

| Coluna | Campo fonte | Ordenável |
|--------|-------------|-----------|
| # Ticket | `tickets.id` | Sim |
| Assunto | `tickets.assunto` | Não |
| Cliente | `clientes.fantasia` | Sim |
| Técnico | `users.name` (idtecnico_responsavel) | Sim |
| Prioridade | `tickets.prioridade` (badge P1–P4) | Sim |
| Status SLA | `tickets.sla_status` (badge cor) | Sim |
| Situação | `tickets.situacao` | Sim |
| Aberto em | `tickets.created` | Sim |
| Resolvido em | `tickets.data_resolucao` | Sim |
| Tempo total | `tickets.tempo_total_atendimento` (min → h:mm) | Sim |

---

### 2.3 Relatório de SLA — `/relatorios/sla`

**Objetivo:** Medir cumprimento de prazos por período, técnico, cliente e prioridade.

**Seções:**

| # | Seção |
|---|-------|
| 1 | Filtros (período, cliente, prioridade, política SLA, fila) |
| 2 | Cards: Taxa geral OK / Em atenção / Violados |
| 3 | Gráfico: taxa de cumprimento mensal (linha) |
| 4 | Gráfico: violações por prioridade (barra empilhada) |
| 5 | Tabela: top clientes com mais violações |
| 6 | Tabela detalhada de tickets com SLA violado |

**Campos necessários em `tickets`:** `sla_status`, `prioridade`, `sla_percentual_consumido`, `sla_resolucao_minutos`, `sla_resolucao_pausado`, `data_resolucao`, `created`, `idcliente`, `idtecnico_responsavel`.

---

### 2.4 Relatório Financeiro — `/relatorios/financeiro`

**Objetivo:** Faturamento emitido, recebido, inadimplência e previsão de receita.

> **Atenção:** Este relatório agrega as três entidades "fatura":  
> - `faturamento` → documentos de OS (tipo Serviço/OS)  
> - `faturas` → locações (tipo Locação)  
> - `financeiro_lancamentos` → receitas/despesas lançadas manualmente

**Seções:**

| # | Seção |
|---|-------|
| 1 | Filtros (competência range, cliente, status, tipo, faixa de valor) |
| 2 | Cards: Emitido / Recebido / Inadimplência / MRR |
| 3 | Gráfico: faturamento mensal (barras) |
| 4 | Gráfico: recebido vs. emitido (barras agrupadas) |
| 5 | Tabela de faturas (DataTables) |
| 6 | Bloco de inadimplentes (top 10 clientes com faturas vencidas) |
| 7 | Exportação |

---

### 2.5 Relatório de Contratos — `/relatorios/contratos`

**Objetivo:** Status da carteira de contratos, consumo de horas e previsão de renovação.

**Seções:**

| # | Seção |
|---|-------|
| 1 | Filtros (status, tipo, vigência range, consumo de horas %) |
| 2 | Cards: Ativos / A vencer em 30 dias / Encerrados no mês / MRR |
| 3 | Gráfico: contratos por status (donut) |
| 4 | Gráfico: consumo de horas por cliente (barras horizontais) |
| 5 | Tabela: listagem com consumo e alerta de vencimento |

**Fonte de dados:** `clicontratos` (agrupado por cliente) JOIN `contratos_horas`.

---

### 2.6 Relatório de Agenda/Visitas — `/relatorios/agenda`

**Objetivo:** Visitas técnicas presenciais agendadas e realizadas no período.

**Seções:**

| # | Seção |
|---|-------|
| 1 | Filtros (período, técnico, cliente) |
| 2 | Cards: Agendadas / Realizadas / Canceladas |
| 3 | Tabela de visitas com status e horas registradas |

**Fonte de dados:** `ticketshoras` (horaini, horafin, data, idtecnico, idticket) JOIN `tickets`.

> **Nota:** `ticketshoras.data` armazena string no formato `d/m/Y`. Converter com `DateTime::createFromFormat('d/m/Y', $data)` ao filtrar por período.

---

## 3. Relatórios Disponíveis no Portal

### 3.1 Painel `/portal/relatorios`

Mesma estrutura de painel, porém com restrições ABAC automáticas (Seção 8). O cliente não vê o menu financeiro nem de contratos globais.

**Filtros obrigatórios — não editáveis pelo cliente:**

```php
$conditions['tickets.idcliente'] = $clienteAtual->id;
$conditions['tickets.idempresa'] = $this->Auth->user('idempresa');
```

---

### 3.2 Histórico de Atendimentos — `/portal/relatorios/atendimentos`

| Coluna | Visível |
|--------|---------|
| # Ticket | Sim |
| Assunto | Sim |
| Prioridade | Sim |
| Situação | Sim |
| SLA status | Sim (badge apenas — sem % consumido) |
| Técnico responsável | Somente nome |
| Aberto em | Sim |
| Resolvido em | Sim |
| Tempo total | Não (oculto) |

**Filtros disponíveis:** Período, Situação, Prioridade.  
**Exportação:** PDF ou Excel básico (sem fórmulas).

---

### 3.3 Consumo de SLA — `/portal/relatorios/sla`

Exibe apenas indicadores de cumprimento de prazos **do próprio cliente**.

| Indicador | Exibição |
|-----------|----------|
| Taxa OK | % + badge verde |
| Em atenção | count + badge amarelo |
| Violados | count + badge vermelho |
| Gráfico: % cumprimento 6 meses | Linha Chart.js |
| Política SLA ativa | Nome da política (sem valores de SLO em minutos) |

---

### 3.4 Consumo do Contrato — `/portal/relatorios/contrato`

Exibe consumo de horas do período selecionado. **Sem valores monetários.**

| Indicador | Fonte |
|-----------|-------|
| Horas contratadas/mês | `contratos_horas.horas_contratadas` |
| Horas consumidas | `contratos_horas.horas_utilizadas` |
| Saldo restante | `contratos_horas.saldo_horas` |
| Gráfico: consumo % | Barra horizontal Chart.js (gauge visual) |

---

### 3.5 Faturas — `/portal/relatorios/faturas`

Histórico de cobranças do próprio cliente.

**Colunas:**

| Coluna | Descrição |
|--------|-----------|
| Nº Fatura | — |
| Referência | Mês/ano |
| Descrição | Resumo |
| Vencimento | Data + badge prazo |
| Valor | R$ |
| Status | Pendente / Paga / Vencida |
| Ações | Ver detalhes / Download PDF / Boleto |

**Filtros:** Período (range mês/ano), Status.  
**Oculto:** Desconto, observações internas, tipo interno.

---

## 4. Filtros Globais

### 4.1 Filtros comuns a todos os relatórios ERP

| Filtro | Tipo HTML | Campo base | Obrigatório |
|--------|-----------|------------|-------------|
| Período início | `input[type=date]` | variável por relatório | Sim (default: 1º do mês atual) |
| Período fim | `input[type=date]` | variável por relatório | Sim (default: hoje) |
| Empresa | `select` hidden | `idempresa` da sessão | Automático |
| Cliente | `select` com busca (select2) | `idcliente` | Não |

> O campo `idempresa` **nunca** vem de parâmetro GET. Sempre da sessão: `$this->Auth->user('idempresa')`.

### 4.2 Filtros por relatório

#### Atendimentos

| Filtro | Tipo | Valores |
|--------|------|---------|
| Situação | Select múltiplo | pendente / em_andamento / resolvido / respondido / cancelado |
| Prioridade | Select múltiplo | P1 / P2 / P3 / P4 |
| Tipo ticket | Select múltiplo | incidente / requisicao / problema / mudanca |
| Técnico | Select (users role=0) | JOIN users |
| Fila | Select múltiplo | JOIN filas |
| SLA status | Select | dentro_sla / em_risco / violado |
| Agrupamento | Select | dia / semana / mês / técnico / cliente |

#### SLA

| Filtro | Tipo | Valores |
|--------|------|---------|
| Política SLA | Select | JOIN sla_policies |
| Status SLA | Select múltiplo | dentro_sla / em_risco / violado |
| Prioridade | Select múltiplo | P1–P4 |
| Fila | Select | JOIN filas |

#### Financeiro

| Filtro | Tipo | Valores |
|--------|------|---------|
| Competência início | Month input (YYYY-MM) | — |
| Competência fim | Month input (YYYY-MM) | — |
| Status fatura | Select múltiplo | pendente / pago / vencido / cancelado |
| Tipo | Select múltiplo | mensalidade / avulso / os / locacao |
| Valor mínimo | `input[type=number]` | — |
| Valor máximo | `input[type=number]` | — |

#### Contratos

| Filtro | Tipo | Valores |
|--------|------|---------|
| Status | Select múltiplo | ativo / suspenso / encerrado / em_renovacao |
| Tipo contrato | Select múltiplo | suporte / locacao / desenvolvimento / misto |
| Vigência início | `input[type=date]` | — |
| Vigência fim | `input[type=date]` | — |
| Consumo horas | Select | todos / abaixo_50 / entre_50_80 / acima_80 / esgotado |

---

## 5. Indicadores Principais

### 5.1 Indicadores de Atendimento

| Indicador | Query base | Período default |
|-----------|-----------|-----------------|
| Total abertos | `COUNT(*) WHERE situacao IN ('pendente','em_andamento')` | Mês atual |
| Total encerrados | `COUNT(*) WHERE situacao IN ('resolvido','respondido')` | Mês atual |
| Tempo médio resolução | `AVG(EXTRACT(EPOCH FROM (data_resolucao - created))/60)` | Mês atual |
| Tempo médio 1ª resposta | `AVG(EXTRACT(EPOCH FROM (data_primeira_resposta - created))/60)` | Mês atual |
| Taxa de reabertura | tickets reabertos / tickets resolvidos × 100 | Mês atual |
| Tickets por técnico | `COUNT(*) GROUP BY idtecnico_responsavel` | Mês atual |
| Distribuição por prioridade | `COUNT(*) GROUP BY prioridade` | Mês atual |

> **Reabertura:** ticket é reaberto quando `situacao` muda de `resolvido`/`respondido` para `pendente`/`em_andamento`. Registrado via `ticketsmovs` (tipo = valor específico a definir, sugerido: `C_TicketMovReabertura = 953`).

### 5.2 Indicadores de SLA

| Indicador | Query base |
|-----------|-----------|
| Taxa cumprimento | `COUNT(*) FILTER (WHERE sla_status = 'dentro_sla') / COUNT(*) * 100` |
| Tickets em atenção | `COUNT(*) WHERE sla_status = 'em_risco'` |
| Tickets violados | `COUNT(*) WHERE sla_status = 'violado'` |
| % violação por prioridade | `GROUP BY prioridade, sla_status` |
| Top 5 clientes mais violações | `GROUP BY idcliente ORDER BY violados DESC LIMIT 5` |

### 5.3 Indicadores Financeiros

| Indicador | Fonte | Query base |
|-----------|-------|-----------|
| Faturamento emitido mês | `faturamento` | `SUM(valor_total) WHERE status != 'cancelado' AND competencia = mês` |
| Recebido mês | `financeiro_lancamentos` | `SUM(valor) WHERE status = 'recebido' AND data_recebimento >= início_mês` |
| Inadimplência | `faturamento` | `SUM(valor_total) WHERE status = 'vencido'` |
| Taxa inadimplência | — | inadimplencia / emitido × 100 |
| MRR (receita recorrente) | `clicontratos` | `SUM(vltotal) WHERE ativo = true` |

### 5.4 Indicadores de Contratos

| Indicador | Query base |
|-----------|-----------|
| Contratos ativos | `SELECT COUNT(DISTINCT idcliente) FROM clicontratos WHERE ativo = true` |
| A vencer em 30 dias | `WHERE dtvalidade BETWEEN CURRENT_DATE AND CURRENT_DATE + 30` |
| Encerrados no mês | `WHERE dtcancelamento >= início_mês AND dtcancelamento <= fim_mês` |
| Consumo médio horas | `AVG(horas_utilizadas / NULLIF(horas_contratadas,0) * 100) FROM contratos_horas` |

### 5.5 Estrutura do Service Class

Seguir o padrão `DashboardService`. Criar `src/Service/Relatorio/RelatorioService.php`:

```php
namespace App\Service\Relatorio;

class RelatorioService
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Cake\Datasource\ConnectionManager::get('default');
    }

    /**
     * Snapshot para os cards do painel /relatorios/index.
     * Retorna array JSON-safe — mesma convenção de DashboardService::operationalSnapshot().
     */
    public function painelSnapshot(int $idempresa, ?int $idcliente = null): array
    {
        $base = ['idempresa' => $idempresa];
        if ($idcliente) {
            $base['idcliente'] = $idcliente;
        }

        return [
            'tickets_abertos'     => $this->_countTickets($base, ['pendente','em_andamento']),
            'sla_violados_hoje'   => $this->_slaVioladosHoje($idempresa, $idcliente),
            'faturamento_mes'     => $this->_faturamentoMes($idempresa, $idcliente),
            'contratos_a_vencer'  => $this->_contratosAVencer($idempresa, $idcliente),
            'inadimplencia'       => $this->_inadimplencia($idempresa, $idcliente),
            'gerado_em'           => date('Y-m-d H:i:s'),
        ];
    }

    public function ticketsAgrupados(array $filtros): array { /* ... */ }
    public function slaAgrupado(array $filtros): array { /* ... */ }
    public function financeiroMensal(array $filtros): array { /* ... */ }
    public function contratosStatus(array $filtros): array { /* ... */ }
}
```

### 5.6 Cache de Totalizadores

Criar tabela de snapshot para evitar queries repetitivas no painel:

```sql
CREATE TABLE IF NOT EXISTS relatorios_cache (
    id           SERIAL PRIMARY KEY,
    chave        VARCHAR(100) NOT NULL,   -- ex: 'painel_1_0' (tipo_idempresa_idcliente)
    payload      JSONB NOT NULL,
    gerado_em    TIMESTAMP NOT NULL DEFAULT NOW(),
    expira_em    TIMESTAMP NOT NULL
);

CREATE UNIQUE INDEX idx_relatorios_cache_chave ON relatorios_cache(chave);
```

**Lógica de cache no controller:**

```php
$chave = 'painel_' . $idempresa . '_' . ($idcliente ?? '0');
$cached = $this->RelatoriosCache->findByChave($chave)
    ->where(['expira_em >' => date('Y-m-d H:i:s')])
    ->first();

if (!$cached) {
    $payload = $this->RelatorioService->painelSnapshot($idempresa, $idcliente);
    $this->RelatoriosCache->upsert($chave, $payload, '+5 minutes');
} else {
    $payload = $cached->payload;
}
```

---

## 6. Gráficos Sugeridos

**Biblioteca:** Chart.js 4.4.1, já carregado em `default.ctp`. Todos os gráficos são renderizados via `<canvas>` + JavaScript inline no template `.ctp`, com dados passados pelo controller via `json_encode`.

**Padrão de injeção de dados (mesmo do `dashboard.ctp` e `financeiro/index.ctp`):**

```php
// Controller
$this->set('graficoDados', json_encode($dados));
```

```html
<!-- Template .ctp -->
<canvas id="meuGrafico"></canvas>
<script>
var dados = <?= $graficoDados ?>;
new Chart(document.getElementById('meuGrafico'), {
    type: 'line',
    data: { labels: dados.labels, datasets: [{ data: dados.valores }] }
});
</script>
```

---

### 6.1 Relatório de Atendimentos

| Gráfico | Tipo Chart.js | Dados |
|---------|--------------|-------|
| Volume de tickets no tempo | `line` | labels: datas agrupadas; datasets: abertos/fechados |
| Distribuição por situação | `doughnut` | labels: situações; data: counts |
| Distribuição por prioridade | `bar` (horizontal, `indexAxis: 'y'`) | labels: P1–P4; data: counts |
| Tickets por técnico | `bar` | labels: nomes técnicos; data: counts |
| Tempo médio de resolução | `line` | labels: semanas/meses; data: horas médias |

### 6.2 Relatório de SLA

| Gráfico | Tipo Chart.js | Dados |
|---------|--------------|-------|
| Taxa de cumprimento mensal | `line` | labels: últimos 6 meses; data: % OK |
| Violações por prioridade | `bar` (stacked) | datasets: dentro_sla/em_risco/violado por P1–P4 |
| SLA por cliente (top 10) | `bar` (horizontal) | labels: clientes; data: % cumprimento |

**Configuração de stacked bar:**

```javascript
new Chart(ctx, {
    type: 'bar',
    data: { labels: prioridades, datasets: [
        { label: 'OK',        data: ok,       backgroundColor: '#28a745' },
        { label: 'Em risco',  data: emRisco,  backgroundColor: '#ffc107' },
        { label: 'Violado',   data: violado,  backgroundColor: '#dc3545' },
    ]},
    options: { scales: { x: { stacked: true }, y: { stacked: true } } }
});
```

### 6.3 Relatório Financeiro

| Gráfico | Tipo Chart.js | Dados |
|---------|--------------|-------|
| Faturamento mensal | `bar` | labels: últimos 6 meses; data: valor emitido |
| Recebido vs. Emitido | `bar` (grouped) | datasets: emitido/recebido por mês |
| Inadimplência no tempo | `line` | labels: meses; data: % inadimplência |
| Distribuição por tipo | `doughnut` | labels: tipos; data: soma valores |

> **Reutilização:** O array `grafico` de 6 meses já computado em `FinanceiroController` pode ser reaproveitado diretamente. Não duplicar a query.

### 6.4 Relatório de Contratos

| Gráfico | Tipo Chart.js | Dados |
|---------|--------------|-------|
| Contratos por status | `doughnut` | labels: ativo/suspenso/encerrado; data: counts |
| MRR no tempo | `line` | labels: meses; data: soma vltotal ativos |
| Consumo de horas por cliente | `bar` (horizontal) | labels: clientes; data: % consumido |

### 6.5 Portal — Visão Cliente

| Gráfico | Tipo | Descrição |
|---------|------|-----------|
| Tickets por mês | `bar` (grouped) | Abertos vs. encerrados (últimos 6 meses) |
| Distribuição por situação | `doughnut` | Situação atual dos tickets do cliente |
| Consumo de horas | `bar` (horizontal, 1 barra) | % das horas contratadas usadas no mês |
| SLA cumprimento | `line` | % nos últimos 6 meses |

**Barra de consumo de horas (gauge visual):**

```javascript
// Barra única com valor fixo e máximo = 100%
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Consumo do mês'],
        datasets: [{
            data: [percentualConsumido],
            backgroundColor: percentualConsumido >= 90 ? '#dc3545' :
                             percentualConsumido >= 70 ? '#ffc107' : '#28a745',
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        scales: { x: { min: 0, max: 100 } },
        plugins: { legend: { display: false } }
    }
});
```

---

## 7. Permissões RBAC

### 7.1 Tabela de permissões

| Código | Descrição | Perfis padrão |
|--------|-----------|--------------|
| `relatorios.index` | Ver painel de relatórios | gestor, financeiro, tecnico, admin |
| `relatorios.tickets` | Ver relatório de atendimentos | gestor, financeiro, tecnico, admin |
| `relatorios.sla` | Ver relatório de SLA | gestor, financeiro, tecnico, admin |
| `relatorios.financeiro` | Ver relatório financeiro | gestor, financeiro, admin |
| `relatorios.contratos` | Ver relatório de contratos | gestor, financeiro, admin |
| `relatorios.agenda` | Ver relatório de agenda/visitas | gestor, tecnico, admin |
| `relatorios.exportar` | Exportar relatórios (CSV/Excel/PDF) | gestor, financeiro, admin |
| `relatorios.exportar_pdf` | Exportar em PDF | todos com `relatorios.*` |
| `portal.relatorios.index` | Painel relatórios do cliente | cliente_full, cliente_view |
| `portal.relatorios.atendimentos` | Histórico de tickets (portal) | cliente_full, cliente_view |
| `portal.relatorios.sla` | Consumo de SLA (portal) | cliente_full, cliente_view |
| `portal.relatorios.contrato` | Consumo de contrato (portal) | cliente_full |
| `portal.relatorios.faturas` | Faturas do cliente (portal) | cliente_full |

### 7.2 Seeds SQL de permissões

```sql
-- Inserir permissões
INSERT INTO rbac_permissions (nome, descricao, created, modified) VALUES
  ('relatorios.index',              'Painel de relatórios ERP',              NOW(), NOW()),
  ('relatorios.tickets',            'Relatório de atendimentos',             NOW(), NOW()),
  ('relatorios.sla',                'Relatório de SLA',                      NOW(), NOW()),
  ('relatorios.financeiro',         'Relatório financeiro',                  NOW(), NOW()),
  ('relatorios.contratos',          'Relatório de contratos',                NOW(), NOW()),
  ('relatorios.agenda',             'Relatório de visitas/agenda',           NOW(), NOW()),
  ('relatorios.exportar',           'Exportar relatórios',                   NOW(), NOW()),
  ('relatorios.exportar_pdf',       'Exportar em PDF',                       NOW(), NOW()),
  ('portal.relatorios.index',       'Painel relatórios portal cliente',      NOW(), NOW()),
  ('portal.relatorios.atendimentos','Histórico atendimentos portal',         NOW(), NOW()),
  ('portal.relatorios.sla',         'Consumo SLA portal',                    NOW(), NOW()),
  ('portal.relatorios.contrato',    'Consumo contrato portal',               NOW(), NOW()),
  ('portal.relatorios.faturas',     'Faturas portal',                        NOW(), NOW())
ON CONFLICT DO NOTHING;

-- Vincular roles (ajustar IDs conforme ambiente)
-- Gestor: todos os relatórios ERP + exportação
INSERT INTO rbac_roles_permissions (idrole, idpermissao)
  SELECT r.id, p.id FROM rbac_roles r, rbac_permissions p
  WHERE r.nome = 'gestor'
    AND p.nome IN ('relatorios.index','relatorios.tickets','relatorios.sla',
                   'relatorios.financeiro','relatorios.contratos','relatorios.agenda',
                   'relatorios.exportar','relatorios.exportar_pdf')
ON CONFLICT DO NOTHING;

-- Técnico: atendimentos, SLA, agenda
INSERT INTO rbac_roles_permissions (idrole, idpermissao)
  SELECT r.id, p.id FROM rbac_roles r, rbac_permissions p
  WHERE r.nome = 'tecnico'
    AND p.nome IN ('relatorios.index','relatorios.tickets','relatorios.sla','relatorios.agenda')
ON CONFLICT DO NOTHING;
```

### 7.3 Verificação no Controller

```php
// RelatoriosController.php
public function beforeFilter(\Cake\Event\Event $event)
{
    parent::beforeFilter($event);
    // RbacComponent já registrado no AppController — verificação automática
}

public function financeiro()
{
    // Verificação explícita por ação sensível
    if (!$this->Rbac->checkRequest()) {
        $this->Flash->error('Acesso não autorizado.');
        return $this->redirect(['action' => 'index']);
    }
    // ...
}
```

---

## 8. Regras ABAC

### 8.1 Escopo por role

| Condição | Escopo aplicado |
|----------|----------------|
| `role = 0` (PGM) | Todos os dados do `idempresa` da sessão |
| `role = 1` (cliente) | Apenas dados do `idcliente` resolvido da sessão |
| `admin = 1` | Pode trocar `idempresa` — campo `idempresa` disponível como filtro |

### 8.2 Resolução do `idcliente` para role = 1

Usar o mesmo padrão de `TicketsController::viewModal()`:

```php
// RelatoriosController.php — método privado reutilizável
private function _resolverClienteAbac(): ?int
{
    if ($this->Auth->user('role') == 0) {
        return null; // PGM vê tudo da empresa
    }

    $idcliente  = $this->Auth->user('idcliente');
    $idempresa  = $this->Auth->user('idempresa');

    $clienteBase = $this->Clientes->findById($idcliente)->first();
    if (!$clienteBase) {
        return null;
    }

    if ($clienteBase->tipo == C_ClientesTipoJuridica) {
        $clienteAtual = $this->Clientes
            ->findByCnpj(removeCaracteres($clienteBase->cnpj))
            ->where(['idempresa' => $idempresa])
            ->first();
    } else {
        $clienteAtual = $this->Clientes
            ->findByCpf(removeCaracteres($clienteBase->cpf))
            ->where(['idempresa' => $idempresa])
            ->first();
    }

    return $clienteAtual ? $clienteAtual->id : null;
}
```

### 8.3 Aplicação nos métodos do controller

```php
public function tickets()
{
    $idempresa  = $this->Auth->user('idempresa');
    $idcliente  = $this->_resolverClienteAbac(); // null = PGM, int = cliente

    $conditions = ['tickets.idempresa' => $idempresa];
    if ($idcliente !== null) {
        $conditions['tickets.idcliente'] = $idcliente;
    }

    // Aplicar filtros adicionais do GET...
    $filtros = $this->request->getQuery();
    if (!empty($filtros['situacao'])) {
        $conditions['tickets.situacao IN'] = (array)$filtros['situacao'];
    }
    // ...
}
```

### 8.4 Campos ocultos para role = 1

| Campo / Seção | Oculto para cliente |
|---------------|---------------------|
| Valor monetário de contratos | Sim |
| `observacoes` internas | Sim |
| `tempo_total_atendimento` | Sim |
| `sla_percentual_consumido` (numérico) | Sim — exibir apenas badge |
| Relatório financeiro completo | Sim — redirecionar para `/portal/relatorios/faturas` |
| Identificação de outros clientes | Sim — filtro obrigatório por idcliente |
| Nome completo de outros técnicos | Sim — exibir apenas `users.name` do responsável |

### 8.5 Proteção no endpoint de exportação

```php
public function exportar()
{
    $this->request->allowMethod(['post']);

    $idempresa  = $this->Auth->user('idempresa');
    $idcliente  = $this->_resolverClienteAbac();

    $relatorio = $this->request->getData('relatorio');
    $formato   = $this->request->getData('formato');
    $filtros   = $this->request->getData('filtros', []);

    // Sobrescrever sempre — nunca confiar no POST
    $filtros['idempresa'] = $idempresa;
    if ($idcliente !== null) {
        $filtros['idcliente'] = $idcliente;
    }

    // Verificar permissão de formato
    if (in_array($formato, ['csv','xlsx']) && $this->Auth->user('role') == 1) {
        throw new \Cake\Http\Exception\ForbiddenException('Formato não permitido.');
    }

    $this->RelatorioExportService->gerar($relatorio, $formato, $filtros);
    // ... stream do arquivo
}
```

---

## 9. Exportações

### 9.1 Formatos suportados

| Formato | Gerador | Restrição |
|---------|---------|-----------|
| **CSV** | `fputcsv` PHP nativo | role = 0 apenas (PGM) |
| **Excel (XLSX)** | PHPSpreadsheet | role = 0 apenas (PGM) |
| **PDF** | mPDF | todos com `relatorios.exportar_pdf` |
| **PDF portal** | mPDF (template simplificado) | role = 1, somente dados filtrados por cliente |

### 9.2 Endpoint de exportação

```
POST /relatorios/exportar
Content-Type: application/x-www-form-urlencoded

relatorio=tickets
formato=xlsx
filtros[periodo_inicio]=2026-01-01
filtros[periodo_fim]=2026-03-31
filtros[idcliente]=42
filtros[situacao][]=resolvido
filtros[situacao][]=respondido
```

> **Resposta:** download direto com header `Content-Disposition: attachment; filename="relatorio_tickets_2026-01.xlsx"`.

### 9.3 Service de exportação

Criar `src/Service/Relatorio/RelatorioExportService.php`:

```php
namespace App\Service\Relatorio;

class RelatorioExportService
{
    public function gerar(string $relatorio, string $formato, array $filtros): void
    {
        $dados = $this->_coletarDados($relatorio, $filtros);

        match ($formato) {
            'csv'  => $this->_exportarCsv($dados, $relatorio),
            'xlsx' => $this->_exportarXlsx($dados, $relatorio),
            'pdf'  => $this->_exportarPdf($dados, $relatorio, $filtros),
            default => throw new \InvalidArgumentException("Formato inválido: $formato"),
        };
    }

    private function _exportarCsv(array $dados, string $relatorio): void
    {
        $filename = "relatorio_{$relatorio}_" . date('Y-m') . ".csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($fp, array_keys($dados[0] ?? [])); // cabeçalho
        foreach ($dados as $linha) {
            fputcsv($fp, $linha);
        }
        fclose($fp);
        exit;
    }
    // _exportarXlsx() usa PHPSpreadsheet
    // _exportarPdf() usa mPDF com template src/Template/Relatorios/pdf/{$relatorio}.ctp
}
```

### 9.4 Relatórios grandes (> 10 mil linhas)

Para exportações que excedam 10.000 registros:

1. Gerar arquivo em background via shell: `bin/cake relatorio_export --relatorio=tickets --formato=xlsx --filtros=...`
2. Salvar em `webroot/arquivos/relatorios/{idempresa}/{hash}.xlsx`
3. Notificar o usuário por e-mail com link de download (expiração 24h)
4. Link com token: `/relatorios/download/{hash}` — verificar `idempresa` do token antes de servir.

### 9.5 Permissões de formato por perfil

| Formato | Gestor | Financeiro | Técnico | Admin | Cliente |
|---------|--------|------------|---------|-------|---------|
| CSV | Sim | Sim | Não | Sim | Não |
| Excel | Sim | Sim | Não | Sim | Não |
| PDF | Sim | Sim | Sim | Sim | Sim (limitado) |

---

## 10. Critérios de Aceite

- [ ] **CA-01** — `/relatorios/index` carrega em menos de 2 segundos com cache ativo; sem cache, consulta no banco e armazena por 5 min.
- [ ] **CA-02** — O filtro de `idempresa` é **sempre** sobrescrito pelo valor da sessão. Nenhuma manipulação de GET/POST altera o escopo.
- [ ] **CA-03** — Cliente (`role = 1`) acessando `/relatorios/*` é redirecionado para `/portal/relatorios`. Não há vazamento de dados de outros clientes.
- [ ] **CA-04** — Relatório de atendimentos exibe DataTable server-side paginada; com 10.000 tickets, a página carrega em menos de 3 segundos.
- [ ] **CA-05** — Todos os gráficos renderizam com Chart.js 4.4.1 sem erros no console; dados refletem os filtros aplicados.
- [ ] **CA-06** — Exportação CSV produz arquivo UTF-8 com BOM, abre corretamente no Excel BR sem caracteres corrompidos.
- [ ] **CA-07** — Exportação PDF via mPDF inclui: logo da empresa, período do relatório, data de geração, filtros aplicados e totalizadores.
- [ ] **CA-08** — Cliente não visualiza campos monetários de contrato (`vltotal`, `valor_mensal`) nem observações internas em nenhuma rota do portal.
- [ ] **CA-09** — A taxa de cumprimento de SLA é calculada consistentemente com `SlaRecalculationService` (violado = `sla_percentual_consumido > 100` ou prazo expirado com ticket aberto).
- [ ] **CA-10** — Exportação de mais de 10.000 linhas aciona fluxo assíncrono; usuário recebe mensagem "Relatório sendo gerado. Você receberá um e-mail quando estiver pronto."

---

## 11. Edge Cases e Tratamentos

### EC-01 — Período com zero atendimentos

**Situação:** Filtro de período retorna 0 tickets.

**Tratamento:**
```php
if (empty($dados['tickets'])) {
    $this->set('semDados', true);
}
```
Template exibe card "Nenhum dado encontrado para o período selecionado." Gráficos exibem eixos vazios (Chart.js renderiza canvas vazio sem erro). Exportação gera arquivo com apenas cabeçalho + linha "Sem dados".

---

### EC-02 — `data` em `ticketshoras` no formato `d/m/Y` (string)

**Situação:** Coluna `ticketshoras.data` armazena string `"31/03/2026"` em vez de `DATE`. Filtros por período falham se comparados diretamente.

**Tratamento:**
```php
// Converter para comparação no PHP após busca pelo mês/ano
$horasNoMes = array_filter($horas, function($h) use ($inicio, $fim) {
    $data = \DateTime::createFromFormat('d/m/Y', $h->data);
    return $data && $data >= $inicio && $data <= $fim;
});
```

> **Longo prazo:** Adicionar coluna `data_date DATE GENERATED ALWAYS AS (TO_DATE(data, 'DD/MM/YYYY')) STORED` em migration futura.

---

### EC-03 — Cliente sem contrato em `contratos_horas`

**Situação:** Cliente ativo sem nenhum registro em `contratos_horas`. O gauge de consumo não pode ser calculado.

**Tratamento:** Exibir card "Sem contrato de horas vinculado" no lugar do gauge. Não exibir `0%` ou divisão por zero. No portal: ocultar a aba "Consumo do Contrato".

---

### EC-04 — Ticket sem técnico responsável (`idtecnico_responsavel IS NULL`)

**Situação:** Ticket não atribuído. Ao agrupar por técnico, `LEFT JOIN users` retorna `NULL` no nome.

**Tratamento:**
```sql
COALESCE(u.name, 'Não atribuído') AS tecnico
```
Exibir como categoria separada "Não atribuído" nos gráficos e tabelas.

---

### EC-05 — `sla_percentual_consumido` NULL

**Situação:** Ticket criado antes da implantação do SLA engine — campo NULL.

**Tratamento:**
```php
// Excluir de médias e taxas de SLA
$conditions['tickets.sla_percentual_consumido IS NOT'] = null;
```
Exibir na interface: "X tickets excluídos por ausência de dados de SLA" abaixo dos totalizadores.

---

### EC-06 — Exportação de relatório financeiro com as três entidades

**Situação:** O relatório financeiro consolida `faturamento`, `faturas` (locações) e `financeiro_lancamentos`. A UNION pode retornar colunas incompatíveis.

**Tratamento:** Normalizar via query PHP em camadas — não usar UNION SQL diretamente. Usar `RelatorioService::financeiroMensal()` que itera cada fonte e monta array uniforme:

```php
$linhas = array_merge(
    $this->_linhasFaturamento($filtros),   // faturamento → tipo = 'servico'/'os'
    $this->_linhasFaturas($filtros),       // faturas → tipo = 'locacao'
    $this->_linhasLancamentos($filtros),   // financeiro_lancamentos → tipo = 'manual'
);
usort($linhas, fn($a, $b) => $a['data'] <=> $b['data']);
```

---

### EC-07 — Admin com múltiplas empresas

**Situação:** `admin = 1` pode acessar qualquer `idempresa`. O filtro de empresa deve ser editável para admins.

**Tratamento:**
```php
if ($this->Auth->user('admin') == 1) {
    // Exibir select de empresa no filtro
    $empresas = $this->Empresas->find('list')->toArray();
    $this->set('empresas', $empresas);
    $idempresa = $this->request->getQuery('idempresa', $this->Auth->user('idempresa'));
} else {
    $idempresa = $this->Auth->user('idempresa');
}
```

---

### EC-08 — Relatório gerado em horário de pico (> 10k linhas)

**Situação:** Usuário clica em exportar com filtro amplo (ano inteiro, todos clientes). Query pode exceder 30s e dar timeout.

**Tratamento:**
1. Antes da query, estimar volume: `SELECT COUNT(*) FROM tickets WHERE ...`
2. Se COUNT > 10.000: acionar fluxo assíncrono (Seção 9.4) e retornar JSON `{"async": true, "mensagem": "..."}`
3. Se COUNT ≤ 10.000: executar sincrono e stream do arquivo.

```php
$count = $this->Tickets->find()->where($conditions)->count();
if ($count > 10000) {
    $this->_agendarExportacaoAsync($relatorio, $formato, $filtros);
    return $this->response->withType('application/json')
        ->withStringBody(json_encode(['async' => true, 'mensagem' => 'Relatório sendo gerado. Você receberá um e-mail.']));
}
```

---

### EC-09 — Cache stale após mudança de dados críticos

**Situação:** Cache de 5 min pode mostrar dados desatualizados após um ticket ser fechado ou fatura paga.

**Tratamento:** Invalidar cache seletivamente em eventos críticos:

```php
// No TicketsController::alterarSituacao() — após fechar ticket:
$this->RelatoriosCache->invalidarPorPrefixo('painel_' . $idempresa);

// No FaturamentoController::alterarStatus() — após pagar:
$this->RelatoriosCache->invalidarPorPrefixo('painel_' . $idempresa);
```

```php
// RelatoriosCacheTable.php
public function invalidarPorPrefixo(string $prefixo): void
{
    $this->deleteAll(['chave LIKE' => $prefixo . '%']);
}
```

---

### EC-10 — Acesso direto à URL de exportação via GET (CSRF bypass)

**Situação:** Usuário tenta acessar `/relatorios/exportar?relatorio=financeiro&formato=csv` via GET para contornar verificação de role.

**Tratamento:**
```php
public function exportar()
{
    // Aceitar apenas POST — rejeitar GET/DELETE/etc.
    $this->request->allowMethod(['post']);
    // Security component já valida CSRF token em todos os POSTs
    // ...
}
```

O `Security` component do CakePHP está habilitado no `AppController` e já rejeita requisições POST sem token CSRF válido. Não é necessário token adicional.

---

### EC-11 — Gráfico com dataset vazio para período específico

**Situação:** Em um gráfico de linha com 6 meses, um mês específico não tem dados. Chart.js conecta pontos pulando meses vazios.

**Tratamento:** Gerar array completo de meses no PHP, preenchendo com `null` (não `0`) onde não há dados:

```php
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $meses[$mes] = $dadosPorMes[$mes] ?? null; // null = ponto sem dados
}
```

```javascript
// Chart.js respeita null como "sem dado" e não conecta a linha
datasets: [{ data: dados, spanGaps: false }]
```

---

## Apêndice A — Estrutura de Arquivos

```
src/
  Controller/
    RelatoriosController.php          ← ERP: index, tickets, sla, financeiro, contratos, agenda, exportar
    Portal/
      RelatoriosController.php        ← Portal: index, atendimentos, sla, contrato, faturas

  Service/
    Relatorio/
      RelatorioService.php            ← painelSnapshot(), ticketsAgrupados(), slaAgrupado(), etc.
      RelatorioExportService.php      ← gerar(), _exportarCsv(), _exportarXlsx(), _exportarPdf()

  Model/
    Table/
      RelatoriosCacheTable.php        ← invalidarPorPrefixo(), upsert()

  Template/
    Relatorios/
      index.ctp
      tickets.ctp
      sla.ctp
      financeiro.ctp
      contratos.ctp
      agenda.ctp
      pdf/
        tickets.ctp                   ← template mPDF
        sla.ctp
        financeiro.ctp
    Portal/
      Relatorios/
        index.ctp
        atendimentos.ctp
        sla.ctp
        contrato.ctp
        faturas.ctp

config/
  Migrations/
    20260401000001_CreateRelatoriosCache.php
```

---

## Apêndice B — Migration: `relatorios_cache`

```php
<?php
// config/Migrations/20260401000001_CreateRelatoriosCache.php
use Migrations\AbstractMigration;

class CreateRelatoriosCache extends AbstractMigration
{
    public function change(): void
    {
        $this->table('relatorios_cache', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id',        'integer',  ['identity' => true])
            ->addColumn('chave',     'string',   ['limit' => 100, 'null' => false])
            ->addColumn('payload',   'jsonb',    ['null' => false])
            ->addColumn('gerado_em', 'timestamp',['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('expira_em', 'timestamp',['null' => false])
            ->addIndex(['chave'],    ['unique' => true, 'name' => 'idx_relatorios_cache_chave'])
            ->create();
    }
}
```

---

## Apêndice C — Rota de Exportação

Adicionar em `config/routes.php` dentro do escopo existente:

```php
$routes->scope('/relatorios', ['controller' => 'Relatorios'], function ($routes) {
    $routes->get('/',            ['action' => 'index']);
    $routes->get('/tickets',     ['action' => 'tickets']);
    $routes->get('/sla',         ['action' => 'sla']);
    $routes->get('/financeiro',  ['action' => 'financeiro']);
    $routes->get('/contratos',   ['action' => 'contratos']);
    $routes->get('/agenda',      ['action' => 'agenda']);
    $routes->post('/exportar',   ['action' => 'exportar']);
    $routes->get('/download/:hash', ['action' => 'download'])
        ->setPatterns(['hash' => '[a-zA-Z0-9]{20,30}']);
});

$routes->prefix('portal', function ($routes) {
    $routes->scope('/relatorios', ['controller' => 'Portal/Relatorios'], function ($routes) {
        $routes->get('/',              ['action' => 'index']);
        $routes->get('/atendimentos',  ['action' => 'atendimentos']);
        $routes->get('/sla',           ['action' => 'sla']);
        $routes->get('/contrato',      ['action' => 'contrato']);
        $routes->get('/faturas',       ['action' => 'faturas']);
        $routes->post('/exportar',     ['action' => 'exportar']);
    });
});
```
