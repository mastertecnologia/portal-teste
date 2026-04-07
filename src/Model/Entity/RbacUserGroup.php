<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacUserGroup extends Entity {

	protected $_accessible = [
		'user_id' => true,
		'group_id' => true,
	];
}
