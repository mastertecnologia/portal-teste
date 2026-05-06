<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkflowSlaEscalationLog extends Entity {

	protected $_accessible = [
		'ticket_id' => true,
		'empresa_id' => true,
		'workflow_state_from' => true,
		'workflow_state_to' => true,
		'reason_code' => true,
		'created_at' => true,
	];

}
