<?php
use Migrations\AbstractMigration;

/**
 * Garante coluna idticket em ordensservico (vínculo ticket → OS) quando ainda não existir.
 */
class OrdensservicoIdticketIfMissing extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('ordensservico')) {
			return;
		}
		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->execute('ALTER TABLE ordensservico ADD COLUMN IF NOT EXISTS idticket INTEGER NULL');
			$this->execute('CREATE INDEX IF NOT EXISTS ix_ordensservico_idempresa_idticket ON ordensservico (idempresa, idticket) WHERE idticket IS NOT NULL');
		}
	}

	public function down() {
		// Não remove coluna: pode já existir antes desta migration em bases legadas.
	}
}
