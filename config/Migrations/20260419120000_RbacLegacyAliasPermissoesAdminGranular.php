<?php
use Migrations\AbstractMigration;

/**
 * Expandir macro permissoes.admin para códigos granulares (Fase 2/7): relatório efetivo, grupos, auditoria, políticas.
 * Requer catálogo sincronizado (rbac_permissions com estes codes).
 */
class RbacLegacyAliasPermissoesAdminGranular extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['permissoes.admin', 'permissoes.users.effective'],
		['permissoes.admin', 'permissoes.groups.manage'],
		['permissoes.admin', 'permissoes.audit.view'],
		['permissoes.admin', 'permissoes.policies.manage'],
	];

	public function up() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"INSERT INTO rbac_permission_legacy_aliases (legacy_code, canonical_code, notes, created, modified) " .
				"SELECT '%s', '%s', 'permissoes.admin → granular (Fase 4–5)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
