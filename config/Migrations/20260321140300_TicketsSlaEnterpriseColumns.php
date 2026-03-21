<?php
/**
 * Campos enterprise em tickets: tipo, prioridade, impacto/urgência, SLA, prazos, auditoria resumida.
 * Tudo nullable/default — compatível com dados legados (situacao, assunto, solicitacao permanecem canônicos).
 */
use Migrations\AbstractMigration;

class TicketsSlaEnterpriseColumns extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->_upPgsql();
		} else {
			$this->_upGeneric();
		}
		if ($this->hasTable('sla_policies')) {
			$tt = $this->table('tickets');
			if ($tt->hasColumn('sla_policy_id')) {
				try {
					$this->table('tickets')
						->addForeignKey('sla_policy_id', 'sla_policies', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
						->update();
				} catch (\Throwable $e) {
				}
			}
		}
	}

	protected function _upPgsql(): void {
		$stmts = [
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS tipo_ticket VARCHAR(32) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS categoria VARCHAR(128) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS subcategoria VARCHAR(128) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS prioridade VARCHAR(8) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS impacto VARCHAR(16) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS urgencia VARCHAR(16) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS origem_ticket VARCHAR(32) NULL DEFAULT \'portal\'',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_policy_id INTEGER NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_resposta_minutos INTEGER NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_resolucao_minutos INTEGER NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_resposta_pausado BOOLEAN NOT NULL DEFAULT false',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_resolucao_pausado BOOLEAN NOT NULL DEFAULT false',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_percentual_consumido NUMERIC(6,2) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS sla_status VARCHAR(16) NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS data_primeira_resposta TIMESTAMPTZ NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS data_resolucao TIMESTAMPTZ NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS data_fechamento TIMESTAMPTZ NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS data_limite_resposta TIMESTAMPTZ NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS data_limite_resolucao TIMESTAMPTZ NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS tempo_total_atendimento INTEGER NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS tempo_total_pausado INTEGER NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS causa_raiz TEXT NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS resolucao TEXT NULL',
			'ALTER TABLE tickets ADD COLUMN IF NOT EXISTS fechado_automaticamente BOOLEAN NOT NULL DEFAULT false',
		];
		foreach ($stmts as $sql) {
			$this->execute($sql);
		}
		$this->execute('CREATE INDEX IF NOT EXISTS ix_tickets_prioridade ON tickets (prioridade)');
		$this->execute('CREATE INDEX IF NOT EXISTS ix_tickets_tipo_ticket ON tickets (tipo_ticket)');
		$this->execute('CREATE INDEX IF NOT EXISTS ix_tickets_sla_status ON tickets (sla_status)');
		$this->execute('CREATE INDEX IF NOT EXISTS ix_tickets_data_limite_resposta ON tickets (data_limite_resposta)');
		$this->execute('CREATE INDEX IF NOT EXISTS ix_tickets_data_limite_resolucao ON tickets (data_limite_resolucao)');
	}

	protected function _upGeneric(): void {
		$cols = [
			'tipo_ticket' => ['type' => 'string', 'limit' => 32, 'null' => true],
			'categoria' => ['type' => 'string', 'limit' => 128, 'null' => true],
			'subcategoria' => ['type' => 'string', 'limit' => 128, 'null' => true],
			'prioridade' => ['type' => 'string', 'limit' => 8, 'null' => true],
			'impacto' => ['type' => 'string', 'limit' => 16, 'null' => true],
			'urgencia' => ['type' => 'string', 'limit' => 16, 'null' => true],
			'origem_ticket' => ['type' => 'string', 'limit' => 32, 'null' => true, 'default' => 'portal'],
			'sla_policy_id' => ['type' => 'integer', 'null' => true],
			'sla_resposta_minutos' => ['type' => 'integer', 'null' => true],
			'sla_resolucao_minutos' => ['type' => 'integer', 'null' => true],
			'sla_resposta_pausado' => ['type' => 'boolean', 'null' => false, 'default' => false],
			'sla_resolucao_pausado' => ['type' => 'boolean', 'null' => false, 'default' => false],
			'sla_percentual_consumido' => ['type' => 'decimal', 'precision' => 6, 'scale' => 2, 'null' => true],
			'sla_status' => ['type' => 'string', 'limit' => 16, 'null' => true],
			'data_primeira_resposta' => ['type' => 'timestamp', 'null' => true],
			'data_resolucao' => ['type' => 'timestamp', 'null' => true],
			'data_fechamento' => ['type' => 'timestamp', 'null' => true],
			'data_limite_resposta' => ['type' => 'timestamp', 'null' => true],
			'data_limite_resolucao' => ['type' => 'timestamp', 'null' => true],
			'tempo_total_atendimento' => ['type' => 'integer', 'null' => true],
			'tempo_total_pausado' => ['type' => 'integer', 'null' => true],
			'causa_raiz' => ['type' => 'text', 'null' => true],
			'resolucao' => ['type' => 'text', 'null' => true],
			'fechado_automaticamente' => ['type' => 'boolean', 'null' => false, 'default' => false],
		];
		$tt = $this->table('tickets');
		foreach ($cols as $name => $opts) {
			if ($tt->hasColumn($name)) {
				continue;
			}
			$type = $opts['type'];
			$rest = $opts;
			unset($rest['type']);
			$this->table('tickets')->addColumn($name, $type, $rest)->update();
			$tt = $this->table('tickets');
		}
	}

	public function down() {
	}
}
