<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Dados fiscais do cadastro mestre (cliente/fornecedor).
 */
class ClientesRegimeTributarioCnae extends AbstractMigration {

	public function up(): void {
		if (!$this->hasTable('clientes')) {
			return;
		}
		$t = $this->table('clientes');
		if (!$t->hasColumn('regime_tributario')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN regime_tributario VARCHAR(32) NULL');
		}
		if (!$t->hasColumn('cnae_principal')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN cnae_principal VARCHAR(20) NULL');
		}
		if (!$t->hasColumn('data_abertura')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN data_abertura DATE NULL');
		}
		if (!$t->hasColumn('tipo_endereco')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN tipo_endereco VARCHAR(40) NULL');
		}
	}

	public function down(): void {
		if (!$this->hasTable('clientes')) {
			return;
		}
		foreach (['tipo_endereco', 'data_abertura', 'cnae_principal', 'regime_tributario'] as $col) {
			$t = $this->table('clientes');
			if ($t->hasColumn($col)) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS ' . $col);
			}
		}
	}
}
