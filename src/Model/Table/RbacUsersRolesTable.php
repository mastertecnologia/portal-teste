<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacUsersRolesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_users_roles');
		$this->setPrimaryKey(['user_id', 'role_id']);
		$this->setEntityClass('App\Model\Entity\RbacUsersRole');
	}
}
