<?php
/**
 * Fecha gaps do MODULO_CONTRATOS_COMPLETO §1.1–1.2 face a 20260407100000_ContractModulePhase1Expand.
 *
 * - contracts: assinatura (provider, URLs, timestamps), alarga autentique_doc_id.
 * - contract_signatories: auth/action Autentique, IDs de signatário, datas recusa/visualização.
 * - contract_notifications: tipo VARCHAR(60).
 *
 * Não adiciona segunda coluna de status em contracts nem notes_client (usa-se observacoes_cli + ORM).
 * PostgreSQL apenas. Legado locação (faturas) / clicontratos não é alterado.
 */
use Migrations\AbstractMigration;

class ContractModulePhase1bSchemaGaps extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		$this->_expandContractsGaps();
		$this->_expandContractSignatoriesGaps();
		$this->_expandContractNotificationsGaps();
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		$this->_shrinkContractNotificationsGaps();
		$this->_shrinkContractSignatoriesGaps();
		$this->_shrinkContractsGaps();
	}

	protected function _expandContractsGaps() {
		if (!$this->hasTable('contracts')) {
			return;
		}
		$sql = <<<'SQL'
ALTER TABLE contracts
	ADD COLUMN IF NOT EXISTS signature_provider VARCHAR(50) DEFAULT 'autentique',
	ADD COLUMN IF NOT EXISTS signed_file_url TEXT NULL,
	ADD COLUMN IF NOT EXISTS sent_for_signature_at TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS fully_signed_at TIMESTAMP WITHOUT TIME ZONE NULL
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
		try {
			$this->execute('ALTER TABLE contracts ALTER COLUMN autentique_doc_id TYPE VARCHAR(255)');
		} catch (\Throwable $e) {
		}
	}

	protected function _expandContractSignatoriesGaps() {
		if (!$this->hasTable('contract_signatories')) {
			return;
		}
		$sql = <<<'SQL'
ALTER TABLE contract_signatories
	ADD COLUMN IF NOT EXISTS auth_type VARCHAR(50) NOT NULL DEFAULT 'email',
	ADD COLUMN IF NOT EXISTS action_type VARCHAR(30) NOT NULL DEFAULT 'SIGN',
	ADD COLUMN IF NOT EXISTS autentique_signer_id VARCHAR(255) NULL,
	ADD COLUMN IF NOT EXISTS visualizado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS recusado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS motivo_recusa TEXT NULL
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}

	protected function _expandContractNotificationsGaps() {
		if (!$this->hasTable('contract_notifications')) {
			return;
		}
		try {
			$this->execute('ALTER TABLE contract_notifications ALTER COLUMN tipo TYPE VARCHAR(60)');
		} catch (\Throwable $e) {
		}
	}

	protected function _shrinkContractsGaps() {
		if (!$this->hasTable('contracts')) {
			return;
		}
		try {
			$this->execute('ALTER TABLE contracts ALTER COLUMN autentique_doc_id TYPE VARCHAR(100)');
		} catch (\Throwable $e) {
		}
		$sql = <<<'SQL'
ALTER TABLE contracts
	DROP COLUMN IF EXISTS fully_signed_at,
	DROP COLUMN IF EXISTS sent_for_signature_at,
	DROP COLUMN IF EXISTS signed_file_url,
	DROP COLUMN IF EXISTS signature_provider
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}

	protected function _shrinkContractSignatoriesGaps() {
		if (!$this->hasTable('contract_signatories')) {
			return;
		}
		$sql = <<<'SQL'
ALTER TABLE contract_signatories
	DROP COLUMN IF EXISTS motivo_recusa,
	DROP COLUMN IF EXISTS recusado_em,
	DROP COLUMN IF EXISTS visualizado_em,
	DROP COLUMN IF EXISTS autentique_signer_id,
	DROP COLUMN IF EXISTS action_type,
	DROP COLUMN IF EXISTS auth_type
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}

	protected function _shrinkContractNotificationsGaps() {
		if (!$this->hasTable('contract_notifications')) {
			return;
		}
		try {
			$this->execute('ALTER TABLE contract_notifications ALTER COLUMN tipo TYPE VARCHAR(50)');
		} catch (\Throwable $e) {
		}
	}
}
