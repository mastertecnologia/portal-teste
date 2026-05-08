<?php
use Migrations\AbstractMigration;

class ContractConsumptionsItemPeriodLink extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_consumptions')) {
			return;
		}
		$this->execute(<<<'SQL'
ALTER TABLE contract_consumptions
	ADD COLUMN IF NOT EXISTS contract_service_id INTEGER NULL,
	ADD COLUMN IF NOT EXISTS period_type VARCHAR(40) NULL,
	ADD COLUMN IF NOT EXISTS consumed_quantity NUMERIC(12,4) NULL
SQL
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_consumptions')) {
			return;
		}
		try {
			$this->execute(<<<'SQL'
ALTER TABLE contract_consumptions
	DROP COLUMN IF EXISTS consumed_quantity,
	DROP COLUMN IF EXISTS period_type,
	DROP COLUMN IF EXISTS contract_service_id
SQL
			);
		} catch (\Throwable $e) {
		}
	}
}
