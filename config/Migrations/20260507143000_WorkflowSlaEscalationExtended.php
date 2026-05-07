<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Auto-escalonamento: fila, nível de suporte e notificações; log com payload detalhado.
 */
class WorkflowSlaEscalationExtended extends AbstractMigration {

	public function up() {
		if ($this->hasTable('workflow_sla_policies')) {
			$t = $this->table('workflow_sla_policies');
			if (!$t->hasColumn('escalate_to_queue_id')) {
				$t->addColumn('escalate_to_queue_id', 'integer', ['null' => true, 'default' => null])
					->addIndex(['escalate_to_queue_id'], ['name' => 'ix_wf_sla_escalate_queue'])
					->update();
			}
			if (!$t->hasColumn('escalate_to_support_level_id')) {
				$t->addColumn('escalate_to_support_level_id', 'integer', ['null' => true, 'default' => null])
					->addIndex(['escalate_to_support_level_id'], ['name' => 'ix_wf_sla_escalate_level'])
					->update();
			}
			if (!$t->hasColumn('notify_manager')) {
				$t->addColumn('notify_manager', 'boolean', ['null' => false, 'default' => false])->update();
			}
			if (!$t->hasColumn('notify_customer')) {
				$t->addColumn('notify_customer', 'boolean', ['null' => false, 'default' => false])->update();
			}
			if (!$t->hasColumn('notify_technician')) {
				$t->addColumn('notify_technician', 'boolean', ['null' => false, 'default' => false])->update();
			}
		}

		if ($this->hasTable('workflow_sla_escalation_logs')) {
			$l = $this->table('workflow_sla_escalation_logs');
			if (!$l->hasColumn('payload')) {
				$l->addColumn('payload', 'text', ['null' => true, 'default' => null])->update();
			}
			if (!$l->hasColumn('event_type')) {
				$l->addColumn('event_type', 'string', ['limit' => 48, 'null' => true, 'default' => null])
					->addIndex(['event_type'], ['name' => 'ix_wf_sla_log_event_type'])
					->update();
			}
		}
	}

	public function down() {
		if ($this->hasTable('workflow_sla_escalation_logs')) {
			$l = $this->table('workflow_sla_escalation_logs');
			try {
				if ($l->hasColumn('event_type')) {
					$l->removeIndex(['event_type'])->update();
				}
			} catch (\Throwable $e) {
			}
			if ($l->hasColumn('payload')) {
				$l->removeColumn('payload')->update();
			}
			if ($l->hasColumn('event_type')) {
				$l->removeColumn('event_type')->update();
			}
		}
		if ($this->hasTable('workflow_sla_policies')) {
			$t = $this->table('workflow_sla_policies');
			try {
				if ($t->hasColumn('escalate_to_queue_id')) {
					$t->removeIndex(['escalate_to_queue_id'])->update();
				}
			} catch (\Throwable $e) {
			}
			try {
				if ($t->hasColumn('escalate_to_support_level_id')) {
					$t->removeIndex(['escalate_to_support_level_id'])->update();
				}
			} catch (\Throwable $e) {
			}
			foreach ([
				'notify_technician',
				'notify_customer',
				'notify_manager',
				'escalate_to_support_level_id',
				'escalate_to_queue_id',
			] as $col) {
				if ($t->hasColumn($col)) {
					$t->removeColumn($col)->update();
				}
			}
		}
	}
}
