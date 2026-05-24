# 02 — Causas raiz (por que não bate com a referência)

## 1. Estratégia “lado a lado” (principal)

Documentado em `docs/MIGRACAO_PGM_ERP_COMPLETO.md`:

- Rotas de **produção** (`/clientes`, `/orcamentos`, `/produtos`…) continuam com templates legados ou híbridos.
- Rotas **protótipo** (`/clientes-prototype`, `/orcamentos-prototype`, …) usam layout e markup mais próximos do mock.
- **Fase 7 (switchover)** ainda não conectada: `PortalUi::redirectToPrototypeIfEnabled()` **não é chamada em nenhum controller** — só existe em `src/Utility/PortalUi.php`.
- `.env` padrão: `PORTAL_PREMIUM_MODULES` vazio → nenhum módulo promovido a premium.

**Efeito:** abrir `/clientes` ≠ abrir o trecho `pg-clientes` do HTML de referência; para ver paridade parcial é preciso `/clientes-prototype/lista`.

## 2. Três stacks visuais simultâneas

| Stack | Layout / CSS | Quem usa |
|-------|----------------|----------|
| A — AdminLTE | `style.min.css`, `.page-wrapper`, `.container-fluid`, `.card` Bootstrap | Maioria dos módulos não migrados (Faturas, Users, muitos formulários antigos) |
| B — Shell premium legado | `default.ctp` + `pgm-app-shell-premium.css` + CSS módulo (`cli-*`, `prd-*`, …) | Clientes lista, Produtos index, Orçamentos (parcial), Financeiro bancos (parcial) |
| C — Shell ERP mock | `erp_prototype.ctp` + `pgm-erp-prototype.css` | Todos os `*PrototypeController` |

O mock é **uma** stack (C). As URLs que você usa no dia a dia são em grande parte **A+B**.

## 3. Markup diferente do mock (mesmo quando “premium”)

- Referência: `id="pg-clientes"`, classes `.pg`, `.stats`, `.tbl`
- Produção Clientes: `cli-root cli-layout-unificado` + header `cli-crm-page-head` (`src/Template/Clientes/index.ctp`)
- Produção Protótipo: `ErpPrototype/page_header` + `.stats` inline (`ClientesPrototype/lista.ctp`)

Ou seja: há **duas implementações de Clientes**, nenhuma é cópia literal do `pg-clientes` do HTML.

## 4. Cobertura incompleta do mock

| Métrica | Valor |
|---------|--------|
| Telas no HTML de referência | **124** `pg-*` |
| Rotas `*-prototype` em `routes.php` | **~73** conexões (várias são API/bridge, não telas) |
| Telas protótipo entregues (estimativa doc/audit) | Service Desk ~18; demais módulos 1–5 cada |
| PCP / Indústria | Adiado (13 telas sem backend) |

Wizards orçamentos/OS, importadores, kanban OS dedicado, assistente empresa 5 passos, etc. ainda **placeholder** ou bridge para legado.

## 5. Service Desk — terceiro caminho

- Operacional clássico: `ServicedeskController` → `Layout/servicedesk.ctp` (`sd-shell`, `prd-root`)
- Protótipo: `ServicedeskPrototypeController` → `erp_prototype` + `disablePgmAppShellPremium` (irrelevante fora do default)
- React: `dashboard-react/` para kanban/dashboard

Nenhum deles reproduz as 26 telas SD do mock de uma vez.

## 6. Conflito de tokens / cores

`docs/DESIGN_TOKENS_FASE1.md` documenta:

- `--pgm-primary` `#00a876` vs sidebar `--pgm-teal` `#1d9e75` vs orçamentos `--orc-teal` `#00C08B` vs shell `--pgm-app-shell-teal` `#00c08b`

A referência usa `--teal` no `:root` do mock; o portal aplica **várias primárias** conforme o módulo → sensação de “não é o mesmo sistema”.

## 7. Chrome duplicado no layout produção

`default.ctp` com shell premium ativo carrega:

1. `pgm_shell_topbar` (breadcrumb novo)
2. Bloco legado `.row.page-titles` + `Breadcrumbs` — **salvo** se `hideLayoutPageTitle === true`

Apenas **~16 de ~83** controllers definem `hideLayoutPageTitle`. Ex.: `ProdutosController::index()` **não** define — a lista tem `prd-topbar` interno **e** pode ainda mostrar título/breadcrumb AdminLTE acima.

## 8. Sidebar e multi-empresa

- Mock: seletor de empresa na topbar do shell `pgm-erp-shell`
- Produção: empresa/data/perfil na sidebar (`layout-sidebar-shell.css`); seletor rico só em algumas telas Clientes (`pgmTopbarEmpresas`)

## 9. Expectativa vs regra do projeto

`cursorrules` e `MIGRACAO` dizem explicitamente: **não copiar HTML literal** — converter para CakePHP com classes CSS do webroot. Isso explica divergências de estrutura DOM mesmo quando a intenção é “mesmo visual”.
