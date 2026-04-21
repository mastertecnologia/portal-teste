# Plano de migração: Sidebar CakePHP → React (sem quebrar o portal)

Este documento alinha-se ao backup local **`BACKUP_PRE_MIGRACAO_SIDEBAR_REACT_2026-04-20`** (cópias de `default.ctp`, `sidebar.ctp`, `sidebarcli.ctp`, `client.ctp`). Essa pasta está no `.gitignore`; mantém-se na tua máquina para restauro rápido.

---

## Princípios de segurança

1. **Predefinição:** `PGM_SIDEBAR_REACT` ausente ou `0` → o portal usa o menu **atual** (`element('sidebar')` / `sidebarcli`) sem alteração de comportamento.
2. **Ativação explícita:** com `PGM_SIDEBAR_REACT=1` no `.env` e sessão autenticada (`Auth->user('id')`), o layout `default.ctp` renderiza `<div id="sidebar-app">` e o bundle Vite: **staff** (`role == 0`) recebe props `variant: 'staff'`; **portal cliente** (`role != 0`) recebe `variant: 'client'` alinhado a `sidebarcli.ctp`.
3. **Layout cliente:** com a mesma flag, `client.ctp` usa `#sidebar-app` + bundle; sem flag mantém `element('sidebarcli')` (corrigido em relação ao `element('sidebar')` legado, que era o menu staff).
4. **Conflito jQuery:** o botão de colapsar no componente React **não** usa a classe `sidebartoggler`, para evitar duplo toggle com `public/dist/js/custom.js` (que também manipula `mini-sidebar` no body).

---

## Onde está o quê

| Peça | Caminho |
|------|--------|
| Flag e config | `config/pgm_sidebar.php`, carregado em `config/bootstrap.php` |
| Layout staff | `src/Template/Layout/default.ctp` |
| Menu atual staff/cliente | `src/Template/Element/sidebar.ctp`, `sidebarcli.ctp` |
| Contexto PHP partilhado | `src/View/Sidebar/PgmSidebarStaffContext.php` |
| Payload JSON staff | `src/View/Sidebar/PgmSidebarStaffPayloadBuilder.php` |
| Payload JSON cliente | `src/View/Sidebar/PgmSidebarClientPayloadBuilder.php` |
| URLs API sino (staff) | `src/View/Sidebar/PgmPortalSidebarNotifUrls.php` |
| Atribuição à vista | `AppController::beforeRender()` → `pgmSidebarReactProps` |
| Bundle React (build) | `dashboard-react/` → `npm run build:sidebar` → `public/js/pgm-sidebar-react/sidebar-app.js` (DocumentRoot = `public/`) |
| Código fonte React | `dashboard-react/src/sidebar/Sidebar.jsx`, `sidebarMount.jsx` |
| Build Vite isolado | `dashboard-react/vite.sidebar.config.js` |

### Forma de `pgmSidebarReactProps` (Etapa B)

Objeto JSON para `window.__PGM_SIDEBAR_PROPS__`:

**Staff (`variant: "staff"`)**

- `activePath`, `dashboardItem`, `sections`, `workspace`, `user`, `footerLinks`, `notificationsBell`
- `notificationBellApi` (opcional): `{ urlCount, urlList, urlMarkAll, urlMarkReadBase, urlPrefs }` — consumido pelo React com `fetch` (paridade com `portal_notification_bell.ctp`)

**Cliente (`variant: "client"`)**

- `navBlocks`: lista de `{ type: 'link' | 'group', ... }` (grupos com `items[]`, ícones Font Awesome)
- `workspace`, `user`, `footerLinks`, `activePath`; `notificationsBell` tipicamente `false`

---

## Etapas (ordem recomendada)

### Etapa A — Concluída (baseline + infra)

- [x] Backup com nome fixo: `BACKUP_PRE_MIGRACAO_SIDEBAR_REACT_2026-04-20` + `LEIA-ME_RESTAURO.txt`
- [x] `config/pgm_sidebar.php` + carga no `bootstrap.php`
- [x] Documentação em `.env.example` (`#PGM_SIDEBAR_REACT=0`)
- [x] `default.ctp`: ramo condicional + CSS FA (só se React) + scripts Lucide + `__PGM_SIDEBAR_PROPS__` + módulo `sidebar-app.js`
- [x] Ajuste do botão colapsar no React (sem classe `sidebartoggler`)

### Etapa B — Modelo de dados (servidor)

- [x] Contexto partilhado: `App\View\Sidebar\PgmSidebarStaffContext::computeFromArray()` (usado por `sidebar.ctp` via `get_defined_vars()` + `extract`).
- [x] Payload JSON: `App\View\Sidebar\PgmSidebarStaffPayloadBuilder::build()` — menu staff, workspace, utilizador, `footerLinks`, `activePath`.
- [x] `AppController::beforeRender()` define `pgmSidebarReactProps` quando `PGM_SIDEBAR_REACT=1` e há sessão (staff ou cliente).
- [x] `dashboard-react/src/sidebar/Sidebar.jsx` consome as props do servidor (com fallback demo se vazio no staff).
- [x] Sino de notificações no React (`PortalNotificationsBell.jsx` + `notificationBellApi`) alinhado ao elemento Cake.

### Etapa C — Paridade visual e Turbo

- [x] `default.ctp`: `window.__PGM_REACT_SIDEBAR__` com menu React; `pgmTurboSyncSidebarActive` ignora a árvore do `<aside>` quando a flag está ativa (evita conflito com classes geridas pelo React) e mantém `pgmTurboRebindDynamicUi()`.
- [x] `pgmTurboMarkNavLinks`: seletor alargado ao footer (`preview-dd-item`, `dropdown-item`); `window.pgmTurboShellMarkNavLinks` exposto para reexecutar após o mount do React.
- [x] `Sidebar.jsx`: estado `livePath` com `turbo:frame-load` e `popstate`; `data-turbo-frame` / `data-turbo-action` nos links (regras alinhadas a `pgmTurboSameOriginHref`); Lucide após carga do frame.
- [x] `sidebarMount.jsx`: `queueMicrotask` chama `pgmTurboShellMarkNavLinks` após o primeiro render.
- [x] Sino / `portal_notification_bell` com paridade funcional no React (`fetch`, CSRF, painel, marcar lidas).

### Etapa D — `sidebarcli` e layout `client.ctp`

- [x] Props `variant: 'client'` e `PgmSidebarClientPayloadBuilder` espelhando `sidebarcli.ctp`.
- [x] Condicional em `client.ctp` + CSS shell/premium; PHP continua com `sidebarcli` quando a flag está desligada.

### Etapa E — Remoção do legado (opcional, no fim)

- [ ] Com flag sempre 1 em produção e período de observação: remover HTML duplicado dos `.ctp` ou reduzir a elementos mínimos + JSON.

---

## Como testar a React sidebar (ambiente local)

1. `cd dashboard-react && npm run build:sidebar`
2. No `.env` da raiz do portal: `PGM_SIDEBAR_REACT=1`
3. Limpar cache de config do Cake se aplicável (`tmp/cache`…)
4. Abrir o portal com utilizador staff (`role == 0`): deve aparecer o menu React de demonstração até existir `pgmSidebarReactProps` real.

**Atenção:** com a flag a `1`, o menu **não** é o PHP completo até a Etapa B; use só para desenvolvimento.

---

## Restauro em caso de erro grave

1. Copiar os ficheiros `.bak` da pasta de backup para os caminhos originais (ver `BACKUP_PRE_MIGRACAO_SIDEBAR_REACT_2026-04-20/LEIA-ME_RESTAURO.txt`).
2. Reverter manualmente: remover `config/pgm_sidebar.php`, o bloco `pgm_sidebar` em `bootstrap.php`, e as alterações ao `default.ctp` se não usares o backup completo.
3. Garantir `PGM_SIDEBAR_REACT=0` ou remover a variável do `.env`.

Opcional em Git, após commit estável: `git tag backup/pre-migracao-sidebar-react-2026-04-20`.

---

## Sidebar vazia com a flag ativa (produção)

1. **URL do bundle (404):** com rewrite, `SCRIPT_NAME` pode ser `/index.php` (sem `/portal`). Os layouts usam `PgmAppUrlBase::path($this->request)` para alinhar ao `base` do Cake. No DevTools → Rede, confirme **200** em `…/portal/js/pgm-sidebar-react/sidebar-app.js`.
2. **`Error: Controller class Js could not be found`:** o pedido ao `.js` está a ir ao `index.php` em vez de ficheiro estático — o bundle tem de existir sob **`public/js/pgm-sidebar-react/`** (DocumentRoot = `public/`), não só em `webroot/`. Volte a correr `npm run build:sidebar` após o alinhamento do Vite.
3. **`APP_BASE`:** se o passo 1 ainda falhar, defina `APP_BASE=/portal` (ou o prefixo real) no `.env` e limpe o cache de config.
4. **JSON das props:** UTF-8 inválido no nome do utilizador podia partir o `<script>` das props; usa-se `JSON_INVALID_UTF8_SUBSTITUTE` quando disponível.
5. **Erro no React:** se o mount falhar, aparece mensagem vermelha dentro da coluna e o detalhe na consola (F12).

---

## Checklist rápido antes de ativar em produção

- [ ] `npm run build:sidebar` integrado no pipeline de deploy
- [ ] `public/js/pgm-sidebar-react/sidebar-app.js` presente no servidor (alinhado a `WEBROOT_DIR=public` / Apache em `public/`)
- [ ] Testes manuais nas rotas críticas (OS, clientes, financeiro, logout)
- [ ] Plano de rollback: `PGM_SIDEBAR_REACT=0` + deploy do `default.ctp` Cake
