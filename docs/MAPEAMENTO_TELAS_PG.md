# Mapeamento telas pg-* ↔ Portal Cake

Referência: `docs/referencias/pgm_erp_completo_2.html` (124 telas `pg-*`).

Atualize com `php bin/audit_pgm_erp_mock.php --md`.

## Switchover ativo (`PremiumUiTrait`)

Com `PORTAL_PREMIUM_MODULES` no `.env`, as rotas legadas abaixo redirecionam para o protótipo:

| Módulo .env | Legado | Protótipo |
|-------------|--------|-----------|
| `clientes` | `Clientes/index` | `ClientesPrototype/lista` |
| `produtos` | `Produtos/index`, `Produtos/estoque` | `ProdutosPrototype/lista`, `estoque` |
| `orcamentos` | `Orcamentos/index` (equipe) | `OrcamentosPrototype/lista` |
| `ordens` | `Ordensservico/index` | `OrdensservicoPrototype/lista` |
| `financeiro` | `Financeiro/index` | `FinanceiroPrototype/lista` |
| `bancos` | `FinanceiroBancos/index` | `BancosPrototype/lista` |
| `fornecedores` | — | `FornecedoresPrototype/lista` |
| `servicedesk` | — | `ServicedeskPrototype/index` |

Forçar legado: `?legacy_ui=1`.

## Telas mapeadas em `config/portal_ui_screens.php`

| pg-* | Título | Paridade | Legado | Protótipo |
|------|--------|----------|--------|-----------|
| `pg-home` | Dashboard | legacy_only | Users/dashboard | — |
| `pg-lista` | Lista orçamentos | partial | Orcamentos/index | OrcamentosPrototype/lista |
| `pg-novo` | Novo orçamento | partial | Orcamentos/add | OrcamentosPrototype/view/novo |
| `pg-os-lista` | Lista OS | partial | Ordensservico/index | OrdensservicoPrototype/lista |
| `pg-clientes` | Clientes | partial | Clientes/index | ClientesPrototype/lista |
| `pg-cliente-360` | Cliente 360° | partial | Clientes/visao360 | ClientesPrototype/cliente360 |
| `pg-produtos` | Produtos | partial | Produtos/index | ProdutosPrototype/lista |
| `pg-estoque` | Estoque | partial | Produtos/estoque | ProdutosPrototype/estoque |
| `pg-financeiro` | Financeiro | partial | Financeiro/index | FinanceiroPrototype/lista |
| `pg-bancos` | Bancos | partial | FinanceiroBancos/index | BancosPrototype/lista |
| `pg-fornecedores` | Fornecedores | partial | Clientes/index | FornecedoresPrototype/lista |
| `pg-sd-dashboard` | SD Dashboard | partial | Servicedesk/index | ServicedeskPrototype/index |

As demais telas `pg-*` do mock devem ser acrescentadas ao config conforme fases em `docs/MIGRACAO_PGM_ERP_COMPLETO.md`.
