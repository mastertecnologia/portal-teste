# 03 — Matriz: rota → UI → proximidade da referência

Legenda: **Alta** = shell + componentes do mock; **Média** = premium módulo no default; **Baixa** = AdminLTE clássico.

| Módulo | URL produção típica | URL protótipo | Layout produção | Paridade ref. |
|--------|---------------------|---------------|-----------------|---------------|
| Clientes | `/clientes` | `/clientes-prototype/lista` | default + `cli-*` | Média / Alta (protótipo) |
| Produtos | `/produtos` | `/produtos-prototype/lista` | default + `prd-*` | Média (index sem hide title) |
| Orçamentos | `/orcamentos` | `/orcamentos-prototype/lista` | `orcamentos` ou default + `orc-*` | Média |
| OS | `/ordensservico` | `/ordens-prototype/lista` | legado | Baixa / Média (protótipo) |
| Financeiro | `/financeiro` | `/financeiro-prototype/lista` | misto | Baixa / Média |
| Bancos | `/financeiro-bancos` | `/bancos-prototype/lista` | premium parcial | Média |
| Service Desk | `/servicedesk`, `/tickets` | `/servicedesk-prototype/*` | servicedesk / tickets | Baixa–Média |
| Empresas/RBAC | `/empresas`, `/users` | `/empresas-prototype`, `/sistema-prototype` | legado | Baixa / Média |
| Fiscal | `/fiscal/*` | — | advanced module CSS | Baixa (fora do mock ERP) |
| PCP | — | `/pcp-prototype` (parcial) | — | Não no escopo aprovado |

## Duas linhas em Clientes (documentado)

`docs/reference/README.md`:

| Rota | CSS / shell |
|------|-------------|
| `/clientes/*` | `clientes-layout-unificado.css` + `pgm-app-shell-premium` |
| `/clientes-prototype/*` | `erp_prototype` + classes estilo mock |

Convergência para um único desenho: item pendente na Fase 3 do plano de migração.

## Onde estão os `pg-*` no código Cake

Quase só em templates `*Prototype*` e elementos `ErpPrototype/` / `ServicedeskPrototype/ref/`.  
Templates de produção (`Clientes/index.ctp`, `Produtos/index.ctp`) **não** usam `id="pg-…"`.
