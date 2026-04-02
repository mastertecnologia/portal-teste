<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permissões para controllers canónicos ContractManagement + ContractTemplates.
 * Quem já tinha erp.advanced.contracts recebe as duas (paridade com listagem legada).
 * Sincronizar catálogo: PermissoesController::adminSyncRegistry (config/permissions_registry.php).
 */
class ContractCanonicalRbacPermissions extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}

		$t = $this->table('rbac_permissions');
		$hasTs = $t->hasColumn('created') && $t->hasColumn('modified');

		if ($hasTs) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'erp.contracts.management', 'Contratos — gestão (canónico)', 'Operações', 'ContractManagement', '*', 'rbac', 'empresa', 'Rotas /modulo-contratos/*.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.contracts.management');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'erp.contracts.templates', 'Contratos — modelos', 'Operações', 'ContractTemplates', '*', 'rbac', 'empresa', 'CRUD /contract-templates/*.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.contracts.templates');
SQL
			);
		} else {
			$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.contracts.management', 'Contratos — gestão (canónico)', 'Operações', 'ContractManagement', '*', 'rbac', 'empresa', 'Rotas /modulo-contratos/*.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.contracts.management');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.contracts.templates', 'Contratos — modelos', 'Operações', 'ContractTemplates', '*', 'rbac', 'empresa', 'CRUD /contract-templates/*.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.contracts.templates');
SQL
			);
		}

		if ($this->hasTable('rbac_roles_permissions') && $this->hasTable('rbac_permissions')) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_roles_permissions (role_id, permission_id)
SELECT DISTINCT rrp.role_id, p_new.id
FROM rbac_roles_permissions rrp
INNER JOIN rbac_permissions p_old ON p_old.id = rrp.permission_id AND p_old.code = 'erp.advanced.contracts'
CROSS JOIN rbac_permissions p_new
WHERE p_new.code IN ('erp.contracts.management', 'erp.contracts.templates')
ON CONFLICT DO NOTHING
SQL
			);
		}
	}

	public function down() {
	}
}
