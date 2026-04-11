<?php
use Migrations\AbstractMigration;

/**
 * Certificados A1: serial OpenSSL pode exceder VARCHAR(100); senha cifrada (v2) pode exceder 255;
 * CN do subject pode exceder 500 em DNs longos.
 */
class FiscalCertificadosWidenMeta extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('fiscal_certificados')) {
			return;
		}
		$table = $this->table('fiscal_certificados');
		$table->changeColumn('serial_number', 'string', ['limit' => 255, 'null' => true]);
		$table->changeColumn('senha_hash', 'string', ['limit' => 512, 'null' => true]);
		$table->changeColumn('cn_subject', 'string', ['limit' => 1000, 'null' => true]);
		$table->update();
	}

	public function down() {
		// Reverter pode truncar dados já gravados — não suportado.
	}
}
