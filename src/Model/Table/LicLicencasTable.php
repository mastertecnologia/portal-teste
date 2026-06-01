<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LicLicencasTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_licencas');
		$this->setDisplayField('codigo');
		$this->setPrimaryKey('id');
		$this->belongsTo('Clientes', [
			'foreignKey' => 'idcliente',
			'joinType' => 'INNER',
		]);
		$this->belongsTo('LicCatalogoProdutos', [
			'foreignKey' => 'idcatalogo',
			'joinType' => 'LEFT',
		]);
		$this->hasMany('LicAssentos', [
			'foreignKey' => 'idlicenca',
			'dependent' => true,
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');
		$validator
			->scalar('codigo')
			->maxLength('codigo', 40)
			->requirePresence('codigo', 'create')
			->notEmpty('codigo');

		return $validator;
	}
}
