<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacPermissionPolicy extends Entity {

	protected $_accessible = [
		'name' => true,
		'rbac_permission_id' => true,
		'priority' => true,
		'conditions_json' => true,
		'active' => true,
		'description' => true,
		'created' => true,
		'modified' => true,
	];
}
