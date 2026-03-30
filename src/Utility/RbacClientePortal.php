<?php
namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Garante vínculo do papel «Cliente portal» (slug cliente_portal) para usuários cliente com acesso ao portal.
 */
class RbacClientePortal {

	const ROLE_SLUG = 'cliente_portal';

	/**
	 * Se o usuário for cliente com permissaoacesso, associa ao papel cliente_portal (idempotente).
	 */
	public static function syncUserIfEligible($userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return;
		}
		try {
			$Users = TableRegistry::get('Users');
			$user = $Users->get($userId);
			if ((int)$user->role !== 1 || empty($user->permissaoacesso)) {
				return;
			}
			$RbacRoles = TableRegistry::get('RbacRoles');
			$role = $RbacRoles->find()->where(['slug' => self::ROLE_SLUG, 'active' => true])->first();
			if ($role === null) {
				return;
			}
			$RbacUsersRoles = TableRegistry::get('RbacUsersRoles');
			$exists = $RbacUsersRoles->find()
				->where(['user_id' => $userId, 'role_id' => $role->id])
				->first();
			if ($exists !== null) {
				return;
			}
			$RbacUsersRoles->save($RbacUsersRoles->newEntity([
				'user_id' => $userId,
				'role_id' => $role->id,
			]));
		} catch (\Exception $e) {
			// Tabelas RBAC ausentes ou usuário inexistente: ignorar
		}
	}
}
