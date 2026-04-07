<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacUserGroupsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_user_groups');
		$this->setPrimaryKey(['user_id', 'group_id']);
		$this->setEntityClass('App\Model\Entity\RbacUserGroup');
	}
}
