<?php
/**
 * RBAC/ABAC em runtime.
 *
 * mode:
 *   off     — não aplica (padrão).
 *   warn    — registra no log quando o acesso seria negado; não bloqueia.
 *   enforce — bloqueia se o usuário tiver papéis em rbac_users_roles e não tiver permissão; sem papéis RBAC
 *             o pedido segue (híbrido), salvo enforce_block_without_roles (Fase 8).
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
 * Diagnóstico: bin/cake rbac_rollout stats | unassigned_equipe (ver IMPLEMENTATION_LOG Fase 8).
 *
 * Fase 9 (parcial): audit_decisions_db — gravar decisões em rbac_audit_authorizations.
 *   false (padrão) — não grava.
 *   true — só negações (warn/enforce sem permissão; bloqueio enforce_block_without_roles).
 *   'all' — também concessões (volume alto; só diagnóstico temporário).
 */
$mode = 'off';
if (function_exists('env')) {
	$e = env('RBAC_MODE');
	if ($e !== null && $e !== '') {
		$mode = strtolower(trim((string)$e));
	}
}

return [
	'Rbac' => [
		'mode' => in_array($mode, ['off', 'warn', 'enforce'], true) ? $mode : 'off',
		// Administrador legado (admin=1, role=0 equipe) ignora RBAC
		'bypass_legacy_super' => true,
		// Ações que começam com estes prefixos ignoram RBAC (APIs JSON, etc.)
		'skip_action_prefixes' => ['api'],
		// Controller#action ou controller#* (minúsculo)
		'whitelist' => [
			'users#login',
			'users#logout',
			'users#loginempresa',
			'users#acessoempresa',
			'users#alteraempresa',
			'users#loginduasetapas',
			'users#desativaverificacao',
			'users#verificalogincadastro',
			'users#dashboard',
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
			// JSON de catálogo (mesma regra que solicitar; gate em OrcamentosController::beforeFilter)
			'orcamentos#catalogosugestoes',
			// Notificações internas (JSON; equipe autenticada)
			'portalnotifications#unreadcount',
			'portalnotifications#listjson',
			'portalnotifications#markread',
			'portalnotifications#markallread',
			'portalnotifications#preferences',
			'portalnotifications#savepreferences',
		],
		// Em modo warn, também exibir Flash (pode ser repetitivo)
		'warn_flash' => false,
		// Incluir permissões canónicas (rbac_permission_legacy_aliases) quando o papel tiver só códigos legados
		'expand_legacy_aliases' => true,
		// Log info quando a permissão que casou for um código legacy presente na tabela de aliases
		'legacy_permission_log' => false,
		// Fase 8: pedidos de utilizador autenticado sem rbac_users_roles (mode ≠ off)
		'log_unassigned_rbac_users' => false,
		// Fase 8: em enforce, negar acesso se não houver papéis (tipicamente após backfill de equipe)
		'enforce_block_without_roles' => false,
		// Se true, enforce_block_without_roles só aplica a equipe (users.role === 0)
		'enforce_block_without_roles_equipe_only' => true,
		// Incluir role_id vindos de rbac_group_roles para os grupos do utilizador (rbac_user_groups)
		'expand_group_roles' => true,
		// Gravar decisões RBAC na tabela rbac_audit_authorizations: false | true (só negações) | 'all'
		'audit_decisions_db' => false,
		// Fase 5 (futuro): avaliar rbac_permission_policies.conditions_json após match de rota — não ligado em runtime ainda
		// 'evaluate_permission_policies' => false,
	],
];
