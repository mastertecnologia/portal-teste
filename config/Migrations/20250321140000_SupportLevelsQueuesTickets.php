<?php
/**
 * Níveis de suporte (support_levels), vínculo em queues / queues_users / users / tickets.
 * idempresa = empresa da fila (company_id no domínio).
 */
use Migrations\AbstractMigration;

class SupportLevelsQueuesTickets extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('support_levels')) {
			$this->table('support_levels')
				->addColumn('name', 'string', ['limit' => 64, 'null' => false])
				->addColumn('description', 'text', ['null' => true])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps()
				->addIndex(['sort_order'], ['name' => 'ix_support_levels_sort'])
				->create();
		} else {
			$this->_ensureSupportLevelsColumnsMatchPhinx();
		}

		$this->_seedSupportLevelsSql();

		if ($this->_isPgsql()) {
			$this->execute('ALTER TABLE queues ADD COLUMN IF NOT EXISTS support_level_id INTEGER NULL');
			$this->execute('ALTER TABLE queues ADD COLUMN IF NOT EXISTS description TEXT NULL');
			$this->execute('ALTER TABLE queues_users ADD COLUMN IF NOT EXISTS support_level_id INTEGER NULL');
			$this->execute('ALTER TABLE users ADD COLUMN IF NOT EXISTS support_level_id INTEGER NULL');
			$this->execute('ALTER TABLE tickets ADD COLUMN IF NOT EXISTS support_level_id INTEGER NULL');
			$this->_pgsqlTryFk('queues', 'support_level_id', 'support_levels', 'id');
			$this->_pgsqlTryFk('queues_users', 'support_level_id', 'support_levels', 'id');
			$this->_pgsqlTryFk('users', 'support_level_id', 'support_levels', 'id');
			$this->_pgsqlTryFk('tickets', 'support_level_id', 'support_levels', 'id');
			$this->_backfillQueuesLevelsPg();
			$this->_backfillTicketsSupportLevelPg();
		} else {
			if ($this->hasTable('queues')) {
				$qt = $this->table('queues');
				if (!$qt->hasColumn('support_level_id')) {
					$this->table('queues')->addColumn('support_level_id', 'integer', ['null' => true])->update();
				}
				if (!$qt->hasColumn('description')) {
					$this->table('queues')->addColumn('description', 'text', ['null' => true])->update();
				}
			}
			if ($this->hasTable('queues_users')) {
				$qu = $this->table('queues_users');
				if (!$qu->hasColumn('support_level_id')) {
					$this->table('queues_users')->addColumn('support_level_id', 'integer', ['null' => true])->update();
				}
			}
			if ($this->hasTable('users')) {
				$ut = $this->table('users');
				if (!$ut->hasColumn('support_level_id')) {
					$this->table('users')->addColumn('support_level_id', 'integer', ['null' => true])->update();
				}
			}
			if ($this->hasTable('tickets')) {
				$tt = $this->table('tickets');
				if (!$tt->hasColumn('support_level_id')) {
					$this->table('tickets')->addColumn('support_level_id', 'integer', ['null' => true])->update();
				}
			}
		}
	}

	/** Phinx/Cake em alguns ambientes não retornam getAdapterType() === 'pgsql'; PDO é canônico. */
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

	/**
	 * Tabelas criadas manualmente ou por script antigo podem existir sem created/modified;
	 * o seed não deve depender dessas colunas no PostgreSQL.
	 */
	protected function _ensureSupportLevelsColumnsMatchPhinx(): void {
		if (!$this->hasTable('support_levels')) {
			return;
		}
		$t = $this->table('support_levels');
		if ($this->_isPgsql()) {
			$this->execute('ALTER TABLE support_levels ADD COLUMN IF NOT EXISTS description TEXT NULL');
			$this->execute('ALTER TABLE support_levels ADD COLUMN IF NOT EXISTS sort_order INTEGER NOT NULL DEFAULT 0');
			$this->execute('ALTER TABLE support_levels ADD COLUMN IF NOT EXISTS created TIMESTAMP NULL');
			$this->execute('ALTER TABLE support_levels ADD COLUMN IF NOT EXISTS modified TIMESTAMP NULL');
			return;
		}
		if (!$t->hasColumn('description')) {
			$t->addColumn('description', 'text', ['null' => true])->update();
			$t = $this->table('support_levels');
		}
		if (!$t->hasColumn('sort_order')) {
			$t->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])->update();
			$t = $this->table('support_levels');
		}
		if (!$t->hasColumn('created')) {
			$t->addColumn('created', 'timestamp', ['null' => true])->update();
			$t = $this->table('support_levels');
		}
		if (!$t->hasColumn('modified')) {
			$t->addColumn('modified', 'timestamp', ['null' => true])->update();
		}
	}

	protected function _seedSupportLevelsSql(): void {
		if (!$this->hasTable('support_levels')) {
			return;
		}
		$this->_ensureSupportLevelsColumnsMatchPhinx();

		$isPg = $this->_isPgsql();
		$nowF = $isPg ? 'NOW()' : 'CURRENT_TIMESTAMP';
		$rows = [
			['N1', 'Suporte inicial / triagem', 1],
			['N2', 'Suporte avançado / field service', 2],
			['N3', 'Infraestrutura / especializado', 3],
			['NOC', 'Monitoramento', 4],
			['Serviço', 'Requisições de serviço', 5],
		];
		foreach ($rows as $r) {
			$n = str_replace("'", "''", $r[0]);
			$d = str_replace("'", "''", $r[1]);
			$o = (int)$r[2];
			if ($isPg) {
				// Não usar created/modified no INSERT: tabelas legadas podem não tê-las ainda neste ponto.
				$this->execute(
					"INSERT INTO support_levels (name, description, sort_order) "
					. "SELECT '{$n}', '{$d}', {$o} "
					. "WHERE NOT EXISTS (SELECT 1 FROM support_levels WHERE sort_order = {$o} LIMIT 1)"
				);
			} else {
				$this->execute(
					"INSERT IGNORE INTO support_levels (name, description, sort_order, created, modified) "
					. "VALUES ('{$n}', '{$d}', {$o}, {$nowF}, {$nowF})"
				);
			}
		}
		if ($isPg) {
			try {
				$this->execute(
					'UPDATE support_levels SET created = COALESCE(created, NOW()), modified = COALESCE(modified, NOW()) '
					. 'WHERE created IS NULL OR modified IS NULL'
				);
			} catch (\Throwable $e) {
				// Colunas podem não existir em schema legado extremo; INSERT já garantiu as linhas.
			}
		}
	}

	protected function _pgsqlTryFk(string $tbl, string $col, string $refTbl, string $refCol): void {
		$cname = $tbl . '_' . $col . '_fkey';
		$sql = 'ALTER TABLE ' . $tbl . ' ADD CONSTRAINT ' . $cname . ' '
			. 'FOREIGN KEY (' . $col . ') REFERENCES ' . $refTbl . '(' . $refCol . ') ON DELETE SET NULL ON UPDATE CASCADE';
		try {
			$this->execute($sql);
		} catch (\Throwable $e) {
		}
	}

	protected function _backfillQueuesLevelsPg(): void {
		$map = ['n1' => 1, 'n2' => 2, 'n3' => 3, 'noc' => 4, 'servico' => 5];
		foreach ($map as $cod => $ord) {
			$codEsc = str_replace("'", "''", $cod);
			$this->execute(
				'UPDATE queues q SET support_level_id = sl.id FROM support_levels sl '
				. 'WHERE sl.sort_order = ' . (int)$ord . " AND q.codigo = '{$codEsc}' AND q.support_level_id IS NULL"
			);
		}
		$this->execute(
			'UPDATE queues q SET support_level_id = sl.id FROM support_levels sl '
			. 'WHERE sl.sort_order = 1 AND q.support_level_id IS NULL'
		);
	}

	protected function _backfillTicketsSupportLevelPg(): void {
		try {
			$this->execute(
				'UPDATE tickets t SET support_level_id = q.support_level_id FROM queues q '
				. 'WHERE t.queue_id = q.id AND t.support_level_id IS NULL AND q.support_level_id IS NOT NULL'
			);
		} catch (\Throwable $e) {
			// tickets.queue_id ou colunas podem não existir em legado
		}
		// nivel_atendimento pode ser texto não numérico (ex. "N1"); cast direto aborta a transação no PostgreSQL.
		try {
			$this->execute(
				'UPDATE tickets t SET support_level_id = sl.id FROM support_levels sl '
				. 'WHERE t.support_level_id IS NULL AND sl.sort_order = ( '
				. 'CASE '
				. 'WHEN t.nivel_atendimento IS NULL THEN 1 '
				. "WHEN trim(t.nivel_atendimento::text) ~ '^[0-9]+$' THEN trim(t.nivel_atendimento::text)::integer "
				. 'ELSE 1 END )'
			);
		} catch (\Throwable $e) {
			try {
				$this->execute(
					'UPDATE tickets t SET support_level_id = sl.id FROM support_levels sl '
					. 'WHERE t.support_level_id IS NULL AND sl.sort_order = 1'
				);
			} catch (\Throwable $e2) {
			}
		}
	}

	public function down() {
	}
}
