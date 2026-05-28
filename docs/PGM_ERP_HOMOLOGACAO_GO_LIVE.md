# Homologação e go-live — UI premium `pgm_erp_completo.html`

Checklist operacional para colocar módulos premium em produção **sem alterar APIs Grid/ERP**.

## Pré-requisitos

1. Migration aplicada: `bin/cake migrations migrate` (inclui `PcpModuleFoundation` e demais pendências).
2. Cobertura gerada:
   ```bash
   python3 bin/generate_pgm_erp_coverage.py
   php bin/audit_pgm_erp_mock.php
   bash bin/homologacao_pgm_erp.sh
   ```
3. Referência visual: [docs/reference/pgm_erp_completo.html](reference/pgm_erp_completo.html).

## Switchover por módulo (`.env`)

```ini
PORTAL_UI_MODE=mixed
PORTAL_PREMIUM_MODULES=clientes,orcamentos,produtos,servicedesk
```

Helper: `App\Utility\PortalUi::isPremiumModule('clientes')`.

Ordem sugerida de ativação em produção:

| Ordem | Módulo | Rota protótipo | Validação mínima |
|------:|--------|----------------|------------------|
| 1 | Home | `/erp-home-prototype` | KPIs batem com listagens legadas |
| 2 | Clientes | `/clientes-prototype` | CRUD legado + visão 360 |
| 3 | Orçamentos | `/orcamentos-prototype` | Wizard, PDF, status |
| 4 | OS | `/ordens-prototype` | listAPI/refreshAPI inalterados |
| 5 | Service Desk | `/servicedesk-prototype` | APIs tickets JSON |
| 6 | Produtos | `/produtos-prototype` | addAPI/listAPI + SOAP preço |
| 7 | Financeiro/Bancos | `*-prototype` | Faturas, extrato, conciliação |
| 8 | Sistema/Empresas | `sistema/empresas-prototype` | RBAC admin |
| 9 | PCP | `/pcp-prototype` | Tabelas `pcp_*` populadas |

## Regressão integração Grid (não negociável)

| Endpoint | Teste |
|----------|--------|
| `POST /clientes/add-api` | Payload ERP de homologação |
| `GET /clientes/list-api` | Lista paginada |
| `POST /produtos/add-api` | Cadastro produto |
| `GET /produtos/list-api` | Lista produtos |
| `GET /ordensservico/list-api` | OS liberadas faturamento |
| `POST /ordensservico/refresh-api` | Atualização situação |

Documentação: [PGM_ERP_INTEGRACOES_GRID.md](PGM_ERP_INTEGRACOES_GRID.md).

## Rollback

1. Remover módulo de `PORTAL_PREMIUM_MODULES` ou definir `PORTAL_UI_MODE=legacy`.
2. Rotas `*-prototype` permanecem; usuários voltam ao legado automaticamente.
3. Não reverter migrations PCP em produção sem backup — tabelas novas são aditivas.

## Pós go-live

- Monitorar logs `[API-ORDENS]` e erros SOAP `GetEstoqueProdutos`.
- Comparar totais financeiros (CR/CP) entre `FinanceiroPrototype` e `Financeiro/index`.
- Registrar gaps restantes em `docs/generated/pgm_erp_coverage_report.json` (`placeholder_screens`).
