<?php
use Migrations\AbstractMigration;

/**
 * CSAT pós-fechamento: respostas do cliente após ticket ser marcado como resolvido/fechado.
 * Cada ticket pode ter no máximo 1 resposta (unique no ticket_id).
 */
class TicketCsatResponses extends AbstractMigration {

	public function change() {
		if ($this->hasTable('ticket_csat_responses')) {
			return;
		}
		$table = $this->table('ticket_csat_responses');
		$table
			->addColumn('idempresa', 'integer', ['null' => false])
			->addColumn('ticket_id', 'integer', ['null' => false])
			->addColumn('idcliente', 'integer', ['null' => true, 'default' => null])
			->addColumn('csat_score', 'integer', ['null' => false, 'comment' => '1 a 5 estrelas'])
			->addColumn('nps_score', 'integer', ['null' => true, 'default' => null, 'comment' => '0 a 10 NPS'])
			->addColumn('comentario', 'text', ['null' => true, 'default' => null])
			->addColumn('token', 'string', ['limit' => 60, 'null' => false, 'comment' => 'token público para envio por e-mail'])
			->addColumn('responded_at', 'datetime', ['null' => false])
			->addColumn('responded_ip', 'string', ['limit' => 45, 'null' => true, 'default' => null])
			->addTimestamps('created', 'modified')
			->addIndex(['ticket_id'], ['unique' => true])
			->addIndex(['idempresa', 'responded_at'])
			->addIndex(['token'], ['unique' => true])
			->create();
	}
}
