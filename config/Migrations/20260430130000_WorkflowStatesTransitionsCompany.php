<?php
use Migrations\AbstractMigration;

class WorkflowStatesTransitionsCompany extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('workflow_states')) {
			$this->table('workflow_states')
				->addColumn('nome', 'string', ['limit' => 120, 'null' => false])
				->addColumn('codigo', 'string', ['limit' => 80, 'null' => false])
				->addColumn('is_inicial', 'boolean', ['null' => false, 'default' => false])
				->addColumn('is_final', 'boolean', ['null' => false, 'default' => false])
				->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['codigo'], ['unique' => true, 'name' => 'ux_workflow_states_codigo'])
				->create();
		}

		if (!$this->hasTable('workflow_transitions')) {
			$this->table('workflow_transitions')
				->addColumn('from_state_id', 'integer', ['null' => false])
				->addColumn('to_state_id', 'integer', ['null' => false])
				->addColumn('empresa_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['from_state_id'], ['name' => 'ix_wf_transitions_from'])
				->addIndex(['to_state_id'], ['name' => 'ix_wf_transitions_to'])
				->addIndex(['empresa_id'], ['name' => 'ix_wf_transitions_empresa'])
				->addIndex(['from_state_id', 'to_state_id', 'empresa_id'], ['name' => 'ux_wf_transitions_unique', 'unique' => true])
				->create();
		}

		try {
			$this->table('workflow_transitions')
				->addForeignKey('from_state_id', 'workflow_states', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
				->addForeignKey('to_state_id', 'workflow_states', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
				->update();
		} catch (\Throwable $e) {
		}

		if ($this->hasTable('tickets')) {
			$tickets = $this->table('tickets');
			if (!$tickets->hasColumn('workflow_state_id')) {
				$tickets
					->addColumn('workflow_state_id', 'integer', ['null' => true, 'default' => null])
					->addIndex(['workflow_state_id'], ['name' => 'ix_tickets_workflow_state'])
					->update();
			}
			try {
				$this->table('tickets')
					->addForeignKey('workflow_state_id', 'workflow_states', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
					->update();
			} catch (\Throwable $e) {
			}
		}

		$this->_seedDefaultWorkflow();
	}

	protected function _seedDefaultWorkflow(): void {
		$now = date('Y-m-d H:i:s');
		$states = [
			['nome' => 'Aberto', 'codigo' => 'aberto', 'is_inicial' => true, 'is_final' => false],
			['nome' => 'Em execução', 'codigo' => 'emandamento', 'is_inicial' => false, 'is_final' => false],
			['nome' => 'Pendente', 'codigo' => 'pendente', 'is_inicial' => false, 'is_final' => false],
			['nome' => 'Resolvido', 'codigo' => 'resolvido', 'is_inicial' => false, 'is_final' => true],
			['nome' => 'Fechado', 'codigo' => 'fechado', 'is_inicial' => false, 'is_final' => true],
		];
		foreach ($states as $st) {
			$exists = $this->fetchRow("SELECT id FROM workflow_states WHERE codigo = '" . addslashes($st['codigo']) . "' LIMIT 1");
			if (!$exists) {
				$this->table('workflow_states')->insert([[
					'nome' => $st['nome'],
					'codigo' => $st['codigo'],
					'is_inicial' => $st['is_inicial'] ? 1 : 0,
					'is_final' => $st['is_final'] ? 1 : 0,
					'created_at' => $now,
				]])->saveData();
			}
		}

		$ids = [];
		$rows = (array)$this->fetchAll('SELECT id, codigo FROM workflow_states');
		foreach ($rows as $r) {
			$ids[(string)$r['codigo']] = (int)$r['id'];
		}
		$edges = [
			['aberto', 'emandamento'],
			['emandamento', 'pendente'],
			['emandamento', 'resolvido'],
			['pendente', 'emandamento'],
			['resolvido', 'fechado'],
		];
		foreach ($edges as $e) {
			$from = $ids[$e[0]] ?? 0;
			$to = $ids[$e[1]] ?? 0;
			if ($from <= 0 || $to <= 0) {
				continue;
			}
			$exists = $this->fetchRow(
				'SELECT id FROM workflow_transitions WHERE from_state_id = ' . (int)$from .
				' AND to_state_id = ' . (int)$to .
				' AND empresa_id IS NULL LIMIT 1'
			);
			if (!$exists) {
				$this->table('workflow_transitions')->insert([[
					'from_state_id' => $from,
					'to_state_id' => $to,
					'empresa_id' => null,
					'created_at' => $now,
				]])->saveData();
			}
		}
	}

	public function down() {
		if ($this->hasTable('tickets')) {
			try {
				$this->table('tickets')->dropForeignKey('workflow_state_id')->update();
			} catch (\Throwable $e) {
			}
			$tickets = $this->table('tickets');
			if ($tickets->hasColumn('workflow_state_id')) {
				try {
					$tickets->removeIndex(['workflow_state_id'])->update();
				} catch (\Throwable $e) {
				}
				$tickets->removeColumn('workflow_state_id')->update();
			}
		}

		if ($this->hasTable('workflow_transitions')) {
			$this->table('workflow_transitions')->drop()->save();
		}
		if ($this->hasTable('workflow_states')) {
			$this->table('workflow_states')->drop()->save();
		}
	}
}
