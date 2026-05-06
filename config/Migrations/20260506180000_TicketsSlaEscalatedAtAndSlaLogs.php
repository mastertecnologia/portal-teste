<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Anti-loop do cron de auto-escalonamento + log opcional para diagnóstico na UI admin.
 */
class TicketsSlaEscalatedAtAndSlaLogs extends AbstractMigration {

	public function up() {
		if ($this->hasTable('tickets')) {
			$t = $this->table('tickets');
			if (!$t->hasColumn('sla_escalated_at')) {
				$t->addColumn('sla_escalated_at', 'timestamp', ['null' => true, 'default' => null])
					->addIndex(['sla_escalated_at'], ['name' => 'ix_tickets_sla_escalated_at'])
					->update();
			}
		}

		if (!$this->hasTable('workflow_sla_escalation_logs')) {
			$this->table('workflow_sla_escalation_logs')
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('empresa_id', 'integer', ['null' => false])
				->addColumn('workflow_state_from', 'integer', ['null' => true, 'default' => null])
				->addColumn('workflow_state_to', 'integer', ['null' => true, 'default' => null])
				->addColumn('reason_code', 'string', ['limit' => 64, 'null' => true, 'default' => null])
				->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['ticket_id'], ['name' => 'ix_wf_sla_log_ticket'])
				->addIndex(['empresa_id'], ['name' => 'ix_wf_sla_log_empresa'])
				->addIndex(['created_at'], ['name' => 'ix_wf_sla_log_created'])
				->create();
		}

		if ($this->isPgsql() && $this->hasTable('workflow_sla_policies')) {
			$this->execute(
				'CREATE UNIQUE INDEX IF NOT EXISTS ux_wf_sla_global_state '
				. 'ON workflow_sla_policies (workflow_state_id) WHERE empresa_id IS NULL'
			);
		}
	}

	public function down() {
		if ($this->isPgsql() && $this->hasTable('workflow_sla_policies')) {
			$this->execute('DROP INDEX IF EXISTS ux_wf_sla_global_state');
		}
		if ($this->hasTable('workflow_sla_escalation_logs')) {
			$this->table('workflow_sla_escalation_logs')->drop()->save();
		}
		if ($this->hasTable('tickets')) {
			$t = $this->table('tickets');
			if ($t->hasColumn('sla_escalated_at')) {
				try {
					$t->removeIndex(['sla_escalated_at'])->update();
				} catch (\Throwable $e) {
				}
				$t->removeColumn('sla_escalated_at')->update();
			}
		}
	}

	protected function isPgsql(): bool {
		try {
			$c = $this->getAdapter()->getConnection();
			if ($c && $c->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') {
				return true;
			}
		} catch (\Throwable $e) {
		}
		try {
			$t = strtolower((string)$this->getAdapter()->getAdapterType());

			return $t === 'pgsql' || $t === 'postgres' || $t === 'postgresql';
		} catch (\Throwable $e) {
			return false;
		}
	}
}
