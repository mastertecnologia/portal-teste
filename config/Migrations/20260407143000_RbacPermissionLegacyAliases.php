<?php
use Migrations\AbstractMigration;

/**
 * Fase 2 (infra): mapeamento legado → permissão canónica (ex.: várias linhas para um legado que expande em várias permissões).
 * Resolução em runtime será implementada nos services de autorização (Fase 3/7); esta migration só cria a tabela.
 */
class RbacPermissionLegacyAliases extends AbstractMigration {

	public function up() {
		if ($this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		$this->table('rbac_permission_legacy_aliases')
			->addColumn('legacy_code', 'string', ['limit' => 120, 'null' => false])
			->addColumn('canonical_code', 'string', ['limit' => 120, 'null' => false])
			->addColumn('notes', 'text', ['null' => true, 'default' => null])
			->addTimestamps()
			->addIndex(['legacy_code', 'canonical_code'], ['unique' => true, 'name' => 'ux_rpla_legacy_canonical'])
			->addIndex(['legacy_code'], ['name' => 'ix_rpla_legacy'])
			->addIndex(['canonical_code'], ['name' => 'ix_rpla_canonical'])
			->create();
	}

	public function down() {
		if ($this->hasTable('rbac_permission_legacy_aliases')) {
			$this->table('rbac_permission_legacy_aliases')->drop()->save();
		}
	}
}
