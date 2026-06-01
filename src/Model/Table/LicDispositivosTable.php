<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LicDispositivosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_dispositivos');
		$this->setPrimaryKey('id');
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'INNER']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');
		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmpty('idcliente');

		return $validator;
	}
}
