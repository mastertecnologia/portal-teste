# Checklist de testes — RBAC / ABAC (manual + automático)

Referência rápida para regressão após alterações em autorização. Detalhe do plano: `IMPLEMENTATION_LOG.md` (Roadmap).

## Automático (PHPUnit)

Na raiz do projeto, com `vendor` instalado:

```bash
vendor/bin/phpunit tests/TestCase/Utility/RbacCheckerTest.php
vendor/bin/phpunit tests/TestCase/Utility/RbacPolicyConditionsTest.php
```

## Migrations e catálogo

- [ ] `bin/cake migrations migrate` — inclui `rbac_*`, `rbac_permission_legacy_aliases`, Fase 3 (grupos, auditoria).
- [ ] **Permissões → Sincronizar catálogo** após alterar `config/permissions_registry.php`.
- [ ] Papéis padrão existem (entrada em **Permissões** ou `_ensureDefaultRoles`).

## Modos de runtime (`config/rbac.php` / `RBAC_MODE`)

- [ ] `off` — comportamento legado; utilizadores com papéis RBAC não são bloqueados pelo componente.
- [ ] `warn` — negações registadas em log; opcional `audit_decisions_db` grava em `rbac_audit_authorizations`.
- [ ] `enforce` — negação com redirect; mesma opção de auditoria em BD.

## Aliases legado → canónico (Fase 7)

- [ ] Papel só com macro legada (ex.: `clientes.manage`) permite rotas cobertas pelas atómicas após sync + seeds de aliases.
- [ ] `expand_legacy_aliases` desligado restaura só IDs explícitos na matriz.

## Grupos (Fase 3–4)

- [ ] Utilizador só em grupo com papéis recebe as mesmas permissões que vínculo direto (`expand_group_roles`).
- [ ] CRUD de grupos e membros em **Permissões → Grupos RBAC**.

## Relatório efetivo (Fase 4)

- [ ] **Papéis por usuário → Efetivo** mostra papéis diretos, grupos e lista de permissões pós-expansão de aliases.

## Auditoria (Fase 9)

- [ ] Com `audit_decisions_db` ativo, negações (e opcionalmente `all`) aparecem na tabela e em **Auditoria RBAC** / `bin/cake rbac_rollout audit_recent`.

## APIs e `api*`

- [ ] Actions com prefixo `api` continuam fora do `RbacComponent` (`skip_action_prefixes`); validar integrações e tickets React à parte.

## Legado

- [ ] Admin equipe (`users.admin`, `role === 0`) acede ao painel **Permissões** como antes.
- [ ] Portal cliente (`role === 1`) não deve ser bloqueado por `enforce_block_without_roles_equipe_only` (default).

---

*Última revisão alinhada ao roadmap em `IMPLEMENTATION_LOG.md`.*
