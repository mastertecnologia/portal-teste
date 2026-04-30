<?php
namespace App\Service;

use App\Utility\RbacChecker;
use App\Utility\RbacEffectivePermissionIds;
use App\Utility\RbacPolicyConditions;
use App\Utility\RbacUserRolesResolver;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Simula o encadeamento RBAC/ABAC (catálogo + políticas) sem alterar sessão nem conceder acesso.
 * Alinhado a {@see \App\Controller\Component\RbacComponent} e {@see \App\Utility\AbacQuery}.
 */
class AccessDiagnosticService {

	/** Sessão: captura mínima quando RBAC (enforce) nega antes do redirect a Users/accessDenied. */
	public const SESSION_ACCESS_DENIED_CAPTURE = 'RbacAccessDeniedDiagnostic';

	/**
	 * Monta o relatório completo a partir da captura gravada pelo {@see \App\Controller\Component\RbacComponent}
	 * (mesmo utilizador e janela de tempo validada no controller).
	 *
	 * @param array<string, mixed> $capture user_id, controller, action, reason, ts, prefix?, plugin?
	 * @param array<string, mixed> $userRow Auth->user()
	 * @return array<string, mixed>|null
	 */
	public function diagnoseFromDenialCapture(array $capture, array $userRow): ?array {
		$capUid = (int)($capture['user_id'] ?? 0);
		$sessUid = (int)($userRow['id'] ?? 0);
		if ($capUid <= 0 || $sessUid <= 0 || $capUid !== $sessUid) {
			return null;
		}
		$ctrl = trim((string)($capture['controller'] ?? ''));
		$act = trim((string)($capture['action'] ?? ''));
		if ($ctrl === '' || $act === '') {
			return null;
		}
		$prefix = isset($capture['prefix']) ? (string)$capture['prefix'] : '';
		$plugin = isset($capture['plugin']) ? (string)$capture['plugin'] : '';
		$out = $this->diagnose($userRow, $ctrl, $act, [
			'prefix' => $prefix,
			'plugin' => $plugin,
		]);
		$out['capture'] = [
			'user_id' => $capUid,
			'controller' => $ctrl,
			'action' => $act,
			'reason' => (string)($capture['reason'] ?? ''),
			'ts' => isset($capture['ts']) ? (int)$capture['ts'] : 0,
			'prefix' => $prefix,
			'plugin' => $plugin,
		];
		$capReason = $out['capture']['reason'];
		$simReason = $out['deny_reason'];
		if ($capReason !== '' && $simReason !== null && $capReason !== $simReason) {
			$out['diagnosis_reason_mismatch_note'] = 'Motivo na sessão (' . $capReason . ') difere da simulação agora (' . $simReason . '); o da sessão reflete a negação real.';
		}
		$pCtx = $this->_policyContext($userRow, strtolower(trim($prefix)), strtolower(trim($plugin)));
		$out['policy_conditions_eval'] = $this->_policyRowsEvalForDisplay($out['rbac_policies'] ?? [], $pCtx);

		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $policyRows
	 * @return array<int, array<string, mixed>>
	 */
	protected function _policyRowsEvalForDisplay(array $policyRows, array $ctx): array {
		$rows = [];
		foreach ($policyRows as $pol) {
			$json = $pol['conditions_json'] ?? null;
			$s = $json === null ? '' : trim((string)$json);
			if ($s === '') {
				$rows[] = [
					'policy_id' => (int)($pol['id'] ?? 0),
					'conditions_json' => null,
					'matched' => true,
					'detail' => 'JSON vazio: linha não restringe (OR no runtime, matchesOrEmpty).',
				];

				continue;
			}
			$matched = RbacPolicyConditions::matches($s, $ctx);
			$rows[] = [
				'policy_id' => (int)($pol['id'] ?? 0),
				'conditions_json' => $json,
				'matched' => $matched,
				'detail' => $matched
					? 'Condições satisfeitas para o contexto atual (user.* / request.*).'
					: 'Condições não satisfeitas — esta linha não libera o OR.',
			];
		}

		return $rows;
	}

	/**
	 * @param array $userRow registo users (Auth-like): id, username, name, role, admin, idempresa, idcliente, setor
	 * @param string $controller nome canónico do controller (ex.: Clientes)
	 * @param string $action ação (ex.: index)
	 * @param array $options 'prefix' => string, 'plugin' => string (contexto para rbac_permission_policies)
	 * @return array<string, mixed>
	 */
	public function diagnose(array $userRow, string $controller, string $action, array $options = []): array {
		$uid = (int)($userRow['id'] ?? 0);
		$cRaw = trim($controller);
		$aRaw = trim($action);
		$c = strtolower($cRaw);
		$a = strtolower($aRaw);
		$prefixOpt = isset($options['prefix']) ? strtolower(trim((string)$options['prefix'])) : '';
		$pluginOpt = isset($options['plugin']) ? strtolower(trim((string)$options['plugin'])) : '';

		$out = [
			'ok' => true,
			'disclaimer' => 'Modo simulação: não altera papéis, permissões nem sessão. Valide no catálogo RBAC antes de aplicar mudanças.',
			'user' => [
				'id' => $uid,
				'username' => (string)($userRow['username'] ?? ''),
				'name' => (string)($userRow['name'] ?? ''),
				'role' => (int)($userRow['role'] ?? -1),
				'admin' => !empty($userRow['admin']),
				'idempresa' => $userRow['idempresa'] ?? null,
				'idcliente' => $userRow['idcliente'] ?? null,
			],
			'route' => ['controller' => $cRaw, 'action' => $aRaw],
			'rbac' => [],
			'catalog_matches' => [],
			'user_roles' => [],
			'roles_via_groups_only' => [],
			'user_has_permission' => null,
			'missing_permission_codes' => [],
			'matching_permissions_user_has' => [],
			'effective_match' => null,
			'deny_reason' => null,
			'rbac_policies' => [],
			'abac' => [],
			'roles_that_would_grant' => [],
			'suggestions' => [],
			'legacy_notes' => [],
		];

		$rbacCfg = Configure::read('Rbac');
		if (!is_array($rbacCfg)) {
			$rbacCfg = [];
		}
		$abacCfg = Configure::read('Abac');
		$abacEnabled = is_array($abacCfg) && !empty($abacCfg['enabled']);

		$out['rbac'] = [
			'mode' => isset($rbacCfg['mode']) ? (string)$rbacCfg['mode'] : 'off',
			'core_tables' => $this->_rbacCoreTablesExist(),
			'whitelisted' => $this->_isWhitelisted($c, $a, $rbacCfg),
			'skipped_api_prefix' => $this->_skippedApiPrefix($a, $c, $rbacCfg),
			'legacy_admin_bypass' => !empty($rbacCfg['bypass_legacy_super'])
				&& !empty($userRow['admin'])
				&& (int)($userRow['role'] ?? -1) === 0,
		];

		$out['legacy_notes'][] = 'Prefixo de rota `admin` continua exigindo users.admin (AppController::isAuthorized); este diagnóstico não substitui essa verificação.';

		if (!$out['rbac']['core_tables']) {
			$out['suggestions'][] = 'Tabelas RBAC mínimas ausentes; instale migrações e o catálogo antes de interpretar o resultado.';

			return $out;
		}

		if (($rbacCfg['mode'] ?? 'off') === 'off') {
			$out['suggestions'][] = 'RBAC_MODE está off — o portal não bloqueia por catálogo neste ambiente.';
		}

		if ($out['rbac']['whitelisted']) {
			$out['suggestions'][] = 'Esta rota está em whitelist (config/rbac.php): o RbacComponent não exige permissão de catálogo.';
		}

		if ($out['rbac']['skipped_api_prefix']) {
			$out['suggestions'][] = 'Ação com prefixo api — RBAC de rota ignorado salvo entrada em rbac_api_enforced_actions.';
		}

		if ($out['rbac']['legacy_admin_bypass']) {
			$out['suggestions'][] = 'Administrador legado (equipe + admin) com bypass_legacy_super: RBAC de rota não é aplicado a este usuário.';
		}

		$catalog = $this->_catalogMatchesForRoute($cRaw, $aRaw);
		$out['catalog_matches'] = $catalog['rows'];
		if ($catalog['rows'] === []) {
			$out['suggestions'][] = 'Nenhuma linha em rbac_permissions cobre este controller/action; confira o catálogo ou permissions_registry.php.';
		}

		$directRoleIds = $this->_directUserRoleIds($uid);
		$effectiveRoleIds = RbacUserRolesResolver::effectiveRoleIds($uid);
		$groupOnly = array_values(array_diff($effectiveRoleIds, $directRoleIds));

		$out['user_roles'] = $this->_roleSummaries($effectiveRoleIds);
		$out['roles_via_groups_only'] = $this->_roleSummaries($groupOnly);

		$permMap = RbacEffectivePermissionIds::effectivePermissionIdMapForUser($uid);
		$matchIds = array_column($out['catalog_matches'], 'id');
		$matchIds = array_values(array_unique(array_map('intval', $matchIds)));

		$userMatches = [];
		foreach ($out['catalog_matches'] as $row) {
			$pid = (int)$row['id'];
			if ($pid > 0 && isset($permMap[$pid])) {
				$userMatches[] = $row;
			}
		}
		$out['matching_permissions_user_has'] = $userMatches;

		$missingCodes = [];
		foreach ($out['catalog_matches'] as $row) {
			$pid = (int)$row['id'];
			$code = (string)$row['code'];
			if ($pid > 0 && !isset($permMap[$pid]) && $code !== '') {
				$missingCodes[] = $code;
			}
		}
		$out['missing_permission_codes'] = array_values(array_unique($missingCodes));

		$policyCtx = $this->_policyContext($userRow, $prefixOpt, $pluginOpt);
		$evaluatePolicies = !empty($rbacCfg['evaluate_permission_policies']);

		if ($effectiveRoleIds === []) {
			$out['user_has_permission'] = false;
			$out['deny_reason'] = 'no_rbac_roles';
			$hybrid = empty($rbacCfg['enforce_block_without_roles'])
				|| !($rbacCfg['mode'] === 'enforce');
			$out['suggestions'][] = $hybrid
				? 'Sem papéis RBAC: em modo híbrido o pedido segue para legado/isAuthorized (salvo enforce_block_without_roles). Atribua um papel para passar a exigir permissões do catálogo.'
				: 'Sem papéis RBAC e bloqueio sem papéis ativo: o acesso seria negado. Atribua papéis adequados.';
		} elseif ($userMatches === []) {
			$out['user_has_permission'] = false;
			$out['deny_reason'] = 'no_matching_permission';
			$this->_suggestGrantRoles($out, $matchIds, $effectiveRoleIds);
		} else {
			$best = $this->_pickBestMatch($userMatches, (int)($userRow['role'] ?? 0));
			$policyRows = $evaluatePolicies ? $this->_loadPolicies((int)$best['id']) : [];
			$out['rbac_policies'] = $policyRows;
			$allowed = !$evaluatePolicies || $this->_policiesAllow($policyRows, $policyCtx);
			if (!$allowed) {
				$out['user_has_permission'] = false;
				$out['deny_reason'] = 'policy_denied';
				$out['effective_match'] = $best;
				$out['suggestions'][] = 'A permissão ' . $best['code'] . ' existe no papel, mas rbac_permission_policies bloqueou o contexto atual (sessão/prefixo/plugin). Ajuste políticas ou atributos do usuário conforme conditions_json.';
			} else {
				$out['user_has_permission'] = true;
				$out['effective_match'] = $best;
				$out['deny_reason'] = null;
			}
		}

		if ($matchIds !== []) {
			$out['roles_that_would_grant'] = $this->_rolesGrantingAnyPermission($matchIds);
		}

		$scope = null;
		if (!empty($out['effective_match']['abac_scope'])) {
			$scope = (string)$out['effective_match']['abac_scope'];
		}
		$out['abac'] = [
			'abac_query_enabled' => $abacEnabled,
			'rbac_abac_scope' => $scope,
			'description' => $this->_abacDescription($scope, $abacEnabled, (int)($userRow['role'] ?? 0)),
		];

		if ($out['user_has_permission'] === false && $out['deny_reason'] === 'no_matching_permission' && $matchIds !== []) {
			$codes = array_values(array_unique(array_filter(array_column($out['catalog_matches'], 'code'))));
			$primary = $codes[0] ?? 'permissão adequada';
			$roleNames = array_column(array_filter($out['roles_that_would_grant'], static function ($r) use ($effectiveRoleIds) {
				return !in_array((int)$r['role_id'], $effectiveRoleIds, true);
			}), 'name');
			$roleNames = array_slice(array_values(array_unique($roleNames)), 0, 8);
			if ($roleNames !== []) {
				$out['suggestions'][] = 'Atribua um dos papéis que já incluem esta rota no catálogo (ex.: ' . implode(', ', $roleNames) . ') ou acrescente ao papel atual o código ' . $primary . '.';
			} else {
				$out['suggestions'][] = 'Os papéis atuais não cobrem esta rota; adicione ao papel uma permissão que mapeie ' . $cRaw . '#' . $aRaw . ' (ex.: ' . $primary . ').';
			}
		}

		return $out;
	}

	/**
	 * Diagnóstico para painel admin: copia o utilizador e sobrepõe idempresa/idcliente para avaliar
	 * rbac_permission_policies e texto ABAC sem alterar a sessão nem a base.
	 *
	 * @param array<string, mixed> $userRow Auth / users (equipe)
	 * @param array<string, mixed> $sim prefix, plugin, idempresa?, idcliente?, resource_id? (só metadados / texto)
	 * @return array<string, mixed>
	 */
	public function diagnoseWithSimulatorContext(array $userRow, string $controller, string $action, array $sim = []): array {
		$copy = $userRow;
		$applied = [];
		if (array_key_exists('idempresa', $sim) && $sim['idempresa'] !== null && $sim['idempresa'] !== '') {
			$copy['idempresa'] = (int)$sim['idempresa'];
			$applied['idempresa'] = (int)$copy['idempresa'];
		}
		if (array_key_exists('idcliente', $sim) && $sim['idcliente'] !== null && $sim['idcliente'] !== '') {
			$copy['idcliente'] = (int)$sim['idcliente'];
			$applied['idcliente'] = (int)$copy['idcliente'];
		}
		$prefix = isset($sim['prefix']) ? (string)$sim['prefix'] : '';
		$plugin = isset($sim['plugin']) ? (string)$sim['plugin'] : '';
		$out = $this->diagnose($copy, $controller, $action, [
			'prefix' => $prefix,
			'plugin' => $plugin,
		]);
		$pCtx = $this->_policyContext(
			$copy,
			strtolower(trim($prefix)),
			strtolower(trim($plugin))
		);
		$out['rbac_policy_row_evaluations'] = $this->_policyRowsEvalForDisplay($out['rbac_policies'] ?? [], $pCtx);
		$codes = array_values(array_unique(array_filter(array_column($out['catalog_matches'] ?? [], 'code'))));
		$out['required_permission_codes'] = $codes;
		$out['required_permissions_or_label'] = $codes !== []
			? implode(' ** OU ** ', $codes)
			: '(nenhuma linha em rbac_permissions cobre esta rota)';
		$resId = null;
		if (array_key_exists('resource_id', $sim) && $sim['resource_id'] !== null) {
			$rid = $sim['resource_id'];
			$s = is_scalar($rid) ? trim((string)$rid) : '';
			$resId = $s !== '' ? $s : null;
		}
		$out['simulation'] = [
			'context_overrides' => $applied,
			'resource_id' => $resId,
			'note' => 'Simulação em memória: não grava utilizador. resource_id não participa do match controller/action; policies RBAC usam user.* após overrides de empresa/cliente.',
		];
		$out['abac_evaluated'] = [
			'rbac_abac_scope_effective' => $out['effective_match']['abac_scope'] ?? null,
			'abac_query_enabled' => $out['abac']['abac_query_enabled'] ?? false,
			'description' => $out['abac']['description'] ?? '',
			'rbac_policies_conditions_json_eval' => $out['rbac_policy_row_evaluations'],
		];
		$out['liberation_hints'] = array_values(array_unique(array_merge(
			['Diagnóstico apenas: confirme com política interna antes de alterar papéis ou permissões.'],
			$out['suggestions'] ?? []
		)));

		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $userMatches
	 * @return array<string, mixed>
	 */
	protected function _pickBestMatch(array $userMatches, int $userRole): array {
		usort($userMatches, function ($a, $b) use ($userRole) {
			$ra = $this->_abacScopePreferenceRank($a['abac_scope'] ?? null, $userRole);
			$rb = $this->_abacScopePreferenceRank($b['abac_scope'] ?? null, $userRole);
			if ($ra !== $rb) {
				return $ra - $rb;
			}

			return (int)$a['id'] - (int)$b['id'];
		});
		$best = $userMatches[0];
		foreach ($userMatches as $p) {
			$scope = isset($p['abac_scope']) ? trim((string)$p['abac_scope']) : '';
			if ($scope !== '') {
				$best = $p;
				break;
			}
		}

		return $best;
	}

	/**
	 * @param string|null $scope
	 */
	protected function _abacScopePreferenceRank($scope, int $userRole): int {
		$empty = ($scope === null || $scope === '');
		$key = $empty ? '' : strtolower((string)$scope);
		if ($empty) {
			return 10;
		}
		if ($userRole === 0) {
			$order = ['empresa' => 0, 'own' => 1, 'cliente' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}
		if ($userRole === 1) {
			$order = ['cliente' => 0, 'own' => 1, 'empresa' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}
		$order = ['empresa' => 0, 'own' => 1, 'cliente' => 2];

		return isset($order[$key]) ? $order[$key] : 5;
	}

	/**
	 * @return array{rows: array<int, array<string, mixed>>}
	 */
	protected function _catalogMatchesForRoute(string $controller, string $action): array {
		$c = strtolower(trim($controller));
		$names = [$c];
		if ($c === 'portalcontratos') {
			$names[] = 'portaladvancedcontracts';
		}
		if ($c === 'visitas') {
			$names[] = 'agenda';
		}
		$names = array_values(array_unique($names));
		try {
			$t = TableRegistry::get('RbacPermissions');
			$perms = $t->find()
				->where(function ($exp, $q) use ($names) {
					$lower = $q->func()->lower(['controller' => 'identifier']);
					$ors = [];
					foreach ($names as $n) {
						$ors[] = $exp->eq($lower, strtolower((string)$n));
					}
					if ($ors === []) {
						return $exp->eq('1', '0');
					}
					if (count($ors) === 1) {
						return $ors[0];
					}

					return $exp->or_($ors);
				})
				->order(['id' => 'ASC'])
				->all();
		} catch (\Throwable $e) {
			return ['rows' => []];
		}
		$rows = [];
		foreach ($perms as $p) {
			$row = [
				'controller' => $p->controller,
				'action' => $p->action,
			];
			if (!RbacChecker::matchAction($controller, $action, $row)) {
				continue;
			}
			$rows[] = [
				'id' => (int)$p->id,
				'code' => (string)$p->code,
				'name' => (string)$p->name,
				'module' => (string)($p->module ?? ''),
				'controller' => (string)$p->controller,
				'action' => (string)$p->action,
				'abac_scope' => $p->abac_scope !== null && $p->abac_scope !== '' ? (string)$p->abac_scope : null,
			];
		}

		return ['rows' => $rows];
	}

	/**
	 * @param int[] $roleIds
	 * @return array<int, array<string, mixed>>
	 */
	protected function _roleSummaries(array $roleIds): array {
		$roleIds = array_values(array_unique(array_map('intval', $roleIds)));
		if ($roleIds === []) {
			return [];
		}
		try {
			$roles = TableRegistry::get('RbacRoles')->find()
				->select(['id', 'name', 'slug'])
				->where(['id IN' => $roleIds])
				->order(['name' => 'ASC'])
				->all();
		} catch (\Throwable $e) {
			return [];
		}
		$out = [];
		foreach ($roles as $r) {
			$out[] = [
				'role_id' => (int)$r->id,
				'name' => (string)$r->name,
				'slug' => (string)($r->slug ?? ''),
			];
		}

		return $out;
	}

	/**
	 * @param int[] $matchIds
	 * @return array<int, array<string, mixed>>
	 */
	protected function _rolesGrantingAnyPermission(array $matchIds): array {
		$matchIds = array_values(array_filter(array_map('intval', $matchIds), static function ($v) {
			return $v > 0;
		}));
		if ($matchIds === []) {
			return [];
		}
		try {
			$links = TableRegistry::get('RbacRolesPermissions')->find()
				->select(['role_id', 'permission_id'])
				->where(['permission_id IN' => $matchIds])
				->all();
		} catch (\Throwable $e) {
			return [];
		}
		$roleIds = [];
		$roleToPerm = [];
		foreach ($links as $l) {
			$rid = (int)$l->role_id;
			$pid = (int)$l->permission_id;
			$roleIds[$rid] = true;
			if (!isset($roleToPerm[$rid])) {
				$roleToPerm[$rid] = $pid;
			}
		}
		$ids = array_keys($roleIds);
		if ($ids === []) {
			return [];
		}
		$roles = TableRegistry::get('RbacRoles')->find()
			->select(['id', 'name'])
			->where(['id IN' => $ids])
			->order(['name' => 'ASC'])
			->all();
		$permById = [];
		try {
			$codes = TableRegistry::get('RbacPermissions')->find()
				->select(['id', 'code'])
				->where(['id IN' => $matchIds])
				->all();
			foreach ($codes as $p) {
				$permById[(int)$p->id] = (string)$p->code;
			}
		} catch (\Throwable $e) {
			$permById = [];
		}
		$out = [];
		foreach ($roles as $r) {
			$rid = (int)$r->id;
			$pid = $roleToPerm[$rid] ?? 0;
			$out[] = [
				'role_id' => $rid,
				'name' => (string)$r->name,
				'example_permission_id' => $pid,
				'example_permission_code' => $permById[$pid] ?? '',
			];
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $out
	 * @param int[] $matchIds
	 * @param int[] $effectiveRoleIds
	 */
	protected function _suggestGrantRoles(array &$out, array $matchIds, array $effectiveRoleIds): void {
		$grant = $this->_rolesGrantingAnyPermission($matchIds);
		$extra = [];
		foreach ($grant as $g) {
			if (!in_array((int)$g['role_id'], $effectiveRoleIds, true)) {
				$extra[] = $g['name'];
			}
		}
		$extra = array_slice(array_values(array_unique($extra)), 0, 6);
		if ($extra !== []) {
			$out['suggestions'][] = 'Papéis no catálogo que já liberam esta rota (não atribuídos ao usuário): ' . implode(', ', $extra) . '.';
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $policyRows
	 */
	protected function _policiesAllow(array $policyRows, array $ctx): bool {
		if ($policyRows === []) {
			return true;
		}
		foreach ($policyRows as $pol) {
			$json = $pol['conditions_json'] ?? null;
			if (RbacPolicyConditions::matchesOrEmpty($json, $ctx)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	protected function _loadPolicies(int $permissionId): array {
		if ($permissionId <= 0 || !$this->_permissionPoliciesTableExists()) {
			return [];
		}
		try {
			$rows = TableRegistry::get('RbacPermissionPolicies')->find()
				->select(['id', 'conditions_json', 'active', 'priority'])
				->where(['rbac_permission_id' => $permissionId, 'active' => true])
				->order(['priority' => 'DESC', 'id' => 'ASC'])
				->all();
		} catch (\Throwable $e) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = [
				'id' => (int)$row->id,
				'conditions_json' => $row->conditions_json,
				'priority' => (int)$row->priority,
			];
		}

		return $out;
	}

	protected function _permissionPoliciesTableExists(): bool {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permission_policies', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _rbacCoreTablesExist(): bool {
		return RbacChecker::rbacCoreTablesExist();
	}

	protected function _isWhitelisted(string $c, string $a, array $cfg): bool {
		$list = isset($cfg['whitelist']) ? $cfg['whitelist'] : [];
		if (!is_array($list)) {
			return false;
		}
		foreach ($list as $entry) {
			if (strpos((string)$entry, '#') === false) {
				continue;
			}
			list($wc, $wa) = explode('#', strtolower((string)$entry), 2);
			if ($wc !== $c) {
				continue;
			}
			if ($wa === '*' || $wa === $a) {
				return true;
			}
		}

		return false;
	}

	protected function _skippedApiPrefix(string $actionLower, string $controllerLower, array $cfg): bool {
		$prefixes = isset($cfg['skip_action_prefixes']) && is_array($cfg['skip_action_prefixes'])
			? $cfg['skip_action_prefixes']
			: [];
		foreach ($prefixes as $prefix) {
			$prefix = strtolower((string)$prefix);
			if ($prefix !== '' && strpos($actionLower, $prefix) === 0) {
				if ($prefix === 'api' && $this->_isRbacEnforcedApiAction($controllerLower, $actionLower, $cfg)) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

	protected function _isRbacEnforcedApiAction(string $controllerLower, string $actionLower, array $cfg): bool {
		if (empty($cfg['rbac_api_enforced_actions']) || !is_array($cfg['rbac_api_enforced_actions'])) {
			return false;
		}
		$key = $controllerLower . '#' . $actionLower;
		foreach ($cfg['rbac_api_enforced_actions'] as $entry) {
			if (strtolower(trim((string)$entry)) === $key) {
				return true;
			}
		}

		return false;
	}

	protected function _policyContext(array $userRow, string $prefixStr, string $pluginStr): array {
		$idEmp = isset($userRow['idempresa']) && $userRow['idempresa'] !== '' && $userRow['idempresa'] !== null
			? (int)$userRow['idempresa'] : 0;
		$idCli = isset($userRow['idcliente']) && $userRow['idcliente'] !== '' && $userRow['idcliente'] !== null
			? (int)$userRow['idcliente'] : 0;
		$setor = array_key_exists('setor', $userRow) && $userRow['setor'] !== null && $userRow['setor'] !== ''
			? $userRow['setor'] : '';

		return [
			'user.id' => (int)($userRow['id'] ?? 0),
			'user.username' => (string)($userRow['username'] ?? ''),
			'user.role' => (int)($userRow['role'] ?? -1),
			'user.admin' => !empty($userRow['admin']),
			'user.idempresa' => $idEmp,
			'user.idcliente' => $idCli,
			'user.setor' => $setor,
			'request.prefix' => $prefixStr,
			'request.plugin' => $pluginStr,
		];
	}

	protected function _abacDescription(?string $scope, bool $abacEnabled, int $userRole): string {
		$parts = [];
		if (!$abacEnabled) {
			$parts[] = 'Abac.enabled=false — AbacQuery não filtra consultas por empresa/cliente.';
		} else {
			$parts[] = 'Abac.enabled=true — consultas ORM podem ser filtradas via AbacQuery conforme mapa em config/abac.php.';
		}
		if ($scope === null || $scope === '') {
			$parts[] = 'Escopo ABAC derivado da permissão RBAC (abac_scope): vazio — sem escopo explícito no registo que casou; o runtime pode ainda aplicar colunas padrão do mapa ABAC por tabela.';

			return implode(' ', $parts);
		}
		$s = strtolower($scope);
		if ($s === 'empresa') {
			$parts[] = 'abac_scope=empresa: consultas típicas restringem por idempresa do utilizador (registos da mesma empresa).';
		} elseif ($s === 'cliente') {
			$parts[] = 'abac_scope=cliente: portal/consultas restringem por idcliente (dados do cliente vinculado).';
		} elseif ($s === 'own') {
			$parts[] = 'abac_scope=own: escopo “próprio usuário” (ex.: user_id_column no mapa ABAC).';
		} else {
			$parts[] = 'abac_scope=' . $scope . ' (ver documentação interna / mapas ABAC).';
		}
		if ($userRole === 1) {
			$parts[] = 'Utilizador portal (role=1): AbacQuery prioriza cliente quando o mapa suporta.';
		}

		return implode(' ', $parts);
	}

	/**
	 * @return int[]
	 */
	protected function _directUserRoleIds(int $userId): array {
		if ($userId <= 0) {
			return [];
		}
		try {
			$rows = TableRegistry::get('RbacUsersRoles')->find()
				->select(['role_id'])
				->where(['user_id' => $userId])
				->extract('role_id')
				->toList();

			return array_values(array_unique(array_map('intval', $rows)));
		} catch (\Throwable $e) {
			return [];
		}
	}
}
