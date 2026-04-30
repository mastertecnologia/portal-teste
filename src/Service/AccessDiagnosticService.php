<?php
namespace App\Service;

use App\Utility\RbacChecker;
use App\Utility\RbacPermissionResolver;
use App\Utility\RbacPolicyConditions;
use App\Utility\RbacUserRolesResolver;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Diagnóstico read-only RBAC para access denied / simuladores / pedidos de acesso.
 * Não concede permissão e não deve lançar 500 por JSON ou conditions_json inválidos.
 */
class AccessDiagnosticService {

	public const SESSION_ACCESS_DENIED_CAPTURE = 'Rbac.AccessDeniedDiagnostic';

	/**
	 * Quem pode ver o bloco detalhado em users/access-denied.
	 *
	 * @param array<string,mixed> $user Auth user row
	 */
	public static function canViewRbacDiagnostic(array $user): bool {
		$uid = (int)($user['id'] ?? 0);
		if ($uid <= 0 || (int)($user['role'] ?? -1) !== 0) {
			return false;
		}
		if (!empty($user['admin'])) {
			return true;
		}

		return RbacChecker::userHasPermissionCode($uid, 'permissoes.users.effective')
			|| RbacChecker::userHasPermissionCode($uid, 'rbac.requests.view_all')
			|| RbacChecker::userHasPermissionCode($uid, 'rbac.requests.audit');
	}

	/**
	 * @param array<string,mixed> $capture SESSION_ACCESS_DENIED_CAPTURE
	 * @param array<string,mixed> $user Auth user row
	 * @return array<string,mixed>
	 */
	public function diagnoseFromDenialCapture(array $capture, array $user): array {
		$ctrl = strtolower((string)($capture['controller'] ?? ''));
		$action = strtolower((string)($capture['action'] ?? ''));
		$prefix = (string)($capture['prefix'] ?? '');
		$plugin = (string)($capture['plugin'] ?? '');
		$report = $this->diagnose($user, $ctrl, $action, ['prefix' => $prefix, 'plugin' => $plugin]);
		$report['capture'] = [
			'user_id' => (int)($capture['user_id'] ?? 0),
			'controller' => $ctrl,
			'action' => $action,
			'reason' => (string)($capture['reason'] ?? ''),
			'support_code' => (string)($capture['support_code'] ?? ''),
			'ts' => (int)($capture['ts'] ?? 0),
			'prefix' => $prefix,
			'plugin' => $plugin,
		];
		if (!empty($report['capture']['reason']) && !empty($report['diagnosis_inferred_reason'])) {
			if ((string)$report['capture']['reason'] !== (string)$report['diagnosis_inferred_reason']) {
				$report['diagnosis_reason_mismatch_note'] =
					'Nota: o motivo gravado pelo runtime pode diferir do diagnóstico reconstruído (ex.: corrida ou políticas alteradas).';
			}
		}

		return $report;
	}

	/**
	 * Overrides opcionais: idempresa, idcliente, resource_id, prefix, plugin.
	 *
	 * @param array<string,mixed> $user Auth user row
	 * @param array<string,mixed> $sim
	 * @return array<string,mixed>
	 */
	public function diagnoseWithSimulatorContext(array $user, string $controller, string $action, array $sim = []): array {
		$u = $user;
		foreach (['idempresa', 'idcliente'] as $k) {
			if (array_key_exists($k, $sim) && $sim[$k] !== null && $sim[$k] !== '') {
				$u[$k] = (int)$sim[$k];
			}
		}
		if (array_key_exists('resource_id', $sim) && $sim['resource_id'] !== null && (string)$sim['resource_id'] !== '') {
			$u['rbac_sim_resource_id'] = (string)$sim['resource_id'];
		}

		return $this->diagnose($u, $controller, $action, [
			'prefix' => isset($sim['prefix']) ? (string)$sim['prefix'] : '',
			'plugin' => isset($sim['plugin']) ? (string)$sim['plugin'] : '',
			'is_simulator' => true,
		]);
	}

	/**
	 * @param array<string,mixed> $user
	 * @param array<string,mixed> $opts prefix, plugin, is_simulator
	 * @return array<string,mixed>
	 */
	public function diagnose(array $user, string $controller, string $action, array $opts = []): array {
		try {
			return $this->_diagnoseUnsafe($user, strtolower(trim($controller)), strtolower(trim($action)), $opts);
		} catch (\Throwable $e) {
			return [
				'suggestions' => ['Diagnóstico indisponível no momento. Contacte um administrador.'],
				'error' => 'diagnostic_unavailable',
			];
		}
	}

	/**
	 * @param array<string,mixed> $user
	 * @param array<string,mixed> $opts
	 * @return array<string,mixed>
	 */
	private function _diagnoseUnsafe(array $user, string $ctrl, string $action, array $opts): array {
		$userId = (int)($user['id'] ?? 0);
		$prefix = strtolower(trim((string)($opts['prefix'] ?? '')));
		$plugin = strtolower(trim((string)($opts['plugin'] ?? '')));

		if (!RbacChecker::rbacCoreTablesExist() || $userId <= 0 || $ctrl === '' || $action === '') {
			return ['suggestions' => ['Informação insuficiente para montar diagnóstico.']];
		}

		$roleIds = RbacUserRolesResolver::effectiveRoleIds($userId);
		$cfg = Configure::read('Rbac');
		if (!is_array($cfg)) {
			$cfg = [];
		}

		$userRolesRows = [];
		try {
			$rolesTbl = TableRegistry::get('RbacRoles');
			foreach (TableRegistry::get('RbacUsersRoles')->find()->where(['user_id' => $userId])->all() as $ur) {
				$r = $rolesTbl->find()->where(['id' => (int)$ur->role_id])->first();
				if ($r) {
					$userRolesRows[] = [
						'role_id' => (int)$r->id,
						'name' => (string)$r->name,
						'slug' => (string)($r->slug ?? ''),
					];
				}
			}
		} catch (\Throwable $e) {
			$userRolesRows = [];
		}

		$catalogMatches = $this->_catalogMatchesForRoute($ctrl, $action);
		$permIdsHeld = [];
		if ($roleIds !== []) {
			try {
				$permIdsHeld = TableRegistry::get('RbacRolesPermissions')->find()
					->select(['permission_id'])
					->where(['role_id IN' => $roleIds])
					->extract('permission_id')
					->toList();
				$permIdsHeld = array_values(array_unique(array_map('intval', $permIdsHeld)));
				$expand = !array_key_exists('expand_legacy_aliases', $cfg) || (bool)$cfg['expand_legacy_aliases'];
				if ($expand && $permIdsHeld !== []) {
					$permIdsHeld = RbacPermissionResolver::expandPermissionIds($permIdsHeld);
				}
			} catch (\Throwable $e) {
				$permIdsHeld = [];
			}
		}
		$heldSet = array_fill_keys($permIdsHeld, true);

		$catalogRows = [];
		$matchingUserHas = [];
		$missingCodes = [];

		foreach ($catalogMatches as $p) {
			$cid = (int)$p->id;
			$catalogRows[] = [
				'code' => (string)$p->code,
				'name' => (string)$p->name,
				'controller' => (string)$p->controller,
				'action' => (string)$p->action,
			];
			if (isset($heldSet[$cid])) {
				$matchingUserHas[] = [
					'code' => (string)$p->code,
					'name' => (string)$p->name,
				];
			} else {
				$missingCodes[] = (string)$p->code;
			}
		}
		$missingCodes = array_values(array_unique($missingCodes));

		$effectiveMatch = null;
		if ($catalogMatches !== [] && $roleIds !== []) {
			$userRoleNumeric = (int)($user['role'] ?? 0);
			$permsHeldEntities = [];
			try {
				if ($permIdsHeld !== []) {
					$permsHeldEntities = TableRegistry::get('RbacPermissions')->find()
						->where(['id IN' => $permIdsHeld])->all()->toList();
				}
			} catch (\Throwable $e) {
				$permsHeldEntities = [];
			}

			$routeMatchesHeld = [];
			foreach ($permsHeldEntities as $p) {
				if (RbacChecker::matchAction($ctrl, $action, ['controller' => $p->controller, 'action' => $p->action])) {
					$routeMatchesHeld[] = $p;
				}
			}
			if ($routeMatchesHeld !== []) {
				$this->_sortMatchesByAbacScopePreference($routeMatchesHeld, $userRoleNumeric);
				foreach ($routeMatchesHeld as $cand) {
					if ($cand->abac_scope !== null && (string)$cand->abac_scope !== '') {
						$effectiveMatch = $cand;
						break;
					}
				}
				if ($effectiveMatch === null) {
					$effectiveMatch = $routeMatchesHeld[0];
				}
			}
		}

		$policyEval = [];
		$inferReason = 'no_matching_permission';
		if ($effectiveMatch !== null && !empty($effectiveMatch->id)) {
			$ctx = $this->_policyCtxFromUserRow($user, $prefix, $plugin);
			$policiesAllow = true;
			if (!empty($cfg['evaluate_permission_policies']) && $this->_permissionPoliciesTableExists()) {
				try {
					$policyRows = TableRegistry::get('RbacPermissionPolicies')->find()
						->where(['rbac_permission_id' => (int)$effectiveMatch->id, 'active' => true])
						->order(['priority' => 'DESC', 'id' => 'ASC'])
						->all();
					if ($policyRows->count() > 0) {
						$policiesAllow = false;
						foreach ($policyRows as $pol) {
							$cjRaw = $pol->conditions_json;
							$cjStr = '';
							try {
								$cjStr = is_string($cjRaw) ? $cjRaw : (string)(json_encode($cjRaw, JSON_UNESCAPED_UNICODE) ?: '');
							} catch (\Throwable $e) {
								$cjStr = '';
							}

							try {
								$matchedPolicy = RbacPolicyConditions::matchesOrEmpty($cjRaw, $ctx);
							} catch (\Throwable $e) {
								$matchedPolicy = false;
							}

							$policyEval[] = [
								'policy_id' => (int)$pol->id,
								'matched' => $matchedPolicy,
								'detail' => $matchedPolicy ? 'Condições satisfeitas ou política aberta.' : 'Condições não satisfeitas.',
								'conditions_json' => $cjStr,
							];
							if ($matchedPolicy) {
								$policiesAllow = true;
								break;
							}
						}
					}
				} catch (\Throwable $e) {
					$policiesAllow = true;
				}
			}
			$inferReason = $policiesAllow ? 'ok_would_grant' : 'policy_denied';
		}

		if ($roleIds === []) {
			$inferReason = 'no_rbac_roles';
		}

		$rolesGrant = [];
		try {
			$plink = TableRegistry::get('RbacRolesPermissions');
			$rolesTbl = TableRegistry::get('RbacRoles');
			$wantIds = [];
			foreach ($catalogMatches as $ent) {
				$wantIds[] = (int)$ent->id;
			}
			$wantIds = array_values(array_unique(array_filter($wantIds)));
			if ($wantIds !== []) {
				foreach ($plink->find()->where(['permission_id IN' => $wantIds])->all() as $ln) {
					$rid = (int)$ln->role_id;
					$r = $rolesTbl->find()->where(['id' => $rid])->first();
					if (!$r) {
						continue;
					}
					$key = $rid;
					if (!isset($rolesGrant[$key])) {
						$p = TableRegistry::get('RbacPermissions')->find()->where(['id' => (int)$ln->permission_id])->first();
						$rolesGrant[$key] = [
							'role_id' => $rid,
							'name' => (string)$r->name,
							'example_permission_code' => $p ? (string)$p->code : '?',
						];
					}
				}
			}
		} catch (\Throwable $e) {
			$rolesGrant = [];
		}

		$suggestions = [];
		if ($roleIds === []) {
			$suggestions[] = 'Atribuir um papel RBAC inicial à equipe (ex.: papel operacional piloto).';
		}
		if ($catalogRows === []) {
			$suggestions[] = 'Nenhuma entrada no catálogo rbac_permissions cobre esta combinação controller/action.';
		} elseif ($matchingUserHas === []) {
			$suggestions[] = 'Nenhuma das permissões que cobrem esta rota está nos papéis atuais; considere ajustar papéis ou solicitar inclusão.';
		}

		return [
			'user_roles' => $userRolesRows,
			'catalog_matches' => $catalogRows,
			'matching_permissions_user_has' => $matchingUserHas,
			'missing_permission_codes' => $missingCodes,
			'required_permission_codes' => $missingCodes,
			'required_permissions_or_label' => $missingCodes !== [] ? implode(', ', $missingCodes) : '—',
			'roles_that_would_grant' => array_values($rolesGrant),
			'effective_match' => $effectiveMatch !== null ? [
				'code' => (string)$effectiveMatch->code,
				'name' => (string)$effectiveMatch->name,
			] : null,
			'policy_conditions_eval' => $policyEval,
			'diagnosis_inferred_reason' => $inferReason,
			'suggestions' => $suggestions,
			'abac_evaluated' => [],
			'abac' => ['description' => ''],
		];
	}

	/**
	 * Permissões do catálogo que casam na rota.
	 *
	 * @return \App\Model\Entity\RbacPermission[]
	 */
	private function _catalogMatchesForRoute(string $ctrl, string $action): array {
		try {
			$query = TableRegistry::get('RbacPermissions')->find()->select([
				'RbacPermissions.id',
				'RbacPermissions.code',
				'RbacPermissions.name',
				'RbacPermissions.controller',
				'RbacPermissions.action',
				'RbacPermissions.abac_scope',
			]);

			$out = [];
			foreach ($query->all() as $perm) {
				if (RbacChecker::matchAction($ctrl, $action, ['controller' => $perm->controller, 'action' => $perm->action])) {
					$out[] = $perm;
				}
			}

			return $out;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @param \App\Model\Entity\RbacPermission[] $matches
	 */
	private function _sortMatchesByAbacScopePreference(array &$matches, int $userRole): void {
		usort($matches, function ($a, $b) use ($userRole) {
			$ra = $this->_abacScopePreferenceRank($a->abac_scope ?? null, $userRole);
			$rb = $this->_abacScopePreferenceRank($b->abac_scope ?? null, $userRole);
			if ($ra !== $rb) {
				return $ra - $rb;
			}

			return (int)$a->id - (int)$b->id;
		});
	}

	/**
	 * @param mixed $scope
	 */
	private function _abacScopePreferenceRank($scope, int $userRole): int {
		$empty = ($scope === null || $scope === '');
		if ($empty) {
			return 10;
		}
		$key = strtolower((string)$scope);
		if ((int)$userRole === 0) {
			$order = ['empresa' => 0, 'own' => 1, 'cliente' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}
		if ((int)$userRole === 1) {
			$order = ['cliente' => 0, 'own' => 1, 'empresa' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}

		return 5;
	}

	/**
	 * @param array<string,mixed> $user
	 */
	private function _policyCtxFromUserRow(array $user, string $prefix, string $plugin): array {
		$idEmp = isset($user['idempresa']) && $user['idempresa'] !== '' && $user['idempresa'] !== null
			? (int)$user['idempresa'] : 0;
		$idCli = isset($user['idcliente']) && $user['idcliente'] !== '' && $user['idcliente'] !== null
			? (int)$user['idcliente'] : 0;
		$setor = array_key_exists('setor', $user) && $user['setor'] !== null && $user['setor'] !== ''
			? $user['setor'] : '';

		return [
			'user.id' => (int)($user['id'] ?? 0),
			'user.username' => (string)($user['username'] ?? ''),
			'user.role' => (int)($user['role'] ?? -1),
			'user.admin' => !empty($user['admin']),
			'user.idempresa' => $idEmp,
			'user.idcliente' => $idCli,
			'user.setor' => $setor,
			'request.prefix' => $prefix,
			'request.plugin' => $plugin,
		];
	}

	private function _permissionPoliciesTableExists(): bool {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permission_policies', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
