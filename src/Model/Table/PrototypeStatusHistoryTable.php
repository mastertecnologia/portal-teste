<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class PrototypeStatusHistoryTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('prototype_status_history');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\PrototypeStatusHistoryEntity');
	}
}
