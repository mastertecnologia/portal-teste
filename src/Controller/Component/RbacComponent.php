<?php
namespace App\Controller\Component;

use App\Service\AccessDiagnosticService;
use App\Utility\RbacChecker;
use App\Utility\RbacPermissionResolver;
use App\Utility\RbacPolicyConditions;
use App\Utility\RbacUserRolesResolver;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Verifica catálogo RBAC quando o utilizador tem papéis em rbac_users_roles
 * e/ou papéis herdados via rbac_user_groups + rbac_group_roles (Fase 3).
 */
class RbacComponent extends Component {

	/**
	 * @return string|null Response redirect if access denied (enforce); null to continue
	 */
	public function checkRequest($controller, $action) {
		$cfg = Configure::read('Rbac');
		if (!is_array($cfg)) {
			$cfg = [];
		}
		if (empty($cfg['mode']) || $cfg['mode'] === 'off') {
			return null;
		}
		if (empty($cfg['skip_action_prefixes']) || !is_array($cfg['skip_action_prefixes'])) {
			$cfg['skip_action_prefixes'] = [];
		}

		$user = $this->getController()->Auth->user();
		if (empty($user['id'])) {
			return null;
		}

		$this->getController()->rbacAbacScope = null;
		$this->getController()->rbacAbacPermissionCode = null;
		$this->getController()->rbacDenyReason = null;

		$c = strtolower((string)$controller);
		$a = strtolower((string)$action);

		foreach ($cfg['skip_action_prefixes'] as $prefix) {
			$prefix = strtolower((string)$prefix);
			if ($prefix !== '' && strpos($a, $prefix) === 0) {
				if ($prefix === 'api' && $this->_isRbacEnforcedApiAction($c, $a, $cfg)) {
					continue;
				}

				return null;
			}
		}

		if (!empty($cfg['bypass_legacy_super']) && !empty($user['admin']) && (int)$user['role'] === 0) {
			return null;
		}

		if (!$this->_tablesExist()) {
			return null;
		}

		if ($this->_isWhitelisted($c, $a, $cfg)) {
			return null;
		}

		$uid = (int)$user['id'];
		$roleIds = $this->_userRoleIds($uid);
		if (empty($roleIds)) {
			if (!empty($cfg['log_unassigned_rbac_users']) && $cfg['mode'] !== 'off') {
				Log::info(sprintf('RBAC hybrid skip: user_id=%d has no rbac_users_roles', $uid));
			}
			if ($cfg['mode'] === 'enforce' && !empty($cfg['enforce_block_without_roles'])) {
				$equipeOnly = !array_key_exists('enforce_block_without_roles_equipe_only', $cfg)
					|| $cfg['enforce_block_without_roles_equipe_only'];
				$isEquipe = (int)($user['role'] ?? -1) === 0;
				if (!$equipeOnly || $isEquipe) {
					$this->_persistRbacAuditIfConfigured($uid, false, $controller, $action, $cfg, [], 'no_rbac_roles');
					$this->_storeAccessDeniedDiagnostic($uid, $controller, $action, 'no_rbac_roles');
					$msg = 'Sua conta ainda não tem papéis atribuídos no novo controle de acesso. Contate um administrador.';
					$this->getController()->Flash->error($msg);

					return $this->getController()->redirect(['controller' => 'Users', 'action' => 'accessDenied']);
				}
			}

			return null;
		}

		if ($this->_userCanAccess($roleIds, $controller, $action)) {
			$this->_persistRbacAuditIfConfigured($uid, true, $controller, $action, $cfg, $roleIds);

			return null;
		}

		$denyReason = $this->getController()->rbacDenyReason ?: 'no_matching_permission';
		$this->_persistRbacAuditIfConfigured($uid, false, $controller, $action, $cfg, $roleIds, $denyReason);

		$msg = 'Seu papel não inclui permissão para esta função. Contate um administrador.';

		if ($cfg['mode'] === 'warn') {
			Log::warning(sprintf(
				'RBAC warn: user_id=%d denied %s::%s roles=%s',
				$uid,
				$controller,
				$action,
				implode(',', $roleIds)
			));
			if (!empty($cfg['warn_flash'])) {
				$this->getController()->Flash->warning($msg);
			}

			return null;
		}

		if ($cfg['mode'] === 'enforce') {
			$this->_storeAccessDeniedDiagnostic($uid, $controller, $action, $denyReason);
			$this->getController()->Flash->error($msg);
			// Destino em whitelist: users#accessdenied (config/rbac.php), para não depender de dashboard.view.

			return $this->getController()->redirect(['controller' => 'Users', 'action' => 'accessDenied']);
		}

		return null;
	}

	/**
	 * Grava na sessão dados mínimos para a página accessDenied montar diagnóstico (sem alterar decisão RBAC).
	 *
	 * @param string $reason no_rbac_roles | no_matching_permission | policy_denied
	 */
	protected function _storeAccessDeniedDiagnostic($uid, $controller, $action, $reason) {
		try {
			$req = $this->getController()->getRequest();
			$session = $req->getSession();
			$prefix = $req->getParam('prefix');
			$plugin = $req->getParam('plugin');
			$supportCode = $this->_buildSupportCode();
			$session->write(AccessDiagnosticService::SESSION_ACCESS_DENIED_CAPTURE, [
				'user_id' => (int)$uid,
				'controller' => (string)$controller,
				'action' => (string)$action,
				'reason' => substr((string)$reason, 0, 64),
				'support_code' => $supportCode,
				'ts' => time(),
				'prefix' => ($prefix !== null && $prefix !== false) ? (string)$prefix : '',
				'plugin' => ($plugin !== null && $plugin !== false) ? (string)$plugin : '',
			]);
			$this->_logDeniedSupport($supportCode, (int)$uid, (string)$controller, (string)$action, (string)$reason, $req);
		} catch (\Throwable $e) {
			// não interferir no fluxo de negação
		}
	}

	protected function _buildSupportCode(): string {
		try {
			$bytes = random_bytes(4);

			return 'RBAC-403-' . strtoupper(bin2hex($bytes));
		} catch (\Throwable $e) {
			try {
				$fallback = strtoupper(substr(hash('sha256', (string)microtime(true) . '-' . (string)random_int(PHP_INT_MIN, PHP_INT_MAX)), 0, 8));
			} catch (\Throwable $inner) {
				$fallback = strtoupper(substr(hash('sha256', uniqid('', true)), 0, 8));
			}

			return 'RBAC-403-' . $fallback;
		}
	}

	protected function _logDeniedSupport(string $supportCode, int $uid, string $controller, string $action, string $reason, $req): void {
		try {
			$ip = method_exists($req, 'clientIp') ? (string)$req->clientIp() : '';
			$ua = (string)$req->getEnv('HTTP_USER_AGENT');
			$ua = substr($ua, 0, 255);
			Log::warning(sprintf(
				'RBAC denied support_code=%s user_id=%d controller=%s action=%s reason=%s ip=%s ua=%s ts=%s',
				$supportCode,
				$uid,
				strtolower($controller),
				strtolower($action),
				$reason,
				$ip,
				$ua,
				date('c')
			));
		} catch (\Throwable $e) {
			// não interferir no fluxo de negação
		}
	}

	protected function _isWhitelisted($c, $a, $cfg) {
		$list = isset($cfg['whitelist']) ? $cfg['whitelist'] : [];
		foreach ($list as $entry) {
			if (strpos($entry, '#') === false) {
				continue;
			}
			list($wc, $wa) = explode('#', strtolower($entry), 2);
			if ($wc !== $c) {
				continue;
			}
			if ($wa === '*' || $wa === $a) {
				return true;
			}
		}

		return false;
	}

	protected function _tablesExist() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permissions', $tables, true)
				&& in_array('rbac_roles_permissions', $tables, true)
				&& in_array('rbac_users_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}


	protected function _userRoleIds($uid) {
		return RbacUserRolesResolver::effectiveRoleIds((int)$uid);
	}

	protected function _userCanAccess($roleIds, $controller, $action) {
		$this->getController()->rbacDenyReason = null;
		$matched = $this->_findBestMatchingPermission($roleIds, $controller, $action);
		if ($matched === null) {
			return false;
		}

		if (!$this->_permissionPoliciesAllow($matched)) {
			$this->getController()->rbacDenyReason = 'policy_denied';

			return false;
		}

		$this->getController()->rbacAbacScope = $matched->abac_scope;
		$this->getController()->rbacAbacPermissionCode = $matched->code;

		return true;
	}

	/**
	 * @param int[] $roleIds
	 * @param string $controller
	 * @param string $action
	 * @return \App\Model\Entity\RbacPermission|null
	 */
	protected function _findBestMatchingPermission($roleIds, $controller, $action) {
		$permIds = TableRegistry::get('RbacRolesPermissions')->find()
			->select(['permission_id'])
			->where(['role_id IN' => $roleIds])
			->extract('permission_id')
			->toList();
		$permIds = array_values(array_unique(array_map('intval', $permIds)));
		if (empty($permIds)) {
			return null;
		}

		$cfg = Configure::read('Rbac');
		if (!is_array($cfg)) {
			$cfg = [];
		}
		$expandAliases = array_key_exists('expand_legacy_aliases', $cfg)
			? (bool)$cfg['expand_legacy_aliases']
			: true;
		if ($expandAliases) {
			$permIds = RbacPermissionResolver::expandPermissionIds($permIds);
		}
		if (empty($permIds)) {
			return null;
		}

		$perms = TableRegistry::get('RbacPermissions')->find()
			->where(['id IN' => $permIds])
			->all();

		$matches = [];
		foreach ($perms as $p) {
			$row = [
				'controller' => $p->controller,
				'action' => $p->action,
			];
			if (RbacChecker::matchAction($controller, $action, $row)) {
				$matches[] = $p;
			}
		}
		if (empty($matches)) {
			return null;
		}

		$userRole = (int)$this->getController()->Auth->user('role');
		$this->_sortMatchesByAbacScopePreference($matches, $userRole);

		$best = $matches[0];
		foreach ($matches as $p) {
			if ($p->abac_scope !== null && $p->abac_scope !== '') {
				$best = $p;
				break;
			}
		}

		if (!empty($cfg['legacy_permission_log']) && $best !== null && !empty($best->code)) {
			try {
				$conn = TableRegistry::get('RbacPermissions')->getConnection();
				if (RbacPermissionResolver::isLegacyBundleCode($best->code, $conn)) {
					Log::info(sprintf(
						'RBAC legacy bundle matched: code=%s controller=%s action=%s',
						$best->code,
						$controller,
						$action
					));
				}
			} catch (\Exception $e) {
				// não interromper request
			}
		}

		return $best;
	}

	/**
	 * Quando várias permissões fazem match (ex.: tickets.update + tickets.portal.update em Tickets#edit),
	 * a ordem de iteração da BD era arbitrária e a primeira com abac_scope não vazio podia ser "cliente",
	 * aplicando filtro por idcliente à equipe (role 0) e devolvendo zero linhas no find do ticket.
	 * Equipe prioriza empresa; portal (role 1) prioriza cliente.
	 *
	 * @param \App\Model\Entity\RbacPermission[] $matches
	 * @param int $userRole users.role (0 equipe, 1 portal)
	 */
	protected function _sortMatchesByAbacScopePreference(array &$matches, $userRole) {
		usort($matches, function ($a, $b) use ($userRole) {
			$ra = $this->_abacScopePreferenceRank($a->abac_scope, $userRole);
			$rb = $this->_abacScopePreferenceRank($b->abac_scope, $userRole);
			if ($ra !== $rb) {
				return $ra - $rb;
			}

			return (int)$a->id - (int)$b->id;
		});
	}

	/**
	 * Menor = preferido na ordenação.
	 *
	 * @param string|null $scope
	 * @param int $userRole
	 * @return int
	 */
	protected function _abacScopePreferenceRank($scope, $userRole) {
		$empty = ($scope === null || $scope === '');
		$key = $empty ? '' : strtolower((string)$scope);
		if ($empty) {
			return 10;
		}
		if ((int)$userRole === 0) {
			$order = ['empresa' => 0, 'own' => 1, 'cliente' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}
		if ((int)$userRole === 1) {
			$order = ['cliente' => 0, 'own' => 1, 'empresa' => 2];

			return isset($order[$key]) ? $order[$key] : 5;
		}
		$order = ['empresa' => 0, 'own' => 1, 'cliente' => 2];

		return isset($order[$key]) ? $order[$key] : 5;
	}

	/**
	 * Fase 9 parcial: rbac_audit_authorizations (config audit_decisions_db).
	 *
	 * @param int[] $roleIds
	 * @param string|null $denyReason chave curta em context_json quando granted=false
	 */
	protected function _persistRbacAuditIfConfigured($userId, $granted, $controller, $action, array $cfg, array $roleIds = [], $denyReason = null) {
		$mode = isset($cfg['audit_decisions_db']) ? $cfg['audit_decisions_db'] : false;
		if ($mode === false || $mode === null || $mode === '') {
			return;
		}
		if ($mode !== 'all' && $granted) {
			return;
		}
		if (!$this->_auditTableExists()) {
			return;
		}
		try {
			$code = null;
			if ($granted) {
				$code = $this->getController()->rbacAbacPermissionCode;
				if ($code !== null) {
					$code = substr((string)$code, 0, 120);
				}
			}
			$ctx = ['role_ids' => array_values(array_map('intval', $roleIds))];
			if ($denyReason !== null && $denyReason !== '') {
				$ctx['reason'] = (string)$denyReason;
			}
			$json = json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($json === false) {
				$json = null;
			}
			$row = TableRegistry::get('RbacAuditAuthorizations')->newEntity([
				'user_id' => (int)$userId,
				'granted' => (bool)$granted,
				'controller' => substr(strtolower((string)$controller), 0, 80),
				'action' => substr(strtolower((string)$action), 0, 80),
				'permission_code' => $code,
				'context_json' => $json,
			]);
			TableRegistry::get('RbacAuditAuthorizations')->save($row);
		} catch (\Exception $e) {
			// não interromper request
		}
	}

	protected function _auditTableExists() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_audit_authorizations', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * rbac_permission_policies: pelo menos uma linha ativa deve satisfazer conditions_json (OR por linha).
	 *
	 * @param \App\Model\Entity\RbacPermission $matched
	 */
	protected function _permissionPoliciesAllow($matched) {
		$cfg = Configure::read('Rbac');
		if (!is_array($cfg) || empty($cfg['evaluate_permission_policies'])) {
			return true;
		}
		if (!$this->_permissionPoliciesTableExists()) {
			return true;
		}
		try {
			$rows = TableRegistry::get('RbacPermissionPolicies')->find()
				->where(['rbac_permission_id' => (int)$matched->id, 'active' => true])
				->order(['priority' => 'DESC', 'id' => 'ASC'])
				->all();
			if ($rows->count() === 0) {
				return true;
			}
			$ctx = $this->_policyContextForRequest();
			foreach ($rows as $pol) {
				if (RbacPolicyConditions::matchesOrEmpty($pol->conditions_json, $ctx)) {
					return true;
				}
			}

			return false;
		} catch (\Exception $e) {
			return true;
		}
	}

	protected function _policyContextForRequest(): array {
		$user = $this->getController()->Auth->user();
		$req = $this->getController()->getRequest();
		$prefix = $req->getParam('prefix');
		$prefixStr = ($prefix !== null && $prefix !== false) ? strtolower((string)$prefix) : '';
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
			'request.prefix' => $prefixStr,
			'request.plugin' => strtolower((string)($req->getParam('plugin') ?: '')),
		];
	}

	/**
	 * Lista rbac_api_enforced_actions em config: controller#action minúsculo.
	 */
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

	protected function _permissionPoliciesTableExists() {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_permission_policies', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
