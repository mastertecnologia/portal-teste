<?php
/**
 * Grau de severidade na abertura do ticket (baixa | media | alta | urgente).
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class TicketsSeveridade extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->execute(
				"ALTER TABLE tickets ADD COLUMN IF NOT EXISTS severidade VARCHAR(16) NOT NULL DEFAULT 'media'"
			);
			return;
		}
		$tt = $this->table('tickets');
		if (!$tt->hasColumn('severidade')) {
			$this->table('tickets')
				->addColumn('severidade', 'string', [
					'limit' => 16,
					'null' => false,
					'default' => 'media',
				])
				->update();
		}
	}

	public function down() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		$adapter = $this->getAdapter()->getAdapterType();
		if ($adapter === 'pgsql') {
			$this->execute('ALTER TABLE tickets DROP COLUMN IF EXISTS severidade');
			return;
		}
		$tt = $this->table('tickets');
		if ($tt->hasColumn('severidade')) {
			$this->table('tickets')->removeColumn('severidade')->update();
		}
	}
}
