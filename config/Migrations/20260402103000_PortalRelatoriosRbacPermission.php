<?php
use Migrations\AbstractMigration;

/**
 * Permissão portal.relatorios + vínculo ao papel cliente_portal (RBAC).
 * Catálogo também em config/permissions_registry.php (sincronizar via Permissões se necessário).
 */
class PortalRelatoriosRbacPermission extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}

		// Esquemas legados podem não ter created/modified em rbac_permissions.
		$t = $this->table('rbac_permissions');
		$hasTs = $t->hasColumn('created') && $t->hasColumn('modified');
		if ($hasTs) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'portal.relatorios', 'Portal — relatórios', 'Portal clientes', 'PortalRelatorios', '*', 'rbac', 'cliente', 'Resumo e exportação CSV/Excel sem dados operacionais internos.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.relatorios')
SQL
			);
		} else {
			$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'portal.relatorios', 'Portal — relatórios', 'Portal clientes', 'PortalRelatorios', '*', 'rbac', 'cliente', 'Resumo e exportação CSV/Excel sem dados operacionais internos.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.relatorios')
SQL
			);
		}

		if ($this->hasTable('rbac_roles_permissions') && $this->hasTable('rbac_roles')) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_roles_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM rbac_roles r
JOIN rbac_permissions p ON p.code = 'portal.relatorios'
WHERE r.slug = 'cliente_portal'
ON CONFLICT DO NOTHING
SQL
			);
		}
	}

	public function down() {
		// Não remove permissão em produção
	}
}
