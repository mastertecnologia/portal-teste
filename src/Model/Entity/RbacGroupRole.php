<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacGroupRole extends Entity {

	protected $_accessible = [
		'group_id' => true,
		'role_id' => true,
	];
}
