<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractNotificationsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_notifications');
		$this->setDisplayField('tipo');
		$this->setEntityClass('App\Model\Entity\ContractNotification');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('tipo')
			->maxLength('tipo', 60)
			->requirePresence('tipo', 'create')
			->notEmpty('tipo');

		$validator
			->scalar('destinatario')
			->maxLength('destinatario', 30)
			->allowEmpty('destinatario');

		$validator
			->scalar('canal')
			->maxLength('canal', 20)
			->allowEmpty('canal');

		$validator->boolean('enviado')->allowEmpty('enviado');
		$validator->dateTime('enviado_em')->allowEmpty('enviado_em');
		$validator->scalar('erro')->allowEmpty('erro');

		return $validator;
	}
}
