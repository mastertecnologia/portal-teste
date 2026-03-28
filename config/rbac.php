<?php
/**
 * RBAC/ABAC em runtime.
 *
 * mode:
 *   off     — não aplica (padrão).
 *   warn    — registra no log quando o acesso seria negado; não bloqueia.
 *   enforce — bloqueia se o usuário tiver papéis em rbac_users_roles e não tiver permissão.
 *
 * Variável de ambiente opcional: RBAC_MODE=off|warn|enforce
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
		],
		// Em modo warn, também exibir Flash (pode ser repetitivo)
		'warn_flash' => false,
	],
];
