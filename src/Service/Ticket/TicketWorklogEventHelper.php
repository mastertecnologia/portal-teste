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

	/**
	 * Enriquece o último evento worklog do ticket com saldo/percentagem após débito no contrato.
	 */
	public static function attachContractSnapshotToLatestWorklog(int $idticket, int $idempresa, int $idcliente): void {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			if (!in_array('ticket_events', $c->listTables(), true)) {
				return;
			}
			$snap = ServiceDeskContractHoursService::getSnapshot(
				ServiceDeskContractHoursService::findContractForClient($idcliente, $idempresa)
			);
			$te = TableRegistry::get('TicketEvents');
			$row = $te->find()
				->where(['ticket_id' => $idticket, 'type' => 'worklog', 'idempresa' => $idempresa])
				->orderDesc('id')
				->first();
			if (!$row) {
				return;
			}
			$m = $row->get('metadata');
			if (is_string($m)) {
				$m = json_decode($m, true) ?: [];
			}
			if (!is_array($m)) {
				$m = [];
			}
			$m['balance_hours'] = $snap['balanceHours'];
			$m['percent_used'] = $snap['percentUsed'];
			$m['contract_snapshot'] = $snap;
			$row->set('metadata', $m);
			$te->save($row, ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
		} catch (\Throwable $e) {
		}
	}
}
