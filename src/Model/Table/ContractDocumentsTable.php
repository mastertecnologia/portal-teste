<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class ContractDocumentsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_documents');
		$this->setDisplayField('title');
		$this->belongsTo('Contracts', ['foreignKey' => 'contract_id', 'joinType' => 'INNER']);
	}
}
