<?php
use Migrations\AbstractMigration;

/**
 * Desconto por linha no carrinho de orçamento (orcamentosnovosdesservicos).
 */
class OrcamentosServicoItemDesconto extends AbstractMigration
{
	public function change()
	{
		$table = $this->table('orcamentosnovosdesservicos');
		if (!$table->hasColumn('desconto_valor')) {
			$table->addColumn('desconto_valor', 'decimal', [
				'precision' => 15,
				'scale' => 2,
				'default' => 0,
				'null' => false,
			]);
		}
		if (!$table->hasColumn('desconto_tipo')) {
			$table->addColumn('desconto_tipo', 'string', [
				'limit' => 8,
				'default' => 'pct',
				'null' => false,
			]);
		}
		$table->update();

		if (class_exists('\Cake\Cache\Cache')) {
			\Cake\Cache\Cache::clearAll();
		}
	}
}
