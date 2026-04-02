<?php
/**
 * NCM por produto para cálculo de tributos aproximados (IBPT) em fatura/recibo de locação.
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ProdutosNcmIbpt extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('produtos')) {
			return;
		}
		$this->execute('ALTER TABLE produtos ADD COLUMN IF NOT EXISTS ncm VARCHAR(12) NULL');
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('produtos')) {
			return;
		}
		$this->execute('ALTER TABLE produtos DROP COLUMN IF EXISTS ncm');
	}
}
