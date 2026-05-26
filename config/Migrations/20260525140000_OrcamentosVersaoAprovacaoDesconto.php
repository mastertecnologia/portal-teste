<?php
/**
 * Versões de proposta, aprovação interna (gerente) e desconto persistido.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class OrcamentosVersaoAprovacaoDesconto extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');

		if (!$table->hasColumn('versao')) {
			$table->addColumn('versao', 'integer', [
				'default' => 1,
				'null' => false,
			]);
		}
		if (!$table->hasColumn('idgrupoversao')) {
			$table->addColumn('idgrupoversao', 'integer', [
				'null' => true,
				'default' => null,
			]);
		}
		if (!$table->hasColumn('aprovacao_interna')) {
			$table->addColumn('aprovacao_interna', 'string', [
				'limit' => 20,
				'default' => 'pendente',
				'null' => false,
			]);
		}
		if (!$table->hasColumn('aprovacao_interna_em')) {
			$table->addColumn('aprovacao_interna_em', 'datetime', [
				'null' => true,
				'default' => null,
			]);
		}
		if (!$table->hasColumn('aprovacao_interna_por')) {
			$table->addColumn('aprovacao_interna_por', 'integer', [
				'null' => true,
				'default' => null,
			]);
		}
		if (!$table->hasColumn('aprovacao_interna_motivo')) {
			$table->addColumn('aprovacao_interna_motivo', 'text', [
				'null' => true,
				'default' => null,
			]);
		}
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

		$this->execute(
			'UPDATE orcamentosnovosdes SET idgrupoversao = id WHERE idgrupoversao IS NULL'
		);
		$this->execute(
			'UPDATE orcamentosnovosdes SET versao = 1 WHERE versao IS NULL OR versao < 1'
		);
		$this->execute(
			"UPDATE orcamentosnovosdes SET aprovacao_interna = 'pendente' WHERE aprovacao_interna IS NULL OR aprovacao_interna = ''"
		);
		$this->execute(
			"UPDATE orcamentosnovosdes SET desconto_tipo = 'pct' WHERE desconto_tipo IS NULL OR desconto_tipo = ''"
		);
	}

	public function down() {
		if (!$this->hasTable('orcamentosnovosdes')) {
			return;
		}
		$table = $this->table('orcamentosnovosdes');
		foreach ([
			'desconto_tipo',
			'desconto_valor',
			'aprovacao_interna_motivo',
			'aprovacao_interna_por',
			'aprovacao_interna_em',
			'aprovacao_interna',
			'idgrupoversao',
			'versao',
		] as $col) {
			if ($table->hasColumn($col)) {
				$table->removeColumn($col);
			}
		}
		$table->update();
	}
}
