<?php
use Migrations\AbstractMigration;

/**
 * Quem já tinha tickets.timer passa a reconhecer tickets.audit_timer (Audit::apiValidate).
 */
class RbacLegacyAliasTicketAuditTimer extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['tickets.timer', 'tickets.audit_timer'],
	];

	public function up() {
		if ($this->hasTable('rbac_permissions')) {
			$t = $this->table('rbac_permissions');
			$hasTs = $t->hasColumn('created') && $t->hasColumn('modified');
			if ($hasTs) {
				$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'tickets.audit_timer', 'Tickets — auditoria de tempo (API)', 'Tickets', 'Audit', 'apiValidate,api_validate', 'rbac', 'empresa', 'POST /api/audit/validate — log de ajuste manual (senha de auditoria).', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'tickets.audit_timer');
SQL
				);
			} else {
				$this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'tickets.audit_timer', 'Tickets — auditoria de tempo (API)', 'Tickets', 'Audit', 'apiValidate,api_validate', 'rbac', 'empresa', 'POST /api/audit/validate — log de ajuste manual (senha de auditoria).', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'tickets.audit_timer');
SQL
				);
			}
		}
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"INSERT INTO rbac_permission_legacy_aliases (legacy_code, canonical_code, notes, created, modified) " .
				"SELECT '%s', '%s', 'Audit::apiValidate (timer audit log)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
				"WHERE NOT EXISTS (SELECT 1 FROM rbac_permission_legacy_aliases e WHERE e.legacy_code = '%s' AND e.canonical_code = '%s')",
				$legacy,
				$canonical,
				$legacy,
				$canonical
			));
		}
	}

	public function down() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"DELETE FROM rbac_permission_legacy_aliases WHERE legacy_code = '%s' AND canonical_code = '%s'",
				$legacy,
				$canonical
			));
		}
	}
}
