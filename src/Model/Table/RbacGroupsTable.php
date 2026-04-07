<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacGroupsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_groups');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacGroup');
	}
}
