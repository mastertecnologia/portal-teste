<?php
use Migrations\AbstractMigration;

/**
 * Alias legado permissoes.admin → permissoes.fields.manage (Fase 4/6).
 * Requer catálogo sincronizado (rbac_permissions com este code).
 */
class RbacLegacyAliasPermissoesFieldPermissions extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['permissoes.admin', 'permissoes.fields.manage'],
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
				"SELECT '%s', '%s', 'permissoes.admin → granular (campos)', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
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
