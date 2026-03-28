<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacRole extends Entity {

	protected $_accessible = [
		'slug' => true,
		'name' => true,
		'description' => true,
		'is_system' => true,
		'active' => true,
		'sort_order' => true,
	];
}
