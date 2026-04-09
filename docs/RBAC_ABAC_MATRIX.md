# Matriz papéis × permissões (RBAC) e ABAC

Documento de **manutenção**: onde está a matriz, como a interpretar e como extrair dados para auditoria. O catálogo declarativo continua a ser `config/permissions_registry.php`; a matriz em base reflete o que foi configurado no painel após **Sincronizar catálogo** e edição dos vínculos.

## Onde ver no produto

1. **Configurações** → **Permissões** (hub) → **Matriz papéis × permissões** (`Permissoes::adminMatrix`).
2. Tabela somente leitura: cada coluna é um **papel** (`rbac_roles`), cada linha uma **permissão** (`rbac_permissions`); célula marcada = existe linha em `rbac_roles_permissions`.
3. Ação **Associar tudo a Super administrador** repõe o vínculo do slug `super_admin` com **todas** as permissões atuais do catálogo (útil após sync).

## O que a matriz **não** mostra sozinha

- **Permissões efetivas por utilizador** — dependem de `rbac_users_roles` + grupos (`rbac_user_groups` / `rbac_group_roles`). Usar **Papéis por usuário → Efetivo** (`adminUserEffective`).
- **Macros legadas** — com `expand_legacy_aliases` ativo, um papel pode ter só códigos macro e mesmo assim cobrir atómicos via `rbac_permission_legacy_aliases`. Ver [`LEGACY_COMPATIBILITY.md`](LEGACY_COMPATIBILITY.md).
- **ABAC** — `abac_scope` na linha de `rbac_permissions` indica intenção de escopo (`empresa`, `cliente`, `own`); a filtragem de dados é feita pelo `AbacComponent` / queries, não pela matriz.

## Tabelas envolvidas

| Tabela | Papel |
|--------|--------|
| `rbac_roles` | Papéis (ex.: `super_admin`, `operacao`) |
| `rbac_permissions` | Códigos, `controller`/`action`, `abac_scope` |
| `rbac_roles_permissions` | N:N papel ↔ permissão (é a “matriz” persistida) |
| `rbac_users_roles` | Utilizador ↔ papel |
| `rbac_user_groups` / `rbac_group_roles` | Utilizador ↔ grupo ↔ papéis herdados |

## Consultas SQL (export / auditoria)

Ajuste nomes de schema se necessário. Exemplo **PostgreSQL** (listar vínculos com código e slug):

```sql
SELECT r.slug AS role_slug, r.name AS role_name, p.code AS permission_code, p.module
FROM rbac_roles_permissions rp
JOIN rbac_roles r ON r.id = rp.role_id
JOIN rbac_permissions p ON p.id = rp.permission_id
ORDER BY r.sort_order, p.module, p.code;
```

Contagem por papel:

```sql
SELECT r.slug, COUNT(*) AS n_permissions
FROM rbac_roles r
LEFT JOIN rbac_roles_permissions rp ON rp.role_id = r.id
GROUP BY r.id, r.slug
ORDER BY r.sort_order;
```

## ABAC e matriz

- Cada permissão pode ter `abac_scope` preenchido no catálogo; a matriz não altera esse campo.
- Regras de atributos (empresa, cliente, etc.) vivem em `config/abac.php` e no uso nos controllers/models.

## Documentação relacionada

- [`AUTH_MODEL.md`](AUTH_MODEL.md) — stack de autorização  
- [`LEGACY_COMPATIBILITY.md`](LEGACY_COMPATIBILITY.md) — híbrido e aliases  
- [`DOC3_RBAC_ABAC.md`](DOC3_RBAC_ABAC.md) — visão conceitual  
- [`TEST_CHECKLIST_RBAC.md`](TEST_CHECKLIST_RBAC.md) — regressão  
- [`IMPLEMENTATION_LOG.md`](../IMPLEMENTATION_LOG.md) — roadmap  
