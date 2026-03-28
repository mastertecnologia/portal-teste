<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacUsersRole extends Entity {

	protected $_accessible = [
		'user_id' => true,
		'role_id' => true,
	];
}
