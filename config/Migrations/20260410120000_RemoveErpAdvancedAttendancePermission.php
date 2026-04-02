<?php
use Migrations\AbstractMigration;

/**
 * Remove permissão RBAC do histórico consolidado ERP (AdvancedAttendance), funcionalidade retirada.
 * Mantém portal.advanced.attendance (PortalAdvancedAttendance) e tabelas attendance_histories.
 */
class RemoveErpAdvancedAttendancePermission extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		if ($this->hasTable('rbac_roles_permissions')) {
			$this->execute(
				"DELETE FROM rbac_roles_permissions WHERE permission_id IN (SELECT id FROM rbac_permissions WHERE code = 'erp.advanced.attendance')"
			);
		}
		$this->execute("DELETE FROM rbac_permissions WHERE code = 'erp.advanced.attendance'");
	}

	public function down() {
		// Funcionalidade removida do código; não reintroduz permissão automaticamente.
	}
}
