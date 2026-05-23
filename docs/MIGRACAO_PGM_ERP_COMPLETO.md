# Migração para o desenho `pgm_erp_completo.html` / `pgm_erp_completo_2.html`

> Plano de reestruturação do portal para adotar o **layout, telas e fluxos** do mockup (~100 telas SPA em JavaScript).
> Referência preferida: **`docs/reference/pgm_erp_completo_2.html`** (copiar do `Downloads` do posto de trabalho).
> Status: **EM EXECUÇÃO** — Fases 0–5 com protótipos lado-a-lado; legado Clientes com `clientes-layout-unificado` em produção.

### Auditoria automática (após colocar o HTML no repo)

```bash
php bin/audit_pgm_erp_mock.php
```

### Switchover por módulo (sem apagar legado)

`config/portal_ui.php` + `.env`:

```ini
PORTAL_UI_MODE=mixed
PORTAL_PREMIUM_MODULES=clientes
```

Helper: `App\Utility\PortalUi::isPremiumModule('clientes')` — use em controllers só quando o módulo *-prototype estiver 100% equivalente ao legado.

## ✅ Decisões aprovadas (questionário 19/05/2026)

| Decisão | Escolha |
|---------|---------|
| **Escopo** | Tudo (87 telas — sem PCP) |
| **Estratégia** | Lado-a-lado: rotas `/portal/{modulo}-prototype/*` convivem com legado |
| **Stack** | Cake `.ctp` + jQuery (padrão `ServicedeskPrototype` atual) |
| **PCP/Indústria** | Pular (13 telas adiadas) |
| **Switchover** | Decidido por módulo, após validação |

**Implicações:**
- Cada módulo entrega rotas `*-prototype` sem alterar controllers/views existentes
- Template antigo permanece intocado até o módulo premium ser aprovado pela operação
- Sidebar/topbar/CSS são **compartilhados** (Fase 0) — sem isso cada módulo duplica 30 KB de HTML/CSS
- React `dashboard-react/` continua só para componentes pesados específicos (ex.: kanban drag-drop)

---

## 1. Inventário do mockup

| Categoria | Telas | IDs `pg-*` |
|-----------|-------|------------|
| **Comercial · Orçamentos** | 8 | `pg-lista`, `pg-novo`, `pg-revisao`, `pg-print`, `pg-esign`, `pg-sucesso`, `pg-orc-faturamento`, `pg-orc-cobranca` |
| **Financeiro · Bancos** | 6 | `pg-bancos`, `pg-contas`, `pg-extrato`, `pg-conciliacao`, `pg-transferencias`, `pg-fluxo-caixa` |
| **Financeiro · Outros** | 6 | `pg-financeiro`, `pg-titulos`, `pg-contas-pagar`, `pg-nfe`, `pg-dre`, `pg-relatorios-fin` |
| **Ordens de Serviço (OS)** | 8 | `pg-os-lista`, `pg-os-abertura`, `pg-os-execucao`, `pg-os-aprovacao`, `pg-os-conclusao`, `pg-os-faturamento`, `pg-os-cobranca`, `pg-os-sucesso`, `pg-os-kanban` |
| **Clientes & Fornecedores** | 7 | `pg-clientes`, `pg-cliente-novo`, `pg-cliente-360`, `pg-export-clientes`, `pg-import-clientes`, `pg-fornecedores`, `pg-fornecedor-novo`, `pg-fornecedor-360`, `pg-vendedores` |
| **Produtos · Estoque** | 9 | `pg-produtos`, `pg-produto-novo`, `pg-produto-detalhe`, `pg-estoque`, `pg-estoque-log`, `pg-pc-lista`, `pg-pc-novo`, `pg-inventario`, `pg-inv-historico`, `pg-import-produtos`, `pg-precos`, `pg-precificacao`, `pg-historico-precos`, `pg-relatorios-vendas` |
| **Service Desk** | 26 | `pg-sd-fila`, `pg-sd-ticket`, `pg-sd-portal`, `pg-sd-portal-novo`, `pg-sd-kanban`, `pg-sd-kb`, `pg-sd-config`, `pg-sd-perm`, `pg-sd-fat`, `pg-sd-relatorios`, `pg-sd-dashboard`, `pg-sd-meus`, `pg-sd-grupo`, `pg-sd-aprovacoes`, `pg-sd-csat`, `pg-sd-cmdb`, `pg-sd-problemas`, `pg-sd-mudancas`, `pg-sd-contratos`, `pg-sd-calendar`, `pg-sd-templates`, `pg-sd-automacoes-editor`, `pg-sd-detalhe-kb`, `pg-sd-detalhe-fatura`, `pg-sd-integracoes` |
| **Indústria · PCP** | 13 | `pg-engenharia`, `pg-bom`, `pg-roteiro`, `pg-centro-trabalho`, `pg-configurador`, `pg-pcp-dashboard`, `pg-mrp`, `pg-op-lista`, `pg-op-detalhe`, `pg-apontamento`, `pg-pcp-cronograma`, `pg-qualidade-ind`, `pg-expedicao` |
| **Sistema · Empresas/RBAC** | 9 | `pg-config`, `pg-empresa`, `pg-empresas`, `pg-empresa-nova`, `pg-usuarios`, `pg-auditoria`, `pg-acesso-central`, `pg-acesso-papeis`, `pg-acesso-usuario` |
| **Outros** | `pg-home` (dashboard) | 1 |

**Total: ~100 telas-tela cheia + dezenas de modais + seletor multi-empresa fixo no topbar.**

### Já implementado no portal (branch `cursor/servicedesk-prototype-test`)

Service Desk Protótipo cobre **8 das 26 telas SD**:
- `dashboard`, `fila`, `kanban`, `kb`, `meus`, `aprovacoes`, `calendar`, `portal/portal_novo`
- Restantes (`ticket`, `csat`, `cmdb`, `problemas`, `mudancas`, `contratos`, `config`, `perm`, `fat`, `relatorios`, `templates`, `automacoes-editor`, `detalhe-kb`, `detalhe-fatura`, `integracoes`, `grupo`) **pendentes**.

### Módulos com controllers existentes no portal

| Mockup | Controller atual | Cobertura |
|--------|------------------|-----------|
| Orçamentos | `OrcamentosController` | ✅ existe (UI antiga) |
| Clientes | `ClientesController` | ✅ existe |
| Produtos · Estoque | `ProdutosController` | ⚠ parcial |
| Ordens de Serviço | `OrdensservicoController` | ✅ existe |
| Financeiro | `FinanceiroController`, `FinanceiroBancosController` | ⚠ parcial |
| Service Desk | `ServicedeskController` + `ServicedeskPrototypeController` (novo) | 🟡 em migração |
| Contratos SLA | `ContractManagementController`, `PortalContratosController` | ✅ existe |
| Empresas / RBAC | `EmpresasController`, `RbacAccessRequestsController` | ✅ existe |
| **Indústria · PCP** | ❌ **NÃO EXISTE** | módulo inteiro novo (13 telas) |
| **Multi-empresa seletor topbar** | ❌ não existe | feature transversal nova |

---

## 2. Estratégia recomendada

### Princípios (alinhados ao workspace rule)

1. **Não fazer rewrite total** — telas antigas continuam funcionando até substituição validada
2. **Padrão protótipo lado-a-lado** — novas rotas em `/portal/{modulo}-prototype/*`, igual ao Service Desk já feito
3. **Por módulo, não por tela isolada** — completar um módulo antes de partir para o próximo
4. **Templates Cake `.ctp` + Helpers**, NÃO React/SPA — mantém o stack atual (CakePHP 3.10 + jQuery)
5. **CSS premium centralizado** — extrair os ~22 KB de CSS comum do mockup para um único `pgm-erp-prototype.css`
6. **Data services** — replicar o padrão `ServicedeskPrototypeDataService` para cada módulo

### Anti-padrões a evitar

- ❌ Apagar `Tickets/index.ctp`, `Orcamentos/lista.ctp` etc. antes de a versão nova estar 100% validada em produção
- ❌ Refazer `AppController` ou layout `default.ctp` (continua servindo o ERP atual)
- ❌ Trazer todo o JS SPA do mockup (`goTo()`, estado local) — rotas Cake são server-side
- ❌ Migrar 100 telas num único PR

---

## 3. Fases propostas

### Fase 0 — Fundação (1–2 dias)

- [x] Service Desk Protótipo (parte feita)
- [ ] **CSS premium isolado** `webroot/dist/css/pgm-erp-prototype.css` com variáveis + shell (sidebar, topbar, cards, badges, tabela, modal)
- [ ] **Layout base** `src/Template/Layout/erp_prototype.ctp` (sidebar + topbar + seletor empresa)
- [ ] **Helper compartilhado** `ErpPrototypeHelper` (badges, stepper, breadcrumb)
- [ ] **Seletor multi-empresa** real (lê `empresas` da sessão, troca `idempresa_ativa`)

### Fase 1 — Completar Service Desk Protótipo

Controller `ServicedeskPrototypeController` já mapeia as **18 telas** via `screenDefinitions()` e `display/screen.ctp` (com `ref_page`). Templates dedicados:

- [x] `aprovacoes`, `calendar`, `kb`, `meus`, `portal`, `portal_novo` — entregues anteriormente
- [x] **`cmdb`** — Configuration Items com KPIs por tipo e tabela rica (fase 1, hoje)
- [x] **`contratos`** — contratos SLA + indicador de status (fase 1, hoje)
- [x] **`problemas`** — Gestão de Problemas (ITIL) com tickets candidatos (fase 1, hoje)
- [x] **`mudancas`** — Gestão de Mudanças (proxy via tickets P1) (fase 1, hoje)
- [x] Telas SD com `ref/*.ctp` e dados reais (fila, kanban, meus, cmdb, …); CSAT com link histórico/CSV e operacional clássico
- [ ] `ticket` (detalhe dedicado), refinamentos `config`/`perm`/`integracoes` vs módulos RBAC legado

### Fase 2 — Comercial / Orçamentos · OS

- [x] **`OrcamentosPrototypeController`** com `lista()` funcional + roteamento para wizard via `view($page)`
- [x] **`OrdensservicoPrototypeController`** com `lista()` + roteamento similar
- [x] Templates `lista.ctp` para ambos (KPIs reais via Cake ORM, status badges, busca, link para módulo clássico)
- [x] Template `placeholder.ctp` para telas wizard ainda não implementadas
- [x] Rotas em `config/routes.php`: `/orcamentos-prototype/*` e `/ordens-prototype/*`
- [x] Sidebar premium atualizada para apontar para essas rotas (`'url' => null` → URL Cake)
- [x] **Bridge orçamentos:** lista → `detalhe`; `?id=` em revisao/print/esign → detalhe/PDF/view legado; wizard `novo` grava rascunho real
- [x] **Bridge OS:** lista → `detalhe`; `?id=` em execucao…cobranca → detalhe; abertura grava OS; **Editar** → `Ordensservico/edit`
- [ ] **Sub-fases pendentes** — cada uma vira PR isolado:
  - `pg-orc-faturamento`, `pg-orc-cobranca` (Faturamento/Cobrança)
  - `pg-os-kanban` (board completo)

### Fase 3 — Clientes · Produtos · Estoque · Fornecedores

- [x] **`ClientesPrototypeController`** com `lista()` funcional (KPIs PJ/PF/ativos/inativos) + `view($page)` para novo/360/import/export
- [x] **`ProdutosPrototypeController`** com `lista()` e `estoque()` funcionais (KPIs por tipo, alertas de estoque baixo/zerado) + `view($page)` para preços/log/inventário/import
- [x] **`FornecedoresPrototypeController`** com placeholder informativo (não há tabela `fornecedores` dedicada no portal — fornecedor hoje vive em `clientes` PJ ou no módulo Fiscal)
- [x] Templates: `lista.ctp` + `estoque.ctp` + `placeholder.ctp` em cada módulo
- [x] Rotas em `config/routes.php`: `/clientes-prototype/*`, `/produtos-prototype[/estoque]/*`, `/fornecedores-prototype/*`
- [x] Sidebar premium: itens Clientes, Produtos, Estoque, Preços, Histórico Preços e Fornecedores apontam para essas rotas
- [x] **Bridge protótipo → legado (dados reais):** lista `clientes-prototype` → novo `Clientes/add`, linha/ações → `Clientes/visao360` + `edit`; KPI inadimplentes via `faturas`
- [x] **Bridge produtos:** `novo`/`detalhe?id=` → `Produtos/add` e `edit`; estoque-log/inventário → `Produtos/estoque`; lista/estoque abrem edição legada; atalho módulo clássico
- [x] **Bridge fornecedores:** lista PJ real (`clientes` tipo jurídica) → `Clientes/visao360` + `edit`; `novo` → `Clientes/add`; `360?id=` → visão 360 legada
- [ ] Sub-fases pendentes (cada uma vira PR): KPIs receita/top5/segmento na lista clientes, importador produtos dedicado, unificar shell `erp_prototype` com `clientes-layout-unificado` na 360°

### Fase 4 — Financeiro / Bancos / Fiscal

- [x] **`FinanceiroPrototypeController`** com `lista()` (dashboard com 7 KPIs: CR/CP/saldo do mês), `titulos()` (CR via `faturas`), `contasPagar()` (despesas via `financeiro_lancamentos`) e `view($page)` para wizard NF-e/DRE/Relatórios/Cobrança
- [x] **`BancosPrototypeController`** com `lista()` (cards premium das contas bancárias) e `view($page)` para extrato/conciliação/transferências/fluxo
- [x] Templates: `lista.ctp`, `titulos.ctp`, `contas_pagar.ctp`, `placeholder.ctp` (Financeiro); `lista.ctp` + `placeholder.ctp` (Bancos)
- [x] Rotas em `config/routes.php`: `/financeiro-prototype/*` e `/bancos-prototype/*`
- [x] Sidebar premium: itens Financeiro/Faturamento/Contas a Receber/Cobrança/Contas a Pagar/NF-e/DRE/Relatórios + Bancos/Contas/Extrato/Conciliação/Transferências/Fluxo apontam para as rotas
- [x] **Bridge protótipo → legado:** `view(cobranca|orc-cobranca|orc-faturamento)` → `Faturamento/index`; `relatorios-fin` → `FinanceiroRelatorios/index`; `view(contas)` (Bancos) → `lista`; títulos → `Faturas/edit`; lançamentos CP → `Financeiro/editDespesa` ou `editReceita`; dashboard → atalho `Financeiro/index`; cards banco → `FinanceiroBancos/edit` + extrato filtrado
- [ ] Sub-fases pendentes (PRs futuros): campos fictícios do mock (projeção de caixa avançada), wizard PIX dedicado, KPIs segmento na lista financeira

### Fase 5 — Sistema · RBAC · Multi-empresa

- [x] **`EmpresasPrototypeController`** com `lista()` (multi-empresa: razão social, fantasia, CNPJ, contato, usuários, URL ERP, status, atalho “Usar”) + `view($page)` para nova/editar
- [x] **`SistemaPrototypeController`** com `usuarios()`, `acessoCentral()`, `acessoPapeis()`, `auditoria()` (lê `users`, `rbac_roles`, `rbac_users_roles`, `rbac_access_requests`, `audit_logs`) + `view($page)` para config/empresa/acesso-usuario/acesso-filiais
- [x] Templates: `lista.ctp`, `placeholder.ctp` (Empresas); `usuarios.ctp`, `acesso_central.ctp`, `acesso_papeis.ctp`, `auditoria.ctp`, `placeholder.ctp` (Sistema)
- [x] Rotas: `/empresas-prototype/*` e `/sistema-prototype/*` (mapeando `usuarios`, `acesso-central`, `acesso-papeis`, `auditoria`, `config` para actions dedicadas)
- [x] Sidebar premium: Empresas + Configurações + Empresa + Usuários + Controle de Acesso + Papéis + Auditoria de Acessos + Auditoria LGPD apontam para as rotas
- [x] Acesso restrito a usuários `admin = true` (validação no `isAuthorized`)
- [ ] Sub-fases pendentes (PRs futuros): assistente de nova empresa (5 passos), ficha de acesso de usuário (matriz de papéis × empresas), auditoria de acessos com filtros, simulador "View as user", configurações por empresa

### Fase 6 — Indústria / PCP (**MÓDULO NOVO** — 10–15 dias)

Não existe no portal hoje. Decidir antes:
- Vale a pena criar? Há equipe usando hoje?
- Se sim: BOM, roteiros, MRP, OPs, apontamento, cronograma Gantt, qualidade, expedição — exige modelagem de banco nova

### Fase 7 — Switchover

- Configuração `Portal.ui_mode = legacy|premium` por empresa
- Rotas `/portal/v2/*` viram default
- Templates antigos arquivados em `app_old/` (não removidos)

---

## 4. Estrutura de pastas proposta

```
src/
  Controller/
    Prototype/                 ← novo namespace (opcional)
      OrcamentosPrototypeController.php
      OrdensservicoPrototypeController.php
      ClientesPrototypeController.php
      ProdutosPrototypeController.php
      FinanceiroPrototypeController.php
      BancosPrototypeController.php
      EmpresasPrototypeController.php
      AcessoCentralController.php
      PcpPrototypeController.php             (Fase 6)
    ServicedeskPrototypeController.php       (já existe)

  Service/
    Prototype/
      OrcamentoDataService.php
      OrdemServicoDataService.php
      ClienteDataService.php
      ProdutoDataService.php
      FinanceiroDataService.php
      BancoDataService.php
      EmpresaDataService.php

  View/Helper/
    ErpPrototypeHelper.php                   (badges, stepper, chips)
    ServicedeskPrototypeHelper.php           (já existe)

  Template/
    Layout/
      erp_prototype.ctp                      (sidebar + topbar + seletor)
      servicedesk_prototype.ctp              (já existe; vai herdar do erp_prototype)
    Element/
      ErpPrototype/
        sidebar.ctp
        topbar.ctp
        empresa_selector.ctp
        breadcrumb.ctp
    OrcamentosPrototype/
    OrdensservicoPrototype/
    ClientesPrototype/
    ProdutosPrototype/
    FinanceiroPrototype/
    BancosPrototype/
    EmpresasPrototype/
    AcessoCentral/

webroot/dist/css/
  pgm-erp-prototype.css                      (CSS premium base)
  pages/
    pgm-servicedesk-prototype.css            (já existe)
    pgm-orcamentos-prototype.css
    pgm-os-prototype.css
    pgm-bancos-prototype.css
    ...
```

---

## 5. Decisões pendentes secundárias (decidir conforme avança)

- **Multi-empresa do topbar** — seletor real (troca `idempresa_ativa` na sessão) ou só visual? **Recomendado: real**, usa `EmpresasController::switchSession` existente
- **Tempo/sprints** — sugestão: 1 módulo / sprint semanal (5 dias úteis)
- **PR strategy** — 1 PR por fase, exceto Fase 0 que é fundação (PR único)
- **Switchover por módulo** — Configure key `Portal.premium_modules` = lista de módulos onde o premium é default

---

## 6. Métricas de progresso

| Métrica | Hoje | Meta Fase 1 | Meta Fase 7 |
|---------|------|-------------|-------------|
| Telas premium prontas | 8 | 26 (SD completo) | 87 |
| Módulos cobertos | 1 (SD) | 1 | 7 |
| Linhas de código premium | ~1.500 | ~5.000 | ~25.000 |
| Cobertura RBAC | parcial | total SD | total ERP |

---

## 7. Como criar uma nova tela premium (cookbook)

A Fase 0 já entregou shell, sidebar, topbar, layout e helper compartilhados.
Para qualquer nova tela `pg-*` do mockup, siga:

### 1. Criar o controller

```php
// src/Controller/OrcamentosPrototypeController.php
namespace App\Controller;

class OrcamentosPrototypeController extends AppController {
    public function lista() {
        $this->viewBuilder()->setLayout('erp_prototype');
        $this->set([
            'title'         => 'Orçamentos',
            'erpNavActive'  => 'orc-lista',
            'erpBreadcrumb' => [['label' => 'Orçamentos', 'cur' => true]],
            'erpEmpresas'   => $this->loadEmpresasPorUsuario(),
            // ... dados da tela
        ]);
    }
}
```

### 2. Criar o template

```php
// src/Template/OrcamentosPrototype/lista.ctp
<div class="stats">
    <div class="stat"><div class="stat-l">Total</div><div class="stat-n">42</div></div>
    <!-- ... -->
</div>
<div class="card">
    <?= $this->ErpPrototype->stepper([
        ['label' => 'Lista',    'state' => 'done'],
        ['label' => 'Detalhe',  'state' => 'active'],
        ['label' => 'Impressão','state' => 'pending'],
    ]) ?>
</div>
```

### 3. Registrar a rota em `config/routes.php`

```php
Router::scope('/portal', ['_method' => null], function ($routes) {
    $routes->connect('/orcamentos-prototype', [
        'controller' => 'OrcamentosPrototype',
        'action' => 'lista',
    ]);
});
```

### 4. Atualizar a sidebar

Em `src/Template/Element/ErpPrototype/sidebar.ctp`, troque `'url' => null` pela
URL Cake correspondente (Cake monta o link automaticamente).

### 5. CSS específico (se necessário)

Para CSS exclusivo do módulo, criar `webroot/dist/css/pages/pgm-{modulo}-prototype.css`
e incluir via `<?= $this->Html->css(...) ?>` na view (não no layout).

### 6. Testar

URL: `https://portal.pgm.inf.br/portal/orcamentos-prototype`.
Service Desk Protótipo serve como referência funcional completa.

---

## 8. Convenções

- **CSS namespace**: tudo dentro de `.pgm-erp-shell` para evitar conflito com legado
- **Classes premium**: `card`, `stat`, `btn`, `badge`, `b-*`, `tbl`, `tl-*`, `stp`, `alert-*` — sem prefixo, escopadas pelo shell
- **Rotas**: sempre `/{modulo}-prototype/*` (legado intocado)
- **Sem JS pesado**: sem `goTo()` SPA do mockup; cada link é navegação Cake server-side
- **ABAC/RBAC**: aplicar igual módulos existentes (`$this->Abac->applyToQuery(...)`)
- **Dados reais**: criar `Service/{Modulo}DataService.php` igual ao `ServicedeskPrototypeDataService`
