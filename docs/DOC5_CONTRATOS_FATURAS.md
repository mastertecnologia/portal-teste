# Documento 5 — Especificação do Módulo: Contratos e Faturas

## 1. Objetivo

Consolidar em um único módulo a gestão de contratos de clientes e o histórico de cobranças/faturas, com visibilidade diferenciada entre a equipe interna (ERP) e o cliente (portal).

---

## 2. Personas e Acessos

| Persona | Visão | Ações permitidas |
|---------|-------|----------------|
| Financeiro PGM | Todos os contratos/faturas de todas as empresas | CRUD completo, geração de cobrança |
| Gestor PGM | Visualização completa | Somente leitura + aprovação |
| Técnico PGM | Visualização dos contratos dos seus clientes | Somente leitura |
| Cliente | Contratos e faturas da própria empresa | Visualização + download |

---

## 3. Sub-módulo: Contratos

### 3.1 Tabela principal: `clicontratos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | PK | Identificador |
| `idcliente` | FK clientes | Cliente titular do contrato |
| `numero_contrato` | varchar | Número/código do contrato |
| `descricao` | text | Descrição do contrato |
| `tipo_contrato` | varchar | Suporte / Locação / Desenvolvimento / Misto |
| `vigencia_inicio` | date | Início da vigência |
| `vigencia_fim` | date | Término da vigência (null = indeterminado) |
| `valor_mensal` | decimal | Valor mensal contratado |
| `valor_total` | decimal | Valor total do contrato |
| `horas_incluidas` | integer | Horas técnicas inclusas/mês |
| `situacao` | varchar | Ativo / Suspenso / Encerrado / Em renovação |
| `observacoes` | text | Observações internas |
| `created` | timestamp | Data de cadastro |
| `modified` | timestamp | Última atualização |

### 3.2 Cobertura do Contrato

Campos que definem o que está coberto:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `cobre_suporte_remoto` | boolean | Atendimento remoto incluso |
| `cobre_suporte_presencial` | boolean | Atendimento presencial incluso |
| `cobre_manutencao` | boolean | Manutenção preventiva inclusa |
| `cobre_backup` | boolean | Gestão de backup inclusa |
| `cobre_monitoramento` | boolean | Monitoramento incluso |
| `modulos_cobertos` | jsonb | Array de módulos/sistemas cobertos |
| `limite_chamados_mes` | integer | Máx. tickets por mês (null = ilimitado) |
| `nivel_sla` | varchar | FK → `sla_policies.nome` |

### 3.3 Vigência e Renovação

| Estado | Critério | Ação |
|--------|---------|------|
| **Ativo** | `vigencia_fim IS NULL OR vigencia_fim >= hoje` | Normal |
| **A vencer** | `vigencia_fim` entre hoje e +30 dias | Alertar financeiro |
| **Encerrado** | `vigencia_fim < hoje` | Bloquear novos tickets SLA |
| **Em renovação** | `situacao = 'em_renovacao'` | Aguardando assinatura |

---

### 3.4 Visão ERP — Listagem de Contratos

**URL:** `/clicontratos/index`

**Colunas:**

| Coluna | Descrição |
|--------|-----------|
| Nº Contrato | Número e tipo |
| Cliente | Razão social |
| Vigência | De / Até (ou "Indeterminado") |
| Valor Mensal | Formatado em BRL |
| Horas Inclusas | h/mês |
| SLA | Política aplicada |
| Status | Badge colorido |
| Ações | Ver / Editar / Encerrar |

**Filtros:** Cliente, Tipo, Status, Vigência (range), Valor mínimo/máximo.

### 3.5 Visão ERP — Detalhe do Contrato

```
┌────────────────────────────────────────┐
│  Identificação (número, cliente, tipo) │
├────────────────────────────────────────┤
│  Vigência e Valor                      │
├────────────────────────────────────────┤
│  Cobertura (checkboxes + módulos)      │
├────────────────────────────────────────┤
│  Histórico de Consumo de Horas         │  ← últimos 6 meses
├────────────────────────────────────────┤
│  Tickets vinculados                    │  ← count + link
├────────────────────────────────────────┤
│  Faturas do Contrato                   │  ← tabela inline
├────────────────────────────────────────┤
│  Observações / Notas internas          │
└────────────────────────────────────────┘
```

### 3.6 Visão Cliente — Contratos

**URL:** `/portal/contratos`

Exibe somente contratos onde `clicontratos.idcliente = sessao.idcliente`.

**Informações visíveis:**

- Número, tipo, vigência, status
- Cobertura em formato legível (lista de itens cobertos)
- Horas inclusas e consumidas no mês atual
- Política SLA aplicada
- **Botão:** Abrir ticket (atalho vinculado ao contrato)

**Informações ocultas para cliente:**

- Valor mensal/total
- Observações internas
- Dados de cobrança

---

## 4. Sub-módulo: Faturas

### 4.1 Tabela principal: `faturas` (já existente)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | PK | Identificador |
| `idcliente` | FK | Cliente |
| `idcontrato` | FK `clicontratos` | Contrato de origem (nullable) |
| `numero_fatura` | varchar | Número sequencial |
| `competencia` | date | Mês/ano de referência |
| `valor` | decimal | Valor total |
| `desconto` | decimal | Desconto aplicado |
| `valor_final` | decimal | Valor após desconto |
| `vencimento` | date | Data de vencimento |
| `situacao` | varchar | Pendente / Paga / Vencida / Cancelada |
| `tipo` | varchar | Mensalidade / Avulso / OS / Locação |
| `descricao` | text | Descrição da cobrança |
| `arquivo_pdf` | varchar | Caminho do PDF no servidor |
| `arquivo_boleto` | varchar | Caminho/URL do boleto |
| `created` | timestamp | Emissão |

### 4.2 Cobranças (detalhamento da fatura)

Tabela `faturamento_itens`:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idfaturamento` | FK faturas | Fatura pai |
| `descricao` | varchar | Descrição do item |
| `quantidade` | decimal | Qtd |
| `valor_unitario` | decimal | Preço unitário |
| `valor_total` | decimal | Total do item |
| `tipo_item` | varchar | Hora / Produto / Serviço / Taxa |

### 4.3 Visão ERP — Listagem de Faturas

**URL:** `/faturamento/index` (existente, a ser estendida)

**Colunas:**

| Coluna | Descrição |
|--------|-----------|
| Nº Fatura | Número sequencial |
| Cliente | Razão social |
| Competência | MM/AAAA |
| Vencimento | Data + badge de prazo |
| Valor | R$ total |
| Tipo | Badge de tipo |
| Status | Pendente / Paga / Vencida |
| Ações | Ver / Editar / PDF / Boleto / Receber |

**Filtros:** Cliente, Competência (range), Vencimento (range), Tipo, Status, Valor mín/máx.

**Ações em lote:**
- Marcar como pagas
- Gerar boletos em lote
- Exportar para Excel/PDF

### 4.4 Visão ERP — Detalhe da Fatura

```
┌─────────────────────────────────────────┐
│  Cabeçalho (nº, cliente, competência)   │
├─────────────────────────────────────────┤
│  Itens da cobrança (tabela)             │
├─────────────────────────────────────────┤
│  Totais (subtotal, desconto, total)     │
├─────────────────────────────────────────┤
│  Vencimento + Status                    │
├─────────────────────────────────────────┤
│  Ações: Gerar PDF | Boleto | Receber    │
├─────────────────────────────────────────┤
│  Histórico de pagamento                 │
└─────────────────────────────────────────┘
```

**URL:** `/faturamento/view/:id`

### 4.5 Visão Cliente — Faturas

**URL:** `/portal/faturas`

Exibe somente faturas onde `faturas.idcliente = sessao.idcliente`.

**Colunas visíveis:**

| Coluna | Descrição |
|--------|-----------|
| Nº Fatura | — |
| Referência | Mês/ano |
| Descrição | Resumo do que é cobrado |
| Vencimento | Data + badge |
| Valor | R$ |
| Status | Pendente / Paga / Vencida |
| Ações | Ver detalhes / Download PDF / Boleto |

**Filtros disponíveis para o cliente:**
- Período (range de mês/ano)
- Status (Pendente / Paga / Vencida)

---

## 5. Downloads

### 5.1 PDF da Fatura

| Item | Detalhe |
|------|---------|
| Gerado por | mPDF (`src/Template/Faturamento/imprimir.ctp`) |
| URL (ERP) | `/faturamento/imprimir/:id` |
| URL (cliente) | `/portal/faturas/:id/pdf` |
| Conteúdo | Logo, dados cliente, itens, totais, dados de pagamento |
| Permissão | `faturamento.view` (ERP) / `faturas.cliente_download` (cliente) |

### 5.2 Boleto

| Item | Detalhe |
|------|---------|
| Disponibilidade | Quando `faturas.arquivo_boleto` preenchido |
| URL (ERP) | `/faturamento/boleto/:id` |
| URL (cliente) | `/portal/faturas/:id/boleto` |
| Tipo | Download direto do arquivo armazenado |

---

## 6. Cobranças — Regras de Negócio

### 6.1 Status e transições

```
Pendente ──► Paga
    │
    └──► Vencida (automático quando vencimento < hoje e situacao = Pendente)
              │
              └──► Paga (ao registrar recebimento)
              └──► Cancelada (por ação do financeiro)
```

### 6.2 Geração automática de faturas mensais

- Acionada pelo `FechamentoMensalCommand` (CLI: `bin/cake fechamento_mensal`)
- Gera uma fatura por cliente ativo com contrato vigente
- Consolida OS faturáveis do mês na fatura

### 6.3 Geração a partir de OS

- URL: `POST /faturamento/gerar-de-os/:idordem`
- Cria um faturamento com os itens da ordem de serviço
- Permite ao financeiro incluir cobranças avulsas de horas técnicas

---

## 7. Alertas e Notificações

| Evento | Destinatário | Canal |
|--------|-------------|-------|
| Fatura gerada | Cliente | E-mail |
| Fatura vencida (+1 dia) | Cliente + Financeiro | E-mail |
| Contrato a vencer (+30 dias) | Financeiro | Notificação interna |
| Contrato encerrado | Financeiro + Gestor | Notificação interna |
| Pagamento registrado | Financeiro | Log interno |

---

## 8. Integração com outros módulos

| Módulo | Relação |
|--------|---------|
| Ordens de Serviço | OS gera faturamento via `gerarDeOS` |
| Tickets | Tickets vinculados a contratos consomem horas cobertas |
| SLA | Contratos definem política SLA ativa para o cliente |
| Histórico de Atendimentos | Exibe consumo de horas do contrato no período |
| Relatórios | Fornece dados de inadimplência, recorrência e cobertura |

---

## 9. Considerações de Segurança

- Cliente jamais vê valores de outros clientes (`WHERE idcliente = sessao.idcliente` obrigatório)
- Campos sensíveis (observações internas, margem, custo) nunca expostos na visão cliente
- Download de PDF/boleto gera log no `ticket_histories` (tipo `fatura_download`)
- Token de download com expiração para links compartilháveis
