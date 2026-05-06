<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkflowSlaPolicy extends Entity {

	protected $_accessible = [
		'empresa_id' => true,
		'workflow_state_id' => true,
		'resposta_minutos' => true,
		'resolucao_minutos' => true,
		'pausa_sla' => true,
		'is_final' => true,
		'auto_escalar' => true,
		'escalate_to_state_id' => true,
		'escalate_after_minutos' => true,
		'created_at' => true,
		'updated_at' => true,
		'empresa' => true,
		'workflow_state' => true,
		'escalate_to_state' => true,
	];

}
