<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractAutentiqueLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_autentique_logs');
		$this->setDisplayField('evento');
		$this->setEntityClass('App\Model\Entity\ContractAutentiqueLog');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('evento')
			->maxLength('evento', 100)
			->requirePresence('evento', 'create')
			->notEmpty('evento');

		$validator->integer('user_id')->allowEmpty('user_id');
		$validator->dateTime('created')->allowEmpty('created');

		return $validator;
	}
}
