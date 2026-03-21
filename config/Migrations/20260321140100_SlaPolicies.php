<?php
/**
 * Políticas de SLA por empresa e prioridade (P1–P4). Seed idempotente.
 */
use Migrations\AbstractMigration;

class SlaPolicies extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('empresas')) {
			return;
		}

		if (!$this->hasTable('sla_policies')) {
			$t = $this->table('sla_policies');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('nome', 'string', ['limit' => 128, 'null' => false])
				->addColumn('prioridade', 'string', ['limit' => 8, 'null' => false])
				->addColumn('tipo_ticket', 'string', ['limit' => 32, 'null' => true, 'default' => null])
				->addColumn('resposta_minutos', 'integer', ['null' => false])
				->addColumn('resolucao_minutos', 'integer', ['null' => false])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addTimestamps();
			$t->addIndex(['idempresa'], ['name' => 'ix_sla_policies_idempresa']);
			$t->addIndex(['prioridade'], ['name' => 'ix_sla_policies_prioridade']);
			$t->create();
			if ($this->hasTable('empresas')) {
				try {
					$t = $this->table('sla_policies');
					$t->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
				} catch (\Throwable $e) {
				}
			}
		} else {
			$this->_ensureSlaPoliciesLegacyTimestamps();
		}

		if ($this->_isPgsql()) {
			$this->execute(
				'CREATE UNIQUE INDEX IF NOT EXISTS ux_sla_policies_emp_pri_default '
				. 'ON sla_policies (idempresa, prioridade) WHERE tipo_ticket IS NULL'
			);
		}
		$this->_seedPolicies();
	}

	protected function _isPgsql(): bool {
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

	/** Tabela legada sem created/modified (como support_levels em alguns ambientes). */
	protected function _ensureSlaPoliciesLegacyTimestamps(): void {
		if (!$this->hasTable('sla_policies')) {
			return;
		}
		if ($this->_isPgsql()) {
			$this->execute('ALTER TABLE sla_policies ADD COLUMN IF NOT EXISTS created TIMESTAMP NULL');
			$this->execute('ALTER TABLE sla_policies ADD COLUMN IF NOT EXISTS modified TIMESTAMP NULL');
			return;
		}
		$tbl = $this->table('sla_policies');
		if (!$tbl->hasColumn('created')) {
			$this->table('sla_policies')->addColumn('created', 'timestamp', ['null' => true])->update();
			$tbl = $this->table('sla_policies');
		}
		if (!$tbl->hasColumn('modified')) {
			$this->table('sla_policies')->addColumn('modified', 'timestamp', ['null' => true])->update();
		}
	}

	protected function _seedPolicies(): void {
		if (!$this->hasTable('sla_policies') || !$this->hasTable('empresas')) {
			return;
		}
		$this->_ensureSlaPoliciesLegacyTimestamps();

		$defs = [
			['Padrão P1', 'P1', 15, 240],
			['Padrão P2', 'P2', 30, 480],
			['Padrão P3', 'P3', 60, 1440],
			['Padrão P4', 'P4', 240, 4320],
		];
		$isPg = $this->_isPgsql();
		foreach ($defs as $d) {
			$n = str_replace("'", "''", $d[0]);
			if ($isPg) {
				$this->execute(
					"INSERT INTO sla_policies (idempresa, nome, prioridade, tipo_ticket, resposta_minutos, resolucao_minutos, ativo) "
					. "SELECT e.id, '{$n}', '{$d[1]}', NULL, {$d[2]}, {$d[3]}, true FROM empresas e "
					. "WHERE NOT EXISTS (SELECT 1 FROM sla_policies s WHERE s.idempresa = e.id AND s.prioridade = '{$d[1]}' AND s.tipo_ticket IS NULL)"
				);
			} else {
				$this->execute(
					"INSERT INTO sla_policies (idempresa, nome, prioridade, tipo_ticket, resposta_minutos, resolucao_minutos, ativo, created, modified) "
					. "SELECT e.id, '{$n}', '{$d[1]}', NULL, {$d[2]}, {$d[3]}, true, NOW(), NOW() FROM empresas e "
					. "WHERE NOT EXISTS (SELECT 1 FROM sla_policies s WHERE s.idempresa = e.id AND s.prioridade = '{$d[1]}' AND s.tipo_ticket IS NULL)"
				);
			}
		}
		if ($isPg) {
			try {
				$this->execute(
					'UPDATE sla_policies SET created = COALESCE(created, NOW()), modified = COALESCE(modified, NOW()) '
					. 'WHERE created IS NULL OR modified IS NULL'
				);
			} catch (\Throwable $e) {
			}
		}
	}

	public function down() {
		if ($this->hasTable('sla_policies')) {
			$this->table('sla_policies')->drop()->save();
		}
	}
}
