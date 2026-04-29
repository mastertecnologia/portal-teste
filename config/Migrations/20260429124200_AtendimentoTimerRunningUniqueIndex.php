<?php
use Migrations\AbstractMigration;

/**
 * Garante unicidade de timer aberto por técnico/ticket.
 */
class AtendimentoTimerRunningUniqueIndex extends AbstractMigration {

	protected function resolveUserColumn(): ?string {
		$conn = $this->getAdapter()->getConnection();
		$sql = "SELECT column_name FROM information_schema.columns WHERE table_name = 'atendimento_timer'";
		$rows = $conn->query($sql)->fetchAll(\PDO::FETCH_COLUMN) ?: [];
		$cols = array_map('strtolower', $rows);
		if (in_array('idusuario', $cols, true)) {
			return 'idusuario';
		}
		if (in_array('iduser', $cols, true)) {
			return 'iduser';
		}

		return null;
	}

	public function up() {
		if (!$this->hasTable('atendimento_timer')) {
			return;
		}
		$userCol = $this->resolveUserColumn();
		if ($userCol === null) {
			return;
		}

		$this->execute(
			sprintf(
				'CREATE UNIQUE INDEX IF NOT EXISTS ux_atendimento_timer_open_ticket_user ON atendimento_timer (idticket, %s) WHERE hora_fim IS NULL',
				$userCol
			)
		);
	}

	public function down() {
		$this->execute('DROP INDEX IF EXISTS ux_atendimento_timer_open_ticket_user');
	}
}

