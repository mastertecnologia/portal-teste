<?php
use Migrations\AbstractMigration;

class RbacAccessGrants extends AbstractMigration {

	public function up() {
		if ($this->hasTable('rbac_access_grants') || !$this->hasTable('rbac_access_requests')) {
			return;
		}

		$this->table('rbac_access_grants')
			->addColumn('access_request_id', 'integer', ['null' => false])
			->addColumn('user_id', 'integer', ['null' => false])
			->addColumn('role_id', 'integer', ['null' => false])
			->addColumn('granted_by', 'integer', ['null' => false])
			->addColumn('granted_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
			->addColumn('expires_at', 'timestamp', ['null' => true, 'default' => null])
			->addColumn('revoked_at', 'timestamp', ['null' => true, 'default' => null])
			->addColumn('revoked_by', 'integer', ['null' => true, 'default' => null])
			->addColumn('revoke_reason', 'text', ['null' => true, 'default' => null])
			->addColumn('renewed_grants_id', 'integer', ['null' => true, 'default' => null])
			->addColumn('applied_role_assignment', 'boolean', ['null' => false, 'default' => false])
			->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'active'])
			->addColumn('expiry_notifications_sent_json', 'text', ['null' => true, 'default' => null])
			->addTimestamps('created', 'modified')
			->addIndex(['access_request_id'], ['unique' => true])
			->addIndex(['user_id'])
			->addIndex(['role_id'])
			->addIndex(['status'])
			->addIndex(['expires_at'])
			->addForeignKey('access_request_id', 'rbac_access_requests', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
			->create();
	}

	public function down() {
		if (!$this->hasTable('rbac_access_grants')) {
			return;
		}
		$this->table('rbac_access_grants')->drop()->save();
	}
}
