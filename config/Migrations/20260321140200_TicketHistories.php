<?php
/**
 * Histórico tipado de eventos do ticket (auditoria enterprise; complementa ticketsmovs).
 */
use Migrations\AbstractMigration;

class TicketHistories extends AbstractMigration {

	public function up() {
		if (!$this->hasTable('tickets')) {
			return;
		}
		if ($this->hasTable('ticket_histories')) {
			return;
		}
		$t = $this->table('ticket_histories');
		$t->addColumn('ticket_id', 'integer', ['null' => false])
			->addColumn('usuario_id', 'integer', ['null' => true, 'default' => null])
			->addColumn('tipo_evento', 'string', ['limit' => 48, 'null' => false])
			->addColumn('valor_anterior', 'text', ['null' => true])
			->addColumn('valor_novo', 'text', ['null' => true])
			->addColumn('descricao', 'text', ['null' => true])
			->addColumn('origem_evento', 'string', ['limit' => 32, 'null' => false, 'default' => 'usuario']);
		$t->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
		$t->addIndex(['ticket_id'], ['name' => 'ix_ticket_histories_ticket_id']);
		$t->addIndex(['tipo_evento'], ['name' => 'ix_ticket_histories_tipo_evento']);
		$t->addIndex(['created'], ['name' => 'ix_ticket_histories_created']);
		$t->create();
		try {
			$t = $this->table('ticket_histories');
			$t->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
		} catch (\Throwable $e) {
		}
		if ($this->hasTable('users')) {
			try {
				$t = $this->table('ticket_histories');
				$t->addForeignKey('usuario_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
	}

	public function down() {
		if ($this->hasTable('ticket_histories')) {
			$this->table('ticket_histories')->drop()->save();
		}
	}
}
