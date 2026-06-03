<?php
use Migrations\AbstractMigration;

/**
 * Tabelas de preços (cabeçalho + itens) vinculadas a produtos/serviços do catálogo.
 */
class PrecosTabelasFoundation extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		if (!$this->hasTable('precos_tabelas')) {
			$this->table('precos_tabelas')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('codigo', 'string', ['limit' => 40, 'null' => false])
				->addColumn('nome', 'string', ['limit' => 160, 'null' => false])
				->addColumn('descricao', 'text', ['null' => true, 'default' => null])
				->addColumn('moeda', 'string', ['limit' => 3, 'null' => false, 'default' => 'BRL'])
				->addColumn('vigencia_inicio', 'date', ['null' => true, 'default' => null])
				->addColumn('vigencia_fim', 'date', ['null' => true, 'default' => null])
				->addColumn('vigente', 'boolean', ['null' => false, 'default' => true])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'codigo'], ['unique' => true, 'name' => 'ux_precos_tabelas_empresa_codigo'])
				->create();
		}

		if (!$this->hasTable('precos_tabela_itens')) {
			$this->table('precos_tabela_itens')
				->addColumn('precos_tabela_id', 'integer', ['null' => false])
				->addColumn('produto_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('categoria', 'string', ['limit' => 80, 'null' => true, 'default' => null])
				->addColumn('codigo_item', 'string', ['limit' => 40, 'null' => false])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => false])
				->addColumn('vlunitario', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0])
				->addColumn('ordem', 'integer', ['null' => false, 'default' => 0])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['precos_tabela_id', 'codigo_item'], ['unique' => true, 'name' => 'ux_precos_tabela_itens_codigo'])
				->addIndex(['precos_tabela_id', 'ordem'])
				->create();
		}

		if ($this->hasTable('produtos') && $this->hasTable('precos_tabela_itens')) {
			$this->table('precos_tabela_itens')
				->addForeignKey('produto_id', 'produtos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
				->update();
		}
		if ($this->hasTable('precos_tabelas') && $this->hasTable('precos_tabela_itens')) {
			$this->table('precos_tabela_itens')
				->addForeignKey('precos_tabela_id', 'precos_tabelas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
				->update();
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('precos_tabela_itens')) {
			$this->table('precos_tabela_itens')->drop()->save();
		}
		if ($this->hasTable('precos_tabelas')) {
			$this->table('precos_tabelas')->drop()->save();
		}
	}
}
