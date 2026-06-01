<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LicCatalogoProdutosTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('lic_catalogo_produtos');
		$this->setDisplayField('nome');
		$this->setPrimaryKey('id');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');
		$validator
			->scalar('nome')
			->maxLength('nome', 200)
			->requirePresence('nome', 'create')
			->notEmpty('nome');

		return $validator;
	}
}
