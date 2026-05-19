<?php
use Migrations\AbstractMigration;

/**
 * Fila unificada de aprovações (piloto: dual-write RBAC).
 */
class ApprovalRequests extends AbstractMigration {

	public function change() {
		if ($this->hasTable('approval_requests')) {
			return;
		}
		$table = $this->table('approval_requests');
		$table
			->addColumn('idempresa', 'integer', ['null' => false])
			->addColumn('source_type', 'string', ['limit' => 40, 'null' => false])
			->addColumn('source_id', 'integer', ['null' => false])
			->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'pending'])
			->addColumn('title', 'string', ['limit' => 255, 'null' => false])
			->addColumn('summary_json', 'text', ['null' => true, 'default' => null])
			->addColumn('requested_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('requested_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('assignee_role', 'string', ['limit' => 40, 'null' => true, 'default' => null])
			->addColumn('sla_due_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('decided_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('decided_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('decision_note', 'text', ['null' => true, 'default' => null])
			->addTimestamps('created', 'modified')
			->addIndex(['idempresa', 'status'])
			->addIndex(['source_type', 'source_id'], ['unique' => true])
			->addIndex(['requested_at'])
			->create();
	}
}
