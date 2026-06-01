<?php
use Migrations\AbstractMigration;

/**
 * Fundação do módulo Licenciamento (telas pg-lic-* do mock pgm_erp_completo.html).
 * Campos alinhados ao mock; sem integração Grid dedicada.
 */
class LicModuleFoundation extends AbstractMigration {

	public function change() {
		if (!$this->hasTable('lic_categorias')) {
			$this->table('lic_categorias')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('codigo', 'string', ['limit' => 30, 'null' => false])
				->addColumn('nome', 'string', ['limit' => 120, 'null' => false])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'codigo'], ['unique' => true])
				->create();
		}

		if (!$this->hasTable('lic_catalogo_produtos')) {
			$this->table('lic_catalogo_produtos')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcategoria', 'integer', ['null' => true, 'default' => null])
				->addColumn('sku', 'string', ['limit' => 60, 'null' => true, 'default' => null])
				->addColumn('nome', 'string', ['limit' => 200, 'null' => false])
				->addColumn('idfornecedor_cliente', 'integer', ['null' => true, 'default' => null])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'sku'])
				->create();
		}

		if (!$this->hasTable('lic_licencas')) {
			$this->table('lic_licencas')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => false])
				->addColumn('idcatalogo', 'integer', ['null' => true, 'default' => null])
				->addColumn('codigo', 'string', ['limit' => 40, 'null' => false])
				->addColumn('modelo', 'string', ['limit' => 30, 'null' => false, 'default' => 'assinatura'])
				->addColumn('assentos', 'integer', ['null' => false, 'default' => 1])
				->addColumn('valor_anual', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => null])
				->addColumn('inicio', 'date', ['null' => true, 'default' => null])
				->addColumn('fim', 'date', ['null' => true, 'default' => null])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'ativa'])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'codigo'], ['unique' => true])
				->addIndex(['idempresa', 'idcliente'])
				->addIndex(['idempresa', 'fim'])
				->create();
		}

		if (!$this->hasTable('lic_assentos')) {
			$this->table('lic_assentos')
				->addColumn('idlicenca', 'integer', ['null' => false])
				->addColumn('email', 'string', ['limit' => 180, 'null' => true, 'default' => null])
				->addColumn('iddispositivo', 'integer', ['null' => true, 'default' => null])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'ativo'])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idlicenca'])
				->create();
		}

		if (!$this->hasTable('lic_dispositivos')) {
			$this->table('lic_dispositivos')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => false])
				->addColumn('hostname', 'string', ['limit' => 120, 'null' => true, 'default' => null])
				->addColumn('serial', 'string', ['limit' => 80, 'null' => true, 'default' => null])
				->addColumn('so', 'string', ['limit' => 80, 'null' => true, 'default' => null])
				->addColumn('ultimo_visto', 'datetime', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'idcliente'])
				->create();
		}

		if (!$this->hasTable('lic_cofre_itens')) {
			$this->table('lic_cofre_itens')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => true, 'default' => null])
				->addColumn('idlicenca', 'integer', ['null' => true, 'default' => null])
				->addColumn('titulo', 'string', ['limit' => 200, 'null' => false])
				->addColumn('nivel', 'string', ['limit' => 20, 'null' => false, 'default' => 'medio'])
				->addColumn('secret_blob', 'text', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'idcliente'])
				->create();
		}

		if (!$this->hasTable('lic_solicitacoes')) {
			$this->table('lic_solicitacoes')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => false])
				->addColumn('tipo', 'string', ['limit' => 40, 'null' => false])
				->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'aberta'])
				->addColumn('payload_json', 'text', ['null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
				->addIndex(['idempresa', 'status'])
				->create();
		}

		if (!$this->hasTable('lic_auditoria_eventos')) {
			$this->table('lic_auditoria_eventos')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('iduser', 'integer', ['null' => true, 'default' => null])
				->addColumn('acao', 'string', ['limit' => 80, 'null' => false])
				->addColumn('entidade', 'string', ['limit' => 60, 'null' => true, 'default' => null])
				->addColumn('entidade_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('detalhe', 'text', ['null' => true, 'default' => null])
				->addColumn('ip', 'string', ['limit' => 45, 'null' => true, 'default' => null])
				->addColumn('created', 'datetime', ['null' => false])
				->addIndex(['idempresa', 'created'])
				->create();
		}
	}
}
