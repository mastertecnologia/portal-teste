<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractServicesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_services');
		$this->setDisplayField('service_name');
		$this->setEntityClass('App\Model\Entity\ContractService');
		$this->addBehavior('Timestamp');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('service_name')
			->maxLength('service_name', 255)
			->requirePresence('service_name', 'create')
			->notEmpty('service_name');

		$validator->scalar('service_description')->allowEmpty('service_description');
		$validator->boolean('is_included')->allowEmpty('is_included');
		$validator->decimal('max_hours')->allowEmpty('max_hours');
		$validator
			->scalar('tipo_item')
			->maxLength('tipo_item', 40)
			->allowEmpty('tipo_item');
		$validator->scalar('unidade')->maxLength('unidade', 30)->allowEmpty('unidade');
		$validator->decimal('franquia_horas')->allowEmpty('franquia_horas');
		$validator->decimal('valor_unitario')->allowEmpty('valor_unitario');
		$validator->decimal('valor_total')->allowEmpty('valor_total');
		$validator->date('vigencia_inicio')->allowEmpty('vigencia_inicio');
		$validator->date('vigencia_fim')->allowEmpty('vigencia_fim');
		$validator->boolean('ativo')->allowEmpty('ativo');
		$validator->scalar('observacoes')->allowEmpty('observacoes');

		return $validator;
	}
}
