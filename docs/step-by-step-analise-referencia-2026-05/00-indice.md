# Análise: páginas fora do arquivo de referência

**Data:** 2026-05-24  
**Referência canônica:** `docs/referencias/pgm_erp_completo_2.html` (~124 telas `pg-*`)  
**Plano oficial:** `docs/MIGRACAO_PGM_ERP_COMPLETO.md`

## Arquivos desta pasta

| Arquivo | Conteúdo |
|---------|----------|
| `01-contexto-referencia.md` | O que é o mock e como o portal deveria espelhá-lo |
| `02-causas-raiz.md` | Lista priorizada de motivos do desvio visual |
| `03-matriz-rotas-ui.md` | Legado vs protótipo vs layout por módulo |
| `04-proximos-passos.md` | Ações recomendadas (sem implementação nesta sessão) |

## Conclusão executiva

As páginas **não estão “erradas” por um único bug de CSS**: o repositório mantém **três linhas de UI em paralelo** (AdminLTE legado, shell `default.ctp` + CSS por módulo, shell `erp_prototype`), a migração está **~15–25% coberta** em relação às 124 telas do mock, e o **switchover por módulo (`PortalUi`) ainda não está ligado** nos controllers. Quem navega pelas URLs de produção (`/clientes`, `/produtos`, `/orcamentos`…) vê uma implementação **híbrida** que só se aproxima parcialmente do HTML de referência.
