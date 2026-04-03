<?php
/**
 * Agenda: lembrete automático, tipo e título do compromisso.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class VisitasAgendaLembretes extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('visitas')) {
			return;
		}
		$t = $this->table('visitas');
		if (!$t->hasColumn('lembrete_minutos')) {
			$t->addColumn('lembrete_minutos', 'integer', ['null' => true, 'default' => null])->update();
		}
		if (!$t->hasColumn('lembrete_notificado_em')) {
			$t->addColumn('lembrete_notificado_em', 'timestamp', ['null' => true, 'default' => null])->update();
		}
		if (!$t->hasColumn('agenda_tipo')) {
			$t->addColumn('agenda_tipo', 'integer', ['null' => false, 'default' => 0])->update();
		}
		if (!$t->hasColumn('agenda_titulo')) {
			$t->addColumn('agenda_titulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])->update();
		}
	}

	public function down() {
	}
}
