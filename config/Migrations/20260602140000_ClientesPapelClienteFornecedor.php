<?php
/**
 * Papel do cadastro em clientes: cliente (CRM/vendas) e/ou fornecedor (compras).
 *
 * PostgreSQL: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ClientesPapelClienteFornecedor extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}
		$t = $this->table('clientes');
		if (!$t->hasColumn('eh_cliente')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN eh_cliente BOOLEAN NOT NULL DEFAULT TRUE');
		}
		if (!$t->hasColumn('eh_fornecedor')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN eh_fornecedor BOOLEAN NOT NULL DEFAULT FALSE');
		}
		if (!$t->hasColumn('fornecedor_categoria')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN fornecedor_categoria VARCHAR(80) NULL');
		}
		if (!$t->hasColumn('fornecedor_status_homologacao')) {
			$this->execute("ALTER TABLE clientes ADD COLUMN fornecedor_status_homologacao VARCHAR(32) NULL DEFAULT 'cadastrado'");
		}
		if (!$t->hasColumn('fornecedor_lead_time_dias')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN fornecedor_lead_time_dias SMALLINT NULL');
		}

		$this->execute(
			'UPDATE clientes SET eh_cliente = TRUE WHERE eh_cliente IS DISTINCT FROM TRUE'
		);
		// eh_fornecedor só via formulário (checkbox) ou migration 20260604120000 (sinais explícitos).
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}
		$t = $this->table('clientes');
		foreach (['fornecedor_lead_time_dias', 'fornecedor_status_homologacao', 'fornecedor_categoria', 'eh_fornecedor', 'eh_cliente'] as $col) {
			if ($t->hasColumn($col)) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS ' . $col);
			}
		}
	}
}
