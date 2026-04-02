# Checklist de testes — módulos ERP / portal

Marque após cada entrega. Ajuste URLs se o ambiente usar prefixo (`/portal/...`).

## Geral

- [ ] Login ERP (`role = 0`) e portal cliente (`C_RoleCliente`) funcionam
- [ ] Troca de empresa (se aplicável) não quebra listagens
- [ ] Sem erro 500 nas rotas novas ou alteradas
- [ ] Menu lateral: item aparece e estado ativo correto (ver `docs/DOC2_MAPA_MENUS.md` §6)

## Histórico / atendimento

- [ ] Listagem abre com paginação
- [ ] Filtros (período, cliente, etc.) não geram SQL inválido
- [ ] Detalhe: dados do ticket / histórico coerentes com `idempresa`
- [ ] **Portal:** sem `internal_note` / dados internos em timeline pública

## Contratos (módulo avançado + legado)

- [ ] `contracts` vazio: mensagem ou fallback `clicontratos` (conforme implementado)
- [ ] Detalhe: serviços e documentos; portal só documentos públicos
- [ ] Links `Clicontratos` no ERP respeitam permissão

## Faturas (módulo avançado)

- [ ] Listagem, detalhe, marcar paga (POST + CSRF)
- [ ] Export CSV respeita filtros e empresa
- [ ] **Portal:** só faturas do `idcliente` + `idempresa` da sessão

## Relatórios / indicadores

- [ ] KPIs ou placeholders carregam sem erro
- [ ] Export (CSV/Excel) não vaza dados de outros clientes no portal
- [ ] ERP: indicadores internos apenas para quem tem permissão (quando RBAC aplicado)

## RBAC (quando ativo)

- [ ] Utilizador sem permissão recebe negação controlada (403/redirect)
- [ ] `cliente_portal` com permissões `portal.*` conforme migration/registry

## Regressão rápida

- [ ] `Tickets` operacional / `indexcliente` ainda abre
- [ ] `Faturamento` / `Financeiro` / `Clientes` principais abrem
