<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacAccessGrantsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_access_grants');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
		$this->addBehavior('Timestamp');
	}
}
