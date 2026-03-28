<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacRolesPermission extends Entity {

	protected $_accessible = [
		'role_id' => true,
		'permission_id' => true,
	];
}
