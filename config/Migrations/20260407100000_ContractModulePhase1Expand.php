<?php
/**
 * Módulo de contratos — Fase 1 (schema apenas).
 *
 * - Novas tabelas: contract_templates, contract_signatories, contract_autentique_logs,
 *   contract_renewals, contract_notifications (spec MODULO_CONTRATOS_COMPLETO).
 * - Expande `contracts` só com colunas que NÃO duplicam o modelo inglês existente
 *   (code, type, start_date, end_date, monthly_value, included_hours, notes, auto_renew…).
 * - Expande `contract_services` com campos da spec §1.7.
 *
 * PostgreSQL apenas (mesmo padrão das migrations do módulo avançado).
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ContractModulePhase1Expand extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('contracts')) {
			return;
		}

		if (!$this->hasTable('contract_templates')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_templates (
	id SERIAL PRIMARY KEY,
	idempresa INTEGER NOT NULL,
	nome VARCHAR(150) NOT NULL,
	tipo_contrato VARCHAR(40) DEFAULT 'servico',
	descricao TEXT NULL,
	conteudo_html TEXT NOT NULL DEFAULT '',
	clausulas_padrao JSONB NOT NULL DEFAULT '[]'::jsonb,
	variaveis JSONB NOT NULL DEFAULT '[]'::jsonb,
	ativo BOOLEAN NOT NULL DEFAULT TRUE,
	versao INTEGER NOT NULL DEFAULT 1,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_templates_empresa ON contract_templates (idempresa);
CREATE INDEX idx_contract_templates_ativo ON contract_templates (ativo);
SQL
			);
			if ($this->hasTable('empresas')) {
				try {
					$this->execute('ALTER TABLE contract_templates ADD CONSTRAINT fk_contract_templates_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON UPDATE CASCADE ON DELETE RESTRICT');
				} catch (\Throwable $e) {
				}
			}
		}

		$this->_expandContractsTable();
		$this->_addContractsForeignKeys();

		if (!$this->hasTable('contract_signatories')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_signatories (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	nome VARCHAR(200) NOT NULL,
	email VARCHAR(200) NOT NULL,
	cpf VARCHAR(20) NULL,
	tipo VARCHAR(30) NOT NULL DEFAULT 'cliente',
	ordem INTEGER NOT NULL DEFAULT 1,
	obrigatorio BOOLEAN NOT NULL DEFAULT TRUE,
	autentique_id VARCHAR(100) NULL,
	status VARCHAR(30) NULL DEFAULT 'pendente',
	link_assinatura TEXT NULL,
	assinado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ip_assinatura VARCHAR(50) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_signatories_contract ON contract_signatories (contract_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_signatories ADD CONSTRAINT fk_contract_signatories_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('contract_autentique_logs')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_autentique_logs (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	evento VARCHAR(100) NOT NULL,
	payload JSONB NULL,
	resposta_api JSONB NULL,
	user_id INTEGER NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_autentique_logs_contract ON contract_autentique_logs (contract_id);
CREATE INDEX idx_contract_autentique_logs_created ON contract_autentique_logs (created);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_autentique_logs ADD CONSTRAINT fk_contract_autentique_logs_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE contract_autentique_logs ADD CONSTRAINT fk_contract_autentique_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if (!$this->hasTable('contract_renewals')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_renewals (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	novo_contract_id INTEGER NULL,
	status VARCHAR(30) NOT NULL DEFAULT 'pendente',
	solicitado_por INTEGER NULL,
	solicitado_em TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	nova_vigencia_inicio DATE NULL,
	nova_vigencia_fim DATE NULL,
	novo_valor_mensal NUMERIC(12,2) NULL,
	observacoes TEXT NULL,
	aprovado_por INTEGER NULL,
	aprovado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_renewals_contract ON contract_renewals (contract_id);
CREATE INDEX idx_contract_renewals_status ON contract_renewals (status);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_renewals ADD CONSTRAINT fk_contract_renewals_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('ALTER TABLE contract_renewals ADD CONSTRAINT fk_contract_renewals_novo_contract FOREIGN KEY (novo_contract_id) REFERENCES contracts (id) ON UPDATE CASCADE ON DELETE SET NULL');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE contract_renewals ADD CONSTRAINT fk_contract_renewals_solicitante FOREIGN KEY (solicitado_por) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
				try {
					$this->execute('ALTER TABLE contract_renewals ADD CONSTRAINT fk_contract_renewals_aprovador FOREIGN KEY (aprovado_por) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if (!$this->hasTable('contract_notifications')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_notifications (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	tipo VARCHAR(50) NOT NULL,
	destinatario VARCHAR(30) NOT NULL DEFAULT 'cliente',
	canal VARCHAR(20) NOT NULL DEFAULT 'email',
	enviado BOOLEAN NOT NULL DEFAULT FALSE,
	enviado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	erro TEXT NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_notifications_contract ON contract_notifications (contract_id);
CREATE INDEX idx_contract_notifications_tipo ON contract_notifications (tipo, enviado);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_notifications ADD CONSTRAINT fk_contract_notifications_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		$this->_expandContractServicesTable();
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		$this->_shrinkContractServicesTable();

		if ($this->hasTable('contracts')) {
			try {
				$this->execute('ALTER TABLE contracts DROP CONSTRAINT IF EXISTS fk_contracts_template_id');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('ALTER TABLE contracts DROP CONSTRAINT IF EXISTS fk_contracts_contrato_pai');
			} catch (\Throwable $e) {
			}
			$sql = <<<'SQL'
ALTER TABLE contracts
	DROP COLUMN IF EXISTS template_id,
	DROP COLUMN IF EXISTS valor_total,
	DROP COLUMN IF EXISTS nivel_sla,
	DROP COLUMN IF EXISTS observacoes_cli,
	DROP COLUMN IF EXISTS clausulas,
	DROP COLUMN IF EXISTS modulos_cobertos,
	DROP COLUMN IF EXISTS cobre_remoto,
	DROP COLUMN IF EXISTS cobre_presencial,
	DROP COLUMN IF EXISTS cobre_manutencao,
	DROP COLUMN IF EXISTS cobre_backup,
	DROP COLUMN IF EXISTS cobre_monitoramento,
	DROP COLUMN IF EXISTS limite_chamados,
	DROP COLUMN IF EXISTS dias_aviso_vencimento,
	DROP COLUMN IF EXISTS autentique_doc_id,
	DROP COLUMN IF EXISTS autentique_status,
	DROP COLUMN IF EXISTS autentique_url,
	DROP COLUMN IF EXISTS pdf_path,
	DROP COLUMN IF EXISTS signed_pdf_path,
	DROP COLUMN IF EXISTS aprovado_por,
	DROP COLUMN IF EXISTS aprovado_em,
	DROP COLUMN IF EXISTS assinado_em,
	DROP COLUMN IF EXISTS cancelado_em,
	DROP COLUMN IF EXISTS motivo_cancelamento,
	DROP COLUMN IF EXISTS versao,
	DROP COLUMN IF EXISTS contrato_pai_id
SQL;
			try {
				$this->execute($sql);
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('DROP INDEX IF EXISTS idx_contracts_autentique_doc');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('DROP INDEX IF EXISTS idx_contracts_template_id');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('DROP INDEX IF EXISTS idx_contracts_contrato_pai');
			} catch (\Throwable $e) {
			}
		}

		$drops = [
			'contract_notifications',
			'contract_renewals',
			'contract_autentique_logs',
			'contract_signatories',
			'contract_templates',
		];
		foreach ($drops as $t) {
			if ($this->hasTable($t)) {
				try {
					$this->execute('DROP TABLE ' . $t . ' CASCADE');
				} catch (\Throwable $e) {
				}
			}
		}
	}

	protected function _expandContractsTable() {
		$sql = <<<'SQL'
ALTER TABLE contracts
	ADD COLUMN IF NOT EXISTS template_id INTEGER NULL,
	ADD COLUMN IF NOT EXISTS valor_total NUMERIC(12,2) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS nivel_sla VARCHAR(30) NULL,
	ADD COLUMN IF NOT EXISTS observacoes_cli TEXT NULL,
	ADD COLUMN IF NOT EXISTS clausulas JSONB NOT NULL DEFAULT '[]'::jsonb,
	ADD COLUMN IF NOT EXISTS modulos_cobertos JSONB NOT NULL DEFAULT '[]'::jsonb,
	ADD COLUMN IF NOT EXISTS cobre_remoto BOOLEAN NOT NULL DEFAULT FALSE,
	ADD COLUMN IF NOT EXISTS cobre_presencial BOOLEAN NOT NULL DEFAULT FALSE,
	ADD COLUMN IF NOT EXISTS cobre_manutencao BOOLEAN NOT NULL DEFAULT FALSE,
	ADD COLUMN IF NOT EXISTS cobre_backup BOOLEAN NOT NULL DEFAULT FALSE,
	ADD COLUMN IF NOT EXISTS cobre_monitoramento BOOLEAN NOT NULL DEFAULT FALSE,
	ADD COLUMN IF NOT EXISTS limite_chamados INTEGER NULL,
	ADD COLUMN IF NOT EXISTS dias_aviso_vencimento INTEGER NULL DEFAULT 30,
	ADD COLUMN IF NOT EXISTS autentique_doc_id VARCHAR(100) NULL,
	ADD COLUMN IF NOT EXISTS autentique_status VARCHAR(30) NULL,
	ADD COLUMN IF NOT EXISTS autentique_url TEXT NULL,
	ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(500) NULL,
	ADD COLUMN IF NOT EXISTS signed_pdf_path VARCHAR(500) NULL,
	ADD COLUMN IF NOT EXISTS aprovado_por INTEGER NULL,
	ADD COLUMN IF NOT EXISTS aprovado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS assinado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS cancelado_em TIMESTAMP WITHOUT TIME ZONE NULL,
	ADD COLUMN IF NOT EXISTS motivo_cancelamento TEXT NULL,
	ADD COLUMN IF NOT EXISTS versao INTEGER NOT NULL DEFAULT 1,
	ADD COLUMN IF NOT EXISTS contrato_pai_id INTEGER NULL
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}

		try {
			$this->execute('CREATE INDEX IF NOT EXISTS idx_contracts_autentique_doc ON contracts (autentique_doc_id)');
		} catch (\Throwable $e) {
		}
		try {
			$this->execute('CREATE INDEX IF NOT EXISTS idx_contracts_template_id ON contracts (template_id)');
		} catch (\Throwable $e) {
		}
		try {
			$this->execute('CREATE INDEX IF NOT EXISTS idx_contracts_contrato_pai ON contracts (contrato_pai_id)');
		} catch (\Throwable $e) {
		}
	}

	protected function _addContractsForeignKeys() {
		if (!$this->hasTable('contract_templates')) {
			return;
		}
		try {
			$this->execute('ALTER TABLE contracts ADD CONSTRAINT fk_contracts_template_id FOREIGN KEY (template_id) REFERENCES contract_templates (id) ON UPDATE CASCADE ON DELETE SET NULL');
		} catch (\Throwable $e) {
		}
		try {
			$this->execute('ALTER TABLE contracts ADD CONSTRAINT fk_contracts_contrato_pai FOREIGN KEY (contrato_pai_id) REFERENCES contracts (id) ON UPDATE CASCADE ON DELETE SET NULL');
		} catch (\Throwable $e) {
		}
	}

	protected function _expandContractServicesTable() {
		if (!$this->hasTable('contract_services')) {
			return;
		}
		$sql = <<<'SQL'
ALTER TABLE contract_services
	ADD COLUMN IF NOT EXISTS tipo_item VARCHAR(40) NULL DEFAULT 'servico',
	ADD COLUMN IF NOT EXISTS unidade VARCHAR(30) NULL DEFAULT 'unid',
	ADD COLUMN IF NOT EXISTS franquia_horas NUMERIC(8,2) NULL,
	ADD COLUMN IF NOT EXISTS valor_unitario NUMERIC(12,2) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS valor_total NUMERIC(12,2) NOT NULL DEFAULT 0,
	ADD COLUMN IF NOT EXISTS vigencia_inicio DATE NULL,
	ADD COLUMN IF NOT EXISTS vigencia_fim DATE NULL,
	ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT TRUE,
	ADD COLUMN IF NOT EXISTS observacoes TEXT NULL
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}

	protected function _shrinkContractServicesTable() {
		if (!$this->hasTable('contract_services')) {
			return;
		}
		$sql = <<<'SQL'
ALTER TABLE contract_services
	DROP COLUMN IF EXISTS tipo_item,
	DROP COLUMN IF EXISTS unidade,
	DROP COLUMN IF EXISTS franquia_horas,
	DROP COLUMN IF EXISTS valor_unitario,
	DROP COLUMN IF EXISTS valor_total,
	DROP COLUMN IF EXISTS vigencia_inicio,
	DROP COLUMN IF EXISTS vigencia_fim,
	DROP COLUMN IF EXISTS ativo,
	DROP COLUMN IF EXISTS observacoes
SQL;
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}
}
