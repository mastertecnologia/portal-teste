# Fase 1 — Inventário de tokens, proposta oficial e mapeamento

Documento para **revisão de stakeholders** antes da migração em massa (Fases 3–4).  
**Última atualização:** registo consolidado em **§10** (execução contínua Fases 5–7 e tokens ERP/estoque/layout).

---

## 1. Inventário — tokens globais atuais

### 1.1 `html[data-pgm-theme]` — `public/dist/css/pages/pgm-theme-tokens.css`

| Token | Light | Dark | Uso |
|-------|-------|------|-----|
| `--pgm-primary` | `#00a876` | `#00d9a0` | Marca / links / foco |
| `--pgm-primary-hover` | `#007a55` | `#5cecc4` | Hover primário |
| `--pgm-primary-muted` | rgba | rgba | Fundos suaves |
| `--pgm-secondary` | `#0d5c63` | `#127a82` | Secundária |
| `--pgm-accent` | `#00c9a7` | `#45e5ed` | Destaque |
| `--pgm-bg-page` | `#f0f2f5` | `#12151c` | Fundo página |
| `--pgm-bg-surface` | `#ffffff` | `#1e2329` | Cards / superfície |
| `--pgm-bg-elevated` | `#f8f9fb` | `#262c35` | Superfície elevada |
| `--pgm-text` | `#1a1f2e` | `#e8eaed` | Texto principal |
| `--pgm-text-secondary` | `#374151` | `#c4c9d1` | Texto secundário |
| `--pgm-text-muted` | `#6b7280` | `#9aa0a8` | Muted |
| `--pgm-border` | `#e1e4e8` | `#3d4554` | Borda |
| `--pgm-border-strong` | `#d0d7de` | `#4f5869` | Borda forte |
| `--pgm-success` / `-bg` / `-text` | … | … | Semântica |
| `--pgm-warning` / `-bg` / `-text` | … | … | Semântica |
| `--pgm-danger` / `-bg` / `-text` | … | … | Semântica |
| `--pgm-info-bg` / `-text` | … | … | Info |
| `--pgm-focus-ring` | rgba | rgba | Anel foco |
| `--pgm-link` | `#00a876` | `#45e5ed` | Links |
| `--pgm-section-gap-y` | `1.25rem` | idem | Layout |
| `--pgm-stack-gap` | `1rem` | idem | Layout |
| `--pgm-radius-section` | `12px` | idem | Raio seção |
| `--pgm-erp-teal` | `#00c08b` | `var(--pgm-primary)` | Ação ERP / orçamentos (light explícito; dark alinhado à primária) |
| `--pgm-erp-teal-hover` | `#008f68` | `var(--pgm-primary-hover)` | Hover botões ERP |
| `--pgm-erp-teal-active` | `#0c8f64` | `#00b589` | Estado pressed / `:active` (ex.: estoque → `--est-primary-active`) |
| `--pgm-erp-teal-muted` | rgba | `var(--pgm-primary-muted)` | Fundos suaves ERP |

**Variável de layout (runtime, não no bloco estático acima):** `--pgm-layout-min-h` — definida em `document.documentElement` por `pgmLayoutNoTopbarMinHeight()` em `Layout/default.ctp`; consumida por `.pgm-shell-main` em `layout-sidebar-shell.css` (`min-height: var(--pgm-layout-min-h, 100vh)`). Sem `.pgm-shell-main`, o `body` recebe a classe `pgm-layout-min-h-legacy-wrapper` e `.page-wrapper` usa o mesmo token (sem jQuery `.css('min-height', …)`). Removida quando `body` deixa de ter `layout-no-topbar`.

**Consumidores típicos:** `data-pgm-theme` no `<html>` (default.ctp), Service Desk, integrações.

### 1.2 `body.pgm-theme-light` — `pgm-theme-light.css`

Variáveis no **body** (não duplicadas em `html[data-pgm-theme]`):

| Token | Valor (light interno) | Nota |
|-------|----------------------|------|
| `--pgm-content-bg` | `#f0f2f5` | ≈ `--pgm-bg-page` |
| `--pgm-surface` | `#ffffff` | ≈ `--pgm-bg-surface` |
| `--pgm-surface2` | `#f8f9fb` | ≈ `--pgm-bg-elevated` |
| `--pgm-border` / `--pgm-border2` | `#e1e4e8` / `#d0d7de` | Alinhado a tokens |
| `--pgm-text` / `--pgm-text2` / `--pgm-muted` | … | Nomes divergentes de `--pgm-text-secondary` |
| `--pgm-teal-btn` | `#00a876` | = primária marca |
| `--pgm-input-bg` / `--pgm-input-border` | … | Formulários |
| `--pgm-table-header` / row / border | … | Tabelas |

**Conflito estrutural:** duas “fontes”: `html[data-pgm-theme=*]` vs `body.pgm-theme-light` com nomes ligeiramente diferentes.

### 1.3 Shell sidebar — `layout-sidebar-shell.css` (`:root`)

| Token | Exemplo | Escopo |
|-------|---------|--------|
| `--pgm-sb-bg` | `#0d1117` | Sidebar / legado page em dark |
| `--pgm-sb-surface` | `#161b22` | Superfícies sidebar |
| `--pgm-sb-text` / `--pgm-sb-muted` | … | Texto sidebar |
| `--pgm-teal` / `--pgm-teal-light` | `#1d9e75` / `#5cdbc0` | Acento shell |

### 1.4 Módulos premium — tokens locais (resumo)

| Módulo | Arquivo | Prefixo | Primária / ação típica |
|--------|---------|---------|-------------------------|
| Clientes | `clientes-premium.css` | `--cli-*` | `--cli-teal` `#1d9e75` |
| Produtos | `produtos-premium.css` | `--prd-*` | `--prd-teal` `#1d9e75` |
| Orçamentos | `orcamentos-premium.css` | `--orc-*` | `--orc-teal` `#00C08B` (+ blocos light/dark duplicados no mesmo arquivo) |
| Estoque | `pgm-estoque.css` | `--est-*` | `--est-primary` `#10b981` (só tela estoque) |
| Cofre | `vault-cofre.css` | `--vault-*` | `--vault-accent` `#00c08b` |
| Botões globais | `pgm-action-buttons.css` | `--pgm-btn-*` | `--pgm-btn-teal` `#00c08b` |

**Observações:** orçamentos redefine `--orc-*` em múltiplos escopos (claro/escuro/premium); risco de inconsistência.

---

## 2. Proposta oficial (fonte única de verdade — alvo)

### 2.1 Cores semânticas (manter valores atuais de `pgm-theme-tokens` como baseline)

- **Primária marca:** `--pgm-primary` / `--pgm-primary-hover` / `--pgm-primary-muted` (já definidos).
- **Ação ERP / legado orçamentos-botões:** documentado em §1.1:
  - `--pgm-erp-teal` / `--pgm-erp-teal-hover` / `--pgm-erp-teal-active` / `--pgm-erp-teal-muted`
  - Light: teal explícito; dark: teal alinhado à primária + active dedicado (`#00b589`).
  - Estoque: `pgm-estoque.css` mapeia `--est-primary-active` → `var(--pgm-erp-teal-active, #0c8f64)`.

### 2.2 Superfícies e texto

- Preferir sempre: `--pgm-bg-page`, `--pgm-bg-surface`, `--pgm-bg-elevated`, `--pgm-text`, `--pgm-text-secondary`, `--pgm-text-muted`, `--pgm-border`, `--pgm-border-strong`.
- **Meta Fase 4:** reduzir `--pgm-content-bg`, `--pgm-surface2`, `--pgm-text2` no light body a **aliases** dos nomes `html` (sem remover de imediato para não quebrar legado).

### 2.3 Escala espacial (nova, Fase 1)

| Token | Valor |
|-------|--------|
| `--pgm-space-1` | `4px` |
| `--pgm-space-2` | `8px` |
| `--pgm-space-3` | `12px` |
| `--pgm-space-4` | `16px` |
| `--pgm-space-5` | `24px` |
| `--pgm-space-6` | `32px` |

### 2.4 Border radius (nova, Fase 1)

| Token | Valor | Uso sugerido |
|-------|--------|----------------|
| `--pgm-radius-sm` | `8px` | inputs, chips |
| `--pgm-radius-md` | `10px` | botões grandes |
| `--pgm-radius-lg` | `12px` | cards (= `--pgm-radius-section`) |
| `--pgm-radius-xl` | `16px` | modais / hero |

### 2.5 Tipografia (nova, Fase 1)

| Token | Valor |
|-------|--------|
| `--pgm-font-sans` | `"DM Sans", system-ui, …` |
| `--pgm-font-mono` | `"DM Mono", ui-monospace, …` |

---

## 3. Tabela de mapeamento antigo → novo (adoção gradual)

| Origem | Token antigo | Token alvo (preferencial) |
|--------|--------------|---------------------------|
| Premium clientes | `--cli-bg` | `var(--pgm-bg-page)` dark ou superfície módulo |
| Premium clientes | `--cli-surface` | `var(--pgm-bg-surface)` |
| Premium clientes | `--cli-text` | `var(--pgm-text)` |
| Premium clientes | `--cli-teal` | `var(--pgm-primary)` ou `var(--pgm-erp-teal)` conforme contexto |
| Premium produtos | `--prd-*` | Análogo `--cli-*` → `--pgm-*` |
| Orçamentos | `--orc-teal` | `var(--pgm-erp-teal)` |
| Orçamentos | `--orc-bg-page` / `--orc-text` | `var(--pgm-bg-page)` / `var(--pgm-text)` |
| Estoque | `--est-primary` | `var(--pgm-primary)` ou `var(--pgm-erp-teal)` (decisão: alinhar estoque ao ERP teal na Fase 4) |
| Estoque | `--est-space-*` | `var(--pgm-space-*)` |
| Vault | `--vault-accent` | `var(--pgm-erp-teal)` |
| Botões | `--pgm-btn-teal` | `var(--pgm-erp-teal)` (Fase 3) |
| Body light | `--pgm-content-bg` | Alias futuro: `var(--pgm-bg-page)` |
| Body light | `--pgm-text2` | Alias futuro: `var(--pgm-text-secondary)` |

Nenhuma migração obrigatória nesta fase — apenas referência para PRs futuros.

---

## 4. Nomenclatura final recomendada

- Prefixo único: **`--pgm-`** para tokens globais.
- Subprefixos semânticos: `bg-`, `text-`, `border-`, `radius-`, `space-`, `font-`.
- **Primária marca** ≠ **teal ERP:** usar `--pgm-primary` (marca) e `--pgm-erp-teal` (ações alinhadas ao legado `#00c08b`).
- Módulos: durante transição, manter `--cli-*` etc. como **alias local** que aponta para `--pgm-*` (opcional, por módulo).

---

## 5. Critério de sucesso Fase 1

- [x] Inventário documentado.
- [x] Proposta de escala spacing/radius/font + ERP teal documentada.
- [x] Aliases adicionados em `pgm-theme-tokens.css` sem remover tokens existentes.
- [ ] Stakeholders revisaram e aprovaram primária vs ERP teal e calendário de Fase 4.

---

## 6. Execução realizada (Sprints B–E resumo)

| Fase | Entrega |
|------|---------|
| **B** | `Cli/ui_css.ctp`, `Pgm/form_shell_dark.ctp` — tema claro sem forçar dark; portal cliente com `:not(.pgm-theme-light)`. |
| **C** | `public/dist/css/pages/pgm-components-base.css` (+ espelho `webroot/dist/css/pages/`), incluído após `pgm-theme-tokens` nos layouts que carregam tokens. **Piloto:** `Clientes/index.ctp` — `cli-root pgm-ds-pilot`, classes `pgm-pill-dot`, `pgm-icon-xs`. |
| **4** | Aliases `--pgm-*` em `clientes-premium.css`, `produtos-premium.css`, bloco `.orc-premium-wrap` em `orcamentos-premium.css`, `:root` em `pgm-action-buttons.css`, tokens estoque em `pgm-estoque.css`, `vault-cofre.css`. Bordas `--cli-*` / `--prd-*` de grid escuro **mantidas literais** (tokens globais dark diferem). |
| **5 (lote)** | Lista clientes: remoção de `style=` em pills, ícones, colunas, linhas (cursor já no CSS), avatares inativos → `cli-av--inactive`. Cofre: `vault-cofre--compact` em `Bancosenhas/*`. |
| **6** | Ver §8 — espelhos obrigatórios; script `scripts/sync-css-mirrors.ps1` espelha `public/dist/css/pages` → `webroot/dist/css/pages` e reconcilia pares listados (ficheiro mais recente vence se houver divergência). |

---

## 7. Governança (Fase 7)

1. **Ordem de carga:** `pgm-theme-tokens` → `pgm-components-base` → tema light (se aplicável) → CSS de módulo / premium.
2. **Novos tokens:** apenas em `pgm-theme-tokens.css` (light + dark); documentar neste ficheiro §2.
3. **PRs de módulo:** preferir alias `var(--pgm-*, fallback)` com fallback ao valor legado; não remover `--cli-*` / `--prd-*` de imediato.
4. **Inline:** evitar `style=` em novas views; para legado, lotes pequenos com classe em `pgm-components-base` ou CSS do módulo.
5. **QA regressão:** após mudança de tokens, validar `html[data-pgm-theme="light"|"dark"]` + `body.pgm-theme-light` nos fluxos tocados.
6. **Antes de PR / release** que toque em CSS de `public/dist/css/pages` ou nos pares listados em §8: executar `powershell -ExecutionPolicy Bypass -File scripts/sync-css-mirrors.ps1` e commitar os espelhos alinhados.

---

## 8. Fase 6 — Espelhos de assets

- **Premium servidos por `PgmAssetsController`:** fonte em `webroot/css/`; manter `public/css/` em sync (deploy com `WEBROOT_DIR=public`).
- **`orcamentos-premium.css`:** quatro cópias alinhadas — `webroot/css`, `public/css`, `webroot/dist/css`, `public/dist/css`.
- **`pgm-theme-tokens`, `pgm-theme-light`, `pgm-components-base`:** `public/dist/css/pages/` e `webroot/dist/css/pages/`.
- **`pgm-advanced-module.css`:** contratos avançados e calendário do módulo — manter `public/dist/css/pages/` e `webroot/dist/css/pages/` em sync.
- **`config-admin-shell.css`, `layout-sidebar-shell.css`, `ordensservico-index-shell.css`, mocks estruturais (`portal-relatorios-estrutural.css`, `portal-client-barras-estrutural.css`, `erp-relatorios-estrutural.css`, `erp-clicontratos-estrutural.css`)** e restantes `*.css` em `public/dist/css/pages/`: espelhar para `webroot/dist/css/pages/` (via script §6 ou cópia manual).
- **`vault-cofre.css`:** referenciado como `/css/vault-cofre.css`; manter `webroot/css` e `public/css` iguais se o deploy usar `public` como webroot.
- **`faturas-locacao-doc.css`:** recibo/imprimir de locação; manter `webroot/css` e `public/css` em sync.

---

## 9. Próximos passos opcionais

**Histórico do que já foi feito** está consolidado em **§10** (não duplicar aqui em listas longas ao atualizar o doc — acrescentar novidades em §10).

- Stakeholder sign-off §5 (checkbox pendente).
- Estender Fase 5 (inline) a outras views ainda com legado (ex.: faturas).
- **Feito neste ciclo:** `Orcamentos/index.ctp` (stats + cabeçalhos de tabela) e `Produtos/index.ctp` (badges, pills, colunas numéricas).
- **Feito em seguida:** `Produtos/add.ctp` e `edit.ctp` (form + preview + locação: classes `prd-*`, `prd-is-hidden` + JS com `classList`); `Orcamentos/edit.ctp` e `view.ctp` (crumb, cards, workflow, versões, rodapé — classes `orc-*` em `orcamentos-premium.css`); `Produtos/precificacao.ctp` (sem `<style>` na view: layout full-screen + componentes `prec-*` e `body.prec-screen-active` concentrados em `produtos-premium.css`; utilitários `prec-*` adicionais no fim do mesmo ficheiro; barra mini via `data-pct` + `style.width` após render).
- **Orçamentos (novo lote):** `Orcamentos/add.ctp` (breadcrumb `orc-form-crumb*`, cards `orc-card-mb-14`, inputs readonly `orc-input-readonly-fill`); `formapagamento` com `orc-native-select` (add + `edit`); elementos `orcamentos_secao_produtos_form` / `orcamentos_secao_produtos_rodape` (margem, desconto, totais — classes `orc-*`); `orcamentos_novo_edit_shared.js` usa `orc-is-hidden` em vez de `.hide()/.show()` no bloco de edição de item. **Barra de margem (`#ms-bar`):** `width` via `--orc-margin-pct` em `orcamentos-premium.css` + `style.setProperty` no JS (sem jQuery `.css`); **margem % (`#t-marg`):** classes `orc-tot-val--teal` / `orc-marg-pct--warn` em vez de cor por `.css('color', …)`.
- **`public/dist/js/custom.js` (+ `custom.min.js`):** `min-height` de `.page-wrapper` com `style.minHeight` nativo em loop (sem `$().css('min-height', …)`).
- **`public/dist/js/pages/chat.js`:** altura de `.chat-list` e `.chat-left-inner` com `this.style.height` em `.each()` (sem `.css('height', …)`).
- **`public/js/material-kit.js`:** parallax `materialKitDemo.checkScrollForParallax` — `transform` via `style.*` em cada elemento de `big_image` (sem jQuery `.css({…})`).
- **`public/js/material-dashboard.js`:** camada `.close-layer` — `height` com `$layer[0].style.height` (sem `.css('height', …)`).
- **`public/dist/js/pages/jasny-bootstrap.js`:** file input preview — `line-height` / `max-height` da imagem via `getComputedStyle` + `style.*` nativos (sem jQuery `.css`).
- **`Orcamentos/solicitar.ctp`:** `pgm-sol-field--mt16`, alinhamento de qtd. via `.pgm-sol-item-row input[type=number].pgm-sol-input`; removidos `style=` redundantes (fundo do código já coberto por `.pgm-sol-input`). **`Orcamentos/novaordem.ctp`:** botão “expandir texto” na grelha com classe `btn-orc-expand-text` em `orcamentos-premium.css`.
- **Portal / papel / e-sign:** `viewhash.ctp` — classes no `<style>` do portal (badges, progresso, tabela, decisão, recusa/negociar, sucesso); sem `style=` nos atributos. `imprimir.ctp` — `orc-paper-badge--teal|--amber`, `orc-ptbl-w*`, assinatura/rodapé. `envioassinatura.ctp` — utilitários `.es-*` no bloco `.orc-esign`; `#sign-ok` com `.is-visible` em JS.
- **`Orcamentos/imprimir_pdf.ctp`:** layout do PDF só com classes no `<head>` (`table-dados--header-block`, `td-pdf-logo`, `pdf-logo-img`, `rodape-data p`, etc.); sem `style=` no corpo.
- **`Users/dashboard.ctp`:** portal cliente (`dcli-*` no `<style>` da view); KPIs com `data-w` + script que aplica `width`%; badge requisições `sla-pending` em `public/dist/css/dashboard-pgm.css`. **`Permissoes/admin_index.ctp`:** classes `admin-rbac-*` e `ap-code-*` em `public/dist/css/pages/config-admin-shell.css`.
- **`Permissoes/admin_matrix.ctp`**, **`admin_users.ctp`**, **`admin_user_roles.ctp`:** hero sub-página `admin-rbac-hero--sub`, link `admin-rbac-a-inline`, linha de módulo `admin-rbac-mod-row`, células `admin-rbac-td-left`, rodapé `admin-rbac-footnote`, painel papéis `admin-rbac-role-panel` + tipografia `admin-rbac-role-*` / `ap-text-bright` — tudo em `config-admin-shell.css`; sem `style=` nos atributos.
- **`Bancosenhas/index.ctp`:** `vault-is-hidden`, `vault-btn-hide-reveal`, lista vazia `vault-list-empty-msg`; JS com `classList`. Formulários: `vault-form-note--flush` em `add` / `edit` / `change_password`. CSS em `vault-cofre.css` (`webroot/css` + `public/css` em sync).
- **`Faturas/view.ctp`:** bloco `<style>` da view — `signature-field--w40` / `--w70`, `invoice-sign-col-border`, `invoice-logo-img`, `invoice-table-top-border`, `invoice-valor-destaque` (view sem `style=` nos atributos).
- **`Layout/default.ctp`:** `page-titles` com `pgm-page-titles--pb-tight` em `layout-sidebar-shell.css` (substitui `padding-bottom` inline).
- **`Element/portal_notification_bell.ctp`:** classes `pgm-portal-notif-mark-all`, `pgm-notif-list-body`, `pgm-notif-list-placeholder`, `pgm-portal-notif-prefs-link`, `pgm-nt-msg` no bloco `<style>` do elemento (sem `style=` em markup/JS gerado).
- **`Faturas/edit.ctp`:** `erp-action-bar--flush`, `erp-additem-bar--plain`, cabeçalhos de recibos via `#tableRecibos thead th` no `<style>` da view (sem `style=` nos nós).
- **`Faturas/carrinhoedit.ctp`:** linha com quantidade devolvida → classe `fat-carrinho-row-devolvida` no `<style>` da partial.
- **`Visitas/calendario.ctp`:** legenda de feriados com `pgm-cal-legend-dot--national` / `--empresa` (cursor já coberto por `.calendar-events`).
- **`Element/sidebar.ctp` + `sidebarcli.ctp`:** `pgm-sidebar-flex-min`, ícone de busca `pgm-sb-search-svg`, host DataTables `pgm-sidebar-dt-host--pending` (removido por JS na OS index), `d-none` no `mini-logout`, `pgm-sidebar-caret-push`, `pgm-cli-name-truncate` — regras em `layout-sidebar-shell.css`.
- **`Ordensservico/index.ctp`:** `removeClass('pgm-sidebar-dt-host--pending')` em vez de `.show()` no host; tabela `os-table-flush`, label `os-checkbox-label-flush`, drawer sem `style=` em strings — classes em `ordensservico-index-shell.css`.
- **`Layout/privacy_policy.ctp`:** `.pgm-privacy-theme-float` em `pgm-login-theme.css` (`public/dist` + `webroot/dist`).
- **`Servicedesk/login.ctp`:** `sd-login-card-narrow`, `sd-auth-theme-bar--tight`, `sd-login-hidden` + `classList` no `sdSetTab` (compatível com `display:none !important`).
- **`pgm-components-base.css`:** `pgm-bootbox-msg-lg` / `pgm-bootbox-msg-md`, `pgm-pre-json` (+ `--wrap`, `--h200`), `pgm-solic-outros-wrap` — usados em OS add/edit/view, `cadastrocliente`, etc.
- **`Ordensservico/add.ctp` + `edit.ctp` + `view.ctp`:** container solicitante outros + `<pre>`/bootbox sem `style=` (classes acima).
- **`Users/cadastrocliente.ctp`:** mensagens bootbox com `pgm-bootbox-msg-lg`.
- **`Tickets/imprimir.ctp`:** `ticket-print-root--screen`, `ticket-print-visita-date`, larguras `ticket-print-th-w*`, `ticket-print-empty-note` no `<style>` da view.
- **`Element/Faturas/escrita_fiscal_form.ctp`:** `erp-ef-label-sm`, `erp-ef-title-ico`.
- **`Element/Faturas/observacoes_fiscais_ibpt.ctp`:** bloco `ibpt-*` no partial (impressão fiscal).
- **`Faturas/recibo.ctp` + `Faturas/imprimir.ctp`:** classes `fat-loc-*` em `faturas-locacao-doc.css` (`webroot/css` + `public/css`); sem `style=` nos atributos (cabeçalho, grelhas, tabela produtos, totais, assinatura).
- **`Ordensservico/view.ctp`:** secção documentos de faturamento com `os-view-fat-heading` / `os-view-fat-table` no `<style>` da view.
- **`orcamentos-premium.css`:** literais `#00c08b` restantes só em fallbacks de `var(--orc-teal)` / `var(--pgm-erp-teal)`; catálogo e papel orçamento seguem o token.
- **`Clientes/add` + `Clientes/edit`:** inline removido em favor de classes em `clientes-premium.css` e `pgm-components-base.css` (`pgm-gap-6` / `pgm-gap-8`).
- **`ContractManagement/view.ctp`**, **`add_signatarios.ctp`**, **`add_servicos.ctp`**, **`enviar_assinatura.ctp`:** utilitários `adv-cm-*` (título, separadores, barra de ações, zona cancelar, tabelas KV, cabeçalhos de card, colunas, checkbox) em `pgm-advanced-module.css` (espelho `public` / `webroot`).
- **`Element/ContractManagement/wizard_steps.ctp`:** `pgm-contract-wizard-steps`, pílulas `pgm-contract-wiz-pill` / `--current` / `--disabled` (sem `style=` dinâmico nos links).
- **`Element/ContractTemplates/form_fields.ctp`:** `pgm-gap-6` e `pgm-font-mono` nos JSON.
- **`Users/editcliente.ctp`:** bootbox com `pgm-bootbox-msg-lg`.
- **`Clientes/eventos.ctp`:** `pgm-cli-eventos-card` / `pgm-cli-eventos-table` / `pgm-cli-eventos-timestamp` / `pgm-cli-eventos-code` em `pgm-components-base.css`.
- **`dashboard-erp.css`:** utilitários `dash-erp-scroll--h260`, `--vh70`, `dash-erp-card-title--tab`, `dash-erp-filter-actions`, `dash-erp-meta-align`, `dash-erp-empty-cell` / `--lg`, `dash-erp-th-actions`, `dash-erp-th-w140`, `dash-erp-modal-body-preview`, `dash-erp-preview-iframe` (Relatórios, histórico/finalizados tickets, requisições de acesso).
- **`pgm-client-premium.css`:** `tkcli-filter-group--grow` / `--w160` / `--w180` / `--actions`, `tkcli-tickets-card-body`, cursor em `tr.ticket-row[data-url-view]` (Portal Relatórios + tickets cliente).
- **`pgm-components-base.css`:** `pgm-table-max-w-520`, `pgm-popover-title-date`, `pgm-min-w-0`, `pgm-th-w-220`.
- **`prefaturamento-shell.css`:** `pf-th-select`, `pf-td-conferencias`.
- **`pgm-advanced-module.css`:** `pgm-template-preview-scroll`, `pgm-template-json-pre` (preview de modelos).
- **Financeiro** (`fatura`, `contas_receber`, `index`, `pdf_fatura_financeiro`): classes `cr-*` / `fin-*` / `pdf-*` nos blocos `<style>` das views ou no PDF.
- **Faturamento** (`view` com `--fat-st` na raiz para cor dinâmica; `index`, `add`, `edit`): `fatview-*`, `fatadd-*`, `fat-*`.
- **Clicontratos/view:** `clicontrato-head-main`, `clicontrato-th-key`.
- **Ordensservico** `imprimir.ctp` / `_imprimir.ctp` / `imprimirordens.ctp`: `os-info-cell--span2`, `os-total-row.valortotalh5`, `os-print-logo-w`, `os-print-spacer`.
- **`ticketordem.ctp`:** `pgm-pre-json*` e `pgm-bootbox-msg-md` em strings JS.
- **Tickets/Visitas listas:** `pgm-popover-title-date` em títulos de popover; **`Users/edit`** / **`change_profile`:** `pgm-bootbox-msg-lg`.
- **Mocks `index_estrutural.html`:** sem `<style>` embutido — folhas em `public/dist/css/pages/` (espelho `webroot/dist`): `portal-relatorios-estrutural.css` (todo o bloco `prel-*`); `portal-client-barras-estrutural.css` (KPI/franquia portal + SLA histórico: classes `*__pct--*` e dinâmico `*--dyn` com `style="--pgm-bar-pct: …"`); `erp-relatorios-estrutural.css`; `erp-clicontratos-estrutural.css`. Os HTML referenciam `/dist/css/pages/…` para pré-visualização; em Cake usar `$this->Html->css('/dist/css/pages/…')`.
- **`Users/tarefas.ctp`:** riscado com classe `.users-tarefa-line-done` em `pgm-components-base.css`; jQuery usa `addClass`/`removeClass` (sem `.css('text-decoration', …)`).
- **Fase 5 — opções PHP (`'style' => …` em `Form`/`Html`):** `Bancosenhas` add/edit/change_password — removido `color` redundante (já em `.vault-btn-copy` em `vault-cofre.css`); `Tickets/edit` e `Tickets/view` — classe `text-white` no botão de anexo; `PortalNotifications/preferences` — checkboxes com `.pgm-checkbox-table-cell` em `pgm-components-base.css` (`public` + `webroot`); `ContractManagement/index` — filtros com `.pgm-adv-contract-filter-status` / `filter-cliente` / `filter-mb` em `pgm-advanced-module.css`; `Clientes/edit` — botões ficha com `d-none` inicial e `webroot/js/modules/clientes/cliente-edit-ficha.js` alterna `d-none` em vez de `.hide()/.show()`; `Empresasusers/index`, `Problemas/index`, `Areas/index` — `m-b-20` nos links em vez de margem inline. Em `.ctp`, atributo `style=` só permanece onde há token dinâmico (`Faturamento/view` — `--fat-st`; `loginempresa` — `--login-empresa-bg`).
- **Estoque / tokens:** `--pgm-erp-teal-active` em `pgm-theme-tokens.css` (light + dark); `pgm-estoque.css` usa `--est-primary-active: var(--pgm-erp-teal-active, #0c8f64)` (`public/css` + `webroot/css`).
- **`Layout/default.ctp`:** `verificaSidebar` usa `toggleClass('d-none')` em `#mini-logout` / `.mini-itens` (sem jQuery `.css('display', …)`); `pgmLayoutNoTopbarMinHeight` define `--pgm-layout-min-h` em `:root`, `.pgm-shell-main` e (legado) `body.pgm-layout-min-h-legacy-wrapper .page-wrapper` em `layout-sidebar-shell.css` — sem `.css('min-height', …)` no jQuery.

---

## 10. Registo consolidado — execução (Fases 5–7 e continuidade)

Índice único para auditoria, onboarding e PRs. Caminhos relativos à raiz do repositório.

### 10.1 Script e espelhos (Fase 6)

| Artefacto | Função |
|-----------|--------|
| `scripts/sync-css-mirrors.ps1` | Copia `public/dist/css/pages/*` → `webroot/dist/css/pages/`; se divergirem, alinha `vault-cofre.css`, `faturas-locacao-doc.css`, `orcamentos-premium.css` (pares `public/css` ↔ `webroot/css` e `public/dist/css` ↔ `webroot/dist/css`) pelo ficheiro **mais recente**. |
| §7 ponto 6 | Correr o script antes de PR/release que altere esses CSS. |

### 10.2 Novos CSS em `dist/css/pages` (mocks + UI)

| Ficheiro | Conteúdo |
|----------|----------|
| `portal-relatorios-estrutural.css` | Bloco completo `prel-*` (Portal Relatórios — mock e futura view). |
| `portal-client-barras-estrutural.css` | Barras KPI/franquia portal + SLA histórico: `*__pct--*`; dinâmico `*--dyn` + `style="--pgm-bar-pct: …"`. |
| `erp-relatorios-estrutural.css` | Barras mini / satisfação do mock ERP Relatórios. |
| `erp-clicontratos-estrutural.css` | Barras de franquia do mock Clicontratos. |

Todos com espelho em `webroot/dist/css/pages/`. Mocks HTML: `src/Template/**/index_estrutural.html` com `<link href="/dist/css/pages/…">`; em Cake: `$this->Html->css('/dist/css/pages/…')`.

### 10.3 Tokens e layout (globais)

| Alteração | Onde |
|-----------|------|
| `--pgm-erp-teal-active` | `public/dist/css/pages/pgm-theme-tokens.css` (+ webroot); ver §1.1. |
| `--est-primary-active` → ERP | `public/css/pgm-estoque.css`, `webroot/css/pgm-estoque.css`. |
| `--pgm-layout-min-h` | Definido em JS (`Layout/default.ctp`); consumo em `layout-sidebar-shell.css` (`.pgm-shell-main`). |

### 10.4 Views, elementos e JS (Fase 5 — resumo por módulo)

| Área | Ficheiros / notas |
|------|-------------------|
| Ordens de serviço — relatórios | `Ordensservico/relatorio_ver.ctp`, `relatorios.ctp`; classes em `ordensservico-index-shell.css`. |
| Ordens de serviço — add/edit/view | `Ordensservico/edit.ctp` — `small.qtdEstoque` com classe Bootstrap `d-block`; célula “Referenciar” só com `os-edit-ref-link` + `cursor` em `ordensservico-edit-shell.css` (sem `.css()` no itemTemplate). Campo **Status (`idarea`)** sem `selectpicker`: `os-add-native-select` em `add.ctp` (`ordensservico-add-shell.css`), `os-edit-native-select` em `edit.ctp`, `form-control` nativo em `view.ctp` e `ticketordem.ctp`. |
| Config admin | `Config/index.ctp`; `config-admin-shell.css` (`.admin-text-bright`, `.admin-section-card-link--stack`). |
| Tickets | `Tickets/add.ctp` (`<style>` + `sd-add-native-select` em assunto/severidade; `.ticket-file-name` / JS sem `.css()`); `indexcliente` — filtros nativos `tkcli-filter-native-select` + `pgm-client-premium.css`; `edit.ctp`, `edit_panel_left.ctp`, `view_modal.ctp`, `email.ctp`, `view.ctp` (anexo); `pgm-components-base.css` (mail, timer, quill, `pgm-th-w-200`, técnico responsável). |
| Elementos | `ticket_tecnico_responsavel.ctp`; `header.ctp`, `headercli.ctp` (topbar empresa, sem `style` no Form). |
| Utilizadores / portal | `loginempresa.ctp` + `pgm-login-theme.css` (`--login-empresa-bg`); removidos handlers jQuery órfãos de `.comeceausar` (sem elemento na view). `desativaverificacaosemlogin.ctp`; `tarefas.ctp` + `.users-tarefa-line-done` em `pgm-components-base.css`. |
| Service Desk | `Servicedesk/login.ctp` — `cursor: pointer` para `.comeceausar` / `.recuperasenha` / `.desativarautenticacao` no `<style>` da view; removido `.hover` + jQuery `.css('cursor', …)`. |
| Portal notificações | `PortalNotifications/preferences.ctp` — `.pgm-checkbox-table-cell`. |
| Contratos (lista) | `ContractManagement/index.ctp` — `.pgm-adv-contract-filter-*` em `pgm-advanced-module.css`. |
| Clientes | `Clientes/edit.ctp` (ficha `d-none`); `cliente-edit-ficha.js` (toggle classe). |
| Cofre | `Bancosenhas` add/edit/change_password (sem `style` redundante no botão). |
| Admin / listas | `Empresasusers`, `Problemas`, `Areas` — `m-b-20`. |
| Faturas | `Faturas/index.ctp` — label cliente; filtro sem chave PHP duplicada em `data-live-search`. |
| PDF estoque | `Produtos/estoque_pdf.ctp` — classes no `<style>` do PDF. |
| Opções PHP | Removidos `'style' =>` nos `.ctp` identificados; permanecem só exceções em §10.6. |

### 10.5 `pgm-components-base.css` — utilitários acrescentados neste ciclo

`pgm-gap-10`, `pgm-checkbox-table-cell`, `users-tarefa-line-done`, bloco ticket (`.mail-contnet--full`, `.sd-timer-display`, `.ticket-reply-plain`, `.ticket-quill-editor-mount`, `.pgm-th-w-200`, `.ticket-resp-tecnicos--soft`, …). Espelho: `webroot/dist/css/pages/pgm-components-base.css`.

### 10.6 Exceções — `style=` em atributos HTML (aceite)

| Caso | Motivo |
|------|--------|
| `Faturamento/view.ctp` — `fatview-root` | `style="--fat-st: …"` cor dinâmica do status. |
| `Users/loginempresa.ctp` | `style="--login-empresa-bg: url(…)"` — URL dinâmica; regra em `pgm-login-theme.css`. |
| Mocks / futuras views com barras | `style="--pgm-bar-pct: …"` no fill com classe `*--dyn` (ver `portal-client-barras-estrutural.css` e afins). |

### 10.7 Legado residual (melhorias futuras)

| Item | Nota |
|------|------|
| `Layout/default.ctp` | ~~`.page-wrapper` com `.css('min-height')`~~ — **feito:** classe `pgm-layout-min-h-legacy-wrapper` + `min-height: var(--pgm-layout-min-h)` em `layout-sidebar-shell.css`. |
| ~~Outros `.css()` em `.ctp` (lote cursor/ellipsis/OS)~~ | **Feito:** `Tickets/add` (`.ticket-file-name`); `Ordensservico/edit` (`d-block`, `os-edit-ref-link`); `loginempresa` (remoção de script morto); `Servicedesk/login` (regra CSS no `<style>`). Vigiar novos `.css()` em views; limite JS vendor em **§10.9**. |
| `Orcamentos/imprimir.ctp` | `document.createElement('style')` em JS (não é atributo `style=` no HTML inicial). |

### 10.8 Estado dos mocks `index_estrutural.html`

Sem `<style>` embutido; dependem dos CSS em §10.2. Ficheiros: `Portal/Relatorios`, `Portal/Contratos`, `Portal/Historico`, `Relatorios`, `Clicontratos`.

### 10.9 jQuery `.css()` — migrações feitas e limite (vendors)

**Já tratados no código próprio ou em forks locais pequenos:** remoção de `.css()` nas views Cake relevantes; `public/js` + `webroot/js` `orcamentos_novo_edit_shared.js` (`--orc-margin-pct`, classes de margem); `public/dist/js/custom.js` e `custom.min.js` (`.page-wrapper`); `public/dist/js/pages/chat.js`; `public/js/material-kit.js` e `material-dashboard.js`; `public/dist/js/pages/jasny-bootstrap.js` (preview fileinput).

**Explicitamente fora de escopo** (bibliotecas de terceiros — não reescrever à mão; alinhar só com upgrade/replace upstream): `public/dist/js/pages/bootstrap-select.js`, `public/dist/js/perfect-scrollbar.jquery.min.js`, `public/js/jquery*.js`, `typeahead.bundle.js`, `nouislider.min.js`, `material.min.js`, `public/js/simditor/**`, `public/plugins/**` (ex.: TinyMCE, Simditor empacotado).

**Regra para código novo:** evitar `$(…).css(…)`; preferir classes, variáveis CSS (`setProperty` em `:root` ou no elemento) ou `element.style.*`. Só editar vendor JS com fork documentado, testes e diff controlado.

**Piloto bootstrap-select (redução):** em `Ordensservico/edit.ctp`, campos `pagamento` e `nmrparcelas` (listas curtas, sem `data-live-search`) usam `<select>` nativo com classe `os-edit-native-select` — estilo alinhado ao botão do bootstrap-select em `ordensservico-edit-shell.css` (`public` + `webroot`). O modal de OS em `Ordensservico/index.ctp` já usava `form-control` nativo nestes campos.

**Extensão:** filtro **Tipo / locação** (`#locacao`, `#rel-locacao`) em `Ordensservico/index.ctp` e `relatorios.ctp` — `form-control os-filter-native-select` (sem `selectpicker`); estilos em `ordensservico-index-shell.css`. Em `Clientes/edit.ctp`, campo **Tipo** (`C_ClientesTipo`) deixa de usar `selectpicker` (lista curta); `cliente-edit-ficha.js` continua a fazer `refresh` só nos `.selectpicker` restantes (cidade, utilizadores).

**Situação OS:** filtros `situacao` / `rel-situacao` (`C_OrdensSituacao`) também nativos com `os-filter-native-select`; em `index.ctp`, `selectpicker('refresh')` nos filtros iniciais e nos KPIs limita-se a `#cliente` e `#problema`.

**Status (área) na OS:** `idarea` (lista de áreas/status) tratado como lista curta: sem `data-live-search` nem `selectpicker` nos ecrãs acima; `Orcamentos/novaordem.ctp` — mesmo padrão (`form-control` nativo). `idproblema` mantém `selectpicker` onde a lista de tipos de OS é grande.

**Lote portal / tickets / orçamentos / faturas:** `Tickets/indexcliente` — filtros `assunto` e `situacao` com `tkcli-filter-native-select` (`pgm-client-premium.css`, `public` + `webroot`). `Tickets/add` — `assunto` e `severidade` com `sd-add-native-select` (regras no `<style>` da view); sincronização com o resumo lateral só com `.val()`; `refresh` de solicitantes condicionado a `selectpicker` no DOM. `Orcamentos/add` e `Orcamentos/edit` — `formapagamento` com `orc-native-select` em `orcamentos-premium.css` (quatro cópias `public`/`webroot` × `css`/`dist/css`). `Faturas/index` — removida duplicata da opção `data-live-search` no filtro cliente.
