# Especificação Funcional — Módulo: Histórico Completo de Atendimentos

**Projeto:** Portal PGM / Portal do Cliente  
**Stack:** CakePHP 3.7 · PostgreSQL · Bootstrap 3 · jQuery · DataTables  
**Data:** 2026-03-31  
**Status:** Especificação para implementação

---

## 1. Objetivo do Módulo

O módulo de Tickets existente (`/tickets/index`) é uma **fila de trabalho ativo** — exibe tickets abertos, filtra por situação operacional e serve de ponto de entrada para o técnico agir.

O **Histórico de Atendimentos** é o **arquivo consultável permanente**: mostra todos os tickets (independente de status), com filtros avançados de período, uma timeline cronológica de cada evento do ticket, indicadores de SLA, anexos para download e trilha completa de auditoria.

**O que este módulo entrega que ainda não existe:**

| Lacuna atual | O que este módulo resolve |
|-------------|--------------------------|
| Nenhuma listagem de tickets encerrados além de `/tickets/finalizados` (limit 500, sem filtros) | Listagem paginada, filtrada e exportável de todos os tickets |
| Nenhuma visão do cliente sobre histórico | Portal cliente com timeline, SLA e anexos |
| `ticketsmovs` e `ticket_histories` existem mas nunca são exibidos em conjunto | Timeline unificada de eventos cronológicos |
| SLA calculado mas não exibido ao cliente | Painel de SLA por ticket visível no portal |
| Comentários não têm campo `interno` — todos chegam ao cliente | Migração adiciona `visivel_cliente`; regra de visibilidade implementada |

---

## 2. Perfis que Acessam

| Perfil | `role` / `admin` | O que acessa |
|--------|-----------------|-------------|
| **Técnico PGM** | `role = 0` | Todos os tickets da empresa ativa; todas as abas; comentários internos |
| **Gestor PGM** | `role = 0` + papel RBAC `gestor` | Idem técnico + exportações completas |
| **Admin** | `admin = 1` | Irrestrito — sem filtro de empresa |
| **Cliente** | `role = 1` | Somente tickets do próprio `idcliente`; sem comentários internos; SLA simplificado |

---

## 3. Permissões RBAC

### 3.1 Códigos de permissão a criar

```sql
INSERT INTO rbac_permissions (code, description, controller, action) VALUES
  ('historico.view',          'Ver listagem do histórico de atendimentos', 'Historico', 'index'),
  ('historico.detail',        'Ver detalhe de ticket no histórico',         'Historico', 'view'),
  ('historico.export',        'Exportar histórico (CSV/Excel/PDF)',          'Historico', 'exportar'),
  ('historico.audit',         'Ver trilha de auditoria completa',            'Historico', 'view'),
  ('historico.cliente_view',  'Cliente vê histórico da empresa',             'Historico', 'cliente'),
  ('historico.cliente_detail','Cliente vê detalhe do ticket',                'Historico', 'clienteView');
```

### 3.2 Atribuição por papel

| Permissão | admin | gestor | tecnico | financeiro | cliente_full | cliente_basico |
|-----------|:-----:|:------:|:-------:|:----------:|:------------:|:--------------:|
| `historico.view` | ✅ | ✅ | ✅ | — | — | — |
| `historico.detail` | ✅ | ✅ | ✅ | — | — | — |
| `historico.export` | ✅ | ✅ | — | — | — | — |
| `historico.audit` | ✅ | ✅ | — | — | — | — |
| `historico.cliente_view` | — | — | — | — | ✅ | ✅ |
| `historico.cliente_detail` | — | — | — | — | ✅ | ✅ |

### 3.3 Verificação no controller

```php
// HistoricoController::isAuthorized()
public function isAuthorized($user) {
    $action = $this->request->getParam('action');
    $role   = (int)($user['role'] ?? -1);

    // Técnicos/gestores PGM
    if ($role === 0) {
        if (in_array($action, ['index', 'view', 'exportar'], true)) {
            return $this->Rbac->hasPermission('historico.' . $action);
        }
        return false;
    }

    // Cliente
    if ($role === 1) {
        if ($action === 'cliente') {
            return $this->Rbac->hasPermission('historico.cliente_view');
        }
        if ($action === 'clienteView') {
            return $this->Rbac->hasPermission('historico.cliente_detail');
        }
        return false;
    }

    return (bool)($user['admin'] ?? false);
}
```

---

## 4. Regras ABAC

### 4.1 Escopo por empresa (role = 0)

Toda query aplica filtro por `idempresa` da sessão via `AbacComponent::applyToQuery()`:

```php
// HistoricoController::index()
$query = $this->Tickets->find('all', ['contain' => [...]]);
$this->Abac->applyToQuery($query, 'Tickets', 'tickets');
// → injeta WHERE tickets.idempresa = :idempresa_sessao
```

Admin (`admin = 1`) não recebe o filtro de empresa — pode trocar empresa pelo dropdown.

### 4.2 Escopo por cliente (role = 1)

```php
// HistoricoController::cliente() e clienteView()
if ((int)$user['role'] === 1) {
    $idcliente = (int)$this->Auth->user('idcliente');
    $idempresa = (int)$this->Auth->user('idempresa');

    // Resolve o idcliente dentro da empresa ativa (mesma lógica de TicketsController::viewModal)
    $clienteBase = $this->Clientes->findById($idcliente)->first();
    if ($clienteBase->tipo == C_ClientesTipoJuridica) {
        $clienteAtual = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj))
            ->where(['idempresa' => $idempresa])->first();
    } else {
        $clienteAtual = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf))
            ->where(['idempresa' => $idempresa])->first();
    }

    if (empty($clienteAtual)) {
        // Redireciona com flash de erro
    }

    $query->where(['tickets.idcliente' => $clienteAtual->id]);
}
```

### 4.3 Regra de acesso a ticket individual (role = 1)

O cliente pode ver um ticket se **qualquer uma** das condições abaixo for verdadeira:

| Condição | Campo verificado |
|---------|----------------|
| É o autor do ticket | `tickets.idautor = sessao.iduser` |
| O ticket pertence ao cliente | `tickets.idcliente = cliente_resolvido.id` |
| Tem permissão de acesso amplo | `users.permissaoacesso = true` |

Caso nenhuma condição seja atendida → HTTP 403.

### 4.4 Proteção de anexos

Reutiliza exatamente a lógica de `TicketsController::downloadAnexo()`:
- Verifica `ticketsanexos.idempresa = sessao.idempresa` (sempre)
- Para `role = 1`: verifica idautor/idcliente/permissaoacesso (igual à regra 4.3)

---

## 5. Filtros da Listagem

### 5.1 URL das telas

| Contexto | URL | Controller::Action |
|---------|-----|--------------------|
| ERP (técnico/gestor) | `/historico/index` | `HistoricoController::index` |
| Portal cliente | `/historico/cliente` | `HistoricoController::cliente` |

### 5.2 Parâmetros de query string

Todos os filtros são passados via GET e persistidos na sessão com prefixo `historico_filtros_`.

| Parâmetro | Tipo | Descrição | Disponível |
|-----------|------|-----------|-----------|
| `periodo_ini` | date `Y-m-d` | Início do período (campo `tickets.created`) | ERP + Cliente |
| `periodo_fim` | date `Y-m-d` | Fim do período | ERP + Cliente |
| `assunto` | string | Busca em `tickets.assunto` (ILIKE `%valor%`) | ERP + Cliente |
| `situacao[]` | int[] | Status do ticket (múltiplo) | ERP + Cliente |
| `prioridade[]` | string[] | P1/P2/P3/P4 | ERP |
| `tipo_ticket` | string | incidente/requisicao/problema/mudanca | ERP |
| `sla_status` | string | dentro_sla/em_risco/violado | ERP |
| `idcliente` | int | Filtrar por cliente específico | ERP |
| `idtecnico` | int | Filtrar por técnico responsável | ERP |
| `queue_id` | int | Filtrar por fila | ERP |
| `page` | int | Página atual (DataTables server-side) | ERP + Cliente |
| `length` | int | Registros por página | ERP + Cliente |

### 5.3 Filtros padrão ao abrir a tela

| Contexto | Filtro padrão |
|---------|-------------|
| ERP | `periodo_ini` = primeiro dia do mês atual; `periodo_fim` = hoje |
| Cliente | `periodo_ini` = 6 meses atrás; sem filtro de status |

### 5.4 Valores dos status (`tickets.situacao`)

Os valores são definidos pelas constantes PGM. Mapeamento para exibição:

| Constante | Valor int | Rótulo exibido | Badge Bootstrap |
|-----------|----------|---------------|----------------|
| `situacao = 0` | 0 | Pendente | `label-warning` |
| `C_TicketSituacaoPendente` | — | Aguardando técnico | `label-warning` |
| `C_TicketSituacaoEmandamento` | — | Em andamento | `label-primary` |
| `C_TicketSituacaoRespondido` | — | Respondido | `label-info` |
| `C_TicketSituacaoResolvido` | — | Resolvido | `label-success` |
| `C_TicketSituacaoFechado` | — | Cancelado | `label-danger` |

> **Implementação:** usar a função `SituacaoTicket()` do pacote PGM (já usada em `view.ctp`).

---

## 6. Colunas da Tabela

### 6.1 Visão ERP (`/historico/index`)

| # | Coluna | Fonte | Ordenável | Observação |
|---|--------|-------|:---------:|-----------|
| 1 | **#** | `tickets.id` | ✅ | Link para `/historico/view/:id` |
| 2 | **Abertura** | `tickets.created` | ✅ | Formato `d/m/Y H:i` |
| 3 | **Encerramento** | `tickets.datafinalizado` | ✅ | Vazio se ainda aberto |
| 4 | **Assunto** | `tickets.assunto` truncado (60 chars) | ✅ | Tooltip com texto completo |
| 5 | **Cliente** | `clientes.razaosocial` | ✅ | — |
| 6 | **Solicitante** | `users.name` via `tickets.idautor` | ✅ | — |
| 7 | **Técnico** | `users.name` via `tickets.idtecnico_responsavel` | ✅ | "Sem responsável" se null |
| 8 | **Fila** | `queues.nome` via `tickets.queue_id` | ✅ | — |
| 9 | **Prioridade** | `tickets.prioridade` | ✅ | Badge P1/P2/P3/P4 (só se coluna existir) |
| 10 | **Tipo** | `tickets.tipo_ticket` | ✅ | Badge; só se coluna existir |
| 11 | **Status** | `tickets.situacao` | ✅ | Badge colorido via `SituacaoTicket()` |
| 12 | **SLA** | `tickets.sla_status` | ✅ | Badge `dentro_sla`/`em_risco`/`violado`; só se coluna existir |
| 13 | **Horas** | soma `ticketshoras` (subquery) | — | `HH:MM` |
| 14 | **Ações** | — | — | Botão "Ver" → `/historico/view/:id` |

> **Coluna condicional:** verificar `in_array('prioridade', $schema->columns(), true)` antes de exibir colunas enterprise. Mesma proteção usada em `TicketsController::add()`.

### 6.2 Visão Portal Cliente (`/historico/cliente`)

| # | Coluna | Fonte | Observação |
|---|--------|-------|-----------|
| 1 | **#** | `tickets.id` | Link para `/historico/cliente-view/:id` |
| 2 | **Abertura** | `tickets.created` | `d/m/Y` |
| 3 | **Assunto** | `tickets.assunto` | — |
| 4 | **Status** | `tickets.situacao` | Badge |
| 5 | **SLA** | Simplificado: "No prazo" / "Em risco" / "Atrasado" | Só se `sla_status` existir |
| 6 | **Encerramento** | `tickets.datafinalizado` | — |
| 7 | **Ações** | — | Botão "Ver detalhes" |

---

## 7. Ações do Usuário

### 7.1 Na listagem

| Ação | Quem pode | Comportamento |
|------|----------|--------------|
| **Filtrar** | Todos | Submete form GET; recarrega DataTables |
| **Limpar filtros** | Todos | Reset para filtros padrão; limpa sessão `historico_filtros_*` |
| **Ver detalhe** | Todos | Link para tela de detalhe |
| **Exportar CSV** | `role = 0` com `historico.export` | Download direto; aplica filtros ativos |
| **Exportar Excel** | `role = 0` com `historico.export` | Download direto |
| **Exportar PDF** | `role = 0` com `historico.export` | mPDF; layout A4 |

### 7.2 Na tela de detalhe (ERP)

| Ação | Quem pode | Comportamento |
|------|----------|--------------|
| **Voltar à listagem** | Todos | Link com filtros preservados (`/historico/index?...`) |
| **Abrir ticket para edição** | `role = 0` | Link para `/tickets/edit/:id` |
| **Imprimir** | Todos | `/tickets/imprimir/:id` (existente) |
| **Download de anexo** | Todos (com ABAC) | `GET /tickets/download-anexo/:idanexo` (existente) |
| **Ver anexo inline** | Todos (com ABAC) | `GET /tickets/download-anexo/:idanexo?inline=1` (existente) |

### 7.3 Na tela de detalhe (Portal Cliente)

| Ação | Quem pode | Comportamento |
|------|----------|--------------|
| **Voltar** | Cliente | `/historico/cliente` |
| **Download de anexo** | Cliente com ABAC validado | `GET /tickets/download-anexo/:idanexo` (existente) |
| **Adicionar comentário** | Cliente em tickets abertos | `POST /ticket-comentarios/add/:idticket` (existente) |

---

## 8. Tela de Detalhe

### 8.1 URLs

| Contexto | URL |
|---------|-----|
| ERP | `GET /historico/view/:id` |
| Portal cliente | `GET /historico/cliente-view/:id` |

### 8.2 Estrutura do cabeçalho

```
┌─────────────────────────────────────────────────────────────────┐
│  [← Voltar à listagem]         [Editar ticket] [Imprimir]      │
├─────────────────────────────────────────────────────────────────┤
│  Ticket #42  —  Erro no módulo de notas fiscais                 │
│                                                                 │
│  Status: [Em andamento]  Prioridade: [P2]  Tipo: [Incidente]   │
│                                                                 │
│  Aberto em: 15/03/2026 09:32   Por: João Silva                  │
│  Técnico: Maria Souza          Fila: N2 - Remoto               │
│  Cliente: Empresa ABC Ltda     Encerrado: —                    │
│                                                                 │
│  SLA: [██████████░░░░░░  62%  Em risco]  Limite: 18/03 17:00   │
└─────────────────────────────────────────────────────────────────┘
```

### 8.3 Solicitação original

Bloco expandível com o texto completo de `tickets.solicitacao`, exibido logo abaixo do cabeçalho.

---

## 9. Abas da Tela de Detalhe

```
[Timeline] [Comentários] [SLA] [Anexos] [Auditoria]
           (só role=0)         (sempre)  (só role=0 com historico.audit)
```

### Aba 1 — Timeline

Exibe todos os eventos cronológicos do ticket em ordem crescente de data.

**Fontes de dados (5 tabelas) e a query recomendada:**

```php
// Montar array unificado de eventos:
$eventos = [];

// 1. ticket_histories (fonte principal — enterprise)
foreach ($ticketHistories as $h) {
    $eventos[] = [
        'fonte'     => 'history',
        'timestamp' => $h->created,
        'tipo'      => $h->tipo_evento,
        'usuario'   => $h->usuario->name ?? 'Sistema',
        'descricao' => $this->_descreverHistoryEvento($h),
        'publico'   => true,
    ];
}

// 2. ticketsmovs (movimentações legadas de status)
foreach ($ticketsmovs as $m) {
    $eventos[] = [
        'fonte'     => 'mov',
        'timestamp' => $this->_parseDatatime($m->datetime),
        'tipo'      => 'status_changed',
        'usuario'   => $m->user->name ?? 'Sistema',
        'descricao' => $this->_descreverMov($m),
        'publico'   => true,
    ];
}

// 3. ticketcomentarios
foreach ($comentarios as $c) {
    $isInterno = (bool)($c->visivel_cliente ?? true) === false; // campo novo
    $eventos[] = [
        'fonte'     => 'comentario',
        'timestamp' => $c->created,
        'tipo'      => 'comment_added',
        'usuario'   => $c->user->name ?? '',
        'descricao' => $this->_resumoComentario($c->comentario),
        'publico'   => !$isInterno,
    ];
}

// 4. ticketsanexos
foreach ($anexos as $a) {
    $eventos[] = [
        'fonte'     => 'anexo',
        'timestamp' => $a->created,
        'tipo'      => 'attachment_added',
        'usuario'   => 'Sistema',
        'descricao' => "Arquivo '{$a->arquivo}' anexado.",
        'publico'   => true,
    ];
}

// 5. ticketshoras
foreach ($horas as $h) {
    $min = $this->Ticketshoras->getMinutos($h->horaini, $h->horafin);
    $eventos[] = [
        'fonte'     => 'hora',
        'timestamp' => $h->created ?? $h->data,
        'tipo'      => 'hours_logged',
        'usuario'   => $h->user->name ?? '',
        'descricao' => sprintf('%02d:%02d registrado por %s', floor($min/60), $min%60, $h->user->name ?? ''),
        'publico'   => false, // visível somente para role=0
    ];
}

// Ordenar por timestamp ASC
usort($eventos, fn($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

// Filtrar para cliente
if ((int)$role === 1) {
    $eventos = array_filter($eventos, fn($e) => $e['publico']);
}
```

**Tipos de evento e renderização:**

| `tipo` | Ícone (Bootstrap Glyphicon) | Cor da linha | Texto exibido |
|--------|----------------------------|:------------:|--------------|
| `ticket_created` | `glyphicon-plus` | cinza | "Ticket aberto por [usuário]" |
| `status_changed` | `glyphicon-refresh` | azul | "Status: [A] → [B]" |
| `assigned` | `glyphicon-user` | roxo | "Atribuído para [técnico]" |
| `queue_changed` | `glyphicon-list` | azul | "Movido para fila [fila]" via `C_TicketMovMudancaFila = 952` |
| `transferred` | `glyphicon-transfer` | laranja | "Transferido para [técnico]" via `C_TicketMovTransferencia = 951` |
| `comment_added` | `glyphicon-comment` | verde | Resumo do comentário (100 chars) |
| `attachment_added` | `glyphicon-paperclip` | cinza | "Arquivo [nome] anexado" |
| `hours_logged` | `glyphicon-time` | amarelo | "[H:MM] registrado por [técnico]" |
| `sla_warning` | `glyphicon-warning-sign` | amarelo | "SLA atingiu [%]%" |
| `sla_violated` | `glyphicon-ban-circle` | vermelho | "SLA violado" |
| `resolved` | `glyphicon-ok` | verde | "Resolvido por [técnico]" |
| `closed` | `glyphicon-lock` | vermelho | "Cancelado — motivo: [obs]" |
| `reopened` | `glyphicon-lock-open` | laranja | "Ticket reaberto" |

**Método auxiliar `_descreverMov($m)` — lógica de tradução:**

```php
protected function _descreverMov($m): string {
    $obs = h($m->observacao ?? '');
    $sitNova = (int)$m->sitnova;

    if ($sitNova === (int)C_TicketMovTransferencia)  return "Transferido: $obs";
    if ($sitNova === (int)C_TicketMovMudancaFila)    return "Mudança de fila: $obs";
    if ($sitNova === (int)C_TicketSituacaoFechado)   return "Cancelado. Motivo: $obs";
    if ($sitNova === (int)C_TicketSituacaoResolvido) return $obs ?: "Resolvido.";
    if ($sitNova === (int)C_TicketSituacaoPendente)  return "Movido para 'Aguardando técnico'.";
    if ($sitNova === (int)C_TicketSituacaoEmandamento) return "Movido para 'Em andamento'.";
    if ($sitNova === (int)C_TicketSituacaoRespondido)  return "Respondido: $obs";

    return $obs ?: "Status alterado.";
}
```

---

### Aba 2 — Comentários

**Visível para:** `role = 0` (todos os comentários) e `role = 1` (somente `visivel_cliente = true`).

#### Layout de um comentário

```
┌──────────────────────────────────────────────────────┐
│  [Avatar]  João Silva (Técnico)       15/03 14:22    │
│            [INTERNO]                                  │  ← badge vermelho, só para role=0
│  ─────────────────────────────────────────────────   │
│  O problema foi identificado no módulo de NF-e...    │
└──────────────────────────────────────────────────────┘
```

**Regras de renderização:**
- Comentário de `role = 0`: cabeçalho em azul, rótulo "Técnico"
- Comentário de `role = 1`: cabeçalho em verde, rótulo "Cliente"
- `visivel_cliente = false`: badge `[INTERNO]` vermelho; bloco com fundo `#fff3f3`; escondido de `role = 1`

**Campo novo a ser criado via migration:**
```sql
ALTER TABLE ticketcomentarios ADD COLUMN visivel_cliente BOOLEAN NOT NULL DEFAULT TRUE;
```
Comentários existentes preservam `visivel_cliente = true` (retrocompatível).

**Formulário de novo comentário (somente em tickets não encerrados):**
```
[textarea: Seu comentário]
[☐ Comentário interno (não visível para o cliente)]  ← só para role=0
[Enviar comentário]
```
Usa `POST /ticket-comentarios/add/:idticket` com campo adicional `visivel_cliente`.

---

### Aba 3 — SLA

**Visível para:** todos (com nível de detalhe diferente por role).

#### Layout ERP

```
Política SLA:      Suporte Padrão — P2
Prioridade:        P2 — Alto impacto, alta urgência
Impacto:           Alto
Urgência:          Alta

Primeira resposta:
  Prazo:           15/03/2026 11:32  (2h após abertura)
  Respondido em:   15/03/2026 10:45  ✅ No prazo

Resolução:
  Prazo:           18/03/2026 17:00  (8h úteis)
  Status:          ██████████░░░░░░  62%  EM RISCO
  Tempo restante:  11h 30min

Pausa de SLA:      Não
```

#### Layout Portal Cliente

```
SLA deste atendimento:

  Situação:   EM RISCO  ⚠️
  Prazo:      18/03/2026 17:00
  Progresso:  ██████████░░░░░░  62%
```

#### Campos do banco utilizados

| Campo | Tabela | Observação |
|-------|--------|-----------|
| `sla_policy_id` | tickets | FK → sla_policies |
| `sla_resposta_minutos` | tickets | Minutos SLA resposta |
| `sla_resolucao_minutos` | tickets | Minutos SLA resolução |
| `data_limite_resposta` | tickets | Prazo primeira resposta |
| `data_limite_resolucao` | tickets | Prazo resolução |
| `data_primeira_resposta` | tickets | Quando foi respondido |
| `sla_percentual_consumido` | tickets | % calculado por `SlaRecalculationService` |
| `sla_status` | tickets | `dentro_sla` / `em_risco` / `violado` |
| `sla_resolucao_pausado` | tickets | SLA pausado (aguardando cliente) |
| `prioridade` | tickets | P1–P4 |
| `impacto` | tickets | baixo/medio/alto/critico |
| `urgencia` | tickets | baixa/media/alta/imediata |
| `nome` | sla_policies | Nome da política |

**Todos os campos SLA são condicionais** — verificar `in_array('sla_status', $schema->columns(), true)` antes de exibir a aba.  
Se as colunas não existirem, a aba SLA não aparece.

#### Cores do status SLA

| `sla_status` | Cor Bootstrap | Threshold |
|-------------|:------------:|----------|
| `dentro_sla` | `success` (verde) | `< 80%` |
| `em_risco` | `warning` (amarelo) | `>= 80% e <= 100%` |
| `violado` | `danger` (vermelho) | `> 100%` ou prazo expirado |

---

### Aba 4 — Anexos

**Visível para:** todos (com ABAC).

#### Layout

```
Arquivos anexados (3)

  📎  relatorio_erro.pdf       245 KB   15/03 09:50   [Download] [Visualizar]
  📎  screenshot_tela.png       87 KB   15/03 10:12   [Download] [Visualizar]
  📎  log_sistema.txt           12 KB   16/03 08:30   [Download]
```

#### Campos usados de `ticketsanexos`

| Campo | Observação |
|-------|-----------|
| `id` | PK |
| `idticket` | FK |
| `idempresa` | Verificação ABAC |
| `arquivo` | Nome do arquivo no disco |
| `created` | Data de upload |

> **Caminho físico:** `WWW_ROOT . 'arquivos/tickets/' . $idempresa . '/' . $idticket . '/' . $arquivo`

#### Regras de ação por arquivo

| MIME type | Botão disponível |
|-----------|----------------|
| `image/*` | Download + Visualizar (inline via Magnific Popup — já presente no projeto) |
| `application/pdf` | Download + Visualizar (iframe) |
| Demais | Somente Download |

**Download:** `GET /tickets/download-anexo/:idanexo` (existente — reutilizar sem alteração)  
**Inline:** `GET /tickets/download-anexo/:idanexo?inline=1` (existente)

---

### Aba 5 — Auditoria

**Visível para:** `role = 0` com permissão `historico.audit` **apenas**.  
**Não exibida** para `role = 1` (nunca, nem para admin cliente).

#### Fonte de dados: `ticket_histories`

```sql
SELECT
    th.id,
    th.tipo_evento,
    th.campo_alterado,
    th.valor_anterior,
    th.valor_novo,
    th.created,
    th.metadata,
    u.name AS usuario_nome,
    u.role AS usuario_role
FROM ticket_histories th
LEFT JOIN users u ON u.id = th.usuario_id
WHERE th.ticket_id = :id
ORDER BY th.created ASC, th.id ASC
```

#### Layout tabular

| Data/hora | Usuário | Evento | Campo | Valor anterior | Valor novo |
|-----------|---------|--------|-------|---------------|-----------|
| 15/03 09:32 | João (Cliente) | `created` | — | — | — |
| 15/03 10:45 | Maria (Técnico) | `status_changed` | situacao | 0 (Pendente) | 3 (Em andamento) |
| 15/03 14:22 | Maria (Técnico) | `assigned` | idtecnico_responsavel | null | 5 |
| 16/03 08:30 | Maria (Técnico) | `attachment_added` | — | — | log_sistema.txt |

**Tratamento de valores:**
- Traduzir `situacao` numérico para label via `SituacaoTicket()`
- Exibir `null` como "—"
- Truncar `valor_anterior`/`valor_novo` em 200 chars com tooltip para valor completo

---

## 10. Regras de Visualização de Comentários Internos e Públicos

### 10.1 Migration necessária

```sql
-- Adiciona campo visivel_cliente com default true (retrocompatível)
ALTER TABLE ticketcomentarios
    ADD COLUMN IF NOT EXISTS visivel_cliente BOOLEAN NOT NULL DEFAULT TRUE;

-- Comentários de técnicos (role=0) feitos antes desta migração: manter como públicos
-- Não há retroativo automático — técnico pode marcar manualmente se necessário
```

### 10.2 Tabela de decisão de visibilidade

| `visivel_cliente` | `role` do usuário logado | Exibir? |
|:----------------:|:------------------------:|:-------:|
| `true` | 0 (técnico) | ✅ sim |
| `true` | 1 (cliente) | ✅ sim |
| `false` | 0 (técnico) | ✅ sim (com badge [INTERNO]) |
| `false` | 1 (cliente) | ❌ não (filtrado na query) |

### 10.3 Query para cliente

```php
// Role = 1: filtrar comentários internos
$comentarios = $this->Ticketcomentarios->find('all', [
    'contain' => ['Users' => ['fields' => ['Users.name', 'Users.role']]]
])
->where([
    'Ticketcomentarios.idticket' => $idticket,
    'Ticketcomentarios.visivel_cliente' => true,
])
->order(['Ticketcomentarios.id ASC'])
->toArray();
```

### 10.4 Query para técnico

```php
// Role = 0: todos os comentários
$comentarios = $this->Ticketcomentarios->find('all', [
    'contain' => ['Users' => ['fields' => ['Users.name', 'Users.role']]]
])
->where(['Ticketcomentarios.idticket' => $idticket])
->order(['Ticketcomentarios.id ASC'])
->toArray();
```

### 10.5 Persistência do campo no `TicketcomentariosController::add()` e `apiAdd()`

```php
// Patch do campo novo
$visivel = (bool)($data['visivel_cliente'] ?? true);
// Forçar true quando remetente é cliente (cliente não pode criar comentário interno)
if ((int)$this->Auth->user('role') === 1) {
    $visivel = true;
}
$comentario->visivel_cliente = $visivel;
```

---

## 11. Regras de SLA

### 11.1 Como o SLA é calculado (referência: `SlaRecalculationService`)

1. `elapsed_min = (agora - tickets.created) em minutos`
2. `pct_resolucao = min(999.99, elapsed_min / sla_resolucao_minutos * 100)`
3. `pct_resposta = min(999.99, elapsed_min / sla_resposta_minutos * 100)` → só se `data_primeira_resposta IS NULL`
4. `pct_final = max(pct_resolucao, pct_resposta)`
5. **Violado se:** `data_limite_resolucao < agora` OU `pct_resolucao > 100` OU (`data_limite_resposta < agora` E `data_primeira_resposta IS NULL`)
6. **Status:** `violado` > `em_risco` (pct ≥ 80) > `dentro_sla`
7. **Pausa:** se `sla_resolucao_pausado = true`, o cálculo não avança — retornar o `sla_percentual_consumido` salvo no banco

### 11.2 Exibição no módulo Histórico

O módulo **somente lê** os campos SLA do banco — não recalcula em tempo real na view.  
Para obter o valor mais atualizado, o CLI `bin/cake tickets_sla recalculate` deve rodar periódico.

### 11.3 Para tickets sem colunas SLA (legado)

```php
$temSla = in_array('sla_status', $this->Tickets->getSchema()->columns(), true);

// No controller:
$this->set('temSla', $temSla);

// Na view: exibir aba SLA somente se $temSla === true
```

### 11.4 Prioridade derivada da severidade legada

| `severidade` (legado) | `impacto` | `urgencia` | `prioridade` |
|-----------------------|-----------|-----------|------------|
| `baixa` | baixo | baixa | P4 |
| `media` | medio | media | P3 |
| `alta` | alto | alta | P2 |
| `urgente` | critico | imediata | P1 |

Fonte: `TicketClassificationService::impactoUrgenciaFromSeveridade()`

---

## 12. Anexos

### 12.1 Listagem de anexos no módulo

```php
$anexos = $this->Ticketsanexos->find('all')
    ->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])
    ->order(['id ASC'])
    ->toArray();
```

### 12.2 Verificação de existência física

```php
foreach ($anexos as $a) {
    $caminho = $this->dirAnexos($a->idempresa, $a->idticket) . DS . $a->arquivo;
    $a->arquivo_existe = file_exists($caminho);
    $a->tamanho_fmt    = $a->arquivo_existe ? $this->_formatBytes(filesize($caminho)) : null;
}
```

### 12.3 Regras de download

| Verificação | Falha → |
|-------------|--------|
| `ticketsanexos.idempresa = sessao.idempresa` | Flash error + redirect referer |
| `role = 1`: autor/cliente/permissaoacesso (regra 4.3) | Flash error + redirect dashboard |
| `file_exists($caminho)` | Flash "arquivo não localizado" + redirect |

> Estas regras **já existem** em `TicketsController::downloadAnexo()`. O módulo Histórico apenas **reutiliza** a mesma URL/action — não duplica a lógica.

---

## 13. Auditoria

### 13.1 Tabela `ticket_histories` (já existe — migration 20260321140200)

```sql
CREATE TABLE ticket_histories (
    id          SERIAL PRIMARY KEY,
    ticket_id   INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    usuario_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    tipo_evento VARCHAR(60) NOT NULL,
    campo_alterado  VARCHAR(100),
    valor_anterior  TEXT,
    valor_novo      TEXT,
    created     TIMESTAMP NOT NULL DEFAULT NOW(),
    metadata    JSONB
);
CREATE INDEX idx_ticket_histories_ticket_id ON ticket_histories(ticket_id);
CREATE INDEX idx_ticket_histories_created   ON ticket_histories(created);
```

### 13.2 Eventos a gravar (responsabilidade do `TicketHistoryLogger`)

O módulo Histórico **somente lê** `ticket_histories`. A gravação já é feita por `TicketHistoryLogger` nos pontos do `TicketsController` onde ocorrem mudanças. Nenhum ponto novo de escrita é introduzido por este módulo.

Eventos já gravados:

| Onde é chamado | `tipo_evento` gravado |
|---------------|----------------------|
| `TicketsController::add()` | `created` |
| `TicketsController::edit()` / `apiSaveTicket()` | `status_changed`, `assigned`, `queue_changed` |
| `TicketsController::startTicket()` | `status_changed` |
| `TicketcomentariosController::add()` / `apiAdd()` | `comment_added` |
| `TicketsController::deleteAnexo()` | `attachment_deleted` |
| upload de anexo | `attachment_added` |

### 13.3 `TicketHistoryLogger::log()` — contrato de uso

```php
// Falha silenciosa — nunca bloquear operação principal
use App\Service\Ticket\TicketHistoryLogger;

$logger = new TicketHistoryLogger(TableRegistry::get('TicketHistories'));
$logger->log(
    ticketId: $ticket->id,
    usuarioId: $this->Auth->user('id'),
    tipoEvento: 'status_changed',
    campoAlterado: 'situacao',
    valorAnterior: (string)$sitAntiga,
    valorNovo: (string)$sitnova,
    metadata: ['origem' => 'HistoricoController']  // optional
);
```

---

## 14. Critérios de Aceite

### CA-H01 — Listagem ERP

- [ ] Técnico com `role = 0` acessa `/historico/index` e vê todos os tickets da empresa ativa
- [ ] Filtros de período, assunto, situação, técnico, fila funcionam individualmente e combinados
- [ ] DataTables carrega server-side (sem carregar todos os registros de uma vez)
- [ ] Colunas `prioridade`, `tipo_ticket`, `sla_status` só aparecem se as colunas existirem no banco
- [ ] Ordenação por qualquer coluna funciona
- [ ] Filtros são persistidos na sessão (reabrir a tela mantém os filtros)

### CA-H02 — Listagem Portal Cliente

- [ ] Cliente com `role = 1` acessa `/historico/cliente` e vê **apenas** tickets do próprio `idcliente`
- [ ] Cliente não consegue ver tickets de outro cliente mesmo passando `idcliente=X` na URL
- [ ] Filtros de período e assunto funcionam
- [ ] Coluna SLA exibe texto simplificado: "No prazo" / "Em risco" / "Atrasado"

### CA-H03 — ABAC entre empresas

- [ ] Usuário com acesso a duas empresas, ao trocar de empresa, vê apenas tickets da empresa ativa
- [ ] URL com `/historico/view/999` onde o ticket 999 é de outra empresa retorna 404 ou redireciona com erro

### CA-H04 — Timeline

- [ ] Eventos aparecem em ordem cronológica crescente
- [ ] Eventos de `ticketsmovs` com `sitnova = 951` exibem texto "Transferido: [obs]"
- [ ] Eventos de `ticketsmovs` com `sitnova = 952` exibem texto "Mudança de fila: [obs]"
- [ ] Para `role = 1`, eventos com `publico = false` não aparecem
- [ ] Para `role = 1`, entradas de horas (`hours_logged`) não aparecem

### CA-H05 — Comentários internos

- [ ] Técnico pode marcar um comentário como interno ao enviar
- [ ] Comentário interno exibe badge `[INTERNO]` em vermelho para técnicos
- [ ] Comentário interno **não aparece** na aba Comentários quando acessado por `role = 1`
- [ ] Comentário interno **não aparece** na Timeline quando acessado por `role = 1`
- [ ] Cliente com `role = 1` não consegue enviar comentário com `visivel_cliente = false` (campo ignorado/forçado para true no servidor)
- [ ] Comentários existentes (sem o novo campo) exibem normalmente (default `visivel_cliente = true`)

### CA-H06 — SLA

- [ ] Aba SLA não aparece quando colunas enterprise não existem no banco
- [ ] Barra de progresso verde para `dentro_sla`, amarela para `em_risco`, vermelha para `violado`
- [ ] Ticket com `sla_resolucao_pausado = true` exibe "SLA pausado" e não incrementa a barra
- [ ] Para `role = 1`, a aba mostra apenas: status simplificado, prazo, barra de progresso

### CA-H07 — Anexos

- [ ] Cliente com `role = 1` faz download de anexo do próprio ticket com sucesso
- [ ] Cliente com `role = 1` tenta acessar anexo de ticket de outro cliente → erro 403 ou Flash + redirect
- [ ] Arquivo físico inexistente exibe mensagem de erro (não quebra a página)
- [ ] Imagens abrem inline via Magnific Popup
- [ ] PDFs abrem inline via iframe

### CA-H08 — Auditoria

- [ ] Aba Auditoria não aparece para `role = 1`
- [ ] Aba Auditoria não aparece para `role = 0` sem permissão `historico.audit`
- [ ] Aba Auditoria exibe todos os registros de `ticket_histories` em ordem cronológica
- [ ] Campo `situacao` exibe o label textual (não o número inteiro)
- [ ] Valores nulos exibidos como "—"

### CA-H09 — Exportação

- [ ] Exportação CSV aplica os mesmos filtros ativos da listagem
- [ ] Exportação não disponível para `role = 1`
- [ ] Exportação de listagem com 10.000+ registros não causa timeout (paginar ou stream)

### CA-H10 — Segurança

- [ ] Nenhum parâmetro GET/POST permite alterar o `idempresa` do filtro para outro valor que não seja o da sessão (exceto admin)
- [ ] Parâmetro `idcliente` na URL é ignorado para `role = 1` — usa apenas o `idcliente` da sessão
- [ ] Download de anexo reusa a action existente — não há nova lógica de autorização duplicada

---

## 15. Edge Cases

### EC-H01 — Ticket sem `sla_policy_id`

**Cenário:** Ticket criado antes da migração SLA ou com SLA não configurado.  
**Comportamento esperado:** Aba SLA exibe "Nenhuma política SLA aplicada a este ticket." sem campos em branco ou erros PHP.  
**Verificação:** `if (empty($ticket->sla_policy_id) || !$temSla) → exibir mensagem`

---

### EC-H02 — Ticket sem técnico responsável

**Cenário:** `tickets.idtecnico_responsavel = null`.  
**Comportamento esperado:**
- Coluna "Técnico" na listagem exibe "Sem responsável" (string, não null/vazio)
- Timeline não exibe evento de atribuição
- Aba SLA exibe normalmente

---

### EC-H03 — Arquivo de anexo não existe no disco

**Cenário:** Registro em `ticketsanexos` existe, mas o arquivo físico foi deletado.  
**Comportamento esperado:** Botões "Download" e "Visualizar" ficam desabilitados com tooltip "Arquivo não disponível". A linha ainda aparece na lista de anexos.

---

### EC-H04 — `ticket_histories` vazia para um ticket

**Cenário:** Ticket criado antes da implementação do `TicketHistoryLogger`.  
**Comportamento esperado:**
- Timeline monta eventos somente de `ticketsmovs`, `ticketcomentarios`, `ticketsanexos` e `ticketshoras`
- Aba Auditoria exibe "Nenhum registro de auditoria disponível para este ticket."
- Nenhum erro ou exception

---

### EC-H05 — `ticketsmovs.datetime` em formato string `d/m/Y H:i:s`

**Cenário:** O campo `datetime` em `ticketsmovs` é armazenado como string (formato `15/03/2026 14:22:00`), não como timestamp.  
**Comportamento esperado:** O método `_parseDatatime()` converte para objeto `DateTime` antes de ordenar:

```php
protected function _parseDatatime($v): string {
    if ($v instanceof \DateTimeInterface) {
        return $v->format('Y-m-d H:i:s');
    }
    if (is_string($v) && preg_match('/^\d{2}\/\d{2}\/\d{4}/', $v)) {
        $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $v);
        return $dt ? $dt->format('Y-m-d H:i:s') : $v;
    }
    return (string)$v;
}
```

---

### EC-H06 — Usuário `role = 1` com `permissaoacesso = false` tenta acessar ticket de outro usuário da empresa

**Cenário:** Cliente A tenta acessar `/historico/cliente-view/99` onde o ticket 99 foi aberto pelo Cliente B, mas ambos são da mesma empresa.  
**Comportamento esperado:**
- Verificar: `ticket.idautor != iduser` E `ticket.idcliente != cliente_resolvido.id` E `permissaoacesso = false`
- Retornar HTTP 403 com Flash "Você não possui permissão para acessar este atendimento." e redirect para `/historico/cliente`
- **Não** retornar 404 (evitar enumeração de IDs com mensagens genéricas de "não encontrado")

---

### EC-H07 — Ticket com `idautor` de usuário excluído

**Cenário:** `users.id` referenciado em `tickets.idautor` foi deletado do banco.  
**Comportamento esperado:**
- JOIN `LEFT` com `users` garante que o ticket não desaparece da listagem
- Coluna "Solicitante" exibe "Usuário removido"
- Timeline exibe eventos sem nome de autor (exibe "Sistema" ou "—")

---

### EC-H08 — Filtro de data com `periodo_ini > periodo_fim`

**Cenário:** Usuário submete formulário com data inicial posterior à data final.  
**Comportamento esperado:** Flash de validação "Data inicial não pode ser posterior à data final." Listagem não é executada. Sem exception ou resultado vazio silencioso.

---

### EC-H09 — Ticket com `datafinalizado` null em filtro de período

**Cenário:** Usuário filtra por período de encerramento mas há tickets abertos (sem `datafinalizado`).  
**Comportamento esperado:**
- Filtro de período usa `tickets.created` por padrão
- Se usuário explicitamente filtra por "data de encerramento", tickets sem `datafinalizado` não aparecem (expected — não é um bug)
- Documentar no tooltip do filtro: "Tickets sem data de encerramento não são retornados ao filtrar por este campo."

---

### EC-H10 — Comentário sem `idautor` (legado ou sistema)

**Cenário:** `ticketcomentarios.idautor = null` para comentários antigos ou gerados por automação.  
**Comportamento esperado:**
- JOIN `LEFT` com `users`
- Nome exibido: "Sistema" quando `idautor = null`
- Rótulo de papel: sem badge de "Técnico" ou "Cliente"
- `visivel_cliente` considerado `true` para comentários legados sem o campo

---

## Apêndice A — Rotas a Registrar

```php
// config/routes.php — adicionar no Router::scope('/', ...)

$routes->connect('/historico',                    ['controller' => 'Historico', 'action' => 'index']);
$routes->connect('/historico/index',              ['controller' => 'Historico', 'action' => 'index']);
$routes->connect('/historico/view/*',             ['controller' => 'Historico', 'action' => 'view']);
$routes->connect('/historico/exportar',           ['controller' => 'Historico', 'action' => 'exportar'])->setMethods(['GET']);
$routes->connect('/historico/cliente',            ['controller' => 'Historico', 'action' => 'cliente']);
$routes->connect('/historico/cliente-view/*',     ['controller' => 'Historico', 'action' => 'clienteView']);
```

---

## Apêndice B — Alterações no AppController

```php
// $controllerToMenuMap — adicionar:
'historico' => 'historicoActive',

// $menuStates — adicionar:
'historicoActive' => '',
```

---

## Apêndice C — Migration

```sql
-- 1. Campo visivel_cliente em ticketcomentarios
ALTER TABLE ticketcomentarios
    ADD COLUMN IF NOT EXISTS visivel_cliente BOOLEAN NOT NULL DEFAULT TRUE;

-- 2. Índices para queries do módulo
CREATE INDEX IF NOT EXISTS idx_tickets_created          ON tickets(created);
CREATE INDEX IF NOT EXISTS idx_tickets_datafinalizado    ON tickets(datafinalizado);
CREATE INDEX IF NOT EXISTS idx_tickets_idcliente         ON tickets(idcliente);
CREATE INDEX IF NOT EXISTS idx_tickets_idempresa_created ON tickets(idempresa, created DESC);
CREATE INDEX IF NOT EXISTS idx_ticketcomentarios_idticket_visivel
    ON ticketcomentarios(idticket, visivel_cliente);
```

---

## Apêndice D — Estrutura de Arquivos

```
src/
  Controller/
    HistoricoController.php        ← novo; estende AppController

  Template/
    Historico/
      index.ctp                    ← layout default; DataTables server-side
      view.ctp                     ← abas: timeline | comentarios | sla | anexos | auditoria
      cliente.ctp                  ← layout client; filtros reduzidos
      cliente_view.ctp             ← detalhe para role=1; abas: timeline | comentarios | sla | anexos
      Element/
        timeline_evento.ctp        ← fragmento: 1 card de evento
        sla_barra.ctp              ← fragmento: barra de progresso SLA
        comentario_card.ctp        ← fragmento: 1 card de comentário
        anexo_row.ctp              ← fragmento: 1 linha de anexo
```
