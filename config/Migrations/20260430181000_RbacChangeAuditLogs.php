<?php
use Migrations\AbstractMigration;

class RbacChangeAuditLogs extends AbstractMigration {
	public function change() {
		if ($this->hasTable('rbac_change_audit_logs')) {
			return;
		}
		$table = $this->table('rbac_change_audit_logs');
		$table
			->addColumn('actor_user_id', 'integer', ['null' => false])
			->addColumn('target_user_id', 'integer', ['null' => true, 'default' => null])
			->addColumn('access_request_id', 'integer', ['null' => true, 'default' => null])
			->addColumn('action_type', 'string', ['limit' => 80, 'null' => false])
			->addColumn('before_json', 'text', ['null' => true, 'default' => null])
			->addColumn('after_json', 'text', ['null' => true, 'default' => null])
			->addColumn('metadata_json', 'text', ['null' => true, 'default' => null])
			->addColumn('ip', 'string', ['limit' => 45, 'null' => true, 'default' => null])
			->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true, 'default' => null])
			->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
			->addIndex(['actor_user_id'])
			->addIndex(['target_user_id'])
			->addIndex(['access_request_id'])
			->addIndex(['action_type'])
			->addIndex(['created'])
			->create();
	}
}

