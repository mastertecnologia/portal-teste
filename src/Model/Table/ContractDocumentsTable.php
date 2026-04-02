<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractDocumentsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_documents');
		$this->setDisplayField('title');
		$this->setEntityClass('App\Model\Entity\ContractDocument');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('title')
			->maxLength('title', 255)
			->requirePresence('title', 'create')
			->notEmpty('title');

		$validator
			->scalar('file_path')
			->maxLength('file_path', 500)
			->requirePresence('file_path', 'create')
			->notEmpty('file_path');

		$validator->boolean('is_public')->allowEmpty('is_public');

		return $validator;
	}
}
