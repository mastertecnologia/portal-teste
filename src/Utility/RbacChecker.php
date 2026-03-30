<?php
namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Utilitário para checagem RBAC/ABAC (uso futuro nos controllers).
 *
 * Hoje o portal usa principalmente users.admin + users.role.
 * Quando rbac_users_roles e rbac_roles_permissions estiverem populados,
 * um Component pode chamar matchPermission() após carregar os códigos do usuário.
 */
class RbacChecker {

	const PERM_ORCAMENTOS_SOLICITAR = 'orcamentos.solicitar';

	/**
	 * Cliente (role=1): pode usar solicitar orçamento e API de catálogo.
	 * — Sem tabelas RBAC: mantém legado (só permissaoacesso).
	 * — Permissão orcamentos.solicitar ainda não existe na base: legado.
	 * — Com RBAC ativo e permissão cadastrada: o usuário deve ter pelo menos um papel e
	 *   uma permissão entre orcamentos.solicitar ou orcamentos.portal_cliente (catálogo amplo).
	 */
	public static function clientePodeSolicitarOrcamento($userId, $permissaoAcesso) {
		if (empty($permissaoAcesso)) {
			return false;
		}
		$userId = (int)$userId;
		if ($userId <= 0) {
			return false;
		}
		try {
			$conn = TableRegistry::get('RbacPermissions')->getConnection();
			$tables = $conn->getSchemaCollection()->listTables();
			if (!in_array('rbac_permissions', $tables, true)
				|| !in_array('rbac_roles_permissions', $tables, true)
				|| !in_array('rbac_users_roles', $tables, true)) {
				return true;
			}
			$perm = TableRegistry::get('RbacPermissions')->find()
				->select(['id'])
				->where(['code' => self::PERM_ORCAMENTOS_SOLICITAR])
				->first();
			if ($perm === null) {
				return true;
			}
			$roleIds = TableRegistry::get('RbacUsersRoles')->find()
				->select(['role_id'])
				->where(['user_id' => $userId])
				->extract('role_id')
				->toList();
			$roleIds = array_values(array_unique(array_map('intval', $roleIds)));
			if ($roleIds === []) {
				return false;
			}
			$permPortal = TableRegistry::get('RbacPermissions')->find()
				->select(['id'])
				->where(['code' => 'orcamentos.portal_cliente'])
				->first();
			$permIds = array_values(array_filter([
				(int)$perm->id,
				$permPortal ? (int)$permPortal->id : 0,
			], static function ($v) {
				return $v > 0;
			}));
			if ($permIds === []) {
				return false;
			}
			$n = TableRegistry::get('RbacRolesPermissions')->find()
				->where(['role_id IN' => $roleIds, 'permission_id IN' => $permIds])
				->count();

			return $n > 0;
		} catch (\Exception $e) {
			return true;
		}
	}

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
