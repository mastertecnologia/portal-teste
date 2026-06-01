# Plano de desenvolvimento e migração — `pgm_erp_completo.html`

> Referência canônica: [`docs/referencias/pgm_erp_completo.html`](../docs/referencias/pgm_erp_completo.html) (182 telas `pg-*`, jun/2026).
> Inventário técnico: [`config/pgm_erp_screens.json`](../config/pgm_erp_screens.json) · Cobertura: [`PGM_ERP_COBERTURA_TELAS.md`](PGM_ERP_COBERTURA_TELAS.md) · Grid/API: [`PGM_ERP_INTEGRACOES_GRID.md`](PGM_ERP_INTEGRACOES_GRID.md).
> Plano histórico (fases 0–7): [`MIGRACAO_PGM_ERP_COMPLETO.md`](MIGRACAO_PGM_ERP_COMPLETO.md).

## 1. Situação atual (auditoria)

| Métrica | Valor |
|---------|------:|
| Telas no HTML | **182** |
| Registry (`pgm_erp_screens.json`) | **182** (após sync) |
| Implementadas premium (`status: implemented`) | **69** |
| Bridge → legado (`status: bridge`) | **62** |
| Planejadas (`status: planned`) | **51** (novas no HTML) |

### 1.1 O que já existe no portal

| Área | Protótipo | Legado | Grid ERP |
|------|-----------|--------|----------|
| Shell ERP | `erp_prototype.ctp`, sidebar, topbar | `default.ctp` + `pgm-app-shell-premium` | `empresas.urlerp` |
| Comercial | `OrcamentosPrototype`, `OrdensservicoPrototype` | `Orcamentos`, `Ordensservico` | OS: list/refresh API; orçamentos: ORM local |
| Cadastros | `Clientes`, `Produtos`, `Fornecedores` prototype | CRUD clássico | Clientes/produtos: addAPI/listAPI |
| Financeiro / Bancos | `FinanceiroPrototype`, `BancosPrototype` | `Financeiro`, `FinanceiroBancos` | Sem API nova no protótipo |
| Service Desk | `ServicedeskPrototype` (26 telas) | `Servicedesk`, `Tickets` | Contratos/fat: SOAP legado |
| PCP | `PcpPrototype` + migration `pcp_*` | — | Nenhuma (módulo portal) |
| Sistema / RBAC | `SistemaPrototype`, `EmpresasPrototype` | `Config`, `Permissoes`, `Users` | Nenhuma |
| **Licenciamento** | **Em fundação** (`LicencasPrototype`) | **Não existe** | Fornecedor = `clientes` PJ |
| **Painel admin expandido** | Hub `pg-config` + subtelas `pg-config-*` | `Config/index.ctp` (`config-admin-shell`) | Integrações documentadas, não alteradas |

### 1.2 Lacuna das 51 telas novas (HTML → registry `planned`)

| Bloco | Telas | Prioridade |
|-------|------:|------------|
| **Licenciamento** (`pg-lic-*`) | 39 | P1 — módulo novo no mock |
| **Config administrativo** (`pg-config-*`, `pg-email-template-editar`) | 8 | P0 — só `users.admin` |
| **Bancos** (`pg-bancos-cadastro`, `pg-banco-novo`, `pg-banco-openbanking`) | 3 | P2 — estender `BancosPrototype` |
| **Produtos** (`pg-preco-tabela-nova`) | 1 | P2 — estender `ProdutosPrototype` |

Comando para re-sincronizar registry após alterações no HTML:

```bash
python3 bin/sync_pgm_erp_registry_from_html.py
python3 bin/generate_pgm_erp_coverage.py
```

---

## 2. Princípios (não negociáveis)

1. **Não alterar contratos Grid/ERP** — `listAPI`, `addAPI`, `refreshAPI`, SOAP `.wso`, `empresas.urlerp`. Telas premium consomem ORM ou bridge.
2. **Lado-a-lado** — rotas `/{modulo}-prototype/*`; legado intocado até homologação.
3. **Stack** — CakePHP 3.10, `.ctp`, layout `erp_prototype`, RBAC via `config/erp_prototype_rbac.php`.
4. **Administração do sistema** — telas `pg-config-*`, empresas globais, RBAC matriz, auditoria: **`users.admin = true`** ou permissões `config.manage` / `permissoes.*` delegadas; hub único alinhado ao mock `pg-config`.
5. **Sem inventar dados** — KPIs só com ORM/tabelas reais; mock numérico permanece no HTML de referência até implementação.

---

## 3. Painel administrativo (reorganização)

### 3.1 Estado atual

- **Legado:** [`src/Template/Config/index.ctp`](../src/Template/Config/index.ctp) — painel completo (equipe, empresas, filas, pastas, e-mail).
- **Protótipo:** `SistemaPrototype::config()` redirecionava para legado; **novo:** hub premium `pg-config` em layout ERP com cards do mock.

### 3.2 Mapa mock → implementação

| Tela mock | Rota protótipo | Destino real (bridge) |
|-----------|----------------|------------------------|
| `pg-config` | `/sistema-prototype/config` | Hub premium (cards) |
| `pg-config-integracoes` | `/sistema-prototype/config-integracoes` | Doc + links `PGM_ERP_INTEGRACOES_GRID.md`; cards ERP API / SOAP |
| `pg-config-email` | `/sistema-prototype/config-email` | `Config/emailsuporte` |
| `pg-config-seguranca` | `/sistema-prototype/config-seguranca` | `Permissoes/adminIndex` |
| `pg-config-backup` | `/sistema-prototype/config-backup` | Planejado: política retenção (sem mock de API) |
| `pg-config-numeracao` | `/sistema-prototype/config-numeracao` | Planejado: séries ORM existentes |
| `pg-config-notificacoes` | `/sistema-prototype/config-notificacoes` | `PortalNotifications` / config tickets |
| `pg-config-localizacao` | `/sistema-prototype/config-localizacao` | `Config` + `empresas` |
| `pg-email-template-editar` | `/sistema-prototype/email-template-editar` | `Config/emailsuporte` (fase 2: templates) |
| `pg-empresa`, `pg-empresa-fiscal`, … | já em registry `bridge` | `Empresas/edit`, fiscal legado |
| `pg-usuarios`, `pg-acesso-*` | `SistemaPrototype` | `Users`, `Permissoes` |

**Entrega:** manter `Config/index` para quem usa layout clássico; menu ERP **Sistema** aponta para hub premium; atalho “Painel clássico” no hub.

---

## 4. Módulo Licenciamento (novo)

### 4.1 Escopo funcional (extraído do HTML)

- Multi-tenant por **empresa PGM** (`idempresa`) + **clientes** como empresas-cliente.
- Catálogo (categorias, produtos software), licenças com assentos, dispositivos, cofre de credenciais (auditoria de visualização).
- Pipeline renovações, solicitações, portal cliente (`pg-lic-portal-*`).
- Fornecedores de software = **cadastro `clientes` PJ** (mock: “vinculados ao cadastro mestre de fornecedores”).

### 4.2 Modelo de dados (migration `LicModuleFoundation`)

| Tabela | Uso |
|--------|-----|
| `lic_categorias` | Categorias do catálogo |
| `lic_catalogo_produtos` | Produtos/templates (SKU, fornecedor) |
| `lic_licencas` | Contrato de licença (código, cliente, vigência, valor, status) |
| `lic_assentos` | Atribuição assento → usuário/e-mail/dispositivo |
| `lic_dispositivos` | Inventário para auditoria |
| `lic_cofre_itens` | Metadados cofre (segredo: fase cripto dedicada) |
| `lic_solicitacoes` | Pedidos portal cliente |
| `lic_auditoria_eventos` | Trilha do módulo (complementa `audit_logs`) |

**Sem integração Grid** nesta fase (confirmado no mock e registry).

### 4.3 Fases de entrega Licenciamento

| Fase | Entregável | Telas |
|------|------------|-------|
| L0 | Migration + `LicencasPrototype` hub + dashboard KPIs reais | `pg-lic-dashboard` |
| L1 | CRUD licenças + lista + detalhe + wizard `nova` (4 passos) | 8 telas |
| L2 | Catálogo, categorias, fornecedores (bridge `clientes` PJ) | 10 telas |
| L3 | Dispositivos, cofre (sem crypto até revisão segurança), auditoria | 8 telas |
| L4 | Renovações, calendário, solicitações, relatórios | 6 telas |
| L5 | Portal cliente (`role=1`, prefixo `/cliente/licencas-*`) | 5 telas |
| L6 | Inteligência (regras read-only a partir de dados reais) | 1 tela |

---

## 5. Bancos e produtos (extensões)

| Tela | Ação |
|------|------|
| `pg-bancos-cadastro` | Lista contas premium; bridge `FinanceiroBancos/index` |
| `pg-banco-novo` | Bridge `FinanceiroBancos/add` |
| `pg-banco-openbanking` | UI premium + config existente OFX (sem novo endpoint Grid) |
| `pg-preco-tabela-nova` | Bridge `Produtos` pricing / tabela legada |

---

## 6. Permissões RBAC

Códigos novos (registrar em `permissions_registry.php` e papéis admin):

| Código | Quem |
|--------|------|
| `licencas.view` | Equipe com módulo |
| `licencas.manage` | Gestores licenciamento |
| `licencas.cofre.view` | Visualizar metadados cofre |
| `licencas.cofre.secret` | Revelar segredo (auditoria obrigatória) |
| `config.integracoes.view` | Admin — ver doc integrações |
| `config.admin.hub` | Admin — hub `pg-config` |

Protótipo: `LicencasPrototype` → alias RBAC `clientes` ou módulo `licencas` em `erp_prototype_rbac.php`.

Sidebar: seção **Licenciamento** (equipe); seção **Sistema** permanece `requires_admin`.

---

## 7. Integrações Grid — manutenção documental

**Não criar nem renomear endpoints.** Atualizar somente:

- [`PGM_ERP_INTEGRACOES_GRID.md`](PGM_ERP_INTEGRACOES_GRID.md) (gerado por `bin/generate_pgm_erp_coverage.py`)
- Hub `pg-config-integracoes` com tabela dos endpoints existentes:

| Endpoint | Controller | Direção |
|----------|------------|---------|
| `/clientes/list-api` | `Clientes::listAPI` | ERP→Portal |
| `/clientes/add-api` | `Clientes::addAPI` | ERP→Portal |
| `/produtos/list-api` | `Produtos::listAPI` | ERP→Portal |
| `/produtos/add-api` | `Produtos::addAPI` | ERP→Portal |
| `/ordensservico/list-api` | `Ordensservico::listAPI` | Portal→ERP |
| `/ordensservico/refresh-api` | `Ordensservico::refreshAPI` | ERP→Portal |

SOAP: `empresas.urlerp`, `GetEstoqueProdutos`, contratos, NF-e — ver `grid_soap_notes` no registry.

---

## 8. Switchover e homologação

```bash
python3 bin/sync_pgm_erp_registry_from_html.py
python3 bin/generate_pgm_erp_coverage.py
bash bin/homologacao_pgm_erp.sh          # quando PHP disponível
bash bin/verify_prototype_bridges.sh
```

`.env` (por módulo):

```ini
PORTAL_UI_MODE=mixed
PORTAL_PREMIUM_MODULES=clientes,licencas
```

---

## 9. Ordem de execução recomendada

1. **P0** — Registry sync + hub admin `pg-config` + doc integrações (este PR).
2. **P1** — Licenciamento L0–L2 (migration, dashboard, licenças, catálogo).
3. **P2** — Bancos cadastro/Open Banking UI; preço tabela nova.
4. **P3** — Licenciamento L3–L5 (cofre com segurança, portal).
5. **P4** — Promover bridges a `implemented`; `PORTAL_PREMIUM_MODULES` por módulo homologado.
6. **Contínuo** — Sub-fases pendentes em `MIGRACAO_PGM_ERP_COMPLETO.md` (kanban OS, wizard empresa 5 passos, etc.).

---

## 10. Critérios de “pronto” por tela

- [ ] Rota `*-prototype` registrada em `config/routes.php`
- [ ] Entrada em `pgm_erp_screens.json` com `status` correto
- [ ] RBAC + ABAC aplicados (`ErpPrototypeRbacTrait`, `AbacComponent` onde houver query)
- [ ] Dados reais ORM ou bridge documentado
- [ ] Grid/API: `grid_erp` e `grid_note` preenchidos; nenhum contrato público alterado
- [ ] Homologação: entrada na matriz `PGM_ERP_COBERTURA_TELAS.md` sem placeholder indevido
