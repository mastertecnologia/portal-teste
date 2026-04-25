<?php
use Migrations\AbstractMigration;

/**
 * Mensagens de chat por ticket (persistência PostgreSQL). FKs INTEGER a tickets/users.
 */
class TicketMessagesTable extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('ticket_messages')) {
			return;
		}
		$this->execute(<<<'SQL'
CREATE TABLE ticket_messages (
	id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
	idempresa INTEGER NOT NULL,
	ticket_id INTEGER NOT NULL,
	user_id INTEGER NULL,
	message TEXT NOT NULL,
	type VARCHAR(20) NOT NULL DEFAULT 'text',
	metadata JSONB NULL,
	created TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT chk_ticket_messages_type CHECK (type IN ('text', 'file', 'image', 'system'))
);
CREATE INDEX ix_ticket_messages_ticket ON ticket_messages (ticket_id);
CREATE INDEX ix_ticket_messages_ticket_created ON ticket_messages (ticket_id, created);
CREATE INDEX ix_ticket_messages_idempresa ON ticket_messages (idempresa);
SQL
		);
		if ($this->hasTable('tickets')) {
			try {
				$this->execute('ALTER TABLE ticket_messages ADD CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('users')) {
			try {
				$this->execute('ALTER TABLE ticket_messages ADD CONSTRAINT fk_ticket_messages_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if ($this->hasTable('ticket_messages')) {
			$this->execute('DROP TABLE IF EXISTS ticket_messages CASCADE');
		}
	}
}
