# Prompts para o Cursor (integração no portal-teste)

Colar **no fim** de cada prompt (ajuste o módulo):

```
Trabalhe incrementalmente. Não faça rewrite completo de ficheiros grandes.
Preserve o padrão do projeto (CakePHP 3, idcliente/idempresa, layout existente).
Altere apenas o necessário. Liste ficheiros alterados e como testar.
```

---

## Análise inicial (sem alterar código)

Analisa este projeto CakePHP 3: `src/Controller`, `src/Template`, `config/routes.php`, `config/permissions_registry.php`, `src/Template/Element/sidebar.ctp`, `sidebarcli.ctp`, componente `Rbac`, módulo `Tickets` e módulos `Advanced*` / `PortalAdvanced*`.

Compara com `docs/DOCUMENTO_MESTRE_MODULOS.md` e `docs/portal-modulo-avancado-fase1.md`.

Entrega: plano por etapas pequenas; **não alteres nada ainda**.

---

## Integrar draft HTML

Tenho o rascunho em `docs/ui-drafts/[NOME].html`. Adapta ao padrão atual: layout `default.ctp` / `client.ctp`, classes `col-12 pgm-adv-page` / `pgm-adv-panel` onde aplicável, helpers Cake, sem bibliotecas novas.

Indica que ficheiros `.ctp` crias ou alteras e como validar no browser.

---

## Relatórios ERP (estrutura)

**Nota:** Já existe `RelatoriosController` e menu. Se a tarefa for **expandir** em vez de duplicar, primeiro lê `src/Controller/RelatoriosController.php` e `src/Template/Relatorios/`.

Objetivo desta etapa apenas: garantir entrada de menu, rota e view alinhadas ao documento mestre **ou** acrescentar secção/KPIs em `relatorios/index` sem duplicar `AdvancedReports`.

---

## Relatórios Portal

O portal já tem `PortalRelatorios`. Etapa: alinhar com `docs/DOC6_RELATORIOS.md` (KPIs, filtros, placeholders) **sem** expor dados internos; reutilizar padrão `sidebarcli.ctp`.

---

## Detalhe de contrato / fatura (ERP)

Se a listagem for `AdvancedContracts` / `AdvancedInvoices`: melhorar `view.ctp` com abas/blocos (dados, serviços, documentos, faturas) usando dados reais dos models já associados; evitar duplicar `Clicontratos` salvo requisito explícito.

---

## Exportação

Reutilizar `ReportExportService` ou padrão CSV já usado em `AdvancedInvoices::export` / `PortalRelatorios`. Respeitar filtros GET e escopo empresa/cliente.

---

## RBAC / ABAC

Analisa `permissions_registry.php`, migrations `rbac_*`, `RbacComponent` / `RbacChecker`. Liga permissões aos novos controllers **sem** alterar regras antigas sem necessidade. Portal: `C_RoleCliente` + escopo cliente; notas internas só ERP.

---

## Revisão final antes de merge

Audita alterações: PHP, rotas, layout, permissões. Classifica ficheiros seguro/suspeito/crítico. Indica checklist manual. Não refactors globais.

---

## Mapeamento rápido (este repo)

| Conceito do chat | Implementação atual / alvo |
|------------------|----------------------------|
| Histórico ERP | `Tickets/historico` (clássico), `AdvancedAttendance` + `/modulo-avancado/atendimentos` (menu Tickets) |
| Histórico portal (PG) | `PortalAdvancedAttendance` + `/cliente/historico-atendimento-avancado` (menu Tickets, não em Contratos & faturas) |
| Contratos avançados | `AdvancedContracts`, `PortalAdvancedContracts` |
| Faturas avançadas | `AdvancedInvoices`, `PortalAdvancedInvoices` |
| Indicadores ERP | `AdvancedReports` + `/modulo-avancado/indicadores` |
| Relatórios portal | `PortalRelatorios` |
| Geração faturas | `bin/cake portal_advanced gerar_faturas_mes` |
