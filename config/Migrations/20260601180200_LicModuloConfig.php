<?php
use Migrations\AbstractMigration;

/**
 * Configurações do módulo Licenciamento por empresa (pg-lic-config).
 */
class LicModuloConfig extends AbstractMigration {

	public function change() {
		if (!$this->hasTable('lic_modulo_config')) {
			$this->table('lic_modulo_config')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('alerta_vencimento_dias', 'integer', ['null' => false, 'default' => 30])
				->addColumn('notificar_email', 'string', ['limit' => 180, 'null' => true, 'default' => null])
				->addColumn('cofre_exige_aprovacao', 'boolean', ['null' => false, 'default' => false])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa'], ['unique' => true])
				->create();
		}
	}
}
