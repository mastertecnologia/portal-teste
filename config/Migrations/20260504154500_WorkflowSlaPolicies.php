<?php
use Migrations\AbstractMigration;

class WorkflowSlaPolicies extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('workflow_sla_policies')) {
			$this->table('workflow_sla_policies')
				->addColumn('empresa_id', 'integer', ['null' => false])
				->addColumn('workflow_state_id', 'integer', ['null' => false])
				->addColumn('resposta_minutos', 'integer', ['null' => true, 'default' => null])
				->addColumn('resolucao_minutos', 'integer', ['null' => true, 'default' => null])
				->addColumn('pausa_sla', 'boolean', ['null' => false, 'default' => false])
				->addColumn('is_final', 'boolean', ['null' => false, 'default' => false])
				->addColumn('auto_escalar', 'boolean', ['null' => false, 'default' => false])
				->addColumn('escalate_to_state_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('escalate_after_minutos', 'integer', ['null' => true, 'default' => null])
				->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
				->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['empresa_id'], ['name' => 'ix_wf_sla_empresa'])
				->addIndex(['workflow_state_id'], ['name' => 'ix_wf_sla_state'])
				->addIndex(['auto_escalar'], ['name' => 'ix_wf_sla_auto_escalar'])
				->addIndex(['escalate_to_state_id'], ['name' => 'ix_wf_sla_escalate_to'])
				->addIndex(['empresa_id', 'workflow_state_id'], ['name' => 'ux_wf_sla_empresa_state', 'unique' => true])
				->create();
		}

		try {
			$this->table('workflow_sla_policies')
				->addForeignKey('workflow_state_id', 'workflow_states', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
				->addForeignKey('escalate_to_state_id', 'workflow_states', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
				->update();
		} catch (\Throwable $e) {
		}
	}

	public function down() {
		if (!$this->hasTable('workflow_sla_policies')) {
			return;
		}
		try {
			$this->table('workflow_sla_policies')->dropForeignKey('workflow_state_id')->update();
		} catch (\Throwable $e) {
		}
		try {
			$this->table('workflow_sla_policies')->dropForeignKey('escalate_to_state_id')->update();
		} catch (\Throwable $e) {
		}
		$this->table('workflow_sla_policies')->drop()->save();
	}
}
