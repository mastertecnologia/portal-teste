<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class RbacFieldPermissionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_field_permissions');
		$this->setDisplayField('resource_key');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacFieldPermission');
		$this->belongsTo('RbacPermissions', [
			'foreignKey' => 'rbac_permission_id',
			'joinType' => 'LEFT',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->scalar('resource_key')
			->maxLength('resource_key', 190)
			->requirePresence('resource_key', 'create')
			->notEmpty('resource_key');

		$validator
			->scalar('access_mode')
			->inList('access_mode', ['inherit', 'hidden', 'readonly'], 'Modo inválido.');

		$validator
			->integer('sort_order')
			->allowEmpty('sort_order');

		return $validator;
	}
}
