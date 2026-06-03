<?php
use Migrations\AbstractMigration;

/**
 * Auditoria de alterações de preço (histórico real para pg-historico-precos).
 */
class PrecosHistoricoFoundation extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		if (!$this->hasTable('precos_historico')) {
			$this->table('precos_historico')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('produto_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('precos_tabela_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('codigo_produto', 'string', ['limit' => 40, 'null' => true, 'default' => null])
				->addColumn('descricao_produto', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('tabela_nome', 'string', ['limit' => 160, 'null' => true, 'default' => null])
				->addColumn('preco_anterior', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('preco_novo', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0])
				->addColumn('variacao_pct', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('tipo', 'string', ['limit' => 20, 'null' => false, 'default' => 'ajuste'])
				->addColumn('motivo', 'text', ['null' => true, 'default' => null])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('autor_nome', 'string', ['limit' => 120, 'null' => true, 'default' => null])
				->addColumn('custo_na_epoca', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('margem_apos', 'decimal', ['precision' => 8, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('ip_origem', 'string', ['limit' => 45, 'null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addIndex(['idempresa', 'created'])
				->addIndex(['idempresa', 'produto_id'])
				->addIndex(['precos_tabela_id'])
				->create();
		}

		if ($this->hasTable('produtos') && $this->hasTable('precos_historico')) {
			$this->table('precos_historico')
				->addForeignKey('produto_id', 'produtos', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
				->update();
		}
		if ($this->hasTable('precos_tabelas') && $this->hasTable('precos_historico')) {
			$this->table('precos_historico')
				->addForeignKey('precos_tabela_id', 'precos_tabelas', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
				->update();
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('precos_historico')) {
			$this->table('precos_historico')->drop()->save();
		}
	}
}
