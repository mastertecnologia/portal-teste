<?php
use Migrations\AbstractMigration;

class AssetsSoLicencas extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('assets')) {
			return;
		}

		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS so_edicao VARCHAR(48) NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS windows_chave VARCHAR(64) NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS office_versao VARCHAR(48) NULL');
			$this->execute('ALTER TABLE assets ADD COLUMN IF NOT EXISTS office_chave VARCHAR(64) NULL');

			return;
		}

		$tbl = $this->table('assets');
		if (!$tbl->hasColumn('so_edicao')) {
			$tbl->addColumn('so_edicao', 'string', ['limit' => 48, 'null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('windows_chave')) {
			$tbl->addColumn('windows_chave', 'string', ['limit' => 64, 'null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('office_versao')) {
			$tbl->addColumn('office_versao', 'string', ['limit' => 48, 'null' => true, 'default' => null]);
		}
		if (!$tbl->hasColumn('office_chave')) {
			$tbl->addColumn('office_chave', 'string', ['limit' => 64, 'null' => true, 'default' => null]);
		}
		$tbl->update();
	}

	public function down() {
		// Não remover colunas para preservar dados já cadastrados.
	}
}
