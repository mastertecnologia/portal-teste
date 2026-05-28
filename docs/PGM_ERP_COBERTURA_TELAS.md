# Cobertura de telas — PGM ERP Completo

Gerado em: 2026-05-28 15:55 UTC
Referência HTML: `docs/referencias/pgm_erp_completo_2.html`
Registry: `config/pgm_erp_screens.json`

## Resumo

| Métrica | Valor |
|---------|------:|
| Telas `pg-*` no HTML | 124 |
| Entradas no registry | 131 |
| Implementadas (premium) | 69 |
| Bridge (legado/API intacta) | 62 |
| Placeholder / roadmap | 0 |
| Planejadas | 0 |

## Registry sem `pg-*` no HTML (revisar)

- `pg-orc-alcadas`
- `pg-orc-config-aprovacao`
- `pg-orc-negociacao`
- `pg-orc-portal-cliente`
- `pg-orc-regra-editar`
- `pg-orc-solicitar`
- `pg-orc-versoes`

## Matriz por módulo

| Módulo | Tela | Status | Rota protótipo | Tabelas | Grid ERP |
|--------|------|--------|----------------|---------|----------|
| bancos | `pg-bancos` | OK | `/bancos-prototype/bancos` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| bancos | `pg-conciliacao` | OK | `/bancos-prototype/conciliacao` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| bancos | `pg-contas` | BRIDGE | `/bancos-prototype/contas` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| bancos | `pg-extrato` | OK | `/bancos-prototype/extrato` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| bancos | `pg-fluxo-caixa` | OK | `/bancos-prototype/fluxo-caixa` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| bancos | `pg-transferencias` | OK | `/bancos-prototype/transferencias` | financeiro_bancos, financeiro_extrato_bancario, financeiro_lancamentos | none |
| clientes | `pg-cliente-360` | OK | `/clientes-prototype/visao360/:id` | clientes, faturas, clicontratos | both |
| clientes | `pg-cliente-novo` | BRIDGE | `/clientes-prototype/novo` | clientes | send |
| clientes | `pg-clientes` | OK | `/clientes-prototype/lista` | clientes, faturas, clicontratos | both |
| clientes | `pg-export-clientes` | BRIDGE | `/clientes-prototype/export` | clientes, faturas, clicontratos | both |
| clientes | `pg-import-clientes` | BRIDGE | `/clientes-prototype/import` | clientes, faturas, clicontratos | both |
| comercial | `pg-relatorios-vendas` | BRIDGE | `/relatorios` | orcamentos, ordensservico | none |
| comercial | `pg-vendedores` | BRIDGE | `/relatorios/vendedores` | orcamentos, users | none |
| financeiro | `pg-contas-pagar` | OK | `/financeiro-prototype/contas-pagar` | faturas, financeiro_lancamentos, fiscal_notas | none |
| financeiro | `pg-dre` | OK | `/financeiro-prototype/dre` | faturas, financeiro_lancamentos, fiscal_notas | none |
| financeiro | `pg-financeiro` | OK | `/financeiro-prototype/lista` | faturas, financeiro_lancamentos, fiscal_notas | none |
| financeiro | `pg-nfe` | OK | `/financeiro-prototype/nfe` | faturas, financeiro_lancamentos, fiscal_notas | none |
| financeiro | `pg-relatorios-fin` | BRIDGE | `/financeiro-prototype/relatorios-fin` | faturas, financeiro_lancamentos, fiscal_notas | none |
| financeiro | `pg-titulos` | OK | `/financeiro-prototype/titulos` | faturas, financeiro_lancamentos, fiscal_notas | none |
| fornecedores | `pg-fornecedor-360` | BRIDGE | `/fornecedores-prototype/360` | clientes | none |
| fornecedores | `pg-fornecedor-novo` | BRIDGE | `/fornecedores-prototype/novo` | clientes | none |
| fornecedores | `pg-fornecedores` | OK | `/fornecedores-prototype/lista` | clientes | none |
| home | `pg-home` | OK | `/erp-home-prototype` | orcamentos, ordensservico, tickets, clientes, faturas | none |
| orcamentos | `pg-esign` | BRIDGE | `/orcamentos-prototype/esign` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-lista` | OK | `/orcamentos-prototype/lista` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-novo` | BRIDGE | `/orcamentos-prototype/novo` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-orc-alcadas` | BRIDGE | `/orcamentos-prototype/alcadas` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-cobranca` | BRIDGE | `/orcamentos-prototype/cobranca` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-orc-config-aprovacao` | BRIDGE | `/orcamentos-prototype/config-aprovacao` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-faturamento` | BRIDGE | `/orcamentos-prototype/faturamento` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-orc-negociacao` | BRIDGE | `/orcamentos-prototype/negociacao` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-portal-cliente` | BRIDGE | `/orcamentos-prototype/portal-cliente` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-regra-editar` | BRIDGE | `/orcamentos-prototype/regra-editar` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-solicitar` | BRIDGE | `/orcamentos-prototype/solicitar` | orcamentos, orcamentositens | none |
| orcamentos | `pg-orc-versoes` | BRIDGE | `/orcamentos-prototype/versoes` | orcamentos, orcamentositens | none |
| orcamentos | `pg-print` | BRIDGE | `/orcamentos-prototype/print` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-revisao` | BRIDGE | `/orcamentos-prototype/revisao` | orcamentos, orcamentositens, clientes | none |
| orcamentos | `pg-sucesso` | BRIDGE | `/orcamentos-prototype/sucesso` | orcamentos, orcamentositens, clientes | none |
| ordensservico | `pg-os-abertura` | BRIDGE | `/ordens-prototype/abertura` | ordensservico, ordensitens, clientes, produtos | receive |
| ordensservico | `pg-os-aprovacao` | BRIDGE | `/ordens-prototype/aprovacao` | ordensservico, ordensitens, clientes, produtos | receive |
| ordensservico | `pg-os-cobranca` | BRIDGE | `/ordens-prototype/cobranca` | ordensservico, ordensitens, clientes, produtos | receive |
| ordensservico | `pg-os-conclusao` | BRIDGE | `/ordens-prototype/conclusao` | ordensservico, ordensitens, clientes, produtos | receive |
| ordensservico | `pg-os-execucao` | BRIDGE | `/ordens-prototype/execucao` | ordensservico, ordensitens, clientes, produtos | both |
| ordensservico | `pg-os-faturamento` | BRIDGE | `/ordens-prototype/faturamento` | ordensservico, ordensitens, clientes, produtos | both |
| ordensservico | `pg-os-kanban` | BRIDGE | `/ordens-prototype/kanban` | ordensservico, ordensitens, clientes, produtos | receive |
| ordensservico | `pg-os-lista` | OK | `/ordens-prototype/lista` | ordensservico, ordensitens, clientes, produtos | both |
| ordensservico | `pg-os-sucesso` | BRIDGE | `/ordens-prototype/sucesso` | ordensservico, ordensitens, clientes, produtos | receive |
| pcp | `pg-apontamento` | OK | `/pcp-prototype/apontamento` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-bom` | OK | `/pcp-prototype/bom` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-centro-trabalho` | OK | `/pcp-prototype/centro-trabalho` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-configurador` | OK | `/pcp-prototype/configurador` | pcp_engenharia_fichas, pcp_bom_itens, produtos | none |
| pcp | `pg-cotacoes` | OK | `/pcp-prototype/cotacoes` | pcp_requisicoes_compra | none |
| pcp | `pg-custos-producao` | OK | `/pcp-prototype/custos-producao` | pcp_apontamentos, pcp_centros_trabalho | none |
| pcp | `pg-engenharia` | OK | `/pcp-prototype/engenharia` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-expedicao` | OK | `/pcp-prototype/expedicao` | pcp_ordens_producao | none |
| pcp | `pg-mrp` | OK | `/pcp-prototype/mrp` | pcp_ordens_producao, pcp_bom_itens | none |
| pcp | `pg-op-detalhe` | OK | `/pcp-prototype/op-detalhe` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-op-lista` | OK | `/pcp-prototype/op-lista` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-pcp-cronograma` | OK | `/pcp-prototype/pcp-cronograma` | pcp_ordens_producao | none |
| pcp | `pg-pcp-dashboard` | OK | `/pcp-prototype/dashboard` | pcp_ordens_producao, pcp_centros_trabalho, pcp_engenharia_fichas, pcp_bom_itens, pcp_apontamentos | none |
| pcp | `pg-pedido-compra` | OK | `/pcp-prototype/pedido-compra` | pcp_requisicoes_compra | none |
| pcp | `pg-qualidade-ind` | OK | `/pcp-prototype/qualidade-ind` | pcp_apontamentos | none |
| pcp | `pg-recebimento` | OK | `/pcp-prototype/recebimento` | pcp_requisicoes_compra | none |
| pcp | `pg-requisicoes` | OK | `/pcp-prototype/requisicoes` | pcp_requisicoes_compra | none |
| pcp | `pg-roteiro` | OK | `/pcp-prototype/roteiro` | pcp_roteiro_operacoes, pcp_centros_trabalho, produtos | none |
| produtos | `pg-estoque` | OK | `/produtos-prototype/estoque` | produtos | both |
| produtos | `pg-estoque-log` | BRIDGE | `/produtos-prototype/estoque-log` | produtos | both |
| produtos | `pg-historico-precos` | BRIDGE | `/produtos-prototype/historico-precos` | produtos | both |
| produtos | `pg-import-produtos` | BRIDGE | `/produtos-prototype/import` | produtos | both |
| produtos | `pg-inv-historico` | BRIDGE | `/produtos-prototype/inv-historico` | produtos | both |
| produtos | `pg-inventario` | BRIDGE | `/produtos-prototype/inventario` | produtos | both |
| produtos | `pg-pc-lista` | BRIDGE | `/produtos-prototype/pc-lista` | produtos | both |
| produtos | `pg-pc-novo` | BRIDGE | `/produtos-prototype/pc-novo` | produtos | both |
| produtos | `pg-precificacao` | OK | `/produtos-prototype/precificacao` | produtos | both |
| produtos | `pg-precos` | OK | `/produtos-prototype/precos` | produtos | both |
| produtos | `pg-produto-detalhe` | BRIDGE | `/produtos-prototype/detalhe` | produtos | both |
| produtos | `pg-produto-novo` | BRIDGE | `/produtos-prototype/novo` | produtos | both |
| produtos | `pg-produtos` | OK | `/produtos-prototype/lista` | produtos | both |
| servicedesk | `pg-sd-aprovacoes` | OK | `/servicedesk-prototype/aprovacoes` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-automacoes-editor` | OK | `/servicedesk-prototype/automacoes-editor` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-calendar` | OK | `/servicedesk-prototype/calendar` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-cmdb` | OK | `/servicedesk-prototype/cmdb` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-config` | OK | `/servicedesk-prototype/config` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-contratos` | OK | `/servicedesk-prototype/contratos` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | soap |
| servicedesk | `pg-sd-csat` | OK | `/servicedesk-prototype/csat` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-dashboard` | OK | `/servicedesk-prototype/dashboard` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-detalhe-fatura` | OK | `/servicedesk-prototype/detalhe-fatura` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-detalhe-kb` | OK | `/servicedesk-prototype/detalhe-kb` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-fat` | OK | `/servicedesk-prototype/fat` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | soap |
| servicedesk | `pg-sd-fila` | OK | `/servicedesk-prototype/fila` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-grupo` | OK | `/servicedesk-prototype/grupo` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-integracoes` | OK | `/servicedesk-prototype/integracoes` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-kanban` | OK | `/servicedesk-prototype/kanban` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-kb` | OK | `/servicedesk-prototype/kb` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-kb-editar` | BRIDGE | `/servicedesk-prototype/kb-editar` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-kb-historico` | BRIDGE | `/servicedesk-prototype/kb-historico` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-kb-preview` | BRIDGE | `/servicedesk-prototype/kb-preview` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-meus` | OK | `/servicedesk-prototype/meus` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-mudancas` | OK | `/servicedesk-prototype/mudancas` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-perm` | OK | `/servicedesk-prototype/perm` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-portal` | OK | `/servicedesk-prototype/portal` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-portal-novo` | OK | `/servicedesk-prototype/portal_novo` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-problemas` | OK | `/servicedesk-prototype/problemas` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-relatorios` | OK | `/servicedesk-prototype/relatorios` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-templates` | OK | `/servicedesk-prototype/templates` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| servicedesk | `pg-sd-ticket` | OK | `/servicedesk-prototype/ticket/:id` | tickets, ticket_histories, ativos, clicontratos, faturas, approval_requests | none |
| sistema | `pg-acesso-auditoria` | BRIDGE | `/sistema-prototype/acesso-auditoria` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-acesso-central` | OK | `/sistema-prototype/acesso-central` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-acesso-filiais` | BRIDGE | `/sistema-prototype/acesso-filiais` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-acesso-papeis` | OK | `/sistema-prototype/acesso-papeis` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-acesso-papel-editar` | BRIDGE | `/sistema-prototype/acesso-papel-editar` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-acesso-usuario` | BRIDGE | `/sistema-prototype/acesso-usuario` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-auditoria` | OK | `/sistema-prototype/auditoria` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-config` | OK | `/sistema-prototype/config` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa` | BRIDGE | `/sistema-prototype/empresa` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa-branding` | BRIDGE | `/sistema-prototype/empresa-branding` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa-contatos` | BRIDGE | `/sistema-prototype/empresa-contatos` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa-enderecos` | BRIDGE | `/sistema-prototype/empresa-enderecos` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa-fiscal` | BRIDGE | `/sistema-prototype/empresa-fiscal` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresa-nova` | BRIDGE | `/empresas-prototype/nova` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-empresas` | OK | `/empresas-prototype/lista` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-filial-nova` | BRIDGE | `/sistema-prototype/filial-nova` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-grupo-editar` | BRIDGE | `/sistema-prototype/grupo-editar` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-grupos` | BRIDGE | `/sistema-prototype/grupos` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-log-acessos` | BRIDGE | `/sistema-prototype/log-acessos` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-permissao-nova` | BRIDGE | `/sistema-prototype/permissao-nova` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-sessoes-ativas` | BRIDGE | `/sistema-prototype/sessoes-ativas` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-usuario-editar` | BRIDGE | `/sistema-prototype/usuario-editar` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-usuario-novo` | BRIDGE | `/sistema-prototype/usuario-novo` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-usuarios` | OK | `/sistema-prototype/usuarios` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |
| sistema | `pg-usuarios-cruzados` | BRIDGE | `/sistema-prototype/usuarios-cruzados` | users, empresas, rbac_roles, rbac_users_roles, audit_logs | none |

## Comandos

```bash
python3 bin/generate_pgm_erp_coverage.py
php bin/audit_pgm_erp_mock.php   # requer PHP no PATH
bash bin/homologacao_pgm_erp.sh
```
