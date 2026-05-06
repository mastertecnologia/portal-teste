<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkflowTransition extends Entity {

	protected $_accessible = [
		'from_state_id' => true,
		'to_state_id' => true,
		'empresa_id' => true,
		'created_at' => true,
		'from_state' => true,
		'to_state' => true,
		'empresa' => true,
	];

}
