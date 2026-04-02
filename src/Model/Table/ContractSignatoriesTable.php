<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractSignatoriesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_signatories');
		$this->setDisplayField('nome');
		$this->setEntityClass('App\Model\Entity\ContractSignatory');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}
}
