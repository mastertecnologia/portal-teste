<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ApprovalRequestsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('approval_requests');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\ApprovalRequest');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Solicitante', [
			'className' => 'Users',
			'foreignKey' => 'requested_by',
			'joinType' => 'LEFT',
		]);
		$this->belongsTo('Decisor', [
			'className' => 'Users',
			'foreignKey' => 'decided_by',
			'joinType' => 'LEFT',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');
		$validator
			->scalar('source_type')
			->maxLength('source_type', 40)
			->requirePresence('source_type', 'create')
			->notEmpty('source_type');
		$validator
			->integer('source_id')
			->requirePresence('source_id', 'create')
			->notEmpty('source_id');
		$validator
			->scalar('status')
			->maxLength('status', 20)
			->inList('status', ['pending', 'approved', 'rejected', 'cancelled'], __('Status inválido.'));
		$validator
			->scalar('title')
			->maxLength('title', 255)
			->requirePresence('title', 'create')
			->notEmpty('title');

		return $validator;
	}
}
