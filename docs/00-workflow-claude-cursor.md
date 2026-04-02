# Workflow Claude + Cursor (este repositório)

**Regra de ouro:** Claude desenha e gera rascunhos **fora** do código de produção; Cursor integra **incrementalmente** no projeto real.

Consulte também `docs/agent-rules.md` (execução em sequência, sem pedir confirmação desnecessária, padrão do projeto).

---

## O que já existe no repo (não reinventar)

| Tema | Onde está |
|------|-----------|
| Visão geral, menus, RBAC alvo | `docs/DOC1_VISAO_GERAL.md`, `DOC2_MAPA_MENUS.md` (§6 = sidebar real), `DOC3_RBAC_ABAC.md` |
| Especificação por módulo | `DOC4_HISTORICO_ATENDIMENTOS.md`, `DOC5_CONTRATOS_FATURAS.md`, `DOC6_RELATORIOS.md` |
| Documento mestre | `docs/DOCUMENTO_MESTRE_MODULOS.md` |
| Módulo avançado (PG): contracts, invoices, attendance, audit | `docs/portal-modulo-avancado-fase1.md`, migrations, `Advanced*` / `PortalAdvanced*` |

Backend “nível avançado” em código: `src/Service/PortalAdvanced/`, `PortalAdvancedShell`, tabelas PostgreSQL da Fase 1.

---

## Divisão de ferramentas

| Ferramenta | Uso |
|------------|-----|
| **Claude** | Especificação, wireframe textual, HTML estrutural de referência, revisão de UX, prompts. Saída preferencial em `docs/ui-drafts/*.html` ou `.md`. |
| **Cursor** | Alterar `src/`, `config/`, `webroot/`/`public/`; diffs pequenos; alinhar a controllers/templates existentes; migrations só no padrão PG do projeto. |

**Evitar:** Claude (ou qualquer IA) reescrever ficheiros grandes de produção de uma vez. **Evitar:** pedir ao Cursor “fazer o módulo inteiro” num único prompt.

---

## Fluxo recomendado

1. **Rascunho:** Claude gera HTML/estrutura → guardar em `docs/ui-drafts/` (ver `README` nessa pasta).
2. **Integração:** No Cursor, uma etapa de cada vez: menu + rota → listagem → detalhe → export → permissões.
3. **Validação:** `docs/09-checklist-testes-modulos.md`.
4. **Git:** branch `feature/...`, commits pequenos; merge na `main` só após testes.

---

## Stack real (não pedir React para telas ERP clássicas)

- CakePHP 3, templates `.ctp`, layout `default.ctp` / `client.ctp`.
- PostgreSQL para extensões do módulo avançado; legado pode ter outras convenções (`idcliente`, `idempresa`).
- Service Desk: parte em React; módulos descritos nos DOC* podem ser server-rendered.

---

## Prompts prontos

- Claude: `docs/07-prompts-claude.md`
- Cursor: `docs/08-prompts-cursor.md`
