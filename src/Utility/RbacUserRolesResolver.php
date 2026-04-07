<?php
namespace App\Utility;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Papéis RBAC efetivos de um utilizador: diretos (rbac_users_roles) + herdados de grupos (Fase 3).
 * Usado pelo RbacComponent e pelo relatório admin (Permissões efetivas).
 */
class RbacUserRolesResolver {

	/**
	 * @return int[]
	 */
	public static function effectiveRoleIds(int $userId): array {
		if ($userId <= 0) {
			return [];
		}
		$rows = TableRegistry::get('RbacUsersRoles')->find()
			->select(['role_id'])
			->where(['user_id' => $userId])
			->extract('role_id')
			->toList();

		$roleIds = array_values(array_unique(array_map('intval', $rows)));

		$cfg = Configure::read('Rbac');
		if (!is_array($cfg)) {
			$cfg = [];
		}
		$expandGroups = !array_key_exists('expand_group_roles', $cfg) || $cfg['expand_group_roles'];
		if ($expandGroups && self::groupTablesExist()) {
			try {
				$groupIds = TableRegistry::get('RbacUserGroups')->find()
					->select(['group_id'])
					->where(['user_id' => $userId])
					->extract('group_id')
					->toList();
				$groupIds = array_values(array_unique(array_map('intval', $groupIds)));
				if ($groupIds !== []) {
					$fromGroups = TableRegistry::get('RbacGroupRoles')->find()
						->select(['role_id'])
						->where(['group_id IN' => $groupIds])
						->extract('role_id')
						->toList();
					$fromGroups = array_values(array_unique(array_map('intval', $fromGroups)));
					if ($fromGroups !== []) {
						$roleIds = array_values(array_unique(array_merge($roleIds, $fromGroups)));
					}
				}
			} catch (\Exception $e) {
				// mantém só papéis diretos
			}
		}

		return $roleIds;
	}

	public static function groupTablesExist(): bool {
		try {
			$tables = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_user_groups', $tables, true)
				&& in_array('rbac_group_roles', $tables, true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
