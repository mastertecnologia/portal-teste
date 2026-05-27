<?php
/**
 * E-mail usado no envio da proposta (passo 6 / enviar orçamento) — OTP acesso seguro.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class OrcamentosEmailEnvio extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');
		if (!$table->hasColumn('email_envio')) {
			$table->addColumn('email_envio', 'string', [
				'limit' => 255,
				'null' => true,
				'default' => null,
			]);
		}
		$table->update();
	}

	public function down() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');
		if ($table->hasColumn('email_envio')) {
			$table->removeColumn('email_envio');
		}
		$table->update();
	}
}
