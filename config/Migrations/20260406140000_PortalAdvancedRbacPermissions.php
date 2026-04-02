<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permissões do módulo avançado (ERP + portal) e vínculo ao papel cliente_portal (só portal.*).
 * Catálogo espelhado em config/permissions_registry.php.
 */
class PortalAdvancedRbacPermissions extends AbstractMigration {

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
SELECT 'erp.advanced.contracts', 'Módulo avançado — contratos', 'Operações', 'AdvancedContracts', '*', 'rbac', 'empresa', 'Tabela contracts (comercial avançado).', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.contracts');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'erp.advanced.invoices', 'Módulo avançado — faturas', 'Operações', 'AdvancedInvoices', '*', 'rbac', 'empresa', 'Faturas vinculadas a contracts; marcar paga e export CSV.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.invoices');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'erp.advanced.attendance', 'Módulo avançado — histórico atendimento', 'Operações', 'AdvancedAttendance', '*', 'rbac', 'empresa', 'attendance_histories + timeline (notas internas visíveis).', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.attendance');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'erp.advanced.reports', 'Módulo avançado — indicadores', 'Operações', 'AdvancedReports', '*', 'rbac', 'empresa', 'Resumo tickets + contracts + invoices no período.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.reports');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'portal.advanced.contracts', 'Portal — contratos avançados', 'Portal clientes', 'PortalAdvancedContracts', '*', 'rbac', 'cliente', 'Leitura contracts + documentos públicos.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.contracts');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'portal.advanced.invoices', 'Portal — faturas avançadas', 'Portal clientes', 'PortalAdvancedInvoices', '*', 'rbac', 'cliente', 'Leitura invoices + export CSV do próprio cliente.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.invoices');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'portal.advanced.attendance', 'Portal — histórico atendimento avançado', 'Portal clientes', 'PortalAdvancedAttendance', '*', 'rbac', 'cliente', 'Timeline sem notas internas.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.attendance');
SQL
			);
		} else {
			$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.advanced.contracts', 'Módulo avançado — contratos', 'Operações', 'AdvancedContracts', '*', 'rbac', 'empresa', 'Tabela contracts (comercial avançado).', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.contracts');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.advanced.invoices', 'Módulo avançado — faturas', 'Operações', 'AdvancedInvoices', '*', 'rbac', 'empresa', 'Faturas vinculadas a contracts; marcar paga e export CSV.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.invoices');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.advanced.attendance', 'Módulo avançado — histórico atendimento', 'Operações', 'AdvancedAttendance', '*', 'rbac', 'empresa', 'attendance_histories + timeline (notas internas visíveis).', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.attendance');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'erp.advanced.reports', 'Módulo avançado — indicadores', 'Operações', 'AdvancedReports', '*', 'rbac', 'empresa', 'Resumo tickets + contracts + invoices no período.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'erp.advanced.reports');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'portal.advanced.contracts', 'Portal — contratos avançados', 'Portal clientes', 'PortalAdvancedContracts', '*', 'rbac', 'cliente', 'Leitura contracts + documentos públicos.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.contracts');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'portal.advanced.invoices', 'Portal — faturas avançadas', 'Portal clientes', 'PortalAdvancedInvoices', '*', 'rbac', 'cliente', 'Leitura invoices + export CSV do próprio cliente.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.invoices');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'portal.advanced.attendance', 'Portal — histórico atendimento avançado', 'Portal clientes', 'PortalAdvancedAttendance', '*', 'rbac', 'cliente', 'Timeline sem notas internas.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'portal.advanced.attendance');
SQL
			);
		}

		if ($this->hasTable('rbac_roles_permissions') && $this->hasTable('rbac_roles')) {
			$this->execute(<<<'SQL'
INSERT INTO rbac_roles_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM rbac_roles r
JOIN rbac_permissions p ON p.code IN (
	'portal.advanced.contracts',
	'portal.advanced.invoices',
	'portal.advanced.attendance'
)
WHERE r.slug = 'cliente_portal'
ON CONFLICT DO NOTHING
SQL
			);
		}
	}

	public function down() {
		// Não remove permissões em produção
	}
}
