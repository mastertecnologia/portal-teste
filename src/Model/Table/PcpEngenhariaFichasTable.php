<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpEngenhariaFichasTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_engenharia_fichas');
		$this->setDisplayField('codigo');
		$this->setPrimaryKey('id');
		$this->belongsTo('Empresas')->setForeignKey('idempresa');
		$this->belongsTo('Produtos')->setForeignKey('idproduto');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('codigo', 'create')
			->notEmptyString('codigo');

		return $validator;
	}
}
