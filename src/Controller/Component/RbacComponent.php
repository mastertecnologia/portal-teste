<?php
namespace App\Controller\Component;

use App\Utility\RbacChecker;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Verifica catálogo RBAC quando o usuário possui papéis em rbac_users_roles.
 */
class RbacComponent extends Component {

	/**
	 * @return string|null Response redirect if access denied (enforce); null to continue
	 */
	public function checkRequest($controller, $action) {
		$cfg = Configure::read('Rbac');
		if (empty($cfg) || empty($cfg['mode']) || $cfg['mode'] === 'off') {
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
			return null;
		}

		if ($this->_userCanAccess($roleIds, $controller, $action)) {
			return null;
		}

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
		$rows = TableRegistry::get('RbacUsersRoles')->find()
			->select(['role_id'])
			->where(['user_id' => $uid])
			->extract('role_id')
			->toList();

		return array_values(array_unique(array_map('intval', $rows)));
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

		return $best;
	}
}
