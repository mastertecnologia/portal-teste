<?php
/**
 * RBAC/ABAC em runtime.
 *
 * mode:
 *   off     — não aplica.
 *   warn    — registra no log quando o acesso seria negado; não bloqueia.
 *   enforce — bloqueia se o usuário tiver papéis em rbac_users_roles e não tiver permissão; sem papéis RBAC
 *             o pedido segue (híbrido), salvo enforce_block_without_roles (Fase 8).
 *   Padrão neste ficheiro (sem RBAC_MODE): enforce; menu e auditoria alinhados ao rollout completo (ver literais $file*).
 *
 * Variável de ambiente opcional: RBAC_MODE=off|warn|enforce
 *
 * Fase 8 (rollout):
 *   - Comportamento híbrido padrão: utilizador sem linhas em rbac_users_roles não passa por negação RBAC
 *     (continua a depender de isAuthorized / legado).
 *   - log_unassigned_rbac_users — em mode warn|enforce, regista Log::info em cada pedido de quem não tem papéis RBAC (pilotos).
 *   - enforce_block_without_roles — em mode enforce, negar quem não tem papéis (após backfill); usar com cuidado.
 *   - enforce_block_without_roles_equipe_only — default true: o bloqueio anterior aplica-se só a users.role===0 (equipe);
 *     utilizadores portal (role 1) sem rbac_users_roles não são bloqueados por esta regra.
 *
 * Diagnóstico: bin/cake rbac_rollout stats | unassigned_equipe | report (ver IMPLEMENTATION_LOG Fase 8).
 *
 * Fase 9 (parcial): audit_decisions_db — gravar decisões em rbac_audit_authorizations.
 *   false — não grava.
 *   true — só negações (warn/enforce sem permissão; bloqueio enforce_block_without_roles). Neste repo: $fileAuditDecisionsDb=true.
 *   'all' — também concessões (volume alto; só diagnóstico temporário).
 *   audit_retention_days — inteiro opcional usado como defeito pelo comando
 *   `bin/cake rbac_rollout audit_purge` quando não passa --days; null = obrigatório --days no CLI.
 *
 * Fase 5 (runtime): evaluate_permission_policies — após casar rbac_permissions, se existirem linhas
 *   ativas em rbac_permission_policies para esse permission_id, o acesso só é permitido se **pelo menos uma**
 *   linha tiver conditions_json vazio/null (sem restrição extra) ou `RbacPolicyConditions::matches` true.
 *   Sem linhas na tabela para essa permissão = comportamento anterior (sem política extra). Neste repo: $fileEvaluatePermissionPolicies=true.
 *   Contexto plano para conditions_json (ver RbacComponent): user.id, user.username, user.role, user.admin,
 *   user.idempresa, user.idcliente, user.setor, request.prefix, request.plugin.
 *
 * Fase 6 (menu): menu_filter_config — se true, o atalho do hub Config (sidebar) para equipe admin só some
 *   para quem já tem papéis RBAC/grupos e não tem código config.manage (híbrido: sem papéis RBAC mantém o atalho).
 *   Neste repo: $fileMenuFilterConfig=true (sobrescrever com RBAC_MENU_FILTER_CONFIG).
 *
 * Fase 6b (sidebar): menu_filter_sidebar + menu_sidebar_gates — se true, blocos do menu (equipe) exigem
 *   pelo menos um dos códigos por chave (OR), salvo híbrido sem papéis. Neste repo: $fileMenuFilterSidebar=true.
 *   sidebar_functions_search — bloco «Buscar funções» (pesquisa.sidebar_search); portal (role≠0) ignora o gate na UI
 *   mas precisa do código no papel se RBAC enforce e AJAX Pesquisa/* (ex.: cliente_portal na migration).
 *
 * Fase 6c (submenus): chaves extra por ligação (ex.: ordensservico_list vs ordensservico_nova; tickets_servicedesk
 *   vs tickets_historico; relatorios_painel vs relatorios_indicadores_adv). O grupo colapsável mostra-se se qualquer
 *   sub-gate for true.
 *
 * Fase 6d (submenu módulo avançado): advanced_module_gestao | advanced_module_modelos | advanced_module_faturas —
 *   cada ligação do grupo tem OR de códigos alinhados ao catálogo (gestão /modulo-contratos, modelos, faturas).
 *
 * Fase 6e (pré-faturamento): prefaturamento_fila vs prefaturamento_conferencia — o menu lateral mantém uma ligação à fila
 *   (index); a coluna/formulários de conferências na mesma página respeitam prefaturamento_conferencia. Visível se fila OU
 *   conferência; prefaturamento.manage cobre ambos.
 *
 * Fase 6f (UI compacta): sidebar_notifications_bell (equipa, topo) — portal.notifications*; footer_acesso_remoto — ligação
 *   Normasempresa::acessoremoto no dropdown do rodapé (normasempresa.acessoremoto); footer_perfil_senha — atalhos perfil/senha
 *   (users.profile | users.password); footer_twofactor_menu — “Verificação login” / 2FA (users.twofactor).
 *
 * Modelo de linhas para `.env`: `config/rbac.env.example` (comentado; copiar o necessário).
 *
 * Piloto por ambiente (sobrepõem os literais abaixo quando definidas):
 *   RBAC_MENU_FILTER_CONFIG=1|true|yes|on ou =0|false|no|off
 *   RBAC_MENU_FILTER_SIDEBAR=1|true|yes|on ou =0|false|no|off
 *   RBAC_LOG_UNASSIGNED_USERS=1|true|yes|on ou =0|false|no|off — Fase 8 (com RBAC_MODE warn|enforce)
 *   RBAC_AUDIT_DECISIONS_DB=0|false|no|off | 1|true|yes|on|deny | all — Fase 9 (grava rbac_audit_authorizations)
 *   RBAC_ENFORCE_BLOCK_WITHOUT_ROLES=1|true|yes|on — Fase 8; só após backfill; combina com enforce
 *   RBAC_EVALUATE_POLICIES=1|true|yes|on ou =0|false|no|off — Fase 5 (rbac_permission_policies em runtime)
 *   RBAC_WARN_FLASH=1|true|yes|on ou =0|false|no|off — Flash de aviso em modo warn (pode ser repetitivo)
 *
 * Ações JSON com prefixo "api" (skip_action_prefixes): por defeito ficam fora do RBAC de rota.
 * rbac_api_enforced_actions — lista opcional de "controller#action" (minúsculo) para as quais o RBAC
 *   aplica-se mesmo começando com "api" (ex.: após mapear permissões no catálogo). Default [].
 */
$mode = 'enforce';
if (function_exists('env')) {
	$e = env('RBAC_MODE');
	if ($e !== null && $e !== '') {
		$mode = strtolower(trim((string)$e));
	}
}

$__rbacTriStateEnv = function ($key) {
	if (!function_exists('env')) {
		return null;
	}
	$v = env($key);
	if ($v === null || trim((string)$v) === '') {
		return null;
	}
	$s = strtolower(trim((string)$v));
	if (in_array($s, ['1', 'true', 'yes', 'on'], true)) {
		return true;
	}
	if (in_array($s, ['0', 'false', 'no', 'off'], true)) {
		return false;
	}

	return null;
};

$fileMenuFilterConfig = true;
$fileMenuFilterSidebar = true;
$menuFilterConfig = $__rbacTriStateEnv('RBAC_MENU_FILTER_CONFIG');
$menuFilterConfig = $menuFilterConfig === null ? $fileMenuFilterConfig : $menuFilterConfig;
$menuFilterSidebar = $__rbacTriStateEnv('RBAC_MENU_FILTER_SIDEBAR');
$menuFilterSidebar = $menuFilterSidebar === null ? $fileMenuFilterSidebar : $menuFilterSidebar;

$__rbacAuditDecisionsEnv = function () {
	if (!function_exists('env')) {
		return null;
	}
	$v = env('RBAC_AUDIT_DECISIONS_DB');
	if ($v === null || trim((string)$v) === '') {
		return null;
	}
	$s = strtolower(trim((string)$v));
	if (in_array($s, ['0', 'false', 'no', 'off', 'none'], true)) {
		return false;
	}
	if ($s === 'all') {
		return 'all';
	}
	if (in_array($s, ['1', 'true', 'yes', 'on', 'deny', 'denials', 'negations'], true)) {
		return true;
	}

	return null;
};

$fileLogUnassignedRbacUsers = false;
$logUnassignedRbacUsers = $__rbacTriStateEnv('RBAC_LOG_UNASSIGNED_USERS');
$logUnassignedRbacUsers = $logUnassignedRbacUsers === null ? $fileLogUnassignedRbacUsers : $logUnassignedRbacUsers;

$fileAuditDecisionsDb = true;
$auditDecisionsDb = $__rbacAuditDecisionsEnv();
$auditDecisionsDb = $auditDecisionsDb === null ? $fileAuditDecisionsDb : $auditDecisionsDb;

$fileEnforceBlockWithoutRoles = false;
$enforceBlockWithoutRoles = $__rbacTriStateEnv('RBAC_ENFORCE_BLOCK_WITHOUT_ROLES');
$enforceBlockWithoutRoles = $enforceBlockWithoutRoles === null ? $fileEnforceBlockWithoutRoles : $enforceBlockWithoutRoles;

$fileEvaluatePermissionPolicies = true;
$evaluatePermissionPolicies = $__rbacTriStateEnv('RBAC_EVALUATE_POLICIES');
$evaluatePermissionPolicies = $evaluatePermissionPolicies === null ? $fileEvaluatePermissionPolicies : $evaluatePermissionPolicies;

$fileWarnFlash = false;
$warnFlash = $__rbacTriStateEnv('RBAC_WARN_FLASH');
$warnFlash = $warnFlash === null ? $fileWarnFlash : $warnFlash;

return [
	'Rbac' => [
		'mode' => in_array($mode, ['off', 'warn', 'enforce'], true) ? $mode : 'off',
		// Administrador legado (admin=1, role=0 equipe) ignora RBAC
		'bypass_legacy_super' => true,
		// Ações que começam com estes prefixos ignoram RBAC (APIs JSON, etc.)
		'skip_action_prefixes' => ['api'],
		// Exceções: estas controller#action passam pelo RBAC mesmo com prefixo "api" (minúsculo).
		// Piloto Tickets React (equipe): RBAC de rota quando RBAC_MODE ≠ off. Mapeamento → permissions_registry.
		'rbac_api_enforced_actions' => [
			'tickets#apiindex',
			'tickets#apiview',
			'tickets#apicomments',
			'tickets#apidashboardoperacional',
			'tickets#apitecnicoslista',
			'tickets#apitransferirticket',
			'tickets#apistartticket',
			'tickets#apianexoupload',
			'tickets#apianexodelete',
			'tickets#apitimer',
			'tickets#apisaveticket',
			// Portal cliente — lista JSON (híbrido: só aplica se o utilizador tiver papéis RBAC)
			'tickets#apiindexcliente',
			// Filas (JSON interno; catálogo queues.json.read / queues.json.write)
			'queues#apiindex',
			'queues#apiforticket',
			'queues#getavailablequeues',
			'queues#apisave',
			'queues#apiensuredefaults',
			'queues#apisupportlevels',
			// Comentários JSON (Ticketcomentarios; equipe + portal)
			'ticketcomentarios#apiadd',
		],
		// Controller#action ou controller#* (minúsculo)
		'whitelist' => [
			'users#login',
			'users#logout',
			'users#loginempresa',
			'users#acessoempresa',
			'users#loginduasetapas',
			'users#desativaverificacao',
			'users#verificalogincadastro',
			// Destino do redirect em enforce quando RBAC nega (evita loop com users/dashboard sob catálogo).
			'users#accessdenied',
			// Preferências de UI (AJAX / sem CSRF típico); não exigem permissão de catálogo.
			'users#selecttheme',
			'users#selectsidebar',
			'users#pagelength',
			'users#selecttemplate',
			'users#resetpassword',
			'pgmassets#css',
			'pgmassets#legacycss',
			'error#*',
			'pages#*',
			// APIs integração ERP (auth por token / sem RBAC de sessão típico)
			'ordensservico#listapi',
			'ordensservico#refreshapi',
			'clientes#addapi',
			'clientes#listapi',
			'produtos#addapi',
			'produtos#listapi',
			'clicontratos#addapi',
			'clicontratos#listapi',
			// catalogoSugestoes: fora da whitelist — orcamentos.solicitar / portal.view / view (equipe passa RBAC antes do redirect)
			// PortalNotifications: fora da whitelist — exige portal.notifications.read/write (migration 20260421140000 nos papéis padrão equipe)
		],
		// Em modo warn, também exibir Flash (pode ser repetitivo); env RBAC_WARN_FLASH
		'warn_flash' => $warnFlash,
		// Incluir permissões canónicas (rbac_permission_legacy_aliases) quando o papel tiver só códigos legados
		'expand_legacy_aliases' => true,
		// Log info quando a permissão que casou for um código legacy presente na tabela de aliases
		'legacy_permission_log' => false,
		// Fase 8: pedidos de utilizador autenticado sem rbac_users_roles (mode ≠ off); env RBAC_LOG_UNASSIGNED_USERS
		'log_unassigned_rbac_users' => $logUnassignedRbacUsers,
		// Fase 8: em enforce, negar acesso se não houver papéis (tipicamente após backfill de equipe); env RBAC_ENFORCE_BLOCK_WITHOUT_ROLES
		'enforce_block_without_roles' => $enforceBlockWithoutRoles,
		// Se true, enforce_block_without_roles só aplica a equipe (users.role === 0)
		'enforce_block_without_roles_equipe_only' => true,
		// Incluir role_id vindos de rbac_group_roles para os grupos do utilizador (rbac_user_groups)
		'expand_group_roles' => true,
		// Gravar decisões RBAC na tabela rbac_audit_authorizations: false | true (só negações) | 'all'; env RBAC_AUDIT_DECISIONS_DB
		'audit_decisions_db' => $auditDecisionsDb,
		// Retenção (dias): valor por defeito para `rbac_rollout audit_purge` quando --days omitido; null = exige --days
		'audit_retention_days' => 90,
		// Fase 5: políticas extra por permissão (rbac_permission_policies); env RBAC_EVALUATE_POLICIES
		'evaluate_permission_policies' => $evaluatePermissionPolicies,
		// Fase 6: filtrar atalho Config na sidebar por permissão config.manage (ver docblock; env RBAC_MENU_FILTER_CONFIG)
		'menu_filter_config' => $menuFilterConfig,
		// Fase 6b: filtrar blocos da sidebar (só equipe admin; ver docblock; env RBAC_MENU_FILTER_SIDEBAR)
		'menu_filter_sidebar' => $menuFilterSidebar,
		'menu_sidebar_gates' => [
			// Só aplica a equipe com users.admin (ver RbacChecker::shouldShowSidebarGate); alinhado a users/dashboard no catálogo.
			'dashboard' => 'dashboard.view',
			'sidebar_functions_search' => 'pesquisa.sidebar_search',
			// Fase 6c: painel clássico vs indicadores avançados (cada ligação); secção visível se qualquer for true.
			'relatorios_painel' => 'relatorios.painel.view',
			'relatorios_indicadores_adv' => 'relatorios.indicadores.view',
			'clientes' => 'clientes.view',
			'produtos' => 'produtos.view',
			// Fase 6c: listar vs nova OS; secção visível se list OU nova.
			'ordensservico_list' => ['ordensservico.list', 'ordensservico.full'],
			'ordensservico_nova' => ['ordensservico.create', 'ordensservico.full'],
			// Fase 6c: Service Desk vs histórico clássico; secção visível se qualquer for true.
			'tickets_servicedesk' => ['servicedesk.view', 'servicedesk.tickets'],
			'tickets_historico' => ['tickets.view', 'tickets.api'],
			'queues' => ['queues.admin', 'queues.admin.panel'],
			'orcamentos' => 'orcamentos.view',
			// Fase 6e: fila (index) vs conferência; secção visível se fila OU conferência.
			'prefaturamento_fila' => ['prefaturamento.queue', 'prefaturamento.manage'],
			'prefaturamento_conferencia' => ['prefaturamento.conferencia', 'prefaturamento.manage'],
			'faturamento' => 'faturamento.view',
			'financeiro' => 'financeiro.view',
			// Fase 6d: gestão vs modelos vs faturas; secção visível se qualquer sub-gate for true.
			'advanced_module_gestao' => ['erp.contracts.management', 'erp.advanced.contracts'],
			'advanced_module_modelos' => ['erp.contracts.templates', 'erp.advanced.contracts'],
			'advanced_module_faturas' => ['erp.advanced.invoices', 'erp.advanced.invoices.view'],
			'faturas_locacao' => ['faturas.view', 'faturas.locacao'],
			'visitas_agenda' => ['visitas.view', 'agenda.visitas', 'agenda.alias'],
			'bancosenhas' => ['bancosenhas.view', 'bancosenhas.manage'],
			// Fase 6f: sino notificações (equipa); dropdown rodapé — acesso remoto, perfil/senha, 2FA.
			'sidebar_notifications_bell' => ['portal.notifications', 'portal.notifications.read', 'portal.notifications.write'],
			'footer_acesso_remoto' => 'normasempresa.acessoremoto',
			'footer_perfil_senha' => ['users.profile', 'users.password'],
			'footer_twofactor_menu' => 'users.twofactor',
		],
	],
];
