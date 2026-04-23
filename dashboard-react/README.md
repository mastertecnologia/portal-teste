# PGM ERP — dashboard (React + Vite + Tailwind)

Protótipo com **dois painéis**:

| Rota | Perfil |
|------|--------|
| `/` | Escolha técnico ou cliente |
| `/tecnico` | Fila operacional, filtros, KPIs (mock) |
| `/cliente` | Lista dos chamados do cliente (mock `MOCK_SESSION_CLIENTE`) |
| `/cliente/ticket/:id` | Detalhe, histórico, **novo comentário** (mock) |

Independente do backend CakePHP até ligar `src/lib/api.js`.

## Requisitos

- Node.js 18+

## Uso

**No Windows:** dê duplo clique em `abrir-dashboard.cmd` (na pasta `dashboard-react`). O navegador deve abrir sozinho.

**Ou no terminal:**

```bash
cd dashboard-react
npm install
npm run dev
```

O Vite abre automaticamente `http://localhost:5173/`. Se não abrir, copie esse endereço e cole na barra do navegador (Chrome/Edge).

**Não funciona abrir o arquivo `index.html` direto do Explorer** — o React precisa do servidor (`npm run dev`).

**Se a porta 5173 estiver ocupada**, o Vite tenta a próxima (5174, …). Veja no terminal a URL correta.

**Acesso pelo celular na mesma rede:** no terminal aparece algo como `Network: http://192.168.x.x:5173/` — use esse endereço (com `host: true` já está ativo).

## Build estático (portal CakePHP)

```bash
cd dashboard-react
npm install
npm run build
# equivalente: npm run build:tickets
```

Na **raiz** do repositório também podes usar `npm run build:tickets` (delega para `dashboard-react`).

O build escreve em **`../webroot/tickets-app/`** (o que o CakePHP expõe em `/tickets-app/`) e copia o mesmo conteúdo para **`../public/tickets-app/`** (`npm run build` = `vite build` + `scripts/postbuild_tickets_app_sync.cjs`). O layout `Tickets/react_app.ctp` usa ficheiros em `webroot/`.

## Reverter no Git

O trabalho de integração costuma estar no branch **`feature/tickets-react-portal`**.

| Situação | Comando |
|----------|---------|
| Ainda **não** fez merge no `main` | `git checkout main` — o portal volta ao código anterior; o branch da feature continua no repositório até você apagar. |
| Já fez merge e quer desfazer **só esse commit** | `git log --oneline -10` → `git revert <hash>` |
| Merge com merge commit | `git revert -m 1 <hash_do_merge>` |
| Apagar branch local (opcional) | `git branch -D feature/tickets-react-portal` |

## Integração com o portal

Com `window.__TICKETS_BOOT__` injetado pelo CakePHP, `src/lib/api.js` chama as rotas JSON reais; sem boot, o Vite em `localhost` segue em modo mock.
