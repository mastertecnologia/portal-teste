<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractAutentiqueLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_autentique_logs');
		$this->setDisplayField('evento');
		$this->setEntityClass('App\Model\Entity\ContractAutentiqueLog');

		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
	}
}
