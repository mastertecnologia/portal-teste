<?php
namespace App\Controller\Component;

use App\Utility\RbacChecker;
use App\Utility\RbacPermissionResolver;
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

		$c = strtolower((string)$controller);
		$a = strtolower((string)$action);

		foreach ($cfg['skip_action_prefixes'] as $prefix) {
			$prefix = strtolower((string)$prefix);
			if ($prefix !== '' && strpos($a, $prefix) === 0) {
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
					$msg = 'Sua conta ainda não tem papéis atribuídos no novo controle de acesso. Contate um administrador.';
					$this->getController()->Flash->error($msg);

					return $this->getController()->redirect(['controller' => 'Users', 'action' => 'dashboard']);
				}
			}

			return null;
		}

		if ($this->_userCanAccess($roleIds, $controller, $action)) {
			$this->_persistRbacAuditIfConfigured($uid, true, $controller, $action, $cfg, $roleIds);

			return null;
		}

		$this->_persistRbacAuditIfConfigured($uid, false, $controller, $action, $cfg, $roleIds, 'no_matching_permission');

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
			$this->getController()->Flash->error($msg);

			return $this->getController()->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}

		return null;
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
		$matched = $this->_findBestMatchingPermission($roleIds, $controller, $action);
		if ($matched === null) {
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
}
