# Prompts para o Claude (especificação e rascunhos)

Cole **um bloco de cada vez**. Peça saída em Markdown ou HTML **para guardar em `docs/ui-drafts/`**, não como “alterar o projeto”.

---

## 1) Documento mestre (se precisar atualizar)

Quero atualizar o documento mestre dos três módulos (Histórico de atendimentos, Contratos e faturas, Relatórios) para um ERP CakePHP 3 com portal do cliente, RBAC/ABAC, PostgreSQL no módulo avançado (`contracts`, `invoices`, `attendance_*`).

Entrega: Markdown com visão geral, menus ERP vs portal, dependências, ordem de desenvolvimento. **Não** escrever código de produção; referenciar convenções `idcliente` / `idempresa`.

---

## 2) Especificação funcional — Histórico

Crie especificação funcional do módulo **Histórico completo de atendimentos** (ERP + portal): perfis, RBAC/ABAC, filtros, colunas, detalhe com abas (resumo, timeline, SLA, anexos, auditoria), regras de nota interna vs pública, critérios de aceite, edge cases.

Stack alvo: CakePHP `.ctp`, integração com `tickets` existente e opcionalmente `attendance_histories`.

---

## 3) Especificação funcional — Contratos e faturas

Mesma estrutura para **Contratos e faturas**: diferença contrato vs fatura, portal vs ERP, franquia/consumo, downloads, critérios de aceite. Considerar coexistência de `clicontratos` (legado) e tabela `contracts` (módulo avançado).

---

## 4) Especificação funcional — Relatórios

Especificação para **Relatórios e indicadores**: o que é ERP completo vs portal recortado, filtros, KPIs, exportações, sem dados operacionais sensíveis no portal.

---

## 5) Wireframes textuais

Wireframes textuais para: ERP histórico, contratos, faturas de contrato, relatórios; portal histórico, contratos+faturas, relatórios. Layout: menu lateral escuro, cards KPI, filtros, tabela, detalhe com abas.

---

## 6) HTML estrutural (CakePHP)

Para a tela **[NOME]**, gere **apenas** HTML estrutural em seções comentadas, classes semânticas, sem React/Vue, fácil de adaptar a `.ctp`. Inclua estados: vazio, carregando, sem permissão, erro.

**Instrução final:** “Gere o HTML completo num único bloco para eu guardar em `docs/ui-drafts/arquivo.html`.”

Repita o pedido para: Contratos ERP, Faturas ERP, Relatórios ERP, Portal Histórico, Portal Contratos e faturas, Portal Relatórios.

---

## Regra para o Claude (colar no fim)

Não modificar ficheiros de um repositório real; entregar apenas texto/HTML para o utilizador copiar para `docs/ui-drafts/`.
