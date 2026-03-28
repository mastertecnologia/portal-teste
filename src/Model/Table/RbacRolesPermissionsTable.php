<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacRolesPermissionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_roles_permissions');
		$this->setPrimaryKey(['role_id', 'permission_id']);
		$this->setEntityClass('App\Model\Entity\RbacRolesPermission');
	}
}
