# IMPLEMENTATION_LOG — RBAC + ABAC (ERP)

Registro incremental das fases de evolução de autorização. Cada entrada resume diagnóstico, alterações, artefatos e pendências.

---

### Índice — prioridades P0–P3 (rollout executável)

| Prioridade | Secção neste ficheiro | Issues modelo |
|------------|----------------------|---------------|
| **P0** | [Fase 8 — passos 1–8](#rbac-priority-p0) | A–E |
| **P1** | [Endurecimento + matriz efetiva](#rbac-priority-p1) | F–H |
| **P2** | [Políticas, campos, catálogo](#rbac-priority-p2) | I–K |
| **P3** | [Auditoria, integração, documentação](#rbac-priority-p3) | L–N |

[Tabela resumo *Próximas fases (executável)*](#rbac-roadmap-executable) · [Limites da automação (P0–P3)](#rbac-automation-limits) · Checklist manual: [`docs/TEST_CHECKLIST_RBAC.md`](docs/TEST_CHECKLIST_RBAC.md) · Testes PHPUnit: `composer test-rbac` / `composer rbac-verify-noninteractive` — suites **`rbac`**, **`rbac-integration`**, **`rbac-http`** (bootstrap `tests/bootstrap_http.php`; ver `phpunit.xml.dist`)

---

## Roadmap — fases e estado (encerramento do plano-mestre)

Esta secção consolida o plano alinhado a `AUTH_AUDIT.md` §14 e substitui listas dispersas de “próxima fase”. Numeração **3–6, 8–11** segue o plano de migração do audit; **7** foi usada neste repositório para o resolver de aliases em runtime (subconjunto do antigo “Compat”).

| Fase | Foco | Estado | Onde está documentado / entregável |
|------|------|--------|-------------------------------------|
| **1** | Diagnóstico stack Auth + RBAC + ABAC | Concluída | `AUTH_AUDIT.md`; entradas abaixo. |
| **2** | Catálogo `modulo.recurso.acao`, tabela `rbac_permission_legacy_aliases`, seeds idempotentes | Concluída | Fase 2 + 2b–2f; `config/permissions_registry.php`; `config/Migrations/20260407*.php`. |
| **7** | Resolver legado → canónico na checagem RBAC (`expand_legacy_aliases`, log opcional) | Concluída | Secção “Fase 7” abaixo; `RbacPermissionResolver`, `RbacComponent`, `config/rbac.php`. |
| **3** | Modelo de dados: grupos, políticas, campos, auditoria, `hierarchy_level` em papéis | Parcial | Migration `20260416100000`; tabelas `rbac_groups`, `rbac_user_groups`, `rbac_group_roles` + schema políticas/campos/auditoria; `expand_group_roles` no `RbacComponent`. UI e motor ABAC/campos — Fases 4–5. |
| **4** | Admin: CRUD grupos, matriz efetiva, evolução de `PermissoesController` | Parcial | Grupos + **relatório efetivo** `adminUserEffective` (IDs alinhados a `expand_legacy_aliases` via `RbacEffectivePermissionIds`); matriz com coluna efetiva (`?user_id=` / filtro). |
| **5** | Motor ABAC: políticas (`conditions_json`, prioridade), integração após RBAC estável em enforce | Parcial | `RbacPolicyConditions` + `evaluate_permission_policies` no `RbacComponent`; **repositório:** `evaluate_permission_policies` ativo por defeito em `config/rbac.php` (env `RBAC_EVALUATE_POLICIES` sobrepõe). UI **Permissões → Políticas** + `permissoes.policies.manage`; matriz campo-a-campo pendente. |
| **6** | UI: menu/submenu e campos condicionados a permissões atómicas | Parcial | **Repositório:** `menu_filter_config` + `menu_filter_sidebar` ativos por defeito. Atalho Config, `RbacChecker::shouldShowConfigAdminHub`, `ConfigController`; **6b** `menu_sidebar_gates`; **6c** sub-gates OS / Relatórios / Tickets; **6d** módulo avançado; **6e** pré-faturamento; **6f** sino + `footer_acesso_remoto`. |
| **8** | Rollout: backfill `rbac_users_roles`, modo híbrido ou fallback legado ao ativar `RBAC_MODE=enforce` sem cortar quem ainda não tem papel | Parcial | Flags em `config/rbac.php` + `RbacComponent`; shell `rbac_rollout stats|list_roles|role_stats|user_effective|who_has|menu_gates_check|unassigned_*|assign_*|audit_recent`; CSV onde aplicável; backfill em massa manual/UI. |
| **9** | Auditoria: decisões RBAC e ações sensíveis | Parcial | **Repositório:** `audit_decisions_db` ativo por defeito; `audit_retention_days` = 90. `RbacComponent`; shell `audit_recent` + `audit_purge`; **UI** `Permissoes::adminRbacAudit` + `permissoes.audit.view`; auditoria de ações sensíveis fora do `RbacComponent` ainda por módulo |
| **10** | Testes: PHPUnit nos pontos de autorização + checklist manual API/menu | Parcial | `composer test-rbac` / `composer rbac-verify-noninteractive` — suites **`rbac`**, **`rbac-integration`**, **`rbac-http`** (`RbacPermissoesHttpTest`, `RbacAreasHttpTest`, `RbacEmpresasusersHttpTest`, `RbacProblemasHttpTest`, `RbacFeriadosHttpTest`, `RbacContratosHorasHttpTest`, `RbacNormasempresaHttpTest`, `RbacFinanceiroHttpTest`, `RbacFaturamentoHttpTest`, `RbacClientesHttpTest`, `RbacPrefaturamentoHttpTest`, `RbacBancosenhasHttpTest`, `RbacEmpresasHttpTest`, `RbacOrcamentosHttpTest`, `RbacProdutosHttpTest`, `RbacVisitasHttpTest`, `RbacOrdensservicoHttpTest`; SQLite `:memory:` via `PGM_HTTP_TEST_DATASOURCE`). Scripts: `bin/rbac_verify_noninteractive.ps1` / `.sh`. **Continua pendente (não automatizável só no Git):** checklist manual API/menu; opcional PostgreSQL/fixtures espelhando produção. |
| **11** | Documentação: `AUTH_MODEL.md`, `RBAC_ABAC_MATRIX.md`, `LEGACY_COMPATIBILITY.md`, `TEST_CHECKLIST.md`; alinhar `docs/DOC3_RBAC_ABAC.md` ao catálogo real | Parcial | `AUTH_MODEL`, `LEGACY_COMPATIBILITY`, `RBAC_ABAC_MATRIX`, `TEST_CHECKLIST_RBAC`; DOC3 com links; tabela “código × papel” estática no repo não é mantida (usar painel + SQL em `RBAC_ABAC_MATRIX.md`). |

<a id="rbac-automation-limits"></a>

### Limites: nenhuma execução “única” cobre P0–P3 na íntegra

Pedidos para concluir **todas** as fases faltantes **sem interação humana** esbatem-se com: (1) **P0/P1** — migrations, sync de catálogo e backfill em **`rbac_users_roles`** na **vossa** PostgreSQL, critérios de negócio, janela de observação; (2) **P2** — políticas e `rbac_field_permissions` por formulário/módulo; (3) **P3 trilho A** — inventário e instrumentação de ações sensíveis fora do `RbacComponent` por módulo. Isto **não** pode ser substituído por um único comando no repositório.

**O que *pode* correr de forma não interativa (máquina com PHP + `vendor`):**

| Comando | Função |
|---------|--------|
| `composer rbac-verify-noninteractive` | Alias de `composer test-rbac` (Fase 10 / P3 trilho B no código). |
| `bin/rbac_verify_noninteractive.ps1` ou `bin/rbac_verify_noninteractive.sh` | Mesmas três suites PHPUnit; com `RBAC_RUN_PRE_DEPLOY=1` acrescenta `bin/cake rbac_rollout pre_deploy` (**requer BD** com `rbac_permissions` sincronizado). |
| `composer rbac-verify-with-pre-deploy` | Só `pre_deploy` (strict: `menu_gates_check` + `enforce_readiness`); **requer PostgreSQL** configurado. |
| `.github/workflows/rbac-phpunit.yml` | `composer test-rbac` em CI (sem BD de produção). |

**Pendências transversais já conhecidas**

- **Agenda:** ~~`agenda.alias` apontava para controller inexistente~~ **Resolvido:** catálogo `Visitas`, `RbacChecker::matchAction` aceita legado `Agenda`+`*`, migration `20260418120000_RbacAgendaAliasVisitasController` (BD + aliases).
- **Actions `api*`:** cobertos em `rbac_api_enforced_actions` (Tickets, Queues, `Ticketcomentarios::apiAdd`, `Tickets::apiIndexCliente`). Outros módulos React/API — só se surgirem `api*` novos ou remoção de whitelist pontual.
- **PortalNotifications:** fora da whitelist RBAC; exige `portal.notifications.read/write`; migration `20260421140000` nos papéis equipe padrão.
- **Operação contínua:** após migrations, **Permissões → Sincronizar catálogo** para cada ambiente; CLI `bin/cake rbac_rollout report` para inspecionar modo/listas. Variáveis `RBAC_*` no `.env`: modelo em **`config/rbac.env.example`**. CI: **`.github/workflows/rbac-phpunit.yml`** — `composer test-rbac` em push/PR quando mudam paths listados no YAML (incl. `AppController`, utilitários RBAC/ABAC, models `Rbac*`) ou **execução manual** (*workflow_dispatch* no GitHub).
- **Repositório (pós-rollout runtime):** `config/rbac.php` com `mode=enforce`, filtros de menu, auditoria de negações e políticas ativos por defeito (variáveis `RBAC_*` sobrepõem). `enforce_block_without_roles` mantém-se **false** até backfill completo da equipa — ver `docs/LEGACY_COMPATIBILITY.md` e o passo a passo em **`docs/TEST_CHECKLIST_RBAC.md`** (secção *Endurecimento final — enforce_block_without_roles*). Diagnóstico histórico `AUTH_AUDIT.md` atualizado com nota de rodapé 2026-04-08; `docs/DOC3_RBAC_ABAC.md` referencia o runtime atual.

**Ordem sugerida:** concluir **8** (estratégia + backfill piloto) em paralelo ao desenho da **3**; depois **4 → 5 → 6**; **9–11** conforme estabilização.

<a id="rbac-roadmap-executable"></a>

### Próximas fases (executável) — após `enforce` por defeito no repositório

Ordem prática para a equipa (cada ítem pode virar ticket; dependências indicadas):

| Prioridade | Fase | Entregável concreto | Critério de “feito” |
|------------|------|---------------------|---------------------|
| **P0** | **8** | Backfill `rbac_users_roles` (equipe ativa); `unassigned_equipe` → zero ou lista aceite; `pre_deploy` exit 0 | **Checklist numerado § P0 abaixo** (passos 1–8) + `docs/TEST_CHECKLIST_RBAC.md` § P0; `enforce_block_without_roles` é **P1** (não P0) |
| **P1** | **8** / **4** | `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES` + matriz/UI **efetiva** | **§ P1 abaixo** (passos 1–5; issues F–H); 1 semana de monitorização sem bloqueios críticos |
| **P2** | **5** / **6** | Políticas, campos, gaps de catálogo (incremental) | **§ P2 abaixo** (pacotes 1–3; issues I–K); pelo menos um pacote fechado por sprint ou release |
| **P3** | **9** / **10** / **11** | Auditoria além do RBAC de rota + integração PHPUnit + fecho documental | **§ P3 abaixo** (trilhos A–C; issues L–N); pode correr em paralelo a P2 por módulo |

**Fase 3 (dados):** schema já migrado; trabalho restante é **adoção** nas fases 4–5 (UI grupos/campos/políticas), não nova migration salvo requisito de negócio.

<a id="rbac-priority-p0"></a>

#### P0 — Fase 8: checklist numerado + aceitação global

Checklist operacional (ordem recomendada). **Critério global “P0 feito”:** passos **1–7** concluídos com evidência no ticket; passo **8** (observação 48–72 h) registado sem incidente bloqueante — com `enforce_block_without_roles` ainda **false** até P1.

| # | Passo | Evidência mínima | Aceitação (definição de pronto) |
|---|--------|------------------|----------------------------------|
| **1** | `bin/cake migrations migrate` no ambiente-alvo | Log ou pipeline | Sem migrations pendentes nas tabelas `rbac_*` necessárias ao rollout |
| **2** | **Permissões → Sincronizar catálogo** | Screenshot ou registo de quem executou | `rbac_permissions` contém códigos do `permissions_registry` esperados para esse ambiente |
| **3** | `bin/cake rbac_rollout menu_gates_check --strict` | Stdout + exit code | **Exit 0**; todos os códigos em `menu_sidebar_gates` existem em `rbac_permissions` |
| **4** | `bin/cake rbac_rollout unassigned_equipe` (e `--csv` arquivado) | Ficheiro CSV ou lista vazia | **Zero** utilizadores equipe ativos sem papel **ou** lista explícita de exceções aprovada (owner + data de remediação) |
| **5** | Backfill: **Permissões → Papéis por usuário** e/ou `assign_*` até cumprir o passo 4 | Diff de BD ou export `rbac_users_roles` | Cada `user_id` equipe na lista de exceções tem papel atribuído ou está formalmente isento |
| **6** | Validação amostral: `user_effective --user_id=` para perfis críticos (ex.: 3–5 contas) | Nota no ticket com IDs | Permissões efetivas batem com o esperado pelo negócio (acesso a OS, clientes, faturação, etc.) |
| **7** | `bin/cake rbac_rollout pre_deploy` (ou `enforce_readiness --strict` + `menu_gates_check --strict` separados) | Stdout + exit 0 | **Exit 0** com a mesma `config/rbac.php` + `.env` que produção/staging |
| **8** | Observação pós-go-live (48–72 h): `audit_recent`, logs, tickets internos | Resumo | Sem bloqueios massivos inesperados; negações RBAC explicáveis (papéis em falta no catálogo) |

**Nota:** `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1` **não** faz parte do P0; é **P1** ([§ P1](#rbac-priority-p1); *Endurecimento final* em `docs/TEST_CHECKLIST_RBAC.md`).

##### Modelos de issues (copiar para Jira / GitHub / Azure Boards)

**Issue A — Título:** `[RBAC P0] Ambiente X — sync catálogo + menu_gates_check`  
**Descrição:** Executar passos **2** e **3** da tabela P0; anexar stdout.  
**Aceitação:** `menu_gates_check --strict` exit 0 após sync.

**Issue B — Título:** `[RBAC P0] Ambiente X — inventário unassigned_equipe`  
**Descrição:** Passo **4**; guardar `unassigned_equipe --csv` no drive/ticket.  
**Aceitação:** CSV anexado; lista de exceções vazia ou aprovada por \<owner\>.

**Issue C — Título:** `[RBAC P0] Ambiente X — backfill papéis equipe`  
**Descrição:** Passo **5**; referenciar papéis-alvo (slugs) por função.  
**Aceitação:** Re-execução do passo 4 com zero linhas não explicadas.

**Issue D — Título:** `[RBAC P0] Ambiente X — validação user_effective (amostra)`  
**Descrição:** Passo **6**; listar `user_id` e resultado esperado.  
**Aceitação:** Todos os casos da amostra conferidos.

**Issue E — Título:** `[RBAC P0] Ambiente X — pre_deploy antes do go-live`  
**Descrição:** Passo **7**; mesmo `.env`/config que produção.  
**Aceitação:** `pre_deploy` exit 0; registo da data/versão deployada.

<a id="rbac-priority-p1"></a>

#### P1 — Endurecimento híbrido + matriz efetiva (Fases 8 / 4)

**Pré-requisito:** P0 feito (checklist acima). **Critério global “P1 feito”:** (A) `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1` **ou** alteração em `config/rbac.php` com `enforce_readiness --strict` exit 0 na configuração final **e** (B) plano de matriz efetiva executado ou ticket de UX fechado com critério mensurável.

| # | Passo | Evidência | Aceitação |
|---|--------|-----------|-----------|
| **1** | Staging: `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1` + smoke (login, OS, clientes, faturação) | Registo de testes | Sem bloqueio em perfis piloto |
| **2** | `bin/cake rbac_rollout enforce_readiness --strict` com a mesma combinação que produção | Exit 0 | Shell confirma prontidão |
| **3** | Produção: ativar env (recomendado) ou commit `$fileEnforceBlockWithoutRoles=true` + `RbacPhpConfigTest` | Pipeline / changelog | Comportamento alinhado ao modelo escolhido |
| **4** | Janela 1 semana: monitorizar `audit_recent` e tickets | Resumo semanal | Negações explicáveis; rollback documentado se necessário |
| **5** | **Fase 4:** definir onde mostrar permissão **efetiva** (matriz / `adminUserEffective` / export) | Mock ou PR | Utilizador vê direto+grupos+aliases de forma auditável |

##### Modelos de issues — P1

**Issue F:** `[RBAC P1] Staging — enforce_block_without_roles` — Aceitação: smoke checklist anexado; `enforce_readiness --strict` exit 0.

**Issue G:** `[RBAC P1] Produção — ativar bloqueio sem papéis` — Aceitação: env deployado; `unassigned_equipe` continua vazio; plano de rollback no ticket.

**Issue H:** `[RBAC P1] Matriz / UI — coluna ou relatório efetivo` — Aceitação: critério “efetivo = runtime” validado em ≥1 utilizador de teste.

<a id="rbac-priority-p2"></a>

#### P2 — Políticas, campos e cobertura do catálogo (Fases 5 / 6)

**Pré-requisito:** P0 estável; P1 opcional conforme política da empresa. **Critério global “P2 feito” (incremental):** pelo menos **um** pacote fechado entre: políticas piloto, campos piloto, ou auditoria de rotas sem permissão.

| # | Pacote | Entregável | Aceitação |
|---|--------|------------|-----------|
| **1** | Políticas (Fase 5) | ≥1 permissão de risco com `rbac_permission_policies` revistas | `RbacPolicyConditions` cobre cenário; sem regressão PHPUnit |
| **2** | Campos (Fase 5) | ≥1 formulário com `rbac_field_permissions` + server-side | UI e controller negam edição quando aplicável |
| **3** | Catálogo (Fase 6) | Lista de `controller#action` sem match + correções ou whitelist documentada | Lista residual vazia ou justificada em `config/rbac.php` / registry |

##### Modelos de issues — P2

**Issue I:** `[RBAC P2] Políticas piloto — <módulo>` — Aceitação: linhas em BD + teste manual documentado.

**Issue J:** `[RBAC P2] Campos sensíveis — <entidade/form>` — Aceitação: screenshot + teste de papel restrito.

**Issue K:** `[RBAC P2] Gap catálogo — <controller>` — Aceitação: entrada em `permissions_registry` + sync + verificação em `enforce`.

<a id="rbac-priority-p3"></a>

#### P3 — Auditoria além do componente, testes integração, documentação (Fases 9 / 10 / 11)

**Pré-requisito:** P0 estável; **P1/P2** podem avançar em paralelo. **Critério global “P3 feito” (incremental):** fechar **pelo menos um** trilho **A**, **B** ou **C** por release, até os três estarem cobertos no âmbito acordado (não exige “todo o ERP” num único sprint).

| Trilho | Fase | Passo | Evidência | Aceitação |
|--------|------|-------|-----------|-----------|
| **A** | **9** | **1** — Inventariar ações sensíveis (delete, mass export, troca de papel, etc.) fora do `RbacComponent` | Lista no ticket | Prioridade P0/P1 por módulo acordada |
| **A** | **9** | **2** — Por módulo piloto: log estruturado ou evento + permissão de leitura (`permissoes.audit.view` ou equivalente) | PR ou config | Operação consegue responder “quem fez o quê” para esse fluxo |
| **A** | **9** | **3** — `bin/cake rbac_rollout audit_purge --days` (ou política de retenção) documentada | Runbook | Retenção alinhada a `audit_retention_days` / RGPD interno |
| **B** | **10** | **1** — BD de teste + integração ORM ou app | Repo | **`rbac-integration`** (SQLite `:memory:`) + **`rbac-http`** (`tests/bootstrap_http.php`, datasource SQLite em `config/bootstrap.php` quando `PGM_HTTP_TEST_DATASOURCE`); CI em `.github/workflows/rbac-phpunit.yml` |
| **B** | **10** | **2** — ≥1 fluxo: rota permitida com papel de teste | Teste | **`RbacPermissoesHttpTest`**: admin legado + equipe com `permissoes.matrix.view` / só catálogo → 200 onde aplicável |
| **B** | **10** | **3** — ≥1 fluxo: rota negada com papel de teste | Teste | Mesma suite: matriz/catálogo cruzados, `access-denied`, visitante → login, portal → dashboard; **próximo:** negado explícito em `Users/accessDenied` ou outro módulo |
| **C** | **11** | **1** — DOC3: secção “Fonte de verdade” + links ao registry; marcar exemplos obsoletos | PR em `DOC3_RBAC_ABAC.md` | Leitor não confunde exemplo estático com catálogo real |
| **C** | **11** | **2** — `RBAC_ABAC_MATRIX.md` / `AUTH_MODEL` — delta conhecido vs UI (se aplicável) | Nota no doc | Consultas SQL ou painel referenciados |
| **C** | **11** | **3** — Checklist manual API/menu (trecho em `TEST_CHECKLIST_RBAC`) revisto pós-mudança | Data no rodapé | Última revisão ≤ release |

##### Modelos de issues — P3

**Issue L:** `[RBAC P3-F9] Auditoria sensível — <módulo/ação>` — Aceitação: evento/log + owner de retenção.

**Issue M:** `[RBAC P3-F10] Integração — <fluxo autorização>` — Aceitação: teste verde em CI ou instrução `phpunit --filter` no ticket.

**Issue N:** `[RBAC P3-F11] DOC3 / matriz — alinhar ao registry` — Aceitação: PR merged; exemplos obsoletos removidos ou etiquetados.

### Fase 6c — Sub-gates na sidebar (2026-04-07+)

Com `RBAC_MENU_FILTER_SIDEBAR=1`, ligações dentro de **Relatórios**, **Ordens de serviço** e **Tickets** passam a respeitar permissões distintas (ver `config/rbac.php` e `sidebar.ctp`). Quem só pode listar OS não vê “Nova ordem”; quem só tem Service Desk não vê “Histórico”; painel clássico vs indicadores avançados separados.

### Fase 6d — Módulo avançado na sidebar (2026-04-07+)

O grupo **Módulo avançado** (equipa) deixa de usar um único gate: **Gestão de contratos**, **Modelos de contrato** e **Faturas** têm códigos OR próprios (`erp.contracts.management` / `erp.advanced.contracts`, `erp.contracts.templates` / `erp.advanced.contracts`, `erp.advanced.invoices` / `erp.advanced.invoices.view`). Indicadores avançados continuam só em **Relatórios** (6c).

### Fase 6e — Pré-faturamento fila vs conferências (2026-04-07+)

Substitui o gate único `prefaturamento` por **`prefaturamento_fila`** (`queue` + `manage`) e **`prefaturamento_conferencia`** (`conferencia` + `manage`). O menu mantém um atalho à fila (`index`) se qualquer um for verdadeiro; na mesma página, a coluna e o POST `conferencia` seguem `prefaturamento_conferencia` (`PrefaturamentoController` + `index.ctp`). O catálogo **`prefaturamento.conferencia`** inclui `index` e `conferencia` (migration `20260422120000_RbacPrefaturamentoConferenciaIndexPatch`) para `RBAC_MODE=enforce` sem exigir `prefaturamento.queue` só para abrir a fila.

### Fase 6f — Sino de notificações e acesso remoto (2026-04-07+)

Com `RBAC_MENU_FILTER_SIDEBAR=1`, equipa (`role=0`): o **sino** no topo da sidebar exige um dos códigos `portal.notifications*`; o toggle de tema mantém-se. No **dropdown** do rodapé, **Acesso remoto** exige `normasempresa.acessoremoto` (utilizadores não equipa admin continuam a ver o link — `shouldShowSidebarGate`).

**Progresso global (estimativa para “permissionamento ERP completo”)** — ~**70–80 %** do desenho alvo: **infra + painel + runtime `enforce` por defeito + menu gates + auditoria/políticas ativas** está maduro; falta sobretudo **rollout organizacional** (Fase 8 / **P0**), **endurecimento híbrido** (**P1**), **campos/políticas/gaps de catálogo** (**P2**), extensão dos **testes HTTP** (Fase 10 / **P3 trilho B** — além de Permissões) e **fecho documental** (**P3 trilho C** / Fase 11). Não há um “último commit” único: é convergência até desligar o híbrido legado (`enforce_block_without_roles` + zero `unassigned` aceitável).

---

## Fase 1 — Diagnóstico da estrutura existente

**Data:** 2026-04-07  
**Status:** concluída (somente documentação; sem mudança de comportamento).

### Diagnóstico (resumo)

- Stack: **CakePHP** com `AuthComponent` e autorização por `Controller::isAuthorized`.
- Legado: `users.admin`, `users.role` (0/1), `permissaoacesso`, multi-empresa via `empresasusers`.
- RBAC: tabelas `rbac_*`, catálogo em `config/permissions_registry.php`, enforcement via `RbacComponent` (`config/rbac.php`; sem `RBAC_MODE`, padrão do repositório `enforce` + menu/auditoria/políticas ativos — ver docblock do ficheiro).
- ABAC: `AbacQuery` + `config/abac.php` (escopos empresa/cliente/own); uso pontual em controllers (ex.: `UsersController` dashboard).
- Painel: `PermissoesController` restrito a admin equipe; matriz e vínculo usuário↔papel já existem.
- **Na data da Fase 1 faltava (histórico):** grupos, políticas JSON, matriz campo-a-campo, auditoria de decisões, menu por código atômico. **Desde 2026-04:** schema grupos/políticas/campos/auditoria, gates de menu, `enforce` + políticas/auditoria por defeito — ver **Roadmap**; permanece adoção em profundidade e rollout (Fases 4–6, 8–11).

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Documentação | Criado `AUTH_AUDIT.md` (relatório completo da Fase 1). |
| Documentação | Criado `IMPLEMENTATION_LOG.md` (este arquivo). |

### Arquivos impactados

- `AUTH_AUDIT.md` (novo)
- `IMPLEMENTATION_LOG.md` (novo)

### Migrations / rotas / services / testes

- Nenhuma alteração nesta fase.

### Pendências reais

Ver **Roadmap** (topo deste ficheiro); na altura da Fase 1 ainda não existiam catálogo expandido nem tabela de aliases.

### Próxima fase (histórico)

Fase 2 — concluída; continuidade na tabela Roadmap.

---

## Fase 2 — Catálogo novo de permissões (iteração clientes + usuários)

**Data:** 2026-04-07  
**Status:** catálogo atômico parcial entregue; expansão em runtime via `RbacPermissionResolver` integrada na **Fase 7** (ver secção abaixo).

### Diagnóstico

- O catálogo atual (`permissions_registry.php`) já cobre dezenas de funções com códigos mistos (`.manage`, `users.equipe_*`, alguns já granulares).
- Foi priorizado **Clientes** (ERP + submódulos acessos/contratos + usuários portal) e **usuários da equipe** com padrão `modulo.recurso.acao`, sem remover entradas legadas.

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Migration | Tabela `rbac_permission_legacy_aliases` (`legacy_code`, `canonical_code`, `notes`, timestamps, índice único composto). |
| Migration | Seed idempotente `RbacLegacyAliasSeedClientesUsuarios`: `clientes.manage` → atomics do controller `Clientes`; `users.equipe*` → `usuarios.*`; `users.clientes_*` → `clientes.usuarios.*`. |
| Catálogo | Novas linhas em `config/permissions_registry.php`: `usuarios.*`, `clientes.*`, `clientes.acessos.*`, `clientes.contratos.*`, `clientes.usuarios.*`, `usuarios.roles.assign`. |

### Arquivos impactados

- `config/Migrations/20260407143000_RbacPermissionLegacyAliases.php`
- `config/Migrations/20260407150000_RbacLegacyAliasSeedClientesUsuarios.php`
- `config/permissions_registry.php`

### Migrations criadas/alteradas

- `20260407143000_RbacPermissionLegacyAliases.php`
- `20260407150000_RbacLegacyAliasSeedClientesUsuarios.php`

### Rotas / services / testes

- Nenhuma rota nova. **Obrigatório:** após deploy das migrations, executar **Permissões → Sincronizar catálogo** para inserir os novos códigos em `rbac_permissions`.
- Papéis que já tinham só permissões legadas passam a ser expandidos na checagem quando **Fase 7** está ativa (`expand_legacy_aliases`); também é possível reconfigurar a matriz só com códigos canónicos.

### Pendências reais (Fase 2 — continuação)

1. **Fase 2 (catálogo + aliases):** macros principais cobertos; **Agenda** alinhada a `Visitas` / rota `/agenda` (ver migration `20260418120000`).
2. **Fase 3:** schema de grupos entregue (ver secção Fase 3); `usuarios.groups.assign` e CRUD quando a Fase 4 existir.

### Próxima fase

**Fase 4 — Admin** (CRUD grupos, matriz) e continuação da Fase 3 (políticas/campos em runtime).

---

## Fase 2b — Catálogo atômico: tickets, Service Desk, OS, portal tickets, clicontratos

**Data:** 2026-04-07  
**Status:** concluída (catálogo + seed de aliases; sem motor de resolução em runtime).

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Catálogo | `tickets.view/create/update/delete/assign/timer/email`, `servicedesk.view/create/update/cancel/print`, `ordensservico.view/update/delete/reports`, `tickets.portal.view/create/update`. |
| Migration | `20260407154500_RbacLegacyAliasSeedTicketsOsClicontratos`: `clicontratos.manage` → `clientes.contratos.*`; `servicedesk.tickets` → `servicedesk.*`; `tickets.portal_cliente` → `tickets.portal.*`; `ordensservico.full` → lista de atomics OS. |

### Arquivos impactados

- `config/permissions_registry.php`
- `config/Migrations/20260407154500_RbacLegacyAliasSeedTicketsOsClicontratos.php`

### Observações

- `tickets.assign` inclui `apiTransferirTicket`; com `skip_action_prefixes = ['api']`, essa action pode **não** passar pelo `RbacComponent` — documentado na descrição da permissão.
- Após migrations: **Sincronizar catálogo** no painel Permissões.

---

## Fase 2c — Catálogo atômico: empresas, produtos, orçamentos, financeiro, faturamento

**Data:** 2026-04-07  
**Status:** concluída (catálogo + seed de aliases para legados de empresas/produtos/orçamentos).

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Catálogo | `empresas.view/create/update/tokens.sync/session.switch/migrate`, `produtos.view/create/update/delete/pricing/stock`, `orcamentos.view/create/update/approve`, `orcamentos.portal.view/update`, `financeiro.*`, `faturamento.*`. |
| Migration | `20260407160000_RbacLegacyAliasSeedEmpresasProdutosOrcamentos`: `empresas.manage`, `produtos.manage`, `orcamentos.manage`, `orcamentos.portal_cliente` → atomics (exceto `empresas.session.switch` no legado `empresas.manage`). |

### Arquivos impactados

- `config/permissions_registry.php`
- `config/Migrations/20260407160000_RbacLegacyAliasSeedEmpresasProdutosOrcamentos.php`

### Observações

- `empresas.session.switch` (`alteraempresa`) permanece permissão **à parte**: quem troca de empresa no dropdown não deve depender só de `empresas.manage`; atribuir na matriz aos papéis que precisam (ex.: toda a equipe multi-empresa).
- `financeiro.*` / `faturamento.*` não tinham macro `.manage` no registry; só entradas novas para a matriz.
- Após migrations: **Sincronizar catálogo**.

---

## Fase 2d — Faturas, empresasusers, visitas, relatórios, ERP contratos/faturas/indicadores

**Data:** 2026-04-07  
**Status:** concluída (catálogo + seed de aliases).

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Catálogo | `faturas.view/create/update`, `empresasusers.*`, `visitas.*` + `visitas.portal.view`, `relatorios.painel.*`, `relatorios.indicadores.*`, `portal.relatorios.view/export`, `erp.advanced.contracts.view`, `erp.advanced.invoices.*`, `erp.contracts.management.*` (view/edit/signature/lifecycle/webhook), `erp.contracts.templates.*`. |
| Migration | `20260407163000_RbacLegacyAliasSeedFaturasVisitasRelatoriosErp` mapeia legados `faturas.locacao`, `empresasusers.manage`, `agenda.visitas`, `portal.relatorios`, `erp.advanced.*`, `erp.contracts.management`, `erp.contracts.templates`. |

### Arquivos impactados

- `config/permissions_registry.php`
- `config/Migrations/20260407163000_RbacLegacyAliasSeedFaturasVisitasRelatoriosErp.php`

### Observações

- Ações `aprovarhash` / `rejeitarhash` ficaram em `faturas.update` para evitar duas permissões casando a mesma action `view` no RBAC.
- `relatorios.painel.*` (controller `Relatorios`) não tinha macro legado no registry; só entradas novas para a matriz.
- `erp.contracts.management.webhook` usa `abac_scope` null; em produção validar se o webhook deve ficar fora do RBAC de sessão (já existe `webhookAutentique` em `Security` unlocked).
- Após migrations: **Sincronizar catálogo**.

---

## Fase 2e — Filas, pré-faturamento, bancosenhas, feriados, contratos horas, parâmetros OS, tickets.api

**Data:** 2026-04-07  
**Status:** concluída.

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Catálogo | `prefaturamento.manage` (macro); atomics `queues.admin.*`, `queues.json.*`, `bancosenhas.*`, `feriados.*`, `contratos.horas.*`, `problemas.tipos.*`, `areas.status.*`. |
| Migration | `20260407170000_RbacLegacyAliasSeedQueuesPrefaturamentoParametros` — aliases para `prefaturamento.manage`, `queues.admin`, `bancosenhas.manage`, `feriados.manage`, `contratos.horas`, `problemas.os_tipos`, `areas.os_status`, `tickets.api` → atomics de tickets equipe. |

### Observações

- **Pré-faturamento:** `prefaturamento.queue` e `prefaturamento.conferencia` já existiam; `prefaturamento.manage` é macro novo (`Prefaturamento/*`) que expande para os dois códigos.
- **Filas:** actions `api*` são ignoradas pelo `RbacComponent` (`skip_action_prefixes`); `getAvailableQueues` entra em `queues.json.read` para quando o motor de autorização evoluir.
- **tickets.api:** alias para `tickets.view`…`tickets.email` é **aproximação documental**; muitos endpoints React continuam com prefixo `api` e podem não passar pelo RBAC de rota até política explícita (Roadmap **5** / **8**).

### Arquivos impactados

- `config/permissions_registry.php`
- `config/Migrations/20260407170000_RbacLegacyAliasSeedQueuesPrefaturamentoParametros.php`

---

## Fase 2f — Config macro, permissões (admin), portal avançado, API produtos/clientes

**Data:** 2026-04-07  
**Status:** concluída.

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Catálogo | `config.manage`, `config.bootstrap`; `permissoes.catalog.view`, `permissoes.registry.sync`, `permissoes.matrix.view`, `permissoes.matrix.grant_super`, `permissoes.users.list`, `permissoes.users.assign_roles`; `portal.notifications.read`, `portal.notifications.write`; `portal.contracts.client.view/pdf`, `portal.invoices.client.view/export`, `portal.attendance.client.view`; `api.produtos.list`, `api.produtos.add`, `api.clientes.list`, `api.clientes.add`. |
| Migration | `20260407172000_RbacLegacyAliasSeedPortalConfigPermApi` — aliases de `permissoes.admin`, `config.manage`, `portal.notifications`, `portal.advanced.contracts`, `portal.advanced.invoices`, `portal.advanced.attendance`, `api.produtos`, `api.clientes`. |

### Observações

- Rotas **API** de integração seguem liberadas em `AppController::isAuthorized` com token; os códigos novos servem à matriz e ao **Fase 7** (expansão de aliases em RBAC de rota); cobertura total de `api*` interna permanece no Roadmap.
- Actions `admin*` de permissões exigem o fluxo com prefixo **admin** no Cake.

### Arquivos impactados

- `config/permissions_registry.php`
- `config/Migrations/20260407172000_RbacLegacyAliasSeedPortalConfigPermApi.php`

---

## Fase 7 — Resolução em runtime de aliases legado → canónico

**Data:** 2026-04-07  
**Status:** integrada ao `RbacComponent` (com flag em `config/rbac.php`).

### Comportamento

- Ao montar a lista de `permission_id` do utilizador (via papéis), se `Rbac.expand_legacy_aliases` for true (default), os códigos das linhas em `rbac_permissions` são consultados em `rbac_permission_legacy_aliases` (`legacy_code`); para cada `canonical_code` encontrado, os IDs correspondentes em `rbac_permissions` são **unidos** ao conjunto antes de `RbacChecker::matchAction`.
- Assim, um papel que só tem a macro legada (ex.: `clientes.manage`) passa a considerar também as linhas atómicas sincronizadas no catálogo, desde que existam linhas na tabela de aliases e **Sincronizar catálogo** tenha criado os códigos canónicos em `rbac_permissions`.
- Se `Rbac.legacy_permission_log` for true, quando o melhor match for um `code` que existe como `legacy_code` na tabela de aliases, regista-se `Log::info` com controller/ação (não bloqueia o pedido em caso de erro).

### Alterações realizadas

| Tipo | Descrição |
|------|-----------|
| Utility | `src/Utility/RbacPermissionResolver.php` — `expandPermissionIds`, `isLegacyBundleCode`, deteção segura da tabela de aliases. |
| Component | `RbacComponent::_findBestMatchingPermission` — expansão opcional + log opcional. |
| Config | `config/rbac.php` — `expand_legacy_aliases`, `legacy_permission_log`. |

### Operação

- Executar migrations (incl. `rbac_permission_legacy_aliases` e seeds).
- **Permissões → Sincronizar catálogo** para que `canonical_code` exista em `rbac_permissions`.

### Pendências

- Utilizadores **sem** papéis em `rbac_users_roles` continuam fora do fluxo RBAC por defeito (híbrido); ver **Fase 8** para bloqueio opcional pós-backfill.

---

## Fase 8 — Rollout RBAC (parcial)

**Data:** 2026-04-07  
**Status:** parcial (flags + `RbacComponent` + shell só de **diagnóstico**; sem backfill automático em massa).

### Comportamento

- **Híbrido (padrão):** sem linhas em `rbac_users_roles`, o componente devolve `null` e não nega por RBAC (o resto do fluxo Auth/`isAuthorized` mantém-se).
- **`log_unassigned_rbac_users`:** com `mode` `warn` ou `enforce`, cada pedido de utilizador autenticado sem papéis RBAC gera `Log::info` (útil em piloto para medir quem falta backfill).
- **`enforce_block_without_roles`:** com `mode=enforce`, se true, nega (Flash + redirect ao dashboard) quem não tem papéis; por defeito **`enforce_block_without_roles_equipe_only`** limita isto a `users.role === 0` (equipe), para não bloquear portal cliente sem vínculo RBAC.

### CLI (diagnóstico)

```text
bin/cake rbac_rollout stats
bin/cake rbac_rollout stats --csv
bin/cake rbac_rollout stats --include_inactive
bin/cake rbac_rollout unassigned_equipe
bin/cake rbac_rollout unassigned_equipe --limit=200
bin/cake rbac_rollout unassigned_equipe --csv
bin/cake rbac_rollout unassigned_portal --csv
bin/cake rbac_rollout assign_portal --role_slug=cliente_portal --dry-run
bin/cake rbac_rollout list_roles
bin/cake rbac_rollout list_roles --csv
bin/cake rbac_rollout user_effective --user_id=42
bin/cake rbac_rollout user_effective --user_id=42 --csv --full
bin/cake rbac_rollout who_has --code=clientes.view
bin/cake rbac_rollout who_has --code=dashboard.view --filter_role=0 --scan_limit=8000 --csv
bin/cake rbac_rollout menu_gates_check
bin/cake rbac_rollout menu_gates_check --strict
bin/cake rbac_rollout role_stats
bin/cake rbac_rollout role_stats --csv --all
bin/cake rbac_rollout audit_recent --limit=30
```

- **stats:** contagens por `users.role` (0 equipe, 1 portal) — total, quantos têm papéis RBAC **efetivos** (linha em `rbac_users_roles` e/ou grupo com `rbac_group_roles`), quantos não têm. Por defeito exclui `users.inativo=1`. Com **`--csv`**, duas linhas: cabeçalho e valores (`include_inactive`, `equipe_*`, `portal_*`).
- **unassigned_equipe:** lista até N utilizadores equipe **sem** papéis efetivos (`id`, `username`, `name`). Com **`--csv`**, só cabeçalho + linhas CSV em stdout (UTF-8; redirecionar para ficheiro), sem título decorativo; 0 linhas de dados = só cabeçalho.
- **unassigned_portal:** idem para `role=1`; CSV inclui coluna `idcliente`.
- **assign_portal:** como `assign_equipe`, mas filtra `users.role=1`; típico `--role_slug=cliente_portal` após validar `unassigned_portal`.
- **list_roles:** papéis em `rbac_roles` (por defeito só `active=1`); **`--all`** inclui inativos; **`--csv`** para export. Usar o `slug` em `assign_equipe` / `assign_portal`.
- **user_effective:** para um `users.id`, mostra papéis efetivos (diretos + grupos), contagens de `permission_id` e lista de códigos pós-`expand_legacy_aliases` (como o painel). **`--csv`**: linha meta + coluna `code`; **`--full`**: até 25000 códigos (default ~400 em texto).
- **who_has:** dado `--code=` existente em `rbac_permissions`, percorre até **`--scan_limit`** utilizadores (default 3000, max 20000) e lista quem tem a permissão segundo **`RbacChecker::userHasPermissionCode`** (aliases legados respeitados). **`--filter_role=0|1`**, **`--include_inactive`**, **`--csv`**.
- **menu_gates_check:** confirma que cada código em **`Rbac.menu_sidebar_gates`** existe em **`rbac_permissions`** (útil pós-deploy / antes de `RBAC_MENU_FILTER_SIDEBAR=1`). **`--strict`**: processo termina com código 1 se faltar algum; **`--csv`**: uma linha por par gate+código com `ok|missing`.
- **role_stats:** por papel (`rbac_roles`), conta linhas em **`rbac_roles_permissions`**. **`--all`**: incluir `active=0`; **`--csv`**.
- **audit_recent:** últimas linhas de `rbac_audit_authorizations` (requer `audit_decisions_db` ativo e migration Fase 3).

### Ficheiros

- `config/rbac.php`
- `src/Controller/Component/RbacComponent.php`
- `src/Shell/RbacRolloutShell.php`

### Checklist antes de `enforce_block_without_roles=true`

1. `rbac_rollout stats` — `sem=0` para equipe (ativos) ou política explícita para exceções.
2. **Permissões** — papéis com permissões corretas; **Sincronizar catálogo** se faltar código.
3. Piloto com `RBAC_MODE=warn` e, se útil, `log_unassigned_rbac_users=true`.
4. Só então `enforce` + `enforce_block_without_roles=true` (manter `equipe_only=true` salvo regra para portal).

### Pendências (Fase 8)

- Backfill em massa por SQL/migration específica do cliente ou atribuição via UI **Permissões**; matriz de papéis por função documentada por instalação.

---

## Fase 3 — Modelo expandido (parcial)

**Data:** 2026-04-07  
**Status:** parcial — schema + herança de papéis via grupos em runtime; sem CRUD admin nem consumo de políticas/campos/auditoria.

### Alterações

| Tipo | Descrição |
|------|-----------|
| Migration | `20260416100000_RbacPhase3GroupsPoliciesAudit`: coluna `hierarchy_level` (int, default 0) em `rbac_roles`; tabelas `rbac_groups`, `rbac_user_groups`, `rbac_group_roles`, `rbac_permission_policies`, `rbac_field_permissions`, `rbac_audit_authorizations`. |
| ORM | `RbacGroup`, `RbacUserGroup`, `RbacGroupRole` + respetivas Tables; `RbacRole` com `hierarchy_level` acessível. |
| Runtime | `RbacComponent::_userRoleIds` agrega `role_id` de `rbac_group_roles` para os `group_id` do utilizador em `rbac_user_groups`, se `Rbac.expand_group_roles` for true (default) e as tabelas existirem. |
| Config | `config/rbac.php` — `expand_group_roles`. |
| Verificação | `scripts/postgres/verify_portal_schema.php` — novas tabelas RBAC na lista. |

### Operação

- Executar `bin/cake migrations migrate`.
- Popular `rbac_groups`, `rbac_group_roles`, `rbac_user_groups` via SQL ou futura UI (Fase 4). Até lá o comportamento permanece igual a não haver grupos.

### Pendências (Fase 3)

- Regras de `hierarchy_level` (anti-escalação) em código.
- Preencher e avaliar `rbac_permission_policies.conditions_json` (Fase 5); `rbac_field_permissions` (Fase 4/6); escrita em `rbac_audit_authorizations` (Fase 9).

---

## Fase 4 — Admin (parcial)

**Data:** 2026-04-07  
**Status:** parcial — gestão de **grupos RBAC** no painel; matriz papel×permissão e visão “permissões efetivas” unificada continuam como antes.

### Funcionalidades

- **adminGroups** — lista grupos, contagens de membros e papéis no grupo.
- **adminGroupEdit** — criar/editar grupo (`slug`, `nome`, descrição, ordem, ativo). Grupos `is_system` não alteram slug pelo formulário; não podem ser excluídos.
- **adminGroupRoles** — checkboxes de papéis por grupo (`rbac_group_roles`).
- **adminGroupUsers** — checkboxes de utilizadores equipe por grupo (`rbac_user_groups`).
- **adminGroupDelete** — POST; recusa se `is_system`.

### Catálogo

- `permissoes.groups.manage` e `usuarios.groups.assign` (mesmas actions) em `config/permissions_registry.php`. **Sincronizar catálogo** após deploy.

### Ficheiros

- `src/Controller/PermissoesController.php` (incl. `adminUserEffective`)
- `src/Utility/RbacUserRolesResolver.php` (papéis efetivos; usado também pelo `RbacComponent`)
- `src/Template/Permissoes/admin_groups.ctp`, `admin_group_edit.ctp`, `admin_group_roles.ctp`, `admin_group_users.ctp`, `admin_user_effective.ctp`
- `src/Template/Permissoes/admin_index.ctp`, `admin_users.ctp`, `admin_user_roles.ctp`, `Config/index.ctp`
- `src/Controller/AppController.php` (`Security` — `adminUserEffective`)
- `config/permissions_registry.php` (`permissoes.users.effective`, `usuarios.roles.assign`)

### Pendências (Fase 4)

- Matriz papel×permissão: coluna **efetiva por utilizador** em `adminMatrix` via `?user_id=` ou filtro no ecrã (papéis + grupos + `expand_legacy_aliases`; exclui `rbac_permission_policies`).
- **Políticas e campos:** UI já existe (`adminPermissionPolicies` / `permissoes.policies.manage`; `adminFieldPermissions` / `permissoes.fields.manage`). **Adoção incremental:** `Clientes.field.api_token` + `Clientes.field.inativo` (view + POST em `ClientesController::edit`); continuar por módulo e piloto de políticas em permissões de risco.

### Relatório efetivo (utilizador)

- `adminUserEffective` — papéis diretos, grupos, contagens de IDs na matriz / após aliases, catálogo por módulo.
- `RbacUserRolesResolver::effectiveRoleIds()` — mesma lógica que o `RbacComponent` (grupos + diretos).
- Registry: `permissoes.users.effective`; `usuarios.roles.assign` inclui esta action.

---

## Fase 9 — Auditoria (parcial)

**Data:** 2026-04-07  
**Status:** parcial — decisões do **RbacComponent** gravadas na tabela `rbac_audit_authorizations`; sem ecrã admin nem política de retenção/purge.

### Configuração

- `Rbac.audit_decisions_db` em `config/rbac.php`:
  - `false` (padrão): não grava.
  - `true`: negações — sem permissão correspondente (`reason: no_matching_permission`); bloqueio `enforce_block_without_roles` (`no_rbac_roles`); em modo **warn** o fluxo também grava como negação antes do `Log::warning`.
  - `'all'`: além disso, **concessões** (cada pedido com papéis RBAC que passa — **volume alto**).

### Campos

- `user_id`, `granted`, `controller`, `action`, `permission_code` (só em concessão), `context_json` (`role_ids`, `reason` se negação).

### Ficheiros

- `src/Controller/Component/RbacComponent.php`
- `src/Model/Table/RbacAuditAuthorizationsTable.php`, `Entity/RbacAuditAuthorization.php`
- `config/rbac.php`
- `src/Shell/RbacRolloutShell.php` (`audit_recent`)
- `src/Controller/PermissoesController.php` (`adminRbacAudit`, `Paginator`)
- `src/Template/Permissoes/admin_rbac_audit.ctp`, `admin_index.ctp`
- `config/permissions_registry.php` (`permissoes.audit.view`)

### Pendências (Fase 9)

- Job ou documentação de retenção (truncate/arquivo).
- Auditoria fora do RBAC de rota (ex.: APIs, alterações sensíveis em domínio).

### UI (lista)

- `PermissoesController::adminRbacAudit` — tabela paginada, link a partir do catálogo de permissões.
- Catálogo: `permissoes.audit.view` — **Sincronizar catálogo** após deploy.

---

## Fase 5b — Condições JSON em políticas (runtime opcional)

**Data:** 2026-04-07 (runtime 2026-04-07)  
**Status:** biblioteca + testes + integração no `RbacComponent` quando `evaluate_permission_policies` é **true** (neste repositório **true** por defeito em `config/rbac.php`; `RBAC_EVALUATE_POLICIES` sobrepõe).

### Entregáveis

| Tipo | Descrição |
|------|-----------|
| Utility | `RbacPolicyConditions::matches` / `matchesOrEmpty` — conjunção `all` com regras `eq` ou `in` sobre mapa plano `path` → valor. |
| Testes | `tests/TestCase/Utility/RbacPolicyConditionsTest.php` |
| Config | `config/rbac.php` — `evaluate_permission_policies` (bool). |
| Runtime | Após match de `rbac_permissions`, se existirem linhas **active** em `rbac_permission_policies` para esse `permission_id`, exige **pelo menos uma** linha com `conditions_json` vazio/null ou `matches` true (OR). Sem linhas = sem alteração. |
| ORM | `RbacPermissionPolicy`, `RbacPermissionPoliciesTable`. |
| Auditoria | Negação por política → `rbacDenyReason` = `policy_denied` (audit DB). |

### Próximo passo (Fase 5)

- UI **Permissões → Políticas** (`adminPermissionPolicies`) para CRUD de `rbac_permission_policies` — já no painel; requer migration Fase 3 e **Sincronizar catálogo** para `permissoes.policies.manage`.
- Enriquecer `_policyContextForRequest` no `RbacComponent` com mais chaves (além de `user.idempresa`, etc.) quando políticas de negócio precisarem (ex.: atributos de sessão ou empresa carregada).

---

## Fases 10 e 11 (incremento)

**Data:** 2026-04-07; **Fase 10 HTTP:** 2026-04-08+ (atualizado conforme repo)

- **Fase 10 (unitário + ORM):** `RbacPolicyConditionsTest`, `RbacCheckerTest`, `RbacPhpConfigTest`, suites `rbac` e `rbac-integration` (`RbacEffectivePermissionIdsSqliteTest`).
- **Fase 10 (HTTP — P3 trilho B, parcial):** suite **`rbac-http`**, bootstrap `tests/bootstrap_http.php`; **`RbacPermissoesHttpTest`**, **`RbacAreasHttpTest`**, **`RbacEmpresasusersHttpTest`**, **`RbacProblemasHttpTest`**, **`RbacFeriadosHttpTest`**, **`RbacContratosHorasHttpTest`**, **`RbacNormasempresaHttpTest`** (`Normasempresa::index` + `normasempresa.read`, `::acessoremoto` + `normasempresa.acessoremoto`), **`RbacFinanceiroHttpTest`** (`Financeiro::index` + `financeiro.view`), **`RbacFaturamentoHttpTest`** (`Faturamento::index` + `faturamento.view`), **`RbacClientesHttpTest`** (`Clientes::index` + `clientes.view`), **`RbacPrefaturamentoHttpTest`** (`Prefaturamento::index` + `prefaturamento.queue`), **`RbacBancosenhasHttpTest`** (`Bancosenhas::index` + `bancosenhas.view`), **`RbacEmpresasHttpTest`** (`Empresas::index` + `empresas.view`), **`RbacOrcamentosHttpTest`** (`Orcamentos::index` + `orcamentos.view`; tabela ORM `orcamentosnovosdes`), **`RbacProdutosHttpTest`** (`Produtos::index` + `produtos.view`; seed `rbacHttpSqliteSeedEmpresaMin` + coluna `empresas.urlerp`, tabela `produtos`; SOAP ERP pode falhar — ignorado no controlador), **`RbacVisitasHttpTest`** (`Visitas::index` + `visitas.view`; DDL `visitas` + `listamembros`), **`RbacOrdensservicoHttpTest`** (`Ordensservico::index` + `ordensservico.list`; equipe `admin=0` — evita `bypass_legacy_super` dos painéis só `users.admin`); trait `RbacHttpSqliteFixtureTrait` (DDL incl. `ordensservico` com colunas mínimas do índice OS, `produtos`, `visitas`, `listamembros`, `bancosenhas`, `clientes`, `faturamento`, `financeiro_lancamentos`, `orcamentosnovosdes`, `users`, `empresasusers`, `empresas` com colunas mínimas de listagem, `areas`, `contratos_horas`, `feriados`, `problemas`, `rbac_*`). **Próximas extensões sugeridas:** (1) mais controllers (ex.: `Relatorios` com fixtures tickets/contratos); (2) política `conditions_json` com contexto HTTP; (3) opcional PostgreSQL de teste.
- **Fase 11:** `docs/TEST_CHECKLIST_RBAC.md`, `AUTH_MODEL.md`, `LEGACY_COMPATIBILITY.md`, `RBAC_ABAC_MATRIX.md` e links em `DOC3_RBAC_ABAC.md` — em grande parte feitos; manter alinhados ao catálogo após cada release (**P3 trilho C**: rever DOC3 § exemplos vs `permissions_registry` por release).

### Ordem sugerida para as “próximas fases” (equipa)

| Fase prioritária | Quem / o quê | Notas |
|-----------------|--------------|--------|
| **P0** | Operação + DBA | Backfill `rbac_users_roles`, `unassigned_equipe` → zero ou exceções aprovadas, `pre_deploy` exit 0 — não depende de código novo no repo. |
| **P1** | Operação + staging | `RBAC_ENFORCE_BLOCK_WITHOUT_ROLES` após P0; matriz efetiva já suportada na UI — validar negócio. |
| **P2** | Dev incremental | Pacotes políticas / campos / gaps de catálogo (issues I–K); um pacote por sprint. |
| **P3** | Dev + docs | Trilho A: auditoria fora do `RbacComponent`; trilho B: mais testes HTTP; trilho C: DOC3/registry. |

---

## Modelo de entrada para novas fases

Ao documentar uma fase futura, usar o mesmo formato das secções “Fase X” acima: diagnóstico, alterações, ficheiros, migrations, observações, pendências. O **Roadmap** no topo mantém o estado geral; cada fase concluída ganha a sua secção cronológica abaixo.
