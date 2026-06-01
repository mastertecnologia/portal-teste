<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permissões licencas.* (paridade com config/permissions_registry.php).
 */
class RbacLicencasModulePermissions extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$t = $this->table('rbac_permissions');
		$hasTs = $t->hasColumn('created') && $t->hasColumn('modified');
		$rows = [
			['licencas.view', 'Licenciamento — visualizar', 'Licenciamento', 'LicencasPrototype', '*', 'empresa', 'Painel e listagens do módulo licenças.'],
			['licencas.manage', 'Licenciamento — gerenciar', 'Licenciamento', 'LicencasPrototype', '*', 'empresa', 'CRUD licenças, catálogo, renovações.'],
			['licencas.cofre.view', 'Licenciamento — cofre (metadados)', 'Licenciamento', 'LicencasPrototype', 'view', 'empresa', 'Lista cofre; revelar segredo exige licencas.cofre.secret.'],
			['licencas.cofre.secret', 'Licenciamento — revelar credencial', 'Licenciamento', 'LicencasPrototype', '*', 'empresa', 'Visualização auditada de secret_blob.'],
		];
		foreach ($rows as $r) {
			$this->insertPermission($r, $hasTs);
		}
	}

	/**
	 * @param array{0:string,1:string,2:string,3:string,4:string,5:string,6:string} $r
	 */
	protected function insertPermission(array $r, bool $hasTs): void {
		list($code, $name, $module, $controller, $action, $scope, $desc) = $r;
		$code = str_replace("'", "''", $code);
		$name = str_replace("'", "''", $name);
		$module = str_replace("'", "''", $module);
		$controller = str_replace("'", "''", $controller);
		$action = str_replace("'", "''", $action);
		$scope = str_replace("'", "''", $scope);
		$desc = str_replace("'", "''", $desc);
		if ($hasTs) {
			$this->execute(
				"INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified) "
				. "SELECT '{$code}', '{$name}', '{$module}', '{$controller}', '{$action}', 'rbac', '{$scope}', '{$desc}', 0, NOW(), NOW() "
				. "WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = '{$code}')"
			);
		} else {
			$this->execute(
				"INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order) "
				. "SELECT '{$code}', '{$name}', '{$module}', '{$controller}', '{$action}', 'rbac', '{$scope}', '{$desc}', 0 "
				. "WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = '{$code}')"
			);
		}
	}

	public function down() {
		// Aditivo — não remove permissões em uso.
	}
}
