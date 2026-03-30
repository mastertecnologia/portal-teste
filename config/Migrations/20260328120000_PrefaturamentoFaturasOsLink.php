<?php
/**
 * Pré-faturamento: vínculo fatura (locação) ↔ ordens de serviço + flags de conferência na OS.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class PrefaturamentoFaturasOsLink extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('faturas')) {
			return;
		}
		$osTable = null;
		foreach (['ordensservico', 'ordensservicos'] as $t) {
			if ($this->hasTable($t)) {
				$osTable = $t;
				break;
			}
		}
		if ($osTable === null) {
			return;
		}

		if (!$this->hasTable('faturas_ordens_servico')) {
			$t = $this->table('faturas_ordens_servico');
			$t->addColumn('idfatura', 'integer', ['null' => false]);
			$t->addColumn('idordem', 'integer', ['null' => false]);
			$t->addColumn('idempresa', 'integer', ['null' => false]);
			$t->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['idempresa', 'idfatura', 'idordem'], ['unique' => true, 'name' => 'uq_fat_os_empresa_fat_ordem']);
			$t->create();
		}

		$os = $this->table($osTable);
		if (!$os->hasColumn('prefat_conf_exec')) {
			$os->addColumn('prefat_conf_exec', 'boolean', ['default' => false, 'null' => false]);
		}
		if (!$os->hasColumn('prefat_conf_comercial')) {
			$os->addColumn('prefat_conf_comercial', 'boolean', ['default' => false, 'null' => false]);
		}
		if (!$os->hasColumn('prefat_conf_fiscal')) {
			$os->addColumn('prefat_conf_fiscal', 'boolean', ['default' => false, 'null' => false]);
		}
		if (!$os->hasColumn('prefat_conf_em')) {
			$os->addColumn('prefat_conf_em', 'timestamp', ['null' => true, 'default' => null]);
		}
		if (!$os->hasColumn('prefat_conf_iduser')) {
			$os->addColumn('prefat_conf_iduser', 'integer', ['null' => true, 'default' => null]);
		}
		$os->update();
	}

	public function down() {
		if ($this->hasTable('faturas_ordens_servico')) {
			$this->execute('DROP TABLE IF EXISTS faturas_ordens_servico');
		}
	}
}
