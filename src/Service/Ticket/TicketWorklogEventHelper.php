<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

class TicketWorklogEventHelper {

	/**
	 * Cria registo em ticket_events (worklog) após gravação em ticketshoras (evita duplicar com hook global).
	 */
	public static function afterHoraLancada($ticketshorasEntity, $idempresa, int $idticket, ?int $iduser, $horaini, $horafin): void {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			if (!in_array('ticket_events', $c->listTables(), true)) {
				return;
			}
			$th = TableRegistry::get('Ticketshoras');
			$rawSec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($th, $ticketshorasEntity);
			$billSec = TicketServiceDeskApiService::billingSecondsFromRaw($rawSec);
			$at = (is_object($horafin) && method_exists($horafin, 'format')) ? $horafin : Time::now();
			$te = TableRegistry::get('TicketEvents');
			$te->save($te->newEntity([
				'idempresa' => (int)$idempresa,
				'ticket_id' => $idticket,
				'user_id' => $iduser,
				'type' => 'worklog',
				'description' => 'Horas técnicas registradas',
				'seconds_spent' => $billSec,
				'metadata' => ['ticketshoras_id' => (int)($ticketshorasEntity->id ?? 0)],
				'created' => $at,
			], ['validate' => false]), ['checkRules' => false, 'validate' => false]);
		} catch (\Throwable $e) {
		}
	}
}
