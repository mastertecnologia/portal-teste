<?php
use Migrations\AbstractMigration;

/**
 * Corrige tabelas rbac_permission_legacy_aliases criadas antes da 20260407143000
 * (hasTable fazia skip) ou sem timestamps — as seeds 2026040715* exigem created/modified.
 */
class RbacLegacyAliasesAddTimestampsIfMissing extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		$t = $this->table('rbac_permission_legacy_aliases');
		if (!$t->hasColumn('created')) {
			$t->addColumn('created', 'datetime', ['null' => true, 'default' => null])->update();
		}
		if (!$t->hasColumn('modified')) {
			$t->addColumn('modified', 'datetime', ['null' => true, 'default' => null])->update();
		}
	}

	public function down() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		$t = $this->table('rbac_permission_legacy_aliases');
		if ($t->hasColumn('modified')) {
			$t->removeColumn('modified')->update();
		}
		if ($t->hasColumn('created')) {
			$t->removeColumn('created')->update();
		}
	}
}
