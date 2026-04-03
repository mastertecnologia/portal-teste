<?php
/**
 * Snapshot de escrita fiscal da locação (espelho dos totais tributários / transparência).
 * Campos alinhados a telas tipo DB9; preenchimento automático inicial via IBPT nos totais aproximados.
 * Rodar: bin/cake migrations migrate
 *
 * MySQL: esta versão ignora adapters não-pgsql; use também
 * 20260402194600_FaturasEscritaFiscalEnsureAllDrivers (idempotente se a tabela já existir).
 */
use Migrations\AbstractMigration;

class FaturasEscritaFiscal extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('faturas_escrita_fiscal')) {
			return;
		}
		$this->execute(<<<'SQL'
CREATE TABLE faturas_escrita_fiscal (
	id SERIAL PRIMARY KEY,
	idfatura INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	valor_produtos NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_total_nota NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_servicos NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_desconto NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_frete NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_seguro NUMERIC(14,2) NOT NULL DEFAULT 0,
	icms_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	icms_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	icms_cred_simples_pct NUMERIC(10,4) NOT NULL DEFAULT 0,
	icms_st_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	icms_st_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_trib_aprox_total NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_trib_aprox_federal NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_trib_aprox_estadual NUMERIC(14,2) NOT NULL DEFAULT 0,
	valor_trib_aprox_municipal NUMERIC(14,2) NOT NULL DEFAULT 0,
	ipi_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	ipi_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	pis_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	pis_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	cofins_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	cofins_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	iss_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	iss_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	inss_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	inss_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	irrf_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	irrf_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	irrf_aliquota NUMERIC(10,4) NOT NULL DEFAULT 0,
	csll_bc NUMERIC(14,2) NOT NULL DEFAULT 0,
	csll_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	ii_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	iof_valor NUMERIC(14,2) NOT NULL DEFAULT 0,
	fonte_calculo VARCHAR(64) NULL,
	fonte_ibpt_versao VARCHAR(64) NULL,
	payload_modulos TEXT NULL,
	created TIMESTAMP WITHOUT TIME ZONE NULL,
	modified TIMESTAMP WITHOUT TIME ZONE NULL,
	CONSTRAINT faturas_escrita_fiscal_idfatura_key UNIQUE (idfatura)
);
CREATE INDEX IF NOT EXISTS idx_fesc_idempresa ON faturas_escrita_fiscal (idempresa);
CREATE INDEX IF NOT EXISTS idx_fesc_idfatura ON faturas_escrita_fiscal (idfatura);
SQL
		);
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('faturas_escrita_fiscal')) {
			$this->execute('DROP TABLE faturas_escrita_fiscal');
		}
	}
}
