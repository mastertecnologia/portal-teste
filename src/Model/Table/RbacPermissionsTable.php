<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacPermissionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_permissions');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacPermission');
	}
}
