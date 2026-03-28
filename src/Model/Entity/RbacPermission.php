<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacPermission extends Entity {

	protected $_accessible = [
		'code' => true,
		'name' => true,
		'module' => true,
		'controller' => true,
		'action' => true,
		'perm_type' => true,
		'abac_scope' => true,
		'description' => true,
		'sort_order' => true,
	];
}
