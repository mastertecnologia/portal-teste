# Especificação Funcional — Módulo: Contratos e Faturas

**Projeto:** Portal PGM / Portal do Cliente  
**Stack:** CakePHP 3.7 · PostgreSQL · Bootstrap 3 · jQuery · DataTables · mPDF  
**Data:** 2026-03-31  
**Status:** Especificação para implementação

---

## 1. Objetivo do Módulo

### Problema atual

Hoje o sistema trata contratos e faturas como estruturas exclusivas do back-office:

| Entidade | Situação atual | Problema |
|---------|---------------|---------|
| `clicontratos` | Cadastrado dentro de `ClientesController::edit` | Não existe tela de listagem ou detalhe própria; cliente nunca vê |
| `faturamento` | Controller bloqueado para `role = 1` | Cliente não acessa seus documentos de cobrança |
| `faturas` (locação) | `imprimir` permite `role = 1` mas com bug de `idcliente` | Sem listagem própria para o cliente |
| SLA contratual | Ligado a `sla_policies` por empresa | Não é exibido junto ao contrato do cliente |
| Franquia de horas | Não existe campo dedicado | Consumo de horas invisível para o cliente |

### O que este módulo entrega

1. **ERP:** visão unificada dos contratos de cada cliente (hoje enterrados em `/clientes/edit/:id`) com detalhe de itens, franquia, cobertura e SLA; e listagem de documentos de faturamento por cliente.
2. **Portal cliente:** self-service para consultar contratos ativos, acompanhar franquia de horas, ver cobranças e baixar documentos (PDF / boleto / link público), sem precisar ligar para o suporte.

---

## 2. Diferença entre Contratos e Faturas

É fundamental entender que o sistema tem **três tabelas distintas** que o usuário pode chamar de "fatura". Este módulo cobre as três com papéis bem definidos:

| Entidade | Tabela | O que representa | Controller atual |
|---------|--------|-----------------|-----------------|
| **Itens de contrato** | `clicontratos` | Linhas do contrato: produto contratado, qtde, valor unitário, vigência | `ClicontratosController` |
| **Documento de faturamento** | `faturamento` | Cobrança gerada a partir de OS ou manual; ciclo rascunho→pago | `FaturamentoController` |
| **Fatura de locação** | `faturas` | Locação de equipamentos com itens, aprovação por hash, recibo | `FaturasController` |

### Relação entre as entidades

```
clicontratos (N linhas por cliente)
    └── define o que está coberto e o preço recorrente

faturamento (1 documento por cobrança)
    ├── gerado manualmente ou via OS (gerarDeOS)
    ├── possui itens (faturamento_itens)
    └── ao ser marcado "pago" → gera financeiro_lancamentos

faturas (locação de equipamentos)
    ├── ciclo independente com aprovação/rejeição por hash público
    └── possui itens de equipamento (faturasitens)
```

**Para o módulo Contratos e Faturas:**
- **Contratos** = visão consolidada do cliente baseada em `clicontratos` (itens agrupados por cliente)
- **Faturas** = documentos de `faturamento` (cobrança de serviços) e `faturas` (locação), conforme o tipo do cliente

---

## 3. Perfis que Acessam

| Perfil | `role` | O que acessa |
|--------|--------|-------------|
| **Financeiro PGM** | `role = 0` | CRUD completo de itens de contrato; listagem e edição de faturamento; alterar status |
| **Gestor PGM** | `role = 0` + papel RBAC `gestor` | Visualização completa; sem edição de valores |
| **Técnico PGM** | `role = 0` + papel RBAC `tecnico` | Somente leitura dos contratos dos clientes atendidos |
| **Admin** | `admin = 1` | Irrestrito |
| **Cliente** | `role = 1` | Contratos da própria empresa (sem valores internos); faturas da empresa (somente leitura + download) |

---

## 4. Permissões RBAC

### 4.1 Códigos a criar

```sql
INSERT INTO rbac_permissions (code, description, controller, action) VALUES
  -- Contratos
  ('contratos.view',          'Listar contratos de clientes (ERP)',           'Contratos', 'index'),
  ('contratos.detail',        'Ver detalhe do contrato (ERP)',                'Contratos', 'view'),
  ('contratos.manage',        'Criar/editar itens de contrato',               'Contratos', 'add,edit'),
  ('contratos.delete',        'Excluir item de contrato',                     'Contratos', 'delete'),
  ('contratos.sync',          'Sincronizar contrato com ERP (SOAP)',          'Contratos', 'sincronizar'),
  ('contratos.cliente_view',  'Cliente vê seus contratos',                    'Contratos', 'cliente'),
  ('contratos.cliente_detail','Cliente vê detalhe do contrato',               'Contratos', 'clienteView'),
  -- Faturamento
  ('faturas.view',            'Listar documentos de faturamento (ERP)',       'ContFaturas', 'faturas'),
  ('faturas.manage',          'Criar/editar documento de faturamento',        'ContFaturas', 'addFatura,editFatura'),
  ('faturas.alterar_status',  'Alterar status do documento de faturamento',   'ContFaturas', 'alterarStatus'),
  ('faturas.delete',          'Excluir rascunho de faturamento',              'ContFaturas', 'deleteFatura'),
  ('faturas.cliente_view',    'Cliente vê suas cobranças',                    'ContFaturas', 'clienteFaturas'),
  ('faturas.cliente_download','Cliente baixa PDF/boleto',                     'ContFaturas', 'clienteDownload');
```

### 4.2 Atribuição por papel

| Permissão | admin | gestor | financeiro | tecnico | cliente_full | cliente_basico |
|-----------|:-----:|:------:|:----------:|:-------:|:------------:|:--------------:|
| `contratos.view` | ✅ | ✅ | ✅ | ✅ | — | — |
| `contratos.detail` | ✅ | ✅ | ✅ | ✅ | — | — |
| `contratos.manage` | ✅ | — | ✅ | — | — | — |
| `contratos.delete` | ✅ | — | ✅ | — | — | — |
| `contratos.sync` | ✅ | — | ✅ | — | — | — |
| `contratos.cliente_view` | — | — | — | — | ✅ | ✅ |
| `contratos.cliente_detail` | — | — | — | — | ✅ | ✅ |
| `faturas.view` | ✅ | ✅ | ✅ | — | — | — |
| `faturas.manage` | ✅ | — | ✅ | — | — | — |
| `faturas.alterar_status` | ✅ | — | ✅ | — | — | — |
| `faturas.delete` | ✅ | — | ✅ | — | — | — |
| `faturas.cliente_view` | — | — | — | — | ✅ | ✅ |
| `faturas.cliente_download` | — | — | — | — | ✅ | — |

### 4.3 `isAuthorized()` no controller

```php
public function isAuthorized($user) {
    $action = $this->request->getParam('action');
    $role   = (int)($user['role'] ?? -1);

    if ($role === 0) {
        $readActions  = ['index', 'view', 'faturas', 'viewFatura'];
        $writeActions = ['add', 'edit', 'delete', 'addFatura', 'editFatura',
                         'deleteFatura', 'alterarStatus', 'sincronizar'];
        if (in_array($action, $readActions, true)) {
            return $this->Rbac->hasAny(['contratos.view','contratos.detail',
                                        'faturas.view']);
        }
        if (in_array($action, $writeActions, true)) {
            return $this->Rbac->hasAny(['contratos.manage','contratos.delete',
                                        'faturas.manage','faturas.alterar_status',
                                        'faturas.delete','contratos.sync']);
        }
        return (bool)($user['admin'] ?? false);
    }

    if ($role === 1) {
        $clienteActions = ['cliente', 'clienteView', 'clienteFaturas', 'clienteDownload'];
        return in_array($action, $clienteActions, true);
    }

    return (bool)($user['admin'] ?? false);
}
```

---

## 5. Regras ABAC

### 5.1 Escopo por empresa (role = 0)

Toda query filtra `idempresa = sessao.idempresa`. Admin pode ver qualquer empresa via dropdown.

```php
// Contratos
$q = $this->Clicontratos->find()->where([
    'Clicontratos.idempresa' => $this->Auth->user('idempresa'),
]);

// Faturamento
$q = $this->Faturamento->find()->where([
    'Faturamento.idempresa' => $this->Auth->user('idempresa'),
]);
```

### 5.2 Escopo por cliente (role = 1)

```php
// Resolve o idcliente dentro da empresa ativa (mesma lógica de TicketsController)
$idcliente  = (int)$this->Auth->user('idcliente');
$idempresa  = (int)$this->Auth->user('idempresa');
$clienteBase = $this->Clientes->findById($idcliente)->first();

if ($clienteBase->tipo == C_ClientesTipoJuridica) {
    $clienteAtual = $this->Clientes
        ->findByCnpj(removeCaracteres($clienteBase->cnpj))
        ->where(['idempresa' => $idempresa])->first();
} else {
    $clienteAtual = $this->Clientes
        ->findByCpf(removeCaracteres($clienteBase->cpf))
        ->where(['idempresa' => $idempresa])->first();
}

if (empty($clienteAtual)) {
    $this->Flash->error('Empresa não encontrada para sua conta.');
    return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
}

// Aplicar em todas as queries do cliente:
$q->where(['idcliente' => $clienteAtual->id]);
```

### 5.3 Proteção de acesso a registro individual (role = 1)

Ao acessar `/contratos/cliente-view/:id` ou `/contratos/cliente-faturas/:id`:

```php
// Verificar se o registro pertence ao cliente resolvido:
if ((int)$registro->idcliente !== (int)$clienteAtual->id
    || (int)$registro->idempresa !== $idempresa) {
    $this->Flash->error('Você não tem acesso a este registro.');
    return $this->redirect(['action' => 'cliente']);
}
```

### 5.4 Campos financeiros ocultos para role = 1

Os seguintes campos **nunca chegam à view** do cliente:

| Campo | Tabela | Motivo |
|-------|--------|--------|
| `vlunit` | clicontratos | Preço unitário contratado |
| `vltotal` | clicontratos | Valor total do item de contrato |
| `valor_subtotal` | faturamento | Antes de desconto |
| `valor_desconto` | faturamento | Desconto aplicado |
| `descricao` interna | faturamento | Notas internas do financeiro |

O controller usa `$this->set('mostrarValores', ($role === 0))` e o template verifica antes de exibir cada coluna.

---

## 6. Listagem de Contratos

### 6.1 URLs

| Contexto | URL | Action |
|---------|-----|--------|
| ERP | `GET /contratos/index` | `ContratosController::index` |
| Portal cliente | `GET /contratos/cliente` | `ContratosController::cliente` |

### 6.2 Como os contratos são estruturados no banco

A tabela `clicontratos` armazena **itens de contrato** — uma linha por produto/serviço contratado. Um cliente pode ter várias linhas. O módulo agrupa essas linhas por cliente e apresenta o conjunto como "contrato do cliente".

```
Cliente: Empresa ABC Ltda
  ├── Item 1: Suporte Remoto N1  —  10h/mês  —  R$ 150,00/h
  ├── Item 2: Licença ERP        —  1 unid   —  R$ 800,00/mês
  └── Item 3: Backup Cloud       —  1 TB     —  R$ 200,00/mês
Total mensal: R$ 2.500,00
```

### 6.3 Campos da tabela `clicontratos`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | PK | Identificador do item |
| `idcliente` | FK | Cliente titular |
| `idempresa` | FK | Empresa prestadora |
| `codproduto` | varchar | Código do produto (join com `produtos`) |
| `descricao` | varchar | Descrição do serviço/item |
| `qtde` | decimal | Quantidade (horas, unidades, GB, etc.) |
| `vlunit` | decimal | Valor unitário |
| `vltotal` | decimal | Total = qtde × vlunit |
| `dtcontratacao` | date | Início da vigência |
| `dtvalidade` | date | Término (null = indeterminado) |
| `dtcancelamento` | date | Data de cancelamento (se houver) |
| `erp` | varchar | Referência no ERP legado |

### 6.4 Campos novos a criar via migration

```sql
-- Campos para enriquecer o módulo sem quebrar o fluxo atual
ALTER TABLE clicontratos
    ADD COLUMN IF NOT EXISTS tipo_item      VARCHAR(40)  DEFAULT 'servico',
    ADD COLUMN IF NOT EXISTS franquia_horas DECIMAL(8,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS nivel_sla      VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS observacoes    TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ativo          BOOLEAN      NOT NULL DEFAULT TRUE;

-- tipo_item: 'servico' | 'licenca' | 'hardware' | 'cloud' | 'suporte'
-- franquia_horas: horas mensais inclusas neste item (null = ilimitado ou N/A)
-- nivel_sla: referência à sla_policies.nome (soft FK textual, não FK real)
-- observacoes: notas internas (não visível para role=1)
-- ativo: false quando dtcancelamento preenchida ou vencida
```

### 6.5 View da listagem ERP — agrupada por cliente

**Estrutura:** tabela com um grupo por cliente, expansível.

| Coluna | Fonte | Observação |
|--------|-------|-----------|
| Cliente | `clientes.razaosocial` ou `nome` (PF) | Nome conforme tipo PJ/PF |
| Itens ativos | COUNT WHERE ativo = true | — |
| Vigência | `MIN(dtcontratacao)` a `MAX(dtvalidade)` | "Indeterminado" se dtvalidade null |
| Valor mensal | SUM(vltotal) WHERE ativo = true | Oculto na visão cliente |
| SLA | Lista de `nivel_sla` distintos | — |
| Status | Ativo / Parcial / Encerrado | Ver seção 6.8 |
| Ações | Ver detalhes / Adicionar item | `contratos.detail`, `contratos.manage` |

**Filtros da listagem ERP:**

| Filtro | Tipo | Campo |
|--------|------|-------|
| Cliente | Select com busca | `clicontratos.idcliente` |
| Status | Select | Calculado (ativo/parcial/encerrado) |
| Vigência | Date range | `dtvalidade` |
| Tipo de item | Select | `tipo_item` (campo novo) |

### 6.6 View da listagem Portal Cliente

**URL:** `/contratos/cliente`

Exibe os itens de contrato do `clienteAtual.id`:

| Coluna | Fonte | Observação |
|--------|-------|-----------|
| Serviço/Item | `descricao` | — |
| Tipo | `tipo_item` | Badge (Suporte / Licença / Hardware / Cloud) |
| Franquia | `franquia_horas` | "10h/mês" ou "—" |
| SLA | `nivel_sla` | Nome da política ou "—" |
| Vigência início | `dtcontratacao` | `d/m/Y` |
| Vigência fim | `dtvalidade` | `d/m/Y` ou "Indeterminado" |
| Status | Calculado | Badge Ativo/Encerrado |
| Ações | — | "Ver detalhes" |

**Campos ocultos para cliente:** `codproduto`, `vlunit`, `vltotal`, `observacoes`

### 6.7 Cálculo de status do contrato

```php
protected function _statusContrato($item): string {
    $hoje = date('Y-m-d');

    if (!empty($item->dtcancelamento)) {
        $dc = $item->dtcancelamento instanceof \DateTimeInterface
            ? $item->dtcancelamento->format('Y-m-d')
            : $item->dtcancelamento;
        if ($dc <= $hoje) return 'cancelado';
    }

    if (!empty($item->dtvalidade)) {
        $dv = $item->dtvalidade instanceof \DateTimeInterface
            ? $item->dtvalidade->format('Y-m-d')
            : $item->dtvalidade;
        if ($dv < $hoje)  return 'encerrado';
        if ($dv <= date('Y-m-d', strtotime('+30 days'))) return 'a_vencer';
    }

    return 'ativo';
}
```

| Status | Cor Bootstrap | Critério |
|--------|:------------:|---------|
| `ativo` | `success` | dtvalidade null ou > hoje +30 dias |
| `a_vencer` | `warning` | dtvalidade entre hoje e hoje +30 dias |
| `encerrado` | `default` | dtvalidade < hoje |
| `cancelado` | `danger` | dtcancelamento preenchida e <= hoje |

---

## 7. Tela Detalhe do Contrato

### 7.1 URLs

| Contexto | URL |
|---------|-----|
| ERP | `GET /contratos/view/:idcliente` |
| Portal cliente | `GET /contratos/cliente-view` (sem id — exibe do próprio cliente) |

### 7.2 Estrutura da tela ERP

```
┌──────────────────────────────────────────────────────────────────┐
│  [← Voltar]  Contrato — Empresa ABC Ltda                        │
│  CNPJ: 12.345.678/0001-99   Status: [ATIVO]                     │
├──────────────────────────────────────────────────────────────────┤
│  ITENS DO CONTRATO                               [+ Novo item]  │
│                                                                  │
│  Produto  │ Descrição    │ Qtde │ Vl.Unit │ Vl.Total │ Vigência │
│  SRV001   │ Suporte N1   │ 10h  │ R$150   │ R$1.500  │ 01/2026–  │
│  LIC001   │ Licença ERP  │  1   │ R$800   │ R$800    │ 01/2026–  │
│  ─────────────────────────────────────────────────────────────── │
│                                           Total: R$2.300,00/mês  │
├──────────────────────────────────────────────────────────────────┤
│  FRANQUIA E CONSUMO (ver seção 8)                               │
├──────────────────────────────────────────────────────────────────┤
│  SERVIÇOS COBERTOS (ver seção 9)                                │
├──────────────────────────────────────────────────────────────────┤
│  SLA CONTRATUAL (ver seção 10)                                  │
├──────────────────────────────────────────────────────────────────┤
│  HISTÓRICO DE FATURAMENTO                                        │
│  (últimos 6 documentos de faturamento para este cliente)        │
└──────────────────────────────────────────────────────────────────┘
```

### 7.3 Estrutura da tela Portal Cliente

```
┌──────────────────────────────────────────────────────────────────┐
│  Meu Contrato                                                    │
│  Empresa ABC Ltda  —  Status: [ATIVO]                           │
├──────────────────────────────────────────────────────────────────┤
│  SERVIÇOS CONTRATADOS                                           │
│  (tabela sem vlunit/vltotal)                                    │
├──────────────────────────────────────────────────────────────────┤
│  FRANQUIA DE HORAS (ver seção 8)                                │
├──────────────────────────────────────────────────────────────────┤
│  O QUE ESTÁ COBERTO (ver seção 9)                               │
├──────────────────────────────────────────────────────────────────┤
│  ACORDO DE NÍVEL DE SERVIÇO (ver seção 10)                      │
└──────────────────────────────────────────────────────────────────┘
```

### 7.4 Ações disponíveis na tela de detalhe

| Ação | Quem pode | URL |
|------|----------|-----|
| Adicionar item | `contratos.manage` | `/clicontratos/add/:idcliente` (existente) |
| Editar item | `contratos.manage` | `/clicontratos/edit/:id` (existente) |
| Excluir item | `contratos.delete` | `POST /clicontratos/delete/:id` (existente) |
| Sincronizar com ERP | `contratos.sync` | `POST /contratos/sincronizar/:idcliente` |
| Ver faturas | `faturas.view` | `/contratos/faturas/:idcliente` |
| Abrir ticket | Sempre (role = 0 e 1) | `/tickets/add` |

---

## 8. Franquia e Consumo

### 8.1 Definição

**Franquia** é a quantidade de horas técnicas mensais inclusas em um item de contrato (campo `franquia_horas`). Quando o cliente consome mais horas do que a franquia contratada, o excedente pode ser cobrado separadamente.

### 8.2 Query de consumo do mês atual

```php
// Horas consumidas no mês pelo cliente (reutiliza TicketshorasTable::minutosCliente)
$inicioMes = '01/' . date('m/Y');
$fimMes    = date('t') . '/' . date('m/Y');
$minutos   = $this->Ticketshoras->minutosCliente(
    $clienteAtual->id, $inicioMes, $fimMes
);
$horasConsumidas = round($minutos / 60, 2);

// Franquia total do cliente (soma dos itens ativos com franquia_horas)
$franquiaTotal = $this->Clicontratos->find()
    ->where([
        'idcliente'      => $clienteAtual->id,
        'idempresa'      => $idempresa,
        'ativo'          => true,
        'franquia_horas IS NOT' => null,
    ])
    ->select(['soma' => 'SUM(franquia_horas)'])
    ->enableHydration(false)
    ->first()['soma'] ?? 0;

$percentualConsumo = $franquiaTotal > 0
    ? round(($horasConsumidas / $franquiaTotal) * 100, 1)
    : null;
```

### 8.3 Exibição da franquia

**Visão ERP:**
```
Franquia mensal total:   20h
Consumido em março:      14h 30min  (72,5%)
Saldo disponível:         5h 30min

[████████████████████░░░░░░░░]  72,5%
```

**Visão Portal Cliente:**
```
Horas incluídas no plano:   20h/mês
Horas utilizadas (março):   14h 30min
Saldo restante:              5h 30min

[████████████████░░░░░░░░░░]  72,5%
```

### 8.4 Cores da barra de consumo

| % consumido | Cor | Significado |
|:-----------:|:---:|------------|
| 0–60% | `success` verde | Normal |
| 61–85% | `warning` amarelo | Atenção |
| 86–100% | `danger` vermelho | Limite próximo |
| > 100% | `danger` piscante | Excedente |

### 8.5 Regras de período

- O período exibido por padrão é sempre o **mês atual** (do dia 1 ao último dia)
- Selector de mês disponível para `role = 0` (financeiro pode ver histórico de consumo)
- Para `role = 1`: somente mês atual e até 6 meses anteriores

### 8.6 Exceções de consumo

- Se `franquia_horas = null` em todos os itens: não exibir seção de franquia
- Se `franquiaTotal = 0`: exibir "Franquia não definida no contrato"
- `minutosCliente()` usa `ticketshoras.data` (campo string `d/m/Y`) — usar as datas no mesmo formato

---

## 9. Serviços Cobertos

### 9.1 Conceito

"Serviços cobertos" é a lista legível de itens do contrato, apresentada ao cliente de forma compreensível (sem códigos de produto ou valores). A visão ERP inclui todos os campos; a visão cliente é uma lista simplificada.

### 9.2 Tipos de item de contrato (`tipo_item`)

| Valor | Rótulo | Ícone Bootstrap |
|-------|--------|----------------|
| `servico` | Suporte técnico | `glyphicon-wrench` |
| `licenca` | Licença de software | `glyphicon-list-alt` |
| `hardware` | Equipamento / Hardware | `glyphicon-hdd` |
| `cloud` | Serviço em nuvem | `glyphicon-cloud` |
| `suporte` | Atendimento dedicado | `glyphicon-headphones` |

### 9.3 Renderização — Visão ERP

```
ITENS DO CONTRATO

[wrench] Suporte técnico
  Suporte Remoto N1 — 10h/mês — R$150,00/h — R$1.500,00/mês
  Vigência: 01/01/2026 a indeterminado  [Editar] [Excluir]

[list-alt] Licença de software
  Licença ERP — 1 unid — R$800,00/unid — R$800,00/mês
  Vigência: 01/01/2026 a 31/12/2026  [Editar] [Excluir]
```

### 9.4 Renderização — Visão Portal Cliente

```
O QUE ESTÁ NO SEU PLANO

✔  Suporte técnico remoto — 10 horas mensais inclusas
✔  Licença de software ERP — 1 usuário
✔  Backup em nuvem — 1 TB de armazenamento
```

**Regras de exibição para cliente:**
- Omitir: `codproduto`, `vlunit`, `vltotal`, `observacoes`
- Exibir `qtde` com unidade inferida do `tipo_item` (horas para suporte, unidades para licença)
- Itens com `ativo = false` aparecem riscados com badge `[Encerrado]`
- Itens com `dtvalidade` próxima aparecem com badge `[Vence em X dias]`

---

## 10. SLA Contratual

### 10.1 Conceito

O SLA contratual define quais prazos de atendimento estão garantidos ao cliente com base nos itens de contrato. É diferente do SLA operacional do ticket (calculado por `SlaService`) — é o **compromisso escrito** do contrato.

### 10.2 Fonte dos dados

A relação entre contrato e SLA usa o campo `nivel_sla` em `clicontratos` (soft FK textual para `sla_policies.nome`):

```php
// Busca a política SLA pelo nome registrado no item de contrato
$politicaSla = $this->SlaPolicies->find()
    ->where([
        'nome'       => $item->nivel_sla,
        'idempresa'  => $idempresa,
        'ativo'      => true,
    ])
    ->first();
```

### 10.3 Exibição — Visão ERP

```
SLA CONTRATUAL (por item)

Item: Suporte Remoto N1
  Política: Suporte Padrão — P3
  ┌────────────────────────────────────────────┐
  │  Nível       │ Resposta │ Resolução        │
  │  P1 Crítico  │ 30 min   │ 4 horas          │
  │  P2 Alto     │ 2 horas  │ 8 horas          │
  │► P3 Médio    │ 8 horas  │ 24 horas         │ ← nível do item
  │  P4 Baixo    │ 24 horas │ 72 horas         │
  └────────────────────────────────────────────┘
```

### 10.4 Exibição — Visão Portal Cliente

```
ACORDO DE NÍVEL DE SERVIÇO (SLA)

Seu plano garante os seguintes prazos de atendimento:

  CRÍTICO (sistema parado)
    Primeiro contato: até 30 minutos
    Resolução:        até 4 horas

  ALTO (impacto severo)
    Primeiro contato: até 2 horas
    Resolução:        até 8 horas

  MÉDIO (impacto moderado)
    Primeiro contato: até 8 horas
    Resolução:        até 24 horas

  BAIXO (dúvidas, melhorias)
    Primeiro contato: até 24 horas
    Resolução:        até 72 horas
```

**Regras:**
- Se nenhum item tiver `nivel_sla`: exibir "SLA não especificado neste contrato."
- Se múltiplos itens tiverem SLAs diferentes: exibir o SLA mais restritivo (menor prazo)
- Campos `resposta_minutos`/`resolucao_minutos` de `sla_policies` são convertidos para linguagem humana:
  - `<= 60` min → "X minutos"; `> 60` min → "X horas"; `>= 1440` min → "X dias"

---

## 11. Listagem de Faturas

### 11.1 Esclarecimento de nomenclatura

O módulo unifica dois tipos de cobrança sob o menu "Faturas":

| Tipo | Tabela | Controller | Característica |
|------|--------|-----------|---------------|
| **Faturamento de serviços** | `faturamento` | `FaturamentoController` | OS, horas avulsas, mensalidades; número FT-0001 |
| **Locação de equipamentos** | `faturas` | `FaturasController` | Itens de equipamento; número R001; aprovação por hash |

Para o portal do cliente, **apenas `faturamento` é exposto** (cobrança de serviços). Locação de equipamentos (`faturas`) tem fluxo próprio e não é incluída neste módulo.

### 11.2 URLs

| Contexto | URL | Action |
|---------|-----|--------|
| ERP — todas as faturas | `GET /contratos/faturas` | `ContratosController::faturas` |
| ERP — faturas de um cliente | `GET /contratos/faturas/:idcliente` | `ContratosController::faturas` |
| Portal cliente | `GET /contratos/cliente-faturas` | `ContratosController::clienteFaturas` |

### 11.3 Campos de `faturamento`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | PK | — |
| `idempresa` | FK | Empresa |
| `idcliente` | FK | Cliente |
| `idordem` | FK nullable | OS origem (se gerado via `gerarDeOS`) |
| `idautor` | FK | Quem criou |
| `numero` | varchar | Sequencial FT-0001 |
| `hash` | varchar(20) | Token público (link sem login) |
| `status` | varchar | `rascunho\|pendente\|enviado\|pago\|cancelado` |
| `tipo` | varchar | `servico\|mensalidade\|avulso` |
| `data_emissao` | date | Data de emissão |
| `data_vencimento` | date | Prazo de pagamento |
| `valor_subtotal` | decimal | Antes de descontos |
| `valor_desconto` | decimal | Desconto aplicado |
| `valor_total` | decimal | Final a pagar |
| `descricao` | text | Notas (interna) |

### 11.4 Colunas da listagem ERP

| # | Coluna | Fonte | Ordenável |
|---|--------|-------|:---------:|
| 1 | **Nº** | `faturamento.numero` | ✅ |
| 2 | **Cliente** | `clientes.razaosocial` | ✅ |
| 3 | **Emissão** | `faturamento.data_emissao` | ✅ |
| 4 | **Vencimento** | `faturamento.data_vencimento` | ✅ |
| 5 | **Valor total** | `faturamento.valor_total` (R$) | ✅ |
| 6 | **Tipo** | `faturamento.tipo` | ✅ |
| 7 | **Status** | Badge (ver seção 12) | ✅ |
| 8 | **Ações** | Ver / Editar / Alterar status | — |

**Filtros ERP:**

| Filtro | Tipo |
|--------|------|
| Cliente | Select com busca |
| Status | Select múltiplo |
| Tipo | Select |
| Data emissão | Date range |
| Data vencimento | Date range |

### 11.5 Colunas da listagem Portal Cliente

| # | Coluna | Fonte | Observação |
|---|--------|-------|-----------|
| 1 | **Nº** | `faturamento.numero` | — |
| 2 | **Referência** | Mês/ano de `data_emissao` | "março/2026" |
| 3 | **Vencimento** | `data_vencimento` | Com badge de prazo |
| 4 | **Valor** | `valor_total` (R$) | Exibido para cliente |
| 5 | **Status** | Badge simplificado | Pendente/Pago/Vencido |
| 6 | **Ações** | Ver / Download | Somente `faturas.cliente_download` |

**Campos ocultos para cliente:** `valor_subtotal`, `valor_desconto`, `descricao`, `idordem`, `idautor`, `hash` (link interno)

**Filtros Portal Cliente:**

| Filtro | Tipo |
|--------|------|
| Período | Date range (mês/ano) |
| Status | Pendente / Pago / Vencido |

---

## 12. Status da Fatura

### 12.1 Ciclo de vida do documento `faturamento`

```
RASCUNHO ──► PENDENTE ──► ENVIADO ──► PAGO
    │             │                     │
    └──► CANCELADO ◄───────────────────┘
```

**Transições permitidas:**

| De | Para | Quem autoriza | Efeito colateral |
|----|------|:-------------:|-----------------|
| `rascunho` | `pendente` | `faturas.alterar_status` | Nenhum |
| `rascunho` | `cancelado` | `faturas.alterar_status` | Nenhum |
| `pendente` | `enviado` | `faturas.alterar_status` | Nenhum |
| `pendente` | `cancelado` | `faturas.alterar_status` | Nenhum |
| `enviado` | `pago` | `faturas.alterar_status` | Gera `financeiro_lancamentos` via `_gerarLancamentoFinanceiro()` |
| `enviado` | `cancelado` | `faturas.alterar_status` | Nenhum |
| `pago` | qualquer | NENHUM | Transição bloqueada |
| `cancelado` | qualquer | NENHUM | Transição bloqueada |

**Restrição de edição por status:**
- `rascunho` e `pendente`: edição permitida
- `pago` e `cancelado`: edição **bloqueada** (já implementado em `FaturamentoController::edit()`)

### 12.2 Mapeamento de status para exibição

| `status` | Rótulo | Badge | Visível ao cliente |
|---------|--------|:-----:|:-----------------:|
| `rascunho` | Rascunho | `badge-secondary` | ❌ não |
| `pendente` | Pendente | `badge-warning` | ✅ como "Pendente" |
| `enviado` | Aguardando pagamento | `badge-info` | ✅ como "Pendente" |
| `pago` | Pago | `badge-success` | ✅ como "Pago" |
| `cancelado` | Cancelado | `badge-danger` | ✅ como "Cancelado" |

> O cliente não distingue `pendente` de `enviado` — ambos aparecem como "Pendente" para simplificar.

### 12.3 Cálculo de vencimento para o cliente

```php
// Fatura considerada "vencida" quando:
// status IN ('pendente','enviado') AND data_vencimento < hoje
$hoje = date('Y-m-d');
foreach ($faturas as $f) {
    $status = $f->status;
    $venc   = $f->data_vencimento;

    if (in_array($status, ['pendente', 'enviado'], true)
        && $venc instanceof \DateTimeInterface
        && $venc->format('Y-m-d') < $hoje) {
        $f->status_cliente = 'vencida';
    } elseif (in_array($status, ['pendente', 'enviado'], true)) {
        $f->status_cliente = 'pendente';
    } elseif ($status === 'pago') {
        $f->status_cliente = 'pago';
    } else {
        $f->status_cliente = 'cancelado';
    }
}
```

| `status_cliente` | Badge | Cor |
|:---------------:|:-----:|:---:|
| `pendente` | Pendente | `warning` |
| `vencida` | Vencida | `danger` |
| `pago` | Pago | `success` |
| `cancelado` | Cancelado | `default` |

### 12.4 Promoção automática para "vencida"

O status `vencida` é **calculado em tempo de exibição** — não é salvo no banco. O banco permanece `pendente` ou `enviado`.

Para alertar o financeiro, adicionar indicador no dashboard:

```php
// ContratosController::faturas() — KPI de vencidas
$vencidas = $this->Faturamento->find()->where([
    'Faturamento.idempresa' => $idempresa,
    'Faturamento.status IN' => ['pendente', 'enviado'],
    'Faturamento.data_vencimento <' => date('Y-m-d'),
])->count();
$this->set('kpiVencidas', $vencidas);
```

---

## 13. Downloads no Portal

### 13.1 O que pode ser baixado

| Documento | Gerado por | URL |
|-----------|-----------|-----|
| **PDF do documento de faturamento** | mPDF (layout `print.ctp`) | `GET /contratos/pdf/:id` |
| **Link público do documento** | Hash de 20 chars (`faturamento.hash`) | URL compartilhável sem login |

> O módulo atual **não gera boleto bancário** — apenas exibe o PDF do documento. Integração com gateway de pagamento é escopo futuro.

### 13.2 PDF do documento de faturamento

**Action:** `ContratosController::pdf($id)`

```php
public function pdf($id = null) {
    $idempresa = (int)$this->Auth->user('idempresa');

    $doc = $this->Faturamento->find('all')
        ->where(['Faturamento.id' => $id, 'Faturamento.idempresa' => $idempresa])
        ->contain(['Clientes', 'FaturamentoItens',
                   'Users' => ['fields' => ['Users.id', 'Users.name']]])
        ->first();

    if (empty($doc)) {
        $this->Flash->error('Documento não encontrado.');
        return $this->redirect(['action' => 'faturas']);
    }

    // ABAC: cliente só acessa PDF dos próprios documentos
    if ((int)$this->Auth->user('role') === 1) {
        if ((int)$doc->idcliente !== (int)$clienteAtual->id) {
            $this->Flash->error('Sem permissão para este documento.');
            return $this->redirect(['action' => 'clienteFaturas']);
        }
        // Documentos em rascunho não são visíveis para o cliente
        if ($doc->status === 'rascunho') {
            $this->Flash->error('Documento não disponível.');
            return $this->redirect(['action' => 'clienteFaturas']);
        }
    }

    $empresa = $this->Empresas->get($idempresa);
    $this->viewBuilder()->setLayout('print');
    $this->viewBuilder()->setTemplate('Contratos/pdf_faturamento');
    $this->set(compact('doc', 'empresa'));
}
```

**Conteúdo do PDF (`pdf_faturamento.ctp`):**

```
┌─────────────────────────────────────────────────────┐
│  LOGO EMPRESA                  Nº FT-0042           │
│                                Emissão: 01/03/2026  │
├─────────────────────────────────────────────────────┤
│  PARA:                         DE:                  │
│  Empresa ABC Ltda              PGM Sistemas         │
│  CNPJ: 12.345.678/0001-99      CNPJ: ...            │
├─────────────────────────────────────────────────────┤
│  DESCRIÇÃO DO SERVIÇO                               │
│  ─────────────────────────────────────────────────  │
│  Item  │ Descrição          │ Qtde │ Unit  │ Total  │
│   1    │ Horas de suporte   │ 10h  │R$150  │R$1.500 │
│   2    │ Licença mensal ERP │  1   │R$800  │R$800   │
│  ─────────────────────────────────────────────────  │
│                              Subtotal: R$2.300,00   │
│                              Desconto: R$0,00       │
│                              TOTAL:    R$2.300,00   │
├─────────────────────────────────────────────────────┤
│  Vencimento: 10/03/2026                             │
│  Status: Aguardando pagamento                       │
└─────────────────────────────────────────────────────┘
```

### 13.3 Link público (hash)

O campo `faturamento.hash` (20 chars, já gerado em `FaturamentoTable::gerarHash()`) permite criar um link público sem autenticação:

```
https://portal.pgm.inf.br/contratos/publico/{hash}
```

**Action:** `ContratosController::publico($hash)` — sem autenticação, sem sessão.

```php
public function publico($hash = null) {
    $this->Auth->allow(['publico']);

    $doc = $this->Faturamento->findByHash($hash)
        ->contain(['Clientes', 'FaturamentoItens'])
        ->first();

    if (empty($doc) || $doc->status === 'rascunho') {
        return $this->response->withStatus(404)
            ->withStringBody('Documento não encontrado ou não disponível.');
    }

    $empresa = $this->Empresas->get($doc->idempresa);
    $this->viewBuilder()->setLayout('clear');
    $this->set(compact('doc', 'empresa'));
}
```

**Regras do link público:**
- `rascunho` → 404 (documento interno, não divulgar)
- `cancelado` → exibe documento mas com banner "Documento cancelado"
- Link expira somente se o `hash` for regenerado manualmente pelo financeiro
- Botão "Copiar link" na tela ERP (`/faturamento/view/:id`) que copia a URL pública

### 13.4 Permissões de download no portal

| Condição | Resultado |
|---------|----------|
| `faturas.cliente_download` ausente | Botão "Baixar PDF" oculto |
| `faturas.cliente_download` presente + status `rascunho` | Botão oculto (cliente não vê rascunhos) |
| `faturas.cliente_download` presente + status != rascunho | Botão "Baixar PDF" disponível |
| Link público | Sempre acessível se não rascunho (independe de login) |

---

## 14. Critérios de Aceite

### CA-CF01 — Listagem de contratos ERP

- [ ] Técnico (`role = 0`) acessa `/contratos/index` e vê contratos agrupados por cliente da empresa ativa
- [ ] Troca de empresa pelo dropdown atualiza a lista sem relogin
- [ ] Filtro por status (ativo/a_vencer/encerrado/cancelado) funciona
- [ ] Colunas de valor (`vlunit`, `vltotal`) aparecem apenas para `role = 0`
- [ ] Itens com `dtvalidade` nos próximos 30 dias exibem badge `a_vencer`

### CA-CF02 — Listagem de contratos Portal Cliente

- [ ] Cliente acessa `/contratos/cliente` e vê somente itens onde `idcliente = clienteAtual.id`
- [ ] URL `/contratos/cliente?idcliente=99` não permite ver contratos de outro cliente — parâmetro ignorado
- [ ] Colunas `vlunit`, `vltotal`, `codproduto`, `observacoes` **não** aparecem na visão cliente
- [ ] Itens encerrados aparecem riscados, não são ocultados

### CA-CF03 — Franquia e consumo

- [ ] Barra de consumo calcula corretamente: horas consumidas / franquia total × 100
- [ ] Barra fica verde até 60%, amarela 61–85%, vermelha acima de 85%
- [ ] Se `franquia_horas` é null em todos os itens, a seção "Franquia" não aparece
- [ ] Selector de mês funciona para `role = 0`; cliente vê somente mês atual e 6 meses anteriores

### CA-CF04 — SLA contratual

- [ ] SLA aparece somente quando `nivel_sla` está preenchido em ao menos um item ativo
- [ ] Para múltiplos níveis diferentes no mesmo contrato, exibe o mais restritivo
- [ ] Prazos em minutos são convertidos para linguagem humana (ex: 30 min, 2 horas, 3 dias)
- [ ] "SLA não especificado neste contrato." aparece quando nenhum item tem `nivel_sla`

### CA-CF05 — Listagem de faturas ERP

- [ ] Financeiro vê todos os documentos `faturamento` da empresa ativa
- [ ] Filtros de cliente, status, tipo e período funcionam
- [ ] KPI de faturas vencidas (status pendente/enviado + data_vencimento < hoje) é exibido
- [ ] Botão "Alterar status" abre modal e persiste via `POST /faturamento/alterar-status/:id` (existente)

### CA-CF06 — Listagem de faturas Portal Cliente

- [ ] Cliente vê somente documentos onde `idcliente = clienteAtual.id`
- [ ] Documentos com status `rascunho` **não aparecem** para o cliente
- [ ] Status `pendente` e `enviado` são exibidos como "Pendente" para o cliente
- [ ] Documentos com `data_vencimento < hoje` e status pendente/enviado exibem "Vencida" com badge vermelho

### CA-CF07 — Download de PDF

- [ ] Cliente com `faturas.cliente_download` baixa PDF do próprio documento
- [ ] Cliente sem `faturas.cliente_download` não vê o botão de download
- [ ] Cliente tentando baixar PDF de outro cliente recebe erro 403 + Flash + redirect
- [ ] PDF de documento `rascunho` não é acessível para `role = 1`
- [ ] PDF renderiza todos os itens, cabeçalho da empresa e totais corretamente

### CA-CF08 — Link público (hash)

- [ ] Link `/contratos/publico/{hash}` acessível sem autenticação
- [ ] Hash de documento `rascunho` retorna 404
- [ ] Hash de documento `cancelado` exibe documento com banner de aviso
- [ ] Botão "Copiar link" na tela ERP copia a URL pública para clipboard
- [ ] URL pública não expõe dados financeiros além do que está no documento

### CA-CF09 — ABAC

- [ ] `role = 1` com `?idcliente=X` na URL não acessa contratos/faturas de outro cliente
- [ ] Campos financeiros (`vlunit`, `vltotal`, `valor_subtotal`, `valor_desconto`) nunca chegam ao HTML para `role = 1`
- [ ] Admin pode acessar contratos de qualquer empresa via dropdown

### CA-CF10 — Sincronização com ERP

- [ ] Botão "Sincronizar com ERP" disponível somente com permissão `contratos.sync`
- [ ] Falha no SOAP exibe Flash de erro sem quebrar a tela
- [ ] Sucesso exibe Flash de confirmação

---

## 15. Edge Cases

### EC-CF01 — Cliente sem nenhum item de contrato

**Cenário:** `clicontratos` não tem registros para o cliente logado.  
**Visão ERP:** linha do cliente ausente da listagem.  
**Visão Cliente:** tela exibe "Nenhum contrato ativo encontrado. Entre em contato com o suporte."  
**Não gerar** exceção ou tela em branco.

---

### EC-CF02 — Contrato com `dtvalidade` nula

**Cenário:** Campo `dtvalidade` é `null` — contrato indeterminado.  
**Comportamento:**
- Status calculado como `ativo` (sem data de término, não pode estar "a_vencer" nem "encerrado")
- Coluna "Vigência fim" exibe "Indeterminado"
- Filtros de vigência por data ignoram esse item ao filtrar por "vence até"

---

### EC-CF03 — `dtcontratacao` posterior a `dtvalidade`

**Cenário:** Dado inconsistente — início depois do fim.  
**Comportamento:** Exibir os dois campos, mas adicionar ícone de alerta `⚠` com tooltip "Datas inconsistentes" somente para `role = 0`. Para o cliente, exibir normalmente sem o alerta.  
**Não bloquear** a exibição nem lançar exception.

---

### EC-CF04 — Múltiplos registros do mesmo `codproduto` para o mesmo cliente

**Cenário:** Cliente tem dois itens com `codproduto = 'SRV001'` (pode ocorrer por upgrades ou períodos sobrepostos).  
**Comportamento:** Exibir todas as linhas separadamente. Não deduplica — o financeiro precisa ver o histórico completo. Somar `franquia_horas` de todos os itens ativos para calcular a franquia total.

---

### EC-CF05 — Fatura gerada sem `idordem` (avulsa)

**Cenário:** Fatura criada manualmente sem OS origem (`idordem = null`).  
**Comportamento:** Exibir normalmente sem coluna "OS origem". Campo `idordem` verificado com `empty()` antes de exibir o link para OS.

---

### EC-CF06 — `valor_total = 0` no documento de faturamento

**Cenário:** Documento criado sem itens ou com itens zerados.  
**Comportamento ERP:** Exibir `R$ 0,00` com badge amarelo "Atenção: valor zero" na listagem.  
**Comportamento cliente:** Exibir `R$ 0,00` normalmente, sem badge de atenção.  
**Não bloquear** o fluxo de status — fatura zero pode ser legítima (cortesia, crédito).

---

### EC-CF07 — Hash de faturamento duplicado (colisão)

**Cenário:** `FaturamentoTable::gerarHash()` já trata colisão com loop `do-while`. Mas em ambiente com muitos registros, o loop pode demorar.  
**Mitigação:** Garantir índice único na coluna `hash`:
```sql
CREATE UNIQUE INDEX IF NOT EXISTS uq_faturamento_hash ON faturamento(hash);
```
A tentativa de `save()` com hash duplicado lançaria `PDOException` — envolver em `try/catch` com retry máximo de 5 tentativas.

---

### EC-CF08 — `clienteAtual` não resolvido para empresa ativa

**Cenário:** Cliente fez login na empresa A, mas o cadastro PJ/CPF não existe na empresa B após a troca de empresa no dropdown.  
**Comportamento:** Flash "Seu cadastro não foi encontrado para esta empresa." + redirect para `Users::dashboard`.  
**Não** deixar a tela exibir contratos com `idcliente = null`.

---

### EC-CF09 — `nivel_sla` desatualizado (política deletada ou renomeada)

**Cenário:** Item de contrato tem `nivel_sla = 'Suporte Premium'` mas a `sla_policies` com esse nome foi renomeada ou excluída.  
**Comportamento:**
- `findByNome()` retorna null
- Exibir o texto do campo `nivel_sla` com badge `[política não encontrada]` em amarelo para `role = 0`
- Para `role = 1`: exibir somente o texto do campo sem o badge de erro
- **Não** lançar exception

---

### EC-CF10 — Link público acessado por usuário já logado

**Cenário:** Usuário logado acessa `/contratos/publico/{hash}`.  
**Comportamento:** Exibir o documento normalmente (layout `clear`, sem menu), independente da sessão. A action libera via `$this->Auth->allow(['publico'])` — a sessão não interfere. Não redirecionar para dashboard.

---

### EC-CF11 — Downgrade de status (ex: pago → pendente)

**Cenário:** Financeiro tenta alterar status de `pago` para `pendente` via AJAX ou URL direta.  
**Comportamento:**
- `FaturamentoController::alterarStatus()` verifica as transições permitidas
- `pago` e `cancelado` não aparecem como opções no modal de alteração de status
- Se tentado via POST direto: retornar `422` com `{ "ok": false, "msg": "Transição de status não permitida." }`
- `financeiro_lancamentos` gerado ao marcar `pago` **não é desfeito** automaticamente — o financeiro precisa estornar manualmente

---

## Apêndice A — Rotas a Registrar

```php
// config/routes.php — adicionar no Router::scope('/', ...)

// Contratos ERP
$routes->connect('/contratos',                ['controller' => 'Contratos', 'action' => 'index']);
$routes->connect('/contratos/index',          ['controller' => 'Contratos', 'action' => 'index']);
$routes->connect('/contratos/view/*',         ['controller' => 'Contratos', 'action' => 'view']);
$routes->connect('/contratos/sincronizar/*',  ['controller' => 'Contratos', 'action' => 'sincronizar'])->setMethods(['POST']);

// Faturas ERP
$routes->connect('/contratos/faturas',        ['controller' => 'Contratos', 'action' => 'faturas']);
$routes->connect('/contratos/faturas/*',      ['controller' => 'Contratos', 'action' => 'faturas']);
$routes->connect('/contratos/pdf/*',          ['controller' => 'Contratos', 'action' => 'pdf']);
$routes->connect('/contratos/publico/*',      ['controller' => 'Contratos', 'action' => 'publico']);

// Portal cliente
$routes->connect('/contratos/cliente',        ['controller' => 'Contratos', 'action' => 'cliente']);
$routes->connect('/contratos/cliente-view',   ['controller' => 'Contratos', 'action' => 'clienteView']);
$routes->connect('/contratos/cliente-faturas',['controller' => 'Contratos', 'action' => 'clienteFaturas']);
$routes->connect('/contratos/cliente-download/*', ['controller' => 'Contratos', 'action' => 'clienteDownload']);
```

---

## Apêndice B — Alterações no AppController

```php
// $controllerToMenuMap — adicionar:
'contratos' => 'contratosActive',

// $menuStates — adicionar:
'contratosActive' => '',

// Security::unlockedActions — nenhuma adição necessária
// (publico usa Auth::allow, não Security unlock)
```

---

## Apêndice C — Migrations

```sql
-- 1. Enriquecer clicontratos
ALTER TABLE clicontratos
    ADD COLUMN IF NOT EXISTS tipo_item      VARCHAR(40)  NOT NULL DEFAULT 'servico',
    ADD COLUMN IF NOT EXISTS franquia_horas DECIMAL(8,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS nivel_sla      VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS observacoes    TEXT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ativo          BOOLEAN      NOT NULL DEFAULT TRUE;

-- 2. Índice único para hash de faturamento (segurança)
CREATE UNIQUE INDEX IF NOT EXISTS uq_faturamento_hash ON faturamento(hash);

-- 3. Índices de performance
CREATE INDEX IF NOT EXISTS idx_clicontratos_idcliente_empresa
    ON clicontratos(idcliente, idempresa);
CREATE INDEX IF NOT EXISTS idx_clicontratos_ativo
    ON clicontratos(ativo, dtvalidade);
CREATE INDEX IF NOT EXISTS idx_faturamento_idcliente_empresa
    ON faturamento(idcliente, idempresa);
CREATE INDEX IF NOT EXISTS idx_faturamento_status_vencimento
    ON faturamento(status, data_vencimento);

-- 4. Backfill: marcar como inativo contratos com dtcancelamento no passado
UPDATE clicontratos
SET ativo = FALSE
WHERE dtcancelamento IS NOT NULL
  AND dtcancelamento <= CURRENT_DATE
  AND ativo = TRUE;
```

---

## Apêndice D — Estrutura de Arquivos

```
src/
  Controller/
    ContratosController.php          ← novo; estende AppController
                                       (unifica contratos + faturas do módulo)

  Template/
    Contratos/
      index.ctp                      ← ERP: listagem contratos agrupados por cliente
      view.ctp                       ← ERP: detalhe + itens + franquia + SLA + faturas
      faturas.ctp                    ← ERP: listagem de documentos de faturamento
      pdf_faturamento.ctp            ← layout print: PDF do documento
      publico.ctp                    ← layout clear: link público sem login
      cliente.ctp                    ← Portal cliente: itens de contrato
      cliente_view.ctp               ← Portal cliente: detalhe contrato
      cliente_faturas.ctp            ← Portal cliente: lista de cobranças
      Element/
        franquia_barra.ctp           ← fragmento: barra de consumo
        sla_tabela.ctp               ← fragmento: tabela de SLA
        status_fatura_badge.ctp      ← fragmento: badge de status
        itens_contrato_table.ctp     ← fragmento: tabela de itens (ERP)
        itens_contrato_lista.ctp     ← fragmento: lista simplificada (cliente)
```
