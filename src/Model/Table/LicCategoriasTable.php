<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LicCategoriasTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_categorias');
		$this->setDisplayField('nome');
		$this->setPrimaryKey('id');
		$this->hasMany('LicCatalogoProdutos', ['foreignKey' => 'idcategoria']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');
		$validator
			->scalar('codigo')
			->maxLength('codigo', 30)
			->requirePresence('codigo', 'create')
			->notEmpty('codigo');
		$validator
			->scalar('nome')
			->maxLength('nome', 120)
			->requirePresence('nome', 'create')
			->notEmpty('nome');

		return $validator;
	}
}
