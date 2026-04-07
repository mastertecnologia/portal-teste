<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class RbacAuditAuthorization extends Entity {

	protected $_accessible = [
		'user_id' => true,
		'granted' => true,
		'controller' => true,
		'action' => true,
		'permission_code' => true,
		'context_json' => true,
		'created' => true,
		'modified' => true,
	];
}
