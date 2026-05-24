# 04 — Próximos passos recomendados

## Diagnóstico rápido (você no browser)

1. Abra a **mesma funcionalidade** em duas abas:
   - Produção: ex. `/clientes`
   - Protótipo: `/clientes-prototype/lista`
2. Compare com o mock offline: abra `docs/referencias/pgm_erp_completo_2.html` no navegador e vá em `pg-clientes`.
3. Se só o protótipo parece “certo”, o problema é **switchover + URL**, não CSS quebrado.

## Correções de alto impacto (engenharia)

| Prioridade | Ação |
|------------|------|
| P0 | Ligar `PortalUi::redirectToPrototypeIfEnabled()` nos controllers legados dos módulos validados + `PORTAL_PREMIUM_MODULES` no `.env` |
| P0 | Em **toda** action com topbar/cabeçalho próprio no conteúdo, setar `hideLayoutPageTitle` (e breadcrumbs na topbar via `topbarParentLabel`) |
| P1 | Unificar Clientes: um shell (`erp_prototype` **ou** `cli-layout-unificado`), não dois |
| P1 | Consolidar tokens (`DESIGN_TOKENS_FASE1` fase 4) — uma primária `#1D9E75` / ERP teal |
| P2 | Completar telas placeholder dos protótipos antes de promover módulo |
| P2 | Service Desk: decidir alvo único (protótipo vs servicedesk.ctp vs React) |

## Comandos úteis

```bash
php bin/audit_pgm_erp_mock.php
rg 'hideLayoutPageTitle' src/Controller -l
rg 'setLayout\(.erp_prototype' src/Controller
```

## Manutenibilidade (reflexão)

A arquitetura atual **reduz risco de regressão** (legado intacto) mas **aumenta custo cognitivo**: três shells, prefixos CSS diferentes e switchover não wired. O caminho sustentável é promover módulo a módulo via `PortalUi` + eliminar duplicação de cabeçalho no `default.ctp`, não reescrever o mock inteiro de uma vez.

Possíveis melhorias estruturais: helper de layout `PgmPageShell::configure($breadcrumb)` que define topbar + `hideLayoutPageTitle` num só lugar; teste visual ou checklist por `pg-*` ligado ao CI.
