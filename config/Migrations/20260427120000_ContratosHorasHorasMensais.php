<?php
/**
 * contratos_horas.horas_mensais — horas por mês para exibição «N horas mensais» no Service Desk.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ContratosHorasHorasMensais extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contratos_horas')) {
			return;
		}
		$this->execute('ALTER TABLE contratos_horas ADD COLUMN IF NOT EXISTS horas_mensais NUMERIC(14,4) NULL');
		$this->execute(<<<'SQL'
COMMENT ON COLUMN contratos_horas.horas_mensais IS 'Horas por mês do plano; informado no cadastro exibe «N horas mensais» no resumo do contrato (ticket).'
SQL
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contratos_horas')) {
			return;
		}
		try {
			$this->execute('ALTER TABLE contratos_horas DROP COLUMN IF EXISTS horas_mensais');
		} catch (\Throwable $e) {
		}
	}
}
