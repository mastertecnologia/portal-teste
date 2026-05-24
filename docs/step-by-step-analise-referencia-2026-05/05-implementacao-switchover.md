# 05 — Implementação: mapeamento e correções

## Arquivos criados/alterados

| Arquivo | Função |
|---------|--------|
| `config/portal_ui_screens.php` | Mapeamento `pg-*` ↔ legado/protótipo + tabela switchover |
| `config/bootstrap.php` | Carrega `portal_ui_screens` |
| `src/Utility/PortalUiScreenMap.php` | API de leitura do mapeamento e stats |
| `src/Utility/PortalUi.php` | `isLegacyUiForced()` (`?legacy_ui=1`) |
| `src/Controller/Traits/PremiumUiTrait.php` | Redirect switchover + `configurePgmAppShellTopbar()` |
| `bin/audit_pgm_erp_mock.php` | Relatório de cobertura + `--md` → `docs/MAPEAMENTO_TELAS_PG.md` |
| Controllers (Clientes, Produtos, Orçamentos, OS, Financeiro, Bancos) | Switchover + topbar sem duplicar AdminLTE |

## Como ativar a UI do mock nas rotas legadas

No `.env`:

```ini
PORTAL_PREMIUM_MODULES=clientes,produtos,orcamentos,ordens,financeiro,bancos
```

Exemplos:

- `/clientes` → redireciona para `/clientes-prototype` (lista)
- `/produtos` → `/produtos-prototype`
- `/produtos/estoque` → `/produtos-prototype/estoque`

Forçar legado: `?legacy_ui=1` (ex.: `/clientes?legacy_ui=1`).

## Correção visual imediata (sem switchover)

Mesmo com módulo **fora** de `PORTAL_PREMIUM_MODULES`, as listagens abaixo passam a ocultar o bloco `.page-titles` duplicado e usam a topbar premium:

- Produtos (`index`)
- Ordens de Serviço (`index`)
- Orçamentos (`index` equipe)
- Financeiro (`index`)
- Financeiro Bancos (`index`)

## Próximo lote sugerido

1. Expandir `portal_ui_screens.php` para os ~100 `pg-*` restantes (rodar `php bin/audit_pgm_erp_mock.php` e preencher gaps).
2. Unificar Clientes: uma única linha visual (`erp_prototype` ou `cli-*`).
3. Empresas / Sistema: `EmpresasController::index` → switchover.
