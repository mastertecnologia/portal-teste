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
		}

		$this->_seedSupportLevelsSql();

		if ($this->getAdapter()->getAdapterType() === 'pgsql') {
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

	protected function _seedSupportLevelsSql(): void {
		if (!$this->hasTable('support_levels')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		$nowF = $adapter === 'pgsql' ? 'NOW()' : 'CURRENT_TIMESTAMP';
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
			if ($adapter === 'pgsql') {
				$this->execute(
					"INSERT INTO support_levels (name, description, sort_order, created, modified) "
					. "SELECT '{$n}', '{$d}', {$o}, {$nowF}, {$nowF} "
					. "WHERE NOT EXISTS (SELECT 1 FROM support_levels WHERE sort_order = {$o} LIMIT 1)"
				);
			} else {
				$this->execute(
					"INSERT IGNORE INTO support_levels (name, description, sort_order, created, modified) "
					. "VALUES ('{$n}', '{$d}', {$o}, {$nowF}, {$nowF})"
				);
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
		$this->execute(
			'UPDATE tickets t SET support_level_id = q.support_level_id FROM queues q '
			. 'WHERE t.queue_id = q.id AND t.support_level_id IS NULL AND q.support_level_id IS NOT NULL'
		);
		$this->execute(
			'UPDATE tickets t SET support_level_id = sl.id FROM support_levels sl '
			. 'WHERE t.support_level_id IS NULL AND sl.sort_order = COALESCE(t.nivel_atendimento::integer, 1)'
		);
	}

	public function down() {
	}
}
