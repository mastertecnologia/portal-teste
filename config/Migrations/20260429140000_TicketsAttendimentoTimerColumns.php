<?php
/**
 * Timer de atendimento persistido no ticket (Service Desk — edição inline).
 * started_at: início do segmento atual em "Em execução".
 * total_seconds: acumulado (segmentos fechados + pausas).
 */
use Migrations\AbstractMigration;

class TicketsAttendimentoTimerColumns extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->execute('ALTER TABLE tickets ADD COLUMN IF NOT EXISTS started_at TIMESTAMPTZ NULL');
			$this->execute('ALTER TABLE tickets ADD COLUMN IF NOT EXISTS paused_at TIMESTAMPTZ NULL');
			$this->execute('ALTER TABLE tickets ADD COLUMN IF NOT EXISTS total_seconds INTEGER NOT NULL DEFAULT 0');
			$this->execute('ALTER TABLE tickets ADD COLUMN IF NOT EXISTS finished_at TIMESTAMPTZ NULL');
		} else {
			$t = $this->table('tickets');
			if (!$t->hasColumn('started_at')) {
				$t->addColumn('started_at', 'datetime', ['null' => true])->update();
			}
			if (!$t->hasColumn('paused_at')) {
				$t->addColumn('paused_at', 'datetime', ['null' => true])->update();
			}
			if (!$t->hasColumn('total_seconds')) {
				$t->addColumn('total_seconds', 'integer', ['null' => false, 'default' => 0])->update();
			}
			if (!$t->hasColumn('finished_at')) {
				$t->addColumn('finished_at', 'datetime', ['null' => true])->update();
			}
		}
	}

	public function down() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->execute('ALTER TABLE tickets DROP COLUMN IF EXISTS started_at');
			$this->execute('ALTER TABLE tickets DROP COLUMN IF EXISTS paused_at');
			$this->execute('ALTER TABLE tickets DROP COLUMN IF EXISTS total_seconds');
			$this->execute('ALTER TABLE tickets DROP COLUMN IF EXISTS finished_at');
		} else {
			$t = $this->table('tickets');
			foreach (['finished_at', 'total_seconds', 'paused_at', 'started_at'] as $col) {
				if ($t->hasColumn($col)) {
					$t->removeColumn($col)->update();
				}
			}
		}
	}
}
