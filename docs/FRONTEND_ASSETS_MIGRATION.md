# Plano de migracao — assets front-end (`public/` e `webroot/`)

## Contexto

O repositorio inclui bibliotecas de terceiros copiadas para `public/assets/node_modules`, `public/plugins/` e ficheiros semelhantes. Isto aumenta o tamanho do clone, dificulta atualizacoes de seguranca (CVE) e duplica o que npm/yarn ja resolve.

## Objetivo

Passar a dependencias declaradas em `package.json`, build reproducivel e artefactos gerados **fora do Git** (ou apenas um pacote publicado em release).

## Fases sugeridas

### Fase 1 — Inventario (1–2 dias)

1. Listar entradas em layouts (`src/Template/Layout/`, `*.ctp`) que referenciam `js/`/`css/` sob `public/` e `webroot/`.
2. Identificar scripts realmente usados em producao versus copias de demos (`node_modules/*/examples`).

### Fase 2 — Pacote npm na raiz ou `assets/`

1. Criar `package.json` dedicado (ex.: na raiz `portal-assets/` ou `assets/`) com dependencias de producao (Bootstrap, jQuery, DataTables, Select2, etc.) na versao minima necessaria.
2. Adicionar script de build (esbuild, webpack ou vite) que copia/minifica para `webroot/build/` ou `public/build/`.
3. Atualizar templates para apontar para os ficheiros gerados (com cache-bust por hash no nome do ficheiro).

### Fase 3 — Git e deploy

1. Adicionar pastas geradas ao `.gitignore` (ex.: `public/build/`, `webroot/build/`).
2. No servidor Linux (`DEPLOY_LINUX.md`), passo de deploy: `npm ci && npm run build` antes ou apos `composer install`, conforme pipeline.
3. Remover gradualmente `public/assets/node_modules` do historico (opcional: `git filter-repo` em janela de manutencao; coordenar com a equipa).

### Fase 4 — Excecoes

- **TinyMCE / editores** com muitos plugins: avaliar pacote npm oficial ou ficheiros servidos por versao fixa com checksum.
- **dashboard-react**: ja usa Vite; manter como referencia (`dashboard-react/vite.config.js` → `public/tickets-app/`).

## Riscos

- URLs antigas em caches de browser e integracoes que apontam para caminhos literais em `public/`.
- Regressoes visuais: mitigar com checklist manual nas telas criticas (login, clientes, ordens, fiscal).

## Criterio de conclusao

- Nenhum `node_modules` de terceiros versionado em `public/`.
- Uma unica fonte de verdade (`package-lock.json`) para versoes de JS/CSS de front legado.
