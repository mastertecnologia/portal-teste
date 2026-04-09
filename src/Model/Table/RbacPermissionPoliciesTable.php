<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacPermissionPoliciesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_permission_policies');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacPermissionPolicy');
		$this->belongsTo('RbacPermissions', [
			'foreignKey' => 'rbac_permission_id',
			'joinType' => 'LEFT',
		]);
	}
}
