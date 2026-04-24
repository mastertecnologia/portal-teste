<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

class TicketTimelineWriter {

	/**
	 * Comentário duplicado na timeline (dual-write; falha silenciosa).
	 */
	public static function syncComment(
		int $idticket,
		int $idempresa,
		?int $iduser,
		string $comentario,
		?int $ticketComentarioId = null
	): void {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			if (!in_array('ticket_events', $c->listTables(), true)) {
				return;
			}
			$meta = $ticketComentarioId ? ['ticket_comentario_id' => $ticketComentarioId] : null;
			$te = TableRegistry::get('TicketEvents');
			$te->save($te->newEntity([
				'idempresa' => $idempresa,
				'ticket_id' => $idticket,
				'user_id' => $iduser,
				'type' => 'comment',
				'description' => $comentario,
				'metadata' => $meta,
				'created' => Time::now(),
			], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
		} catch (\Throwable $e) {
		}
	}
}
