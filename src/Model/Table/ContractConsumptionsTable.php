<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractConsumptionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_consumptions');
		$this->setDisplayField('reference_month');
		$this->setEntityClass('App\Model\Entity\ContractConsumption');
		$this->addBehavior('Timestamp');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'LEFT']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('contract_id')
			->requirePresence('contract_id', 'create')
			->notEmpty('contract_id');

		$validator
			->scalar('reference_month')
			->maxLength('reference_month', 7)
			->requirePresence('reference_month', 'create')
			->notEmpty('reference_month')
			->add('reference_month', 'formatMes', [
				'rule' => ['custom', '/^\d{4}-\d{2}$/'],
				'message' => __('Use o formato YYYY-MM.'),
			]);

		$validator->integer('ticket_id')->allowEmpty('ticket_id');
		$validator->integer('service_order_id')->allowEmpty('service_order_id');
		$validator->integer('contract_service_id')->allowEmpty('contract_service_id');
		$validator->scalar('period_type')->maxLength('period_type', 40)->allowEmpty('period_type');
		$validator->scalar('source_type')->maxLength('source_type', 50)->allowEmpty('source_type');
		$validator->scalar('source_id')->maxLength('source_id', 100)->allowEmpty('source_id');
		$validator->scalar('source_hash')->maxLength('source_hash', 191)->allowEmpty('source_hash');
		$validator->decimal('consumed_quantity')->allowEmpty('consumed_quantity');
		$validator->decimal('consumed_hours')->allowEmpty('consumed_hours');
		$validator->decimal('consumed_amount')->allowEmpty('consumed_amount');
		$validator->scalar('notes')->allowEmpty('notes');

		return $validator;
	}
}
