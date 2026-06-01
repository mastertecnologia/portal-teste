# Referência visual ERP completa

| Arquivo | Descrição |
|---------|-----------|
| `pgm_erp_completo.html` | Mock SPA completo (~2,9 MB, **182** telas `pg-*`, incl. Licenciamento + admin). **Arquivo canônico.** |
| `pgm_erp_completo_2.html` | Versão anterior (124 telas); mantida para diff. |
| `public/clientes-lista-layout-unificado.html` | Mock shell + sidebar; lista CRM espelha `src/Template/Clientes/index.ctp` (`cli-crm-lista`). Conteúdo canônico da lista: `#pg-clientes` no ERP completo. |

## Auditoria

```bash
php bin/audit_pgm_erp_mock.php
# ou
php bin/audit_pgm_erp_mock.php docs/referencias/pgm_erp_completo_2.html
```

Extratos por módulo podem ficar em `docs/reference/` (ex.: `pgm_orcamentos_premium.html`).

Plano de migração: `docs/MIGRACAO_PGM_ERP_COMPLETO.md`.
