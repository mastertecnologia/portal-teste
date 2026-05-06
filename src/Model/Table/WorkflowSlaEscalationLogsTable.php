<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class WorkflowSlaEscalationLogsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('workflow_sla_escalation_logs');
	}

}
