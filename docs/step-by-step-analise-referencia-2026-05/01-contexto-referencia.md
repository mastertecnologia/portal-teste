# 01 — Contexto do arquivo de referência

## Arquivo principal

- **Caminho:** `docs/referencias/pgm_erp_completo_2.html`
- **Fallback:** `docs/reference/pgm_erp_completo_2.html`
- **Natureza:** SPA estática em um único HTML (~2,2 MB) com navegação `goTo('pg-…')`
- **Telas:** 124 IDs únicos `pg-*` (ex.: `pg-clientes`, `pg-lista`, `pg-os-kanban`)

## Extratos por módulo (referência parcial)

| Arquivo | Módulo |
|---------|--------|
| `docs/reference/pgm_orcamentos_premium.html` | Orçamentos |
| `docs/reference/ordens_servico_premium.html` | OS (tema escuro legado) |
| Raiz do repo (quando existem) | `pgm_orcamentos_premium.html`, `pgm_portal_autenticado.html`, etc. |

## O que o mock define (contrato visual)

1. **Shell único:** sidebar fixa + topbar com seletor multi-empresa + área `#dynamic-content` / `.page-content`
2. **Componentes:** `.stats`, `.stat`, `.card`, `.tbl`, `.badge`, `.btn-primary`, tokens `--teal`, `--bg-surface`
3. **IDs de tela:** cada “página” é um `<div class="pg" id="pg-…">` mostrado/oculto por JS

## O que o CakePHP implementou

| Camada | Onde | Observação |
|--------|------|------------|
| Shell protótipo | `Layout/erp_prototype.ctp` + `webroot/dist/css/pgm-erp-prototype.css` | Alinhado ao mock; usado só em rotas `*-prototype` |
| Shell produção | `Layout/default.ctp` + `pgm-app-shell-premium.css` | AdminLTE (`style.min.css`) **+** topbar premium **+** `page-wrapper` legado |
| CSS por módulo | `clientes-layout-unificado.css`, `produtos-premium.css`, `orcamentos-premium.css`, … | Classes `cli-*`, `prd-*`, `orc-*` — **não** usam `id="pg-*"` |
| Auditoria | `bin/audit_pgm_erp_mock.php` | Compara contagem `pg-*` vs rotas `*-prototype` |

## Ferramenta de validação

```bash
php bin/audit_pgm_erp_mock.php
```

No ambiente deste agente `php` não estava no PATH; a contagem de telas foi feita com `rg` (124 IDs `pg-*`).
