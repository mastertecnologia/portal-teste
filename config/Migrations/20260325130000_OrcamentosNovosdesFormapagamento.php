<?php
/**
 * Condição de pagamento da proposta (texto livre padronizado na UI: À vista, Parcelado, etc.).
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class OrcamentosNovosdesFormapagamento extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');
		if ($table->hasColumn('formapagamento')) {
			return;
		}
		$table->addColumn('formapagamento', 'string', [
			'limit' => 255,
			'null' => true,
			'default' => null,
		])->update();
	}

	public function down() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');
		if ($table->hasColumn('formapagamento')) {
			$table->removeColumn('formapagamento')->update();
		}
	}
}
