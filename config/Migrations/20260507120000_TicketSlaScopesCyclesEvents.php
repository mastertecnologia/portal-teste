<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * SLA por escopo (cliente, contrato, item de contrato, problema, fila, nível) em workflow_sla_policies
 * mais auditoria em ticket_sla_cycles / ticket_sla_events.
 *
 * Referência de schema existente (migrations):
 * - workflow_sla_policies: 20260504154500 (uniq ux_wf_sla_empresa_state), 20260506154500 (empresa_id nullable),
 *   20260506180000 (índice parcial ux_wf_sla_global_state — removido aqui para permitir várias políticas globais
 *   por estado diferindo por cliente/contrato/etc.; substituído por ux_wf_sla_policy_scope).
 * - tickets: id PK; idempresa; idcliente; queue_id; support_level_id (20250319120000, 20250321140000).
 * - contracts / contract_services: 20260405130000 (id SERIAL, contract_id FK).
 * - problemas: legado (id PK).
 * - clientes, empresas, queues, support_levels, workflow_states, users: PK integer/SERIAL conforme migrations.
 *
 * PostgreSQL: execução completa. Outros drivers: no-op (mesmo padrão de PortalAdvanced*).
 */
class TicketSlaScopesCyclesEvents extends AbstractMigration {

	private const SCOPE_SENTINEL = -999999999;

	public function up(): void {
		if (!$this->isPgsql() || !$this->hasTable('workflow_sla_policies')) {
			return;
		}

		$this->execute('ALTER TABLE workflow_sla_policies DROP CONSTRAINT IF EXISTS ux_wf_sla_empresa_state');
		$this->execute('DROP INDEX IF EXISTS ux_wf_sla_empresa_state');
		$this->execute('DROP INDEX IF EXISTS ux_wf_sla_global_state');

		$this->_addWorkflowSlaPolicyScopeColumns();

		$this->execute(
			'CREATE UNIQUE INDEX ux_wf_sla_policy_scope ON workflow_sla_policies ('
			. 'COALESCE(empresa_id, ' . self::SCOPE_SENTINEL . '), '
			. 'workflow_state_id, '
			. 'COALESCE(idcliente, ' . self::SCOPE_SENTINEL . '), '
			. 'COALESCE(contract_id, ' . self::SCOPE_SENTINEL . '), '
			. 'COALESCE(contract_service_id, ' . self::SCOPE_SENTINEL . '), '
			. 'COALESCE(problema_id, ' . self::SCOPE_SENTINEL . '), '
			. 'COALESCE(queue_id, ' . self::SCOPE_SENTINEL . '), '
			. 'COALESCE(support_level_id, ' . self::SCOPE_SENTINEL . ')'
			. ')'
		);

		$this->_addWorkflowSlaPolicyScopeForeignKeys();
		$this->_addWorkflowSlaPolicyScopeLookupIndexes();

		$this->_createTicketSlaCyclesTable();
		$this->_createTicketSlaEventsTable();
	}

	public function down(): void {
		if (!$this->isPgsql()) {
			return;
		}

		if ($this->hasTable('ticket_sla_events')) {
			$this->execute('DROP TABLE IF EXISTS ticket_sla_events');
		}
		if ($this->hasTable('ticket_sla_cycles')) {
			$this->execute('DROP TABLE IF EXISTS ticket_sla_cycles');
		}

		if (!$this->hasTable('workflow_sla_policies')) {
			return;
		}

		$this->_dropWorkflowSlaPolicyScopeForeignKeys();
		$this->_dropWorkflowSlaPolicyScopeLookupIndexes();

		$this->execute('DROP INDEX IF EXISTS ux_wf_sla_global_state');
		$this->execute('DROP INDEX IF EXISTS ux_wf_sla_policy_scope');

		$this->_dropWorkflowSlaPolicyScopeColumns();

		// Pode falhar se houver mais de uma linha com o mesmo (empresa_id, workflow_state_id)
		// ou mais de uma política global (empresa_id IS NULL) por estado — rever dados antes do rollback.
		$this->execute(
			'CREATE UNIQUE INDEX IF NOT EXISTS ux_wf_sla_empresa_state '
			. 'ON workflow_sla_policies (empresa_id, workflow_state_id)'
		);
		$this->execute(
			'CREATE UNIQUE INDEX IF NOT EXISTS ux_wf_sla_global_state '
			. 'ON workflow_sla_policies (workflow_state_id) WHERE empresa_id IS NULL'
		);
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

	private function _addWorkflowSlaPolicyScopeColumns(): void {
		$cols = [
			'idcliente' => 'INTEGER NULL',
			'contract_id' => 'INTEGER NULL',
			'contract_service_id' => 'INTEGER NULL',
			'problema_id' => 'INTEGER NULL',
			'queue_id' => 'INTEGER NULL',
			'support_level_id' => 'INTEGER NULL',
			'scope_priority' => 'INTEGER NULL',
		];
		foreach ($cols as $name => $ddl) {
			$this->execute(
				"ALTER TABLE workflow_sla_policies ADD COLUMN IF NOT EXISTS {$name} {$ddl}"
			);
		}
	}

	private function _dropWorkflowSlaPolicyScopeColumns(): void {
		foreach ([
			'scope_priority',
			'support_level_id',
			'queue_id',
			'problema_id',
			'contract_service_id',
			'contract_id',
			'idcliente',
		] as $col) {
			$this->execute(
				"ALTER TABLE workflow_sla_policies DROP COLUMN IF EXISTS {$col}"
			);
		}
	}

	private function _addWorkflowSlaPolicyScopeForeignKeys(): void {
		$fks = [
			[
				'name' => 'fk_wf_sla_policies_cliente',
				'col' => 'idcliente',
				'refTable' => 'clientes',
				'refCol' => 'id',
			],
			[
				'name' => 'fk_wf_sla_policies_contract',
				'col' => 'contract_id',
				'refTable' => 'contracts',
				'refCol' => 'id',
			],
			[
				'name' => 'fk_wf_sla_policies_contract_service',
				'col' => 'contract_service_id',
				'refTable' => 'contract_services',
				'refCol' => 'id',
			],
			[
				'name' => 'fk_wf_sla_policies_problema',
				'col' => 'problema_id',
				'refTable' => 'problemas',
				'refCol' => 'id',
			],
			[
				'name' => 'fk_wf_sla_policies_queue',
				'col' => 'queue_id',
				'refTable' => 'queues',
				'refCol' => 'id',
			],
			[
				'name' => 'fk_wf_sla_policies_support_level',
				'col' => 'support_level_id',
				'refTable' => 'support_levels',
				'refCol' => 'id',
			],
		];
		foreach ($fks as $fk) {
			if (!$this->hasTable($fk['refTable'])) {
				continue;
			}
			$this->execute(sprintf(
				'DO $do$ BEGIN '
				. 'ALTER TABLE workflow_sla_policies ADD CONSTRAINT %s '
				. 'FOREIGN KEY (%s) REFERENCES %s (%s) ON UPDATE CASCADE ON DELETE SET NULL; '
				. 'EXCEPTION WHEN duplicate_object THEN NULL; WHEN undefined_table THEN NULL; END $do$',
				$fk['name'],
				$fk['col'],
				$fk['refTable'],
				$fk['refCol']
			));
		}
	}

	private function _dropWorkflowSlaPolicyScopeForeignKeys(): void {
		foreach ([
			'fk_wf_sla_policies_support_level',
			'fk_wf_sla_policies_queue',
			'fk_wf_sla_policies_problema',
			'fk_wf_sla_policies_contract_service',
			'fk_wf_sla_policies_contract',
			'fk_wf_sla_policies_cliente',
		] as $name) {
			$this->execute(
				'ALTER TABLE workflow_sla_policies DROP CONSTRAINT IF EXISTS ' . $name
			);
		}
	}

	private function _addWorkflowSlaPolicyScopeLookupIndexes(): void {
		$list = [
			['ix_wf_sla_policies_idcliente', 'idcliente'],
			['ix_wf_sla_policies_contract_id', 'contract_id'],
			['ix_wf_sla_policies_contract_service_id', 'contract_service_id'],
			['ix_wf_sla_policies_problema_id', 'problema_id'],
			['ix_wf_sla_policies_queue_id', 'queue_id'],
			['ix_wf_sla_policies_support_level_id', 'support_level_id'],
		];
		foreach ($list as [$idx, $col]) {
			$this->execute(
				"CREATE INDEX IF NOT EXISTS {$idx} ON workflow_sla_policies ({$col}) WHERE {$col} IS NOT NULL"
			);
		}
	}

	private function _dropWorkflowSlaPolicyScopeLookupIndexes(): void {
		foreach ([
			'ix_wf_sla_policies_support_level_id',
			'ix_wf_sla_policies_queue_id',
			'ix_wf_sla_policies_problema_id',
			'ix_wf_sla_policies_contract_service_id',
			'ix_wf_sla_policies_contract_id',
			'ix_wf_sla_policies_idcliente',
		] as $idx) {
			$this->execute("DROP INDEX IF EXISTS {$idx}");
		}
	}

	private function _createTicketSlaCyclesTable(): void {
		if ($this->hasTable('ticket_sla_cycles')) {
			return;
		}
		if (!$this->hasTable('tickets')) {
			return;
		}

		$this->execute(<<<'SQL'
CREATE TABLE ticket_sla_cycles (
	id SERIAL PRIMARY KEY,
	ticket_id INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	cycle_number INTEGER NOT NULL DEFAULT 1,
	workflow_state_id INTEGER NULL,
	workflow_sla_policy_id INTEGER NULL,
	idcliente INTEGER NULL,
	contract_id INTEGER NULL,
	contract_service_id INTEGER NULL,
	problema_id INTEGER NULL,
	queue_id INTEGER NULL,
	support_level_id INTEGER NULL,
	sla_resposta_minutos INTEGER NULL,
	sla_resolucao_minutos INTEGER NULL,
	data_limite_resposta TIMESTAMPTZ NULL,
	data_limite_resolucao TIMESTAMPTZ NULL,
	started_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
	ended_at TIMESTAMPTZ NULL,
	metadata JSONB NULL,
	created_at TIMESTAMPTZ NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMPTZ NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX ix_ticket_sla_cycles_ticket ON ticket_sla_cycles (ticket_id);
CREATE INDEX ix_ticket_sla_cycles_empresa ON ticket_sla_cycles (idempresa);
CREATE INDEX ix_ticket_sla_cycles_open ON ticket_sla_cycles (ticket_id) WHERE ended_at IS NULL;
CREATE INDEX ix_ticket_sla_cycles_started ON ticket_sla_cycles (started_at);
SQL
		);

		$this->_tryFk(
			'ticket_sla_cycles',
			'fk_ticket_sla_cycles_ticket',
			'ticket_id',
			'tickets',
			'id',
			'CASCADE',
			'CASCADE'
		);
		if ($this->hasTable('empresas')) {
			$this->_tryFk(
				'ticket_sla_cycles',
				'fk_ticket_sla_cycles_empresa',
				'idempresa',
				'empresas',
				'id',
				'RESTRICT',
				'CASCADE'
			);
		}
		if ($this->hasTable('workflow_states')) {
			$this->_tryFk(
				'ticket_sla_cycles',
				'fk_ticket_sla_cycles_workflow_state',
				'workflow_state_id',
				'workflow_states',
				'id',
				'SET NULL',
				'CASCADE'
			);
		}
		$this->_tryFk(
			'ticket_sla_cycles',
			'fk_ticket_sla_cycles_wf_sla_policy',
			'workflow_sla_policy_id',
			'workflow_sla_policies',
			'id',
			'SET NULL',
			'CASCADE'
		);
	}

	private function _createTicketSlaEventsTable(): void {
		if ($this->hasTable('ticket_sla_events')) {
			return;
		}
		if (!$this->hasTable('tickets')) {
			return;
		}

		$this->execute(<<<'SQL'
CREATE TABLE ticket_sla_events (
	id SERIAL PRIMARY KEY,
	ticket_id INTEGER NOT NULL,
	idempresa INTEGER NOT NULL,
	ticket_sla_cycle_id INTEGER NULL,
	event_type VARCHAR(64) NOT NULL,
	source VARCHAR(32) NULL,
	workflow_sla_policy_id INTEGER NULL,
	payload JSONB NULL,
	created_by_user_id INTEGER NULL,
	created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX ix_ticket_sla_events_ticket ON ticket_sla_events (ticket_id);
CREATE INDEX ix_ticket_sla_events_empresa ON ticket_sla_events (idempresa);
CREATE INDEX ix_ticket_sla_events_cycle ON ticket_sla_events (ticket_sla_cycle_id);
CREATE INDEX ix_ticket_sla_events_type ON ticket_sla_events (event_type);
CREATE INDEX ix_ticket_sla_events_created ON ticket_sla_events (created_at);
SQL
		);

		$this->_tryFk(
			'ticket_sla_events',
			'fk_ticket_sla_events_ticket',
			'ticket_id',
			'tickets',
			'id',
			'CASCADE',
			'CASCADE'
		);
		if ($this->hasTable('empresas')) {
			$this->_tryFk(
				'ticket_sla_events',
				'fk_ticket_sla_events_empresa',
				'idempresa',
				'empresas',
				'id',
				'RESTRICT',
				'CASCADE'
			);
		}
		$this->_tryFk(
			'ticket_sla_events',
			'fk_ticket_sla_events_cycle',
			'ticket_sla_cycle_id',
			'ticket_sla_cycles',
			'id',
			'SET NULL',
			'CASCADE'
		);
		$this->_tryFk(
			'ticket_sla_events',
			'fk_ticket_sla_events_wf_sla_policy',
			'workflow_sla_policy_id',
			'workflow_sla_policies',
			'id',
			'SET NULL',
			'CASCADE'
		);
		if ($this->hasTable('users')) {
			$this->_tryFk(
				'ticket_sla_events',
				'fk_ticket_sla_events_user',
				'created_by_user_id',
				'users',
				'id',
				'SET NULL',
				'CASCADE'
			);
		}
	}

	/**
	 * @param 'CASCADE'|'SET NULL'|'RESTRICT' $onDelete
	 * @param 'CASCADE' $onUpdate
	 */
	private function _tryFk(
		string $table,
		string $constraintName,
		string $column,
		string $refTable,
		string $refColumn,
		string $onDelete,
		string $onUpdate
	): void {
		$sql = sprintf(
			'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
			$table,
			$constraintName,
			$column,
			$refTable,
			$refColumn,
			$onDelete,
			$onUpdate
		);
		$this->execute(
			'DO $do$ BEGIN '
			. $sql
			. '; EXCEPTION WHEN duplicate_object THEN NULL; WHEN undefined_table THEN NULL; END $do$'
		);
	}
}
