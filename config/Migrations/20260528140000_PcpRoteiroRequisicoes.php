<?php
use Migrations\AbstractMigration;

class PcpRoteiroRequisicoes extends AbstractMigration {

	public function change() {
		if (!$this->hasTable('pcp_roteiro_operacoes')) {
			$this->table('pcp_roteiro_operacoes')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idproduto', 'integer', ['null' => false])
				->addColumn('sequencia', 'integer', ['null' => false, 'default' => 10])
				->addColumn('operacao', 'string', ['limit' => 120, 'null' => false])
				->addColumn('idcentro', 'integer', ['null' => true, 'default' => null])
				->addColumn('tempo_setup_min', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('tempo_run_min', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addIndex(['idempresa', 'idproduto'])
				->create();
		}

		if (!$this->hasTable('pcp_requisicoes_compra')) {
			$this->table('pcp_requisicoes_compra')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('numero', 'string', ['limit' => 30, 'null' => false])
				->addColumn('tipo', 'string', ['limit' => 20, 'null' => false, 'default' => 'requisicao'])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'aberta'])
				->addColumn('idproduto', 'integer', ['null' => true, 'default' => null])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('quantidade', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 1])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'numero'], ['unique' => true])
				->addIndex(['idempresa', 'tipo', 'status'])
				->create();
		}
	}
}
