<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Campos opcionais em ticket_sla_cycles para espelhar o prazo de resolução e somar pausa (retomada SLA).
 */
class TicketSlaCyclesDeadlineAtPausedTotals extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return;
		}
		$t = $this->table('ticket_sla_cycles');
		$changed = false;
		if (!$t->hasColumn('deadline_at')) {
			$t->addColumn('deadline_at', 'timestamp', [
				'null' => true,
				'default' => null,
				'comment' => 'Espelho de data_limite_resolucao do ciclo (SLA resolução)',
			]);
			$changed = true;
		}
		if (!$t->hasColumn('total_paused_seconds')) {
			$t->addColumn('total_paused_seconds', 'integer', [
				'null' => false,
				'default' => 0,
				'comment' => 'Segundos acumulados em pausa manual deste ciclo',
			]);
			$changed = true;
		}
		if ($changed) {
			$t->update();
		}
	}

	public function down() {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return;
		}
		$t = $this->table('ticket_sla_cycles');
		if ($t->hasColumn('total_paused_seconds')) {
			$t->removeColumn('total_paused_seconds')->update();
		}
		if ($t->hasColumn('deadline_at')) {
			$t->removeColumn('deadline_at')->update();
		}
	}
}
