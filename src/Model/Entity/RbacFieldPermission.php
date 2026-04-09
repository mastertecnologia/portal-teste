<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacFieldPermission extends Entity {

	protected $_accessible = [
		'resource_key' => true,
		'rbac_permission_id' => true,
		'access_mode' => true,
		'active' => true,
		'sort_order' => true,
		'created' => true,
		'modified' => true,
		'rbac_permission' => true,
	];
}
