# Referências visuais ERP premium

| Arquivo | Descrição |
|---------|-----------|
| `pgm_erp_completo_2.html` | **Referência principal** — no repo está em **`../referencias/pgm_erp_completo_2.html`** (~124 telas `pg-*`). |
| `pgm_erp_completo.html` | Versão anterior do mockup (fallback). |
| `pgm_orcamentos_premium.html` | Extrato — módulo Orçamentos (`pg-lista` … `pg-sucesso`) |
| `ordens_servico_premium.html` | Referência alternativa de OS (tema escuro; legado) |

## Como validar paridade

```bash
# Depois de colocar o HTML aqui:
php bin/audit_pgm_erp_mock.php
php bin/audit_pgm_erp_mock.php /caminho/absoluto/pgm_erp_completo_2.html
```

## Implementação Cake

- Layout shell: `src/Template/Layout/erp_prototype.ctp`
- CSS base: `webroot/dist/css/pgm-erp-prototype.css`
- Rotas conviventes: `/portal/{modulo}-prototype/*` (legado intocado até switchover)
- Plano completo: `docs/MIGRACAO_PGM_ERP_COMPLETO.md`
- Switchover por módulo: `.env` → `PORTAL_PREMIUM_MODULES=clientes,orcamentos` (ver `config/portal_ui.php`)

## Duas linhas visuais em Clientes (importante)

| Rota | UI | Estado |
|------|-----|--------|
| `/clientes/*` | `clientes-layout-unificado.css` + shell PGM app | Cadastro wizard, ficha abas, Visão 360° — **dados reais** |
| `/clientes-prototype/*` | `erp_prototype` + classes `pg-*` | Lista/KPIs/360 protótipo; novo/import ainda placeholder |

O objetivo do mock completo é convergir ambas para o mesmo desenho `pgm_erp_completo_2`, módulo a módulo, sem quebrar APIs nem ERP SOAP.
