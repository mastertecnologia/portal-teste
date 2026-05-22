<?php
/**
 * Campo site (URL/domínio) no cadastro de clientes.
 *
 * PostgreSQL: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class ClientesSite extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}
		$t = $this->table('clientes');
		if (!$t->hasColumn('site')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN site VARCHAR(255) NULL');
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('clientes')) {
			$t = $this->table('clientes');
			if ($t->hasColumn('site')) {
				$this->execute('ALTER TABLE clientes DROP COLUMN IF EXISTS site');
			}
		}
	}
}
