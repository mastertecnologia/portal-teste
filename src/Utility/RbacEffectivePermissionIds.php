<?php
namespace App\Utility;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Conjunto de rbac_permissions.id efetivos por utilizador: papéis diretos + grupos,
 * com expansão opcional via rbac_permission_legacy_aliases (alinhado a RbacComponent).
 */
class RbacEffectivePermissionIds {

	/**
	 * IDs de permissão ligados aos papéis efetivos do utilizador (sem expandir aliases).
	 *
	 * @return int[]
	 */
	public static function roleLinkPermissionIds(int $userId): array {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return [];
		}
		try {
			$roleIds = RbacUserRolesResolver::effectiveRoleIds($userId);
			if ($roleIds === []) {
				return [];
			}
			$permIds = TableRegistry::get('RbacRolesPermissions')->find()
				->select(['permission_id'])
				->where(['role_id IN' => $roleIds])
				->extract('permission_id')
				->toList();

			return array_values(array_unique(array_map('intval', $permIds)));
		} catch (\Exception $e) {
			return [];
		}
	}

	/**
	 * Mapa permission_id => true após aplicar expand_legacy_aliases conforme config/rbac.php.
	 *
	 * @return array<int, true>
	 */
	public static function effectivePermissionIdMapForUser(int $userId): array {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return [];
		}
		try {
			$permIds = self::roleLinkPermissionIds($userId);
			$cfg = Configure::read('Rbac');
			$expandAliases = true;
			if (is_array($cfg) && array_key_exists('expand_legacy_aliases', $cfg)) {
				$expandAliases = (bool)$cfg['expand_legacy_aliases'];
			}
			if ($expandAliases && $permIds !== []) {
				$permIds = RbacPermissionResolver::expandPermissionIds($permIds);
			}
			$map = [];
			foreach ($permIds as $pid) {
				if ($pid > 0) {
					$map[$pid] = true;
				}
			}

			return $map;
		} catch (\Exception $e) {
			return [];
		}
	}
}
