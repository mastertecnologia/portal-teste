<?php
use Migrations\AbstractMigration;

/**
 * Fase 1: CFOP e NCM mínimos para homologação (tabelas globais).
 * Idempotente (WHERE NOT EXISTS). Não remove em down() para não apagar dados manuais.
 */
class FiscalMinimalReferenceSeed extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('fiscal_cfop') || !$this->hasTable('fiscal_ncm')) {
			return;
		}

		$this->execute(<<<'SQL'
INSERT INTO fiscal_cfop (codigo, descricao, tipo, aplicacao)
SELECT '5102', 'Venda de mercadoria adquirida ou recebida de terceiros', 'saida', NULL
WHERE NOT EXISTS (SELECT 1 FROM fiscal_cfop WHERE codigo = '5102');
INSERT INTO fiscal_cfop (codigo, descricao, tipo, aplicacao)
SELECT '6102', 'Compra para comercialização', 'entrada', NULL
WHERE NOT EXISTS (SELECT 1 FROM fiscal_cfop WHERE codigo = '6102');
INSERT INTO fiscal_cfop (codigo, descricao, tipo, aplicacao)
SELECT '5101', 'Venda de produção do estabelecimento', 'saida', NULL
WHERE NOT EXISTS (SELECT 1 FROM fiscal_cfop WHERE codigo = '5101');
INSERT INTO fiscal_cfop (codigo, descricao, tipo, aplicacao)
SELECT '6101', 'Compra para industrialização ou produção rural', 'entrada', NULL
WHERE NOT EXISTS (SELECT 1 FROM fiscal_cfop WHERE codigo = '6101');
SQL
		);

		$this->execute(<<<'SQL'
INSERT INTO fiscal_ncm (codigo, descricao, aliquota_ipi, ex_tipi)
SELECT '84713012', 'Outras máquinas automáticas para processamento de dados', NULL, NULL
WHERE NOT EXISTS (SELECT 1 FROM fiscal_ncm WHERE codigo = '84713012');
SQL
		);
	}

	public function down() {
		// Mantém dados de referência; remoção manual se necessário
	}
}
