<?php
use Migrations\AbstractMigration;

class RbacAccessRequests extends AbstractMigration {
	public function change() {
		if ($this->hasTable('rbac_access_requests')) {
			return;
		}
		$table = $this->table('rbac_access_requests');
		$table
			->addColumn('support_code', 'string', ['limit' => 40, 'null' => false])
			->addColumn('user_id', 'integer', ['null' => false])
			->addColumn('controller', 'string', ['limit' => 80, 'null' => false])
			->addColumn('action', 'string', ['limit' => 80, 'null' => false])
			->addColumn('plugin', 'string', ['limit' => 80, 'null' => true, 'default' => null])
			->addColumn('prefix', 'string', ['limit' => 80, 'null' => true, 'default' => null])
			->addColumn('reason', 'string', ['limit' => 64, 'null' => true, 'default' => null])
			->addColumn('requested_permission_codes', 'text', ['null' => true, 'default' => null])
			->addColumn('suggested_role_ids', 'text', ['null' => true, 'default' => null])
			->addColumn('abac_context', 'text', ['null' => true, 'default' => null])
			->addColumn('status', 'string', ['limit' => 30, 'null' => false, 'default' => 'pending_manager'])
			->addColumn('justification', 'text', ['null' => true, 'default' => null])
			->addColumn('requester_message', 'text', ['null' => true, 'default' => null])
			->addColumn('admin_response', 'text', ['null' => true, 'default' => null])
			->addColumn('reviewed_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('reviewed_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('manager_reviewed_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('manager_reviewed_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('manager_response', 'text', ['null' => true, 'default' => null])
			->addColumn('admin_reviewed_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('admin_reviewed_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('admin_response', 'text', ['null' => true, 'default' => null])
			->addTimestamps('created', 'modified')
			->addIndex(['support_code'])
			->addIndex(['user_id'])
			->addIndex(['status'])
			->addIndex(['user_id', 'controller', 'action', 'status'])
			->addIndex(['created'])
			->create();
	}
}

