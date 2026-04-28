<?php
use Migrations\AbstractMigration;

class AssetsRedeSistemaCredenciais extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('assets')) {
			return;
		}

		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS usuario VARCHAR(128) NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS senha TEXT NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS porta_interna INTEGER NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS porta_externa INTEGER NULL');

			return;
		}

		$tbl = $this->table('assets');
		if (!$tbl->hasColumn('usuario')) {
			$tbl->addColumn('usuario', 'string', ['limit' => 128, 'null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('senha')) {
			$tbl->addColumn('senha', 'text', ['null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('porta_interna')) {
			$tbl->addColumn('porta_interna', 'integer', ['null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('porta_externa')) {
			$tbl->addColumn('porta_externa', 'integer', ['null' => true, 'default' => null]);
		}
		$tbl->update();
	}

	public function down() {
		// Não remover colunas para preservar dados já cadastrados.
	}
}
