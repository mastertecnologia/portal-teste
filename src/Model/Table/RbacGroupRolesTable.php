<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacGroupRolesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_group_roles');
		$this->setPrimaryKey(['group_id', 'role_id']);
		$this->setEntityClass('App\Model\Entity\RbacGroupRole');
	}
}
