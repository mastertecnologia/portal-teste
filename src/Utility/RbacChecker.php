<?php
namespace App\Utility;

/**
 * Utilitário para checagem RBAC/ABAC (uso futuro nos controllers).
 *
 * Hoje o portal usa principalmente users.admin + users.role.
 * Quando rbac_users_roles e rbac_roles_permissions estiverem populados,
 * um Component pode chamar matchPermission() após carregar os códigos do usuário.
 */
class RbacChecker {

	/**
	 * Verifica se controller/ação batem com uma linha de permissão (action * = qualquer).
	 */
	public static function matchAction($controller, $action, array $permissionRow) {
		$c = strtolower(isset($permissionRow['controller']) ? $permissionRow['controller'] : '');
		$a = strtolower(isset($permissionRow['action']) ? (string)$permissionRow['action'] : '*');
		if (strtolower($controller) !== $c) {
			return false;
		}
		if ($a === '' || $a === '*') {
			return true;
		}

		return strtolower($action) === $a;
	}
}
