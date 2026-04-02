<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractServicesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_services');
		$this->setDisplayField('service_name');
		$this->addBehavior('Timestamp');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}
}
