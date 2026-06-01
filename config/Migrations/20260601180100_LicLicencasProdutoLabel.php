<?php
use Migrations\AbstractMigration;

class LicLicencasProdutoLabel extends AbstractMigration {

	public function change() {
		if ($this->hasTable('lic_licencas') && !$this->table('lic_licencas')->hasColumn('produto_label')) {
			$this->table('lic_licencas')
				->addColumn('produto_label', 'string', ['limit' => 200, 'null' => true, 'default' => null, 'after' => 'idcatalogo'])
				->update();
		}
	}
}
