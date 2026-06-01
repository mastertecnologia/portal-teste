# Integrações Grid ERP — mapa por tela e endpoint

Gerado em: 2026-06-01 17:13 UTC

> **Regra:** contratos de API existentes (`listAPI`, `addAPI`, `refreshAPI`, SOAP `.wso`) **não são alterados**.
> Telas premium consomem os mesmos dados via ORM ou delegam ao controller legado (bridge).

## Endpoints HTTP ERP → Portal (token em header)

| Endpoint | Controller | Direção | Tabelas principais |
|----------|------------|---------|-------------------|
| `/clientes/add-api` | `Clientes::addAPI` | ERP→Portal | clientes, clicontratos |
| `/clientes/list-api` | `Clientes::listAPI` | ERP→Portal | clientes |
| `/produtos/add-api` | `Produtos::addAPI` | ERP→Portal | produtos |
| `/produtos/list-api` | `Produtos::listAPI` | ERP→Portal | produtos |
| `/ordensservico/list-api` | `Ordensservico::listAPI` | Portal→ERP | ordensservico |
| `/ordensservico/refresh-api` | `Ordensservico::refreshAPI` | ERP→Portal | ordensservico |

## SOAP / WebGrid (urlerp em `empresas`)

- URL base: `empresas.urlerp` (não em config estático).
- Produtos/OS: SOAP estoque/preço (`ErpGridUrl::wsdl`, GetEstoqueProdutos).
- Contratos: WSDL contratos em `ClicontratosController`.
- NF-e: `FiscalNotasController::sincronizarErp` — envio para WebGrid sem alterar contrato público.

## Telas com vínculo Grid/ERP

| Tela | Módulo | Grid | Observação |
|------|--------|------|------------|
| `pg-cliente-360` | clientes | both | addAPI/listAPI Grid → clientes |
| `pg-cliente-novo` | clientes | send |  |
| `pg-clientes` | clientes | both | addAPI/listAPI Grid → clientes |
| `pg-config-integracoes` | sistema | both | urlerp + listAPI/addAPI documentados em PGM_ERP_INTEGRACOES_GRID.md |
| `pg-estoque` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-estoque-log` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-export-clientes` | clientes | both | addAPI/listAPI Grid → clientes |
| `pg-historico-precos` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-import-clientes` | clientes | both | addAPI/listAPI Grid → clientes |
| `pg-import-produtos` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-inv-historico` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-inventario` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-os-abertura` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-aprovacao` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-cobranca` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-conclusao` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-execucao` | ordensservico | both | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-faturamento` | ordensservico | both | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-kanban` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-lista` | ordensservico | both | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-os-sucesso` | ordensservico | receive | listAPI/refreshAPI + SOAP preço estoque em itens |
| `pg-pc-lista` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-pc-novo` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-precificacao` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-preco-tabela-nova` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-precos` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-produto-detalhe` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-produto-novo` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-produtos` | produtos | both | addAPI/listAPI + SOAP GetEstoqueProdutos |
| `pg-sd-contratos` | servicedesk | soap | Contratos SLA / faturamento via legado ou ERP |
| `pg-sd-fat` | servicedesk | soap | Contratos SLA / faturamento via legado ou ERP |
