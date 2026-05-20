<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Gates RBAC do shell ERP prototype (menu + isAuthorized + APIs de escrita).
 */
class ErpPrototypeAccess {

	/**
	 * @return array<string, mixed>
	 */
	public static function config(): array {
		$cfg = Configure::read('ErpPrototypeRbac');

		return is_array($cfg) ? $cfg : [];
	}

	/**
	 * Item da sidebar visível (OR de códigos, híbrido sem papéis, admin bypass).
	 *
	 * @param string|string[]|null $codes
	 */
	public static function sidebarItemVisible($admin, $role, int $userId, $codes): bool {
		if ($codes === null || $codes === '') {
			return true;
		}

		return RbacChecker::shouldShowSidebarGate($admin, $role, $userId, $codes);
	}

	/**
	 * Gate por chave do item (sidebar_item_gates).
	 */
	public static function sidebarKeyVisible($admin, $role, int $userId, string $itemKey): bool {
		$cfg = self::config();
		$gates = $cfg['sidebar_item_gates'] ?? [];
		if (!is_array($gates) || !array_key_exists($itemKey, $gates)) {
			return true;
		}

		return self::sidebarItemVisible($admin, $role, $userId, $gates[$itemKey]);
	}

	/**
	 * Autorização Auth::isAuthorized para controllers *Prototype e PrototypeHistory.
	 */
	public static function allows(array $user, string $controller, string $action): bool {
		$role = (int)($user['role'] ?? -1);
		if ($role !== 0) {
			return false;
		}
		$admin = !empty($user['admin']);
		$userId = (int)($user['id'] ?? 0);
		$action = strtolower($action);

		if ($controller === 'PrototypeHistory') {
			if ($action === 'setlocale') {
				return true;
			}

			return $admin;
		}

		if ($controller === 'SistemaPrototype') {
			if ($admin) {
				return true;
			}

			return RbacChecker::shouldShowConfigAdminHub($admin ? 1 : 0, $role, $userId);
		}

		if ($controller === 'EmpresasPrototype' && $admin) {
			return true;
		}

		$cfg = self::config();
		$modules = $cfg['controller_modules'] ?? [];
		if (!is_array($modules) || !isset($modules[$controller])) {
			return true;
		}
		$moduleKey = (string)$modules[$controller];
		$moduleDefs = $cfg['modules'] ?? [];
		if (!is_array($moduleDefs) || !isset($moduleDefs[$moduleKey])) {
			return true;
		}
		$module = $moduleDefs[$moduleKey];

		$writeMap = $cfg['write_actions'] ?? [];
		if (is_array($writeMap) && isset($writeMap[$controller])) {
			$actions = $writeMap[$controller];
			if (is_array($actions) && isset($actions[$action])) {
				$bucket = (string)$actions[$action];
				$codes = $module[$bucket] ?? $module['write'] ?? [];

				return RbacChecker::shouldShowSidebarGate($admin ? 1 : 0, $role, $userId, $codes);
			}
		}

		$viewCodes = $module['view'] ?? [];

		return RbacChecker::shouldShowSidebarGate($admin ? 1 : 0, $role, $userId, $viewCodes);
	}

	/**
	 * Verifica permissão de escrita para endpoint AJAX (após guardApiEquipe).
	 *
	 * @return bool
	 */
	public static function allowsApiWrite(array $user, string $controller, string $action): bool {
		$action = strtolower($action);
		$cfg = self::config();
		$writeMap = $cfg['write_actions'] ?? [];
		if (!is_array($writeMap) || !isset($writeMap[$controller])) {
			return true;
		}
		$actions = $writeMap[$controller];
		if (!is_array($actions) || !isset($actions[$action])) {
			return true;
		}

		return self::allows($user, $controller, $action);
	}
}
