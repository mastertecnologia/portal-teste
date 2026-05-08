<?php
use Migrations\AbstractMigration;

class ContractConsumptionsSourceIdempotency extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_consumptions')) {
			return;
		}
		$this->execute(<<<'SQL'
ALTER TABLE contract_consumptions
	ADD COLUMN IF NOT EXISTS source_type VARCHAR(50) NULL,
	ADD COLUMN IF NOT EXISTS source_id VARCHAR(100) NULL,
	ADD COLUMN IF NOT EXISTS source_hash VARCHAR(191) NULL
SQL
		);
		$this->execute('CREATE UNIQUE INDEX IF NOT EXISTS ux_contract_consumptions_source_hash ON contract_consumptions (source_hash) WHERE source_hash IS NOT NULL');
		$this->execute('CREATE UNIQUE INDEX IF NOT EXISTS ux_contract_consumptions_source_origin ON contract_consumptions (contract_id, source_type, source_id) WHERE source_type IS NOT NULL AND source_id IS NOT NULL');
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contract_consumptions')) {
			return;
		}
		try {
			$this->execute('DROP INDEX IF EXISTS ux_contract_consumptions_source_origin');
			$this->execute('DROP INDEX IF EXISTS ux_contract_consumptions_source_hash');
			$this->execute(<<<'SQL'
ALTER TABLE contract_consumptions
	DROP COLUMN IF EXISTS source_hash,
	DROP COLUMN IF EXISTS source_id,
	DROP COLUMN IF EXISTS source_type
SQL
			);
		} catch (\Throwable $e) {
		}
	}
}
