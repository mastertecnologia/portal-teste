<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractConsumptionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_consumptions');
		$this->addBehavior('Timestamp');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id', 'joinType' => 'LEFT']);
	}
}
