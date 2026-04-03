<?php
/**
 * Garante faturas_escrita_fiscal em PostgreSQL e MySQL.
 *
 * A migration 20260402194500 só executava DDL em pgsql; em ambientes default=mysql o registro
 * em phinxlog pode existir sem tabela. Também cobre deploy sem migrate ou falha parcial.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class FaturasEscritaFiscalEnsureAllDrivers extends AbstractMigration {

	protected function _isMysql(): bool {
		try {
			$c = $this->getAdapter()->getConnection();
			if ($c && $c->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
				return true;
			}
		} catch (\Throwable $e) {
		}
		$t = strtolower((string) $this->getAdapter()->getAdapterType());

		return in_array($t, ['mysql', 'mysqli', 'mariadb'], true);
	}

	public function up() {
		if ($this->hasTable('faturas_escrita_fiscal')) {
			return;
		}

		if ($this->getAdapter()->getAdapterType() === 'pgsql') {
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

			return;
		}

		if ($this->_isMysql()) {
			$this->execute(<<<'SQL'
CREATE TABLE `faturas_escrita_fiscal` (
	`id` int NOT NULL AUTO_INCREMENT,
	`idfatura` int NOT NULL,
	`idempresa` int NOT NULL,
	`valor_produtos` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_total_nota` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_servicos` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_desconto` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_frete` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_seguro` decimal(14,2) NOT NULL DEFAULT 0.00,
	`icms_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`icms_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`icms_cred_simples_pct` decimal(10,4) NOT NULL DEFAULT 0.0000,
	`icms_st_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`icms_st_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_trib_aprox_total` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_trib_aprox_federal` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_trib_aprox_estadual` decimal(14,2) NOT NULL DEFAULT 0.00,
	`valor_trib_aprox_municipal` decimal(14,2) NOT NULL DEFAULT 0.00,
	`ipi_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`ipi_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`pis_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`pis_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`cofins_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`cofins_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`iss_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`iss_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`inss_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`inss_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`irrf_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`irrf_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`irrf_aliquota` decimal(10,4) NOT NULL DEFAULT 0.0000,
	`csll_bc` decimal(14,2) NOT NULL DEFAULT 0.00,
	`csll_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`ii_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`iof_valor` decimal(14,2) NOT NULL DEFAULT 0.00,
	`fonte_calculo` varchar(64) DEFAULT NULL,
	`fonte_ibpt_versao` varchar(64) DEFAULT NULL,
	`payload_modulos` text DEFAULT NULL,
	`created` datetime DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `faturas_escrita_fiscal_idfatura_key` (`idfatura`),
	KEY `idx_fesc_idempresa` (`idempresa`),
	KEY `idx_fesc_idfatura` (`idfatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
			);
		}
	}

	public function down() {
		// Não remove em produção via rollback automático
	}
}
