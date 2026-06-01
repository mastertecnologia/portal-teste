# Licenciamento — homologação UAT

Checklist para validar o módulo após deploy (`main` com L1–L7).

## Pré-requisitos

- [ ] `bin/cake migrations migrate` (incl. `lic_*` e RBAC `RbacLicencas*`)
- [ ] Papéis com `licencas.*` (migration `RbacEquipeRolesLicencasModule` ou matriz manual)
- [ ] `RBAC_MODE=enforce` (ou `warn`) conforme política

## Dados de teste (opcional)

```bash
# Substitua N pelo idempresa real (ex. 1 ou 2)
bin/cake licencas seed_demo --idempresa=N --dry-run
bin/cake licencas seed_demo --idempresa=N
bin/cake licencas stats --idempresa=N
```

## Equipe (`role = 0`)

Utilizador com papel **operacao**:

| # | URL | Esperado |
|---|-----|----------|
| 1 | `/licencas-prototype` | Painel KPIs |
| 2 | `/licencas-prototype/licencas` | Lista + filtros |
| 3 | `/licencas-prototype/view/nova` | Wizard 4 passos |
| 4 | `/licencas-prototype/empresas` | Cards empresas-cliente |
| 5 | `/licencas-prototype/fornecedores` | Lista PJ |
| 6 | `/licencas-prototype/view/cofre` | Lista cofre |
| 7 | `/licencas-prototype/view/solicitacoes` | Alterar status |
| 8 | `/licencas-prototype/view/inteligencia` | Insights |
| 9 | `/licencas-prototype/view/relatorios` | Download CSV |
| 10 | `/licencas-prototype/view/auditoria` | Eventos |

Utilizador **operacao** em cofre: **não** deve revelar segredo.

Utilizador **admin_equipe**: revelar cofre → evento em auditoria.

## Portal (`role = 1`)

| # | URL | Esperado |
|---|-----|----------|
| 1 | Menu **Licenças** | Visível |
| 2 | `/cliente/licencas` | Painel |
| 3 | `/cliente/licencas/solicitar` | POST cria solicitação |
| 4 | `/cliente/licencas/cofre` | Metadados, sem segredo |

## Regressão

- [ ] APIs Grid documentadas **não** alteradas (`listAPI` / `addAPI` clientes/produtos)
- [ ] Módulos legados (tickets, fiscal) abrem normalmente

## Produção

Ver também `docs/LICENCIAMENTO_GO_LIVE.md`.
