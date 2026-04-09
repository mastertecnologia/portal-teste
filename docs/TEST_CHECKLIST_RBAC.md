# Checklist de testes — RBAC / ABAC (manual + automático)

Referência rápida para regressão após alterações em autorização. Plano mestre: [`IMPLEMENTATION_LOG.md`](../IMPLEMENTATION_LOG.md) — **Índice P0–P3** no topo (âncoras `rbac-priority-p0` … `rbac-priority-p3`), **Roadmap** e **Próximas fases (executável)**.

## Automático (PHPUnit)

Modelo de `.env`: `.env.example` na raiz; variáveis só RBAC: `config/rbac.env.example`.

Na raiz do projeto, com `vendor` instalado:

```bash
composer test-rbac
# equivalente:
composer rbac-verify-noninteractive
```

O `composer test-rbac` (alias `rbac-verify-noninteractive`) corre em sequência **`rbac`** (sem BD), **`rbac-integration`** (SQLite `:memory:` + ORM, `RbacEffectivePermissionIdsSqliteTest.php`; exige `pdo_sqlite`) e **`rbac-http`** (stack HTTP + Auth + Rbac: `RbacPermissoesHttpTest`, `RbacAreasHttpTest`, `RbacEmpresasusersHttpTest`, `RbacProblemasHttpTest`, `RbacFeriadosHttpTest`, `RbacContratosHorasHttpTest`, `RbacNormasempresaHttpTest`, `RbacFinanceiroHttpTest`, `RbacFaturamentoHttpTest`, `RbacClientesHttpTest`, `RbacPrefaturamentoHttpTest`, `RbacBancosenhasHttpTest`, `RbacEmpresasHttpTest`, `RbacOrcamentosHttpTest`, `RbacProdutosHttpTest`, `RbacVisitasHttpTest`, `RbacOrdensservicoHttpTest`; bootstrap `tests/bootstrap_http.php`; URLs respeitam `App.base` / `APP_BASE`). Isolado: `vendor/bin/phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http`. Scripts sem prompts: `bin/rbac_verify_noninteractive.ps1` (Windows) ou `bin/rbac_verify_noninteractive.sh` (Unix); com `RBAC_RUN_PRE_DEPLOY=1` acrescentam `bin/cake rbac_rollout pre_deploy` (exige PostgreSQL + catálogo). No GitHub: workflow **RBAC PHPUnit** (`.github/workflows/rbac-phpunit.yml`; *workflow_dispatch*; paths em push/PR no YAML).

**Nota:** concluir P0–P3 na íntegra (backfill real, observação, políticas por módulo, auditoria sensível) não é substituível por estes comandos — ver `IMPLEMENTATION_LOG.md` § [Limites: nenhuma execução “única”…](#rbac-automation-limits).

Ou ficheiros isolados:

```bash
vendor/bin/phpunit tests/TestCase/Utility/RbacCheckerTest.php
vendor/bin/phpunit tests/TestCase/Utility/RbacPolicyConditionsTest.php
vendor/bin/phpunit tests/TestCase/Config/RbacPhpConfigTest.php
vendor/bin/phpunit tests/TestCase/Config/AbacPhpConfigTest.php
vendor/bin/phpunit --testsuite rbac-integration
vendor/bin/phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http
```

`RbacPhpConfigTest`: confirma literais em `config/rbac.php` (modo/menu/auditoria/políticas, `expand_legacy_aliases`, `expand_group_roles`, `enforce_block_without_roles_equipe_only`, `legacy_permission_log`; arrays `skip_action_prefixes`, `whitelist`, `rbac_api_enforced_actions`, `menu_sidebar_gates` com entradas mínimas esperadas) com variáveis `RBAC_*` temporariamente limpas (evita falsos negativos com `.env` / CI).

`RbacRolloutShellTest`: `playbookChecklistLines()` não vazio e contém comandos-chave (`report`, `menu_gates_check`, `unassigned_*`, `pre_deploy`, `TEST_CHECKLIST_RBAC.md`, `menu_sidebar_gates`).

`RbacComponentWhitelistTest`: `_isWhitelisted` via reflexão (match exacto, `*`, só primeiro `#` separa controller/ação; entradas sem `#` ignoradas; continuação da lista após controller diferente; minúsculas na lista; lista ausente → false).

`RbacComponentApiEnforcedTest`: `_isRbacEnforcedApiAction` via reflexão (`rbac_api_enforced_actions` ausente ou não-array; match `controller#action`; `trim` + `strtolower` nas entradas; entradas só espaços não casam).

`AbacPhpConfigTest`: `config/abac.php` com `enabled`, colunas esperadas (ex.: `Clientes.cliente_row_id`, `Tickets.cliente_column`, `Users.user_id_column`) e presença de chaves adicionais no mapa (Queues, Visitas, Clicontratos, Orcamentos, ContratosHoras).

`AbacQueryTest`: `resolveScope` (`role` string `'1'` como portal; portal sem colunas → null; equipe sem `empresa_column` e sem `rbacAbacScope` válido → null; equipe com `rbacAbacScope` `own` sem coluna empresa; equipe com `rbacAbacScope` inválido ou só espaços → fallback empresa) e `apply` (`enabled` 0 como desligado; entrada em `tables` que não é array; alias = chave da tabela se omitido no mapa; parâmetro alias `''` → usa `alias` do mapa; alias explícito; empresa/cliente sem coluna no mapa → sem `where`; `idempresa`/`idcliente` vazio ou null → `1=0`; own; scope nulo) com `Configure` + mock de `Query` (sem BD).

`RbacCheckerTest`: `PERM_ORCAMENTOS_SOLICITAR`; `clientePodeSolicitarOrcamento` (sem `permissaoacesso` / `userId` ≤ 0 → `false`, antes de queries); `matchAction` (normalização case; wildcard; sem chave `controller`; sem chave `action` → qualquer ação; controller diferente; lista de ações só com vírgulas; PortalContratos; legado **Agenda**→**Visitas**), `shouldShowSidebarGate` (portal; equipe sem `admin` legado → bypass; lista de códigos vazia com menu estrito), `buildSidebarMenuGates` (chave em branco omitida; `menu_sidebar_gates` inválido ou `Rbac` ausente → `[]`), `userHasPermissionCode` (argumentos inválidos), `shouldShowConfigAdminHub` (via `Configure::write('Rbac', …)`).

`RbacPermissionResolverTest`: `expandPermissionIds` vazio / IDs não positivos; `isLegacyBundleCode` com código vazio, sem tabela de aliases no schema (sem `execute`), e falha de `execute` → false.

`RbacUserRolesResolverTest`: `effectiveRoleIds` com `userId` ≤ 0 (sem queries).

`RbacClientePortalTest`: constante `ROLE_SLUG`; `syncUserIfEligible` com ID inválido (retorno imediato).

`RbacPolicyConditionsTest`: JSON inválido, raiz escalar (ex. string JSON), `all` ausente ou vazio, regra que não é array, `path` vazio, `in` que não é array, regra sem `eq`/`in`, `eq` com chave ausente no contexto (null), `in` com lista vazia, `eq` numérico com int/string, `eq` booleano, `request.plugin`; `eq` prevalece sobre `in` na mesma regra; `matchesOrEmpty` com JSON quebrado, `eq`/`in`/`user.idempresa`.

`RbacHierarchyTest`: `finalizeRoleIdsForSave` (teto 0: pedidos acima do teto removidos; preservação de papéis já no alvo com nível > 0; pedido vazio com alto existente; IDs ≤ 0 ignorados; deduplicação); `rolesVisibleForAssign` (lista vazia; teto 0; teto alto esconde níveis altos salvo já atribuídos ao utilizador).

`RbacAreasHttpTest` (suite `rbac-http`): visitante em `/areas` → login; equipe com `areas.status.view` → 200 (lista “Status de OS”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacEmpresasusersHttpTest` (suite `rbac-http`): visitante em `/empresasusers` → login; equipe com `empresasusers.view` → 200 (“Lista de Relações” + dados de teste); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacProblemasHttpTest` (suite `rbac-http`): visitante em `/problemas` → login; equipe com `problemas.tipos.view` → 200 (“Tipo de OS” + linha de teste); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacFeriadosHttpTest` (suite `rbac-http`): visitante em `/feriados` → login; equipe com `feriados.view` → 200 (“Feriados (horário especial)” + linha de teste no ano corrente); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacContratosHorasHttpTest` (suite `rbac-http`): visitante em `/contratos-horas/index/{idcliente}` → login; equipe com `contratos.horas.view` → 200 (“Contratos de Horas Técnicas” + valores formatados); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacNormasempresaHttpTest` (suite `rbac-http`): visitante em `/normasempresa` ou `/normasempresa/acessoremoto` → login; equipe com `normasempresa.read` → 200 na página de normas (`index.ctp`); equipe com `normasempresa.acessoremoto` → 200 em acesso remoto; equipe só com `permissoes.matrix.view` → `access-denied` em ambos os caminhos.

`RbacFinanceiroHttpTest` (suite `rbac-http`): visitante em `/financeiro` → login; equipe com `financeiro.view` → 200 (dashboard com “Contas a Receber”, KPIs vazios, mensagem de vencimentos); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacFaturamentoHttpTest` (suite `rbac-http`): visitante em `/faturamento` → login; equipe com `faturamento.view` → 200 (cabeçalho “Faturamento”, “Novo Documento”, lista vazia “Nenhum documento encontrado.”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacClientesHttpTest` (suite `rbac-http`): visitante em `/clientes` → login; equipe com `clientes.view` → 200 (“Módulo comercial”, “Clientes”, “Novo cliente”, strip KPI “Ativos · PJ”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacPrefaturamentoHttpTest` (suite `rbac-http`): visitante em `/prefaturamento` → login; equipe com `prefaturamento.queue` → 200 (intro “Ordens de serviço em”, botão “Gerar Faturamento”, fila vazia “Nenhuma OS nesta fila.”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacBancosenhasHttpTest` (suite `rbac-http`): visitante em `/bancosenhas` → login; equipe com `bancosenhas.view` → 200 (“Cofre de senhas”, “Criptografado”, “Nova credencial”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacEmpresasHttpTest` (suite `rbac-http`): visitante em `/empresas` → login; equipe com `empresas.view` → 200 (abas “Ativas” / “Inativas”, colunas “Nome”, “E-mail”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacOrcamentosHttpTest` (suite `rbac-http`): visitante em `/orcamentos` → login; equipe com `orcamentos.view` → 200 (“Módulo comercial”, “Orçamentos”, “Gerar Orçamento”, “Pendentes”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacProdutosHttpTest` (suite `rbac-http`): visitante em `/produtos` → login; equipe com `produtos.view` → 200 (título “Produtos &amp; Serviços”, “Novo Cadastro”, “Precificação”; empresa id=1 em SQLite + WS ERP opcional); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacVisitasHttpTest` (suite `rbac-http`): visitante em `/visitas` → login; equipe com `visitas.view` → 200 (“Cadastrar nova visita”, “Calendário”, coluna “Situação”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacOrdensservicoHttpTest` (suite `rbac-http`): visitante em `/ordensservico` → login; equipe (`admin=0`) com `ordensservico.list` → 200 (“Ordens de Serviço”, “Total de OS”); equipe só com `permissoes.matrix.view` → `access-denied`.

`RbacPermissoesHttpTest` (suite `rbac-http`): anónimo → 302 com `users/login` (matriz e catálogo); prefixo `/portal` com `App.base`; utilizador portal (`role=1`) autenticado → 302 para `users/dashboard` (comportamento Auth `unauthorizedRedirect`); equipe **sem** linhas em `rbac_users_roles` → 302 para `users/dashboard` (híbrido: o teste fixa `enforce_block_without_roles=false` para não depender de `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES` no `.env`; só `isAuthorized`/códigos); admin legado → 200 e “Matriz papéis” e catálogo (`admin-index`, “Permissões do sistema”); equipe com papel RBAC sem permissão de rota → `access-denied`; equipe com `permissoes.matrix.view` → 200; equipe só com `permissoes.catalog.view` → catálogo 200 e matriz → `access-denied`; equipe só com `permissoes.matrix.view` → matriz 200 e catálogo → `access-denied`.

## Migrations e catálogo

- [ ] `bin/cake migrations migrate` — inclui `rbac_*`, `rbac_permission_legacy_aliases`, Fase 3 (grupos, auditoria), correção **agenda.alias** (`20260418120000`).
- [ ] **Permissões → Sincronizar catálogo** após alterar `config/permissions_registry.php`.
- [ ] Papéis padrão existem (entrada em **Permissões** ou `_ensureDefaultRoles`).

## Políticas por permissão (Fase 5 runtime + UI)

- [ ] Com `evaluate_permission_policies` **false** (ex.: `RBAC_EVALUATE_POLICIES=0`), linhas em `rbac_permission_policies` são ignoradas.
- [ ] Com **true** (padrão neste repositório em `config/rbac.php`): para cada permissão casada na rota, se existirem linhas `active` para esse `rbac_permission_id`, **pelo menos uma** deve satisfazer `conditions_json` (vazio = sem restrição extra). Ver `RbacPolicyConditions` e comentários em `config/rbac.php`.
- [ ] **Permissões → Políticas por permissão**: listar, nova, editar, excluir; `conditions_json` JSON válido; papel com `permissoes.policies.manage` ou `permissoes.admin`.

## Hub Config e menu (Fase 6)

- [ ] Com `menu_filter_config` **false** (ex.: `RBAC_MENU_FILTER_CONFIG=0`), equipe `admin` vê o atalho Config na sidebar e acede a `Config/*`.
- [ ] Com **true** (padrão neste repositório): quem tem papéis RBAC e **não** tem `config.manage` deixa de ver o atalho e é bloqueado no `ConfigController` (exceção híbrida: sem linhas em `rbac_users_roles` / grupos ainda sem papéis efetivos — ver `RbacChecker::shouldShowConfigAdminHub`).

## Fase 6b–6c — Gates da sidebar equipe

- [ ] Com `menu_filter_sidebar` **true** (padrão neste repositório; desligar com `RBAC_MENU_FILTER_SIDEBAR=0`), verificar blocos principais (dashboard, clientes, …) com `bin/cake rbac_rollout report` (lista `menu_sidebar_gates`).
- [ ] **Busca de funções:** com filtro lateral, equipe sem `pesquisa.sidebar_search` não vê o bloco «Buscar funções»; após sync + migration `20260423143000_RbacEquipeRolesPesquisaSidebarSearch`, papéis padrão e `cliente_portal` têm a permissão (enforce em `Pesquisa/pesquisa` e `Pesquisa/link`).
- [ ] `bin/cake rbac_rollout menu_gates_check --strict` passa após **Sincronizar catálogo** (códigos do menu existem em `rbac_permissions`).
- [ ] `role_stats` reflete contagens esperadas por papel após ajustes na matriz (papéis vazios = `n_permissions=0`).
- [ ] **6c:** papel só com `ordensservico.list` vê “Listar Ordens” mas não “Nova ordem”; só `servicedesk.view` vê Service Desk mas não “Histórico”; só `relatorios.painel.view` vê painel clássico, não o link de indicadores avançados.
- [ ] **6d:** papel só com `erp.contracts.templates` vê “Modelos de contrato” mas não “Gestão” nem “Faturas” (com `RBAC_MENU_FILTER_SIDEBAR=1`); grupo some se os três sub-gates forem falsos.
- [ ] **6e:** com filtro lateral, papel só com `prefaturamento.queue` vê menu e fila mas não coluna nem POST de conferências; `prefaturamento.manage` mantém acesso total.
- [ ] **6f:** equipa sem `portal.notifications.read` (e sem macro `portal.notifications`) não vê o sino; sem `normasempresa.acessoremoto` não vê «Acesso remoto» no dropdown; sem `users.profile` **e** sem `users.password` não vê os atalhos de perfil/senha (barra rápida e entradas no dropdown); sem `users.twofactor` não vê «Verificação login» (2FA). Com `RBAC_MENU_FILTER_SIDEBAR=1`.

## Rota `/agenda` e `agenda.alias`

- [ ] Migration `20260418120000_RbacAgendaAliasVisitasController` aplicada (corrige `rbac_permissions` e aliases `agenda.alias` → atómicos `visitas.*`).
- [ ] Acesso a URLs `/agenda/*` (controller **Visitas**) autoriza com permissões `visitas.*`, `agenda.visitas` ou `agenda.alias` (linhas antigas com controller `Agenda` ainda casam via `RbacChecker::matchAction`).

## Modos de runtime (`config/rbac.php` / `RBAC_MODE`)

- [ ] Sem `RBAC_MODE` no ambiente, este repositório usa **`enforce`** (ver `RbacPhpConfigTest`). Para desenvolvimento local: `RBAC_MODE=warn` ou `off`.
- [ ] `off` — comportamento legado; utilizadores com papéis RBAC não são bloqueados pelo componente.
- [ ] `warn` — negações registadas em log; com `audit_decisions_db` ativo grava negações em `rbac_audit_authorizations`.
- [ ] `enforce` — negação com redirect; com `audit_decisions_db` ativo (padrão no ficheiro) grava negações em BD.

## Aliases legado → canónico (Fase 7)

- [ ] Papel só com macro legada (ex.: `clientes.manage`) permite rotas cobertas pelas atómicas após sync + seeds de aliases.
- [ ] `expand_legacy_aliases` desligado restaura só IDs explícitos na matriz.

## Grupos (Fase 3–4)

- [ ] Utilizador só em grupo com papéis recebe as mesmas permissões que vínculo direto (`expand_group_roles`).
- [ ] CRUD de grupos e membros em **Permissões → Grupos RBAC**.

## Matriz papel × permissão

- [ ] **Permissões → Matriz** reflete `rbac_roles_permissions` após alterações.
- [ ] Para export/auditoria SQL e limites (efetivo vs. matriz, ABAC), ver [`RBAC_ABAC_MATRIX.md`](RBAC_ABAC_MATRIX.md).

## Relatório efetivo (Fase 4)

- [ ] **Papéis por usuário → Efetivo** mostra papéis diretos, grupos e lista de permissões pós-expansão de aliases.

## P0 — Fase 8 (checklist numerado)

Ordem e critérios de aceitação global: **`IMPLEMENTATION_LOG.md`** → **Próximas fases** → **P0 — Fase 8** (passos 1–8 + issues A–E). Definition of done do rollout **antes** de `enforce_block_without_roles`.

## P1 — Endurecimento + matriz efetiva

Detalhe: **`IMPLEMENTATION_LOG.md`** → **P1** (passos 1–5 + issues F–H). Inclui `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES` e trabalho **Fase 4** (efetivo na UI/relatório).

## P2 — Políticas, campos, cobertura catálogo

Detalhe: **`IMPLEMENTATION_LOG.md`** → **P2** (pacotes 1–3 + issues I–K). Fases **5** e **6** em entregas incrementais.

## P3 — Auditoria extra, integração, documentação

Detalhe: **`IMPLEMENTATION_LOG.md`** → **P3** (trilhos A–C: Fases **9**, **10**, **11**; issues L–N). Pode avançar em paralelo a P2; critério incremental por release.

## Rollout CLI (Fase 8)

- [ ] `bin/cake rbac_rollout unassigned_equipe --csv` e `unassigned_portal --csv` escrevem só linhas CSV (cabeçalho + dados ou só cabeçalho se vazio); redirecionar para ficheiro se necessário.
- [ ] `bin/cake rbac_rollout stats --csv` devolve cabeçalho + uma linha numérica (métricas equipe/portal).
- [ ] `assign_portal --role_slug=cliente_portal --dry-run` lista apenas `users.role=1` sem papéis efetivos; sem dry-run grava `rbac_users_roles`.
- [ ] `list_roles` mostra slugs ativos; `list_roles --all` e `list_roles --csv` conforme necessidade operacional.
- [ ] `user_effective --user_id=N` coincide com **Permissões → Efetivo** para o mesmo utilizador (papéis + contagem de permissões).
- [ ] `who_has --code=…` só considera utilizadores dentro de `--scan_limit`; validar com amostra conhecida (ex.: admin com papel amplo).
- [ ] `menu_gates_check --strict` falha (exit 1) se algum código referenciado em `menu_sidebar_gates` não existir em `rbac_permissions` após **Sincronizar catálogo**.
- [ ] `enforce_readiness --csv` emite cabeçalho + linha com `readiness_ok` e contagens; com `--strict`, exit 1 quando config/tabelas inválidas ou quando `enforce` + `enforce_block_without_roles` encontram utilizadores ativos sem papel conforme as regras do shell.
- [ ] `pre_deploy` executa `menu_gates_check --strict` e `enforce_readiness --strict` em sequência (saída legível); exit 1 se qualquer passo falhar.

## Endurecimento final — `enforce_block_without_roles` (Fase 8)

Só depois de **toda** a equipa ativa (`users.role = 0`) ter pelo menos um papel em `rbac_users_roles` (ou permissões efetivas via grupos). Com isto **false** (padrão no repositório), utilizadores **sem** papéis RBAC continuam a usar só legado/`isAuthorized` e **não** são bloqueados pelo `RbacComponent`. Com **true** em `enforce`, quem não tiver papéis (equipe) é negado nas rotas não whitelist.

**Pré-requisitos**

- [ ] `bin/cake migrations migrate` aplicado; **Sincronizar catálogo** feito no ambiente-alvo.
- [ ] `bin/cake rbac_rollout unassigned_equipe` (ou `--csv`) sem utilizadores inesperados — corrigir com **Permissões → Papéis por usuário** ou `assign_*` / UI antes de continuar.
- [ ] `bin/cake rbac_rollout user_effective --user_id=…` em amostra (gestores, suporte, finanças) confirma permissões mínimas para o dia-a-dia.
- [ ] Em **staging**, definir temporariamente `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1` com `RBAC_MODE=enforce` e validar login + rotas críticas com utilizadores reais.

**Ativação em produção**

- [ ] `bin/cake rbac_rollout enforce_readiness --strict` com a mesma combinação (`enforce` + bloqueio sem papéis) que vai usar em produção — exit 0.
- [ ] Definir **`RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1`** no ambiente (recomendado: não alterar o default do ficheiro até política da equipa ser estável).
- [ ] Opcional: após estabilização, mudar `$fileEnforceBlockWithoutRoles` para `true` em `config/rbac.php` e atualizar `RbacPhpConfigTest` para `assertTrue` — só quando não existir mais dependência de exceções híbridas.

**Rollback**

- [ ] Remover `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES` ou definir `0`; manter `RBAC_MODE=enforce` se desejado.

## Auditoria (Fase 9)

- [ ] Com `audit_decisions_db` ativo, negações (e opcionalmente `all`) aparecem na tabela e em **Auditoria RBAC** / `bin/cake rbac_rollout audit_recent`.

## APIs e `api*`

- [ ] Actions com prefixo `api` ficam fora do `RbacComponent` por defeito (`skip_action_prefixes`), **exceto** as listadas em `rbac_api_enforced_actions` (Tickets, Queues, `Ticketcomentarios`, etc.); validar integrações e React com `RBAC_MODE=enforce`.

## Legado

- [ ] Admin equipe (`users.admin`, `role === 0`) acede ao painel **Permissões** como antes.
- [ ] Portal cliente (`role === 1`) não deve ser bloqueado por `enforce_block_without_roles_equipe_only` (default).

---

*Última revisão: endurecimento `enforce_block_without_roles`, defaults do repositório (`enforce`, menu, auditoria, políticas) e `RbacPhpConfigTest`; roadmap em `IMPLEMENTATION_LOG.md`.*
