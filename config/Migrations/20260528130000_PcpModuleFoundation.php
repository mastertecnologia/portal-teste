<?php
use Migrations\AbstractMigration;

/**
 * Fundação do módulo PCP/Indústria (telas pg-pcp-* do mock pgm_erp_completo.html).
 * Dados reais por empresa; sem integração Grid dedicada nesta fase.
 */
class PcpModuleFoundation extends AbstractMigration {

	public function change() {
		if (!$this->hasTable('pcp_centros_trabalho')) {
			$this->table('pcp_centros_trabalho')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('codigo', 'string', ['limit' => 30, 'null' => false])
				->addColumn('nome', 'string', ['limit' => 120, 'null' => false])
				->addColumn('capacidade_h_dia', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('custo_h', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'codigo'], ['unique' => true])
				->create();
		}

		if (!$this->hasTable('pcp_engenharia_fichas')) {
			$this->table('pcp_engenharia_fichas')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idproduto', 'integer', ['null' => true, 'default' => null])
				->addColumn('codigo', 'string', ['limit' => 40, 'null' => false])
				->addColumn('revisao', 'string', ['limit' => 10, 'null' => false, 'default' => 'A'])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'rascunho'])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'codigo', 'revisao'], ['unique' => true])
				->create();
		}

		if (!$this->hasTable('pcp_bom_itens')) {
			$this->table('pcp_bom_itens')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('parent_produto_id', 'integer', ['null' => false])
				->addColumn('child_produto_id', 'integer', ['null' => false])
				->addColumn('quantidade', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 1])
				->addColumn('scrap_pct', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'parent_produto_id'])
				->create();
		}

		if (!$this->hasTable('pcp_ordens_producao')) {
			$this->table('pcp_ordens_producao')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('numero', 'string', ['limit' => 30, 'null' => false])
				->addColumn('idproduto', 'integer', ['null' => true, 'default' => null])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('quantidade', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 1])
				->addColumn('quantidade_produzida', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 0])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'planejada'])
				->addColumn('data_inicio_prev', 'date', ['null' => true, 'default' => null])
				->addColumn('data_fim_prev', 'date', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'numero'], ['unique' => true])
				->addIndex(['idempresa', 'status'])
				->create();
		}

		if (!$this->hasTable('pcp_apontamentos')) {
			$this->table('pcp_apontamentos')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idordem', 'integer', ['null' => false])
				->addColumn('idcentro', 'integer', ['null' => true, 'default' => null])
				->addColumn('operacao', 'string', ['limit' => 80, 'null' => true, 'default' => null])
				->addColumn('inicio', 'datetime', ['null' => false])
				->addColumn('fim', 'datetime', ['null' => true, 'default' => null])
				->addColumn('quantidade_boa', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 0])
				->addColumn('quantidade_refugo', 'decimal', ['precision' => 15, 'scale' => 4, 'null' => false, 'default' => 0])
				->addColumn('iduser', 'integer', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addIndex(['idempresa', 'idordem'])
				->create();
		}
	}
}
