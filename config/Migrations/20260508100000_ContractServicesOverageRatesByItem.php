<?php
use Migrations\AbstractMigration;

class ContractServicesOverageRatesByItem extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_services')) {
			return;
		}
		$this->execute(<<<'SQL'
ALTER TABLE contract_services
	ADD COLUMN IF NOT EXISTS unit_overage_rate NUMERIC(12,2) NULL,
	ADD COLUMN IF NOT EXISTS business_hour_rate NUMERIC(12,2) NULL,
	ADD COLUMN IF NOT EXISTS after_hours_rate NUMERIC(12,2) NULL,
	ADD COLUMN IF NOT EXISTS weekend_holiday_rate NUMERIC(12,2) NULL
SQL
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_services')) {
			return;
		}
		try {
			$this->execute(<<<'SQL'
ALTER TABLE contract_services
	DROP COLUMN IF EXISTS unit_overage_rate,
	DROP COLUMN IF EXISTS business_hour_rate,
	DROP COLUMN IF EXISTS after_hours_rate,
	DROP COLUMN IF EXISTS weekend_holiday_rate
SQL
			);
		} catch (\Throwable $e) {
		}
	}
}
