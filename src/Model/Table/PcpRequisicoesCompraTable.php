<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpRequisicoesCompraTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_requisicoes_compra');
		$this->setDisplayField('numero');
		$this->setPrimaryKey('id');
		$this->belongsTo('Empresas')->setForeignKey('idempresa');
		$this->belongsTo('Produtos')->setForeignKey('idproduto');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('numero', 'create')
			->notEmptyString('numero')
			->decimal('quantidade');

		return $validator;
	}
}
