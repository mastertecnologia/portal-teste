<?php
/**
 * Logs de auditoria de ajuste manual de tempo (timer) + hash de senha de auditoria por usuário.
 */
use Migrations\AbstractMigration;

class TicketAuditLogsAndUsersAuditPassword extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		if (!$this->hasTable('users')) {
			return;
		}
		if (!$this->hasTable('ticket_audit_logs')) {
			$t = $this->table('ticket_audit_logs');
			$t->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('user_id', 'integer', ['null' => false])
				->addColumn('old_time', 'string', ['limit' => 8, 'null' => false])
				->addColumn('new_time', 'string', ['limit' => 8, 'null' => false])
				->addColumn('reason', 'text', ['null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id'], ['name' => 'ix_ticket_audit_logs_ticket_id']);
			$t->addIndex(['user_id'], ['name' => 'ix_ticket_audit_logs_user_id']);
			$t->addIndex(['created'], ['name' => 'ix_ticket_audit_logs_created']);
			$t->create();
			try {
				$t = $this->table('ticket_audit_logs');
				$t->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
			try {
				$t = $this->table('ticket_audit_logs');
				$t->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('users') && !$this->table('users')->hasColumn('audit_password_hash')) {
			$this->table('users')
				->addColumn('audit_password_hash', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->update();
		}
	}

	public function down() {
		if ($this->hasTable('users') && $this->table('users')->hasColumn('audit_password_hash')) {
			$this->table('users')->removeColumn('audit_password_hash')->update();
		}
		if ($this->hasTable('ticket_audit_logs')) {
			$this->table('ticket_audit_logs')->drop()->save();
		}
	}
}
