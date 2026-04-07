<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacGroup extends Entity {

	protected $_accessible = [
		'slug' => true,
		'name' => true,
		'description' => true,
		'is_system' => true,
		'active' => true,
		'sort_order' => true,
		'created' => true,
		'modified' => true,
	];
}
