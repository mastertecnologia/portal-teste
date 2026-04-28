<?php
use Migrations\AbstractMigration;

class AddAssetsNfeReferencia extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('assets')) {
			return;
		}
		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS nfe_referencia VARCHAR(64) NULL');
			$this->execute('CREATE INDEX IF NOT EXISTS ix_assets_nfe_referencia ON assets (nfe_referencia)');
			return;
		}

		$t = $this->table('assets');
		if (!$t->hasColumn('nfe_referencia')) {
			$t->addColumn('nfe_referencia', 'string', [
				'limit' => 64,
				'null' => true,
				'default' => null,
			])->update();
		}
		try {
			if (!$t->hasIndexByName('ix_assets_nfe_referencia')) {
				$t->addIndex(['nfe_referencia'], ['name' => 'ix_assets_nfe_referencia'])->update();
			}
		} catch (\Throwable $e) {
		}
	}

	public function down() {
		// Mantém coluna para preservar dados existentes.
	}
}
