<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class WorkflowSlaPolicy extends Entity {

	protected $_accessible = [
		'empresa_id' => true,
		'idcliente' => true,
		'contract_id' => true,
		'contract_service_id' => true,
		'problema_id' => true,
		'queue_id' => true,
		'support_level_id' => true,
		'scope_priority' => true,
		'workflow_state_id' => true,
		'resposta_minutos' => true,
		'resolucao_minutos' => true,
		'pausa_sla' => true,
		'is_final' => true,
		'auto_escalar' => true,
		'escalate_to_state_id' => true,
		'escalate_after_minutos' => true,
		'escalate_to_queue_id' => true,
		'escalate_to_support_level_id' => true,
		'notify_manager' => true,
		'notify_customer' => true,
		'notify_technician' => true,
		'ativo' => true,
		'created_at' => true,
		'updated_at' => true,
		'empresa' => true,
		'workflow_state' => true,
		'escalate_to_state' => true,
	];

}
