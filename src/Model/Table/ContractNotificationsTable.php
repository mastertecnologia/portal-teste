<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractNotificationsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_notifications');
		$this->setDisplayField('tipo');
		$this->setEntityClass('App\Model\Entity\ContractNotification');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}
}
