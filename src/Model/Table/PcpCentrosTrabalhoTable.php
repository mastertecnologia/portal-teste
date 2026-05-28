<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PcpCentrosTrabalhoTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('pcp_centros_trabalho');
		$this->setDisplayField('nome');
		$this->setPrimaryKey('id');
		$this->belongsTo('Empresas')->setForeignKey('idempresa');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('codigo', 'create')
			->notEmptyString('codigo')
			->requirePresence('nome', 'create')
			->notEmptyString('nome');

		return $validator;
	}
}
