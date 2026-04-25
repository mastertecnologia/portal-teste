<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Regras: percentual de uso >= 80% → warning, >= 100% → danger, > 100% (excedente) → critical.
 * Persiste em ticket_events (type=alert) sem substituir ticket_histories.
 */
class ServiceDeskAlertService {

	/**
	 * Após actualização do contrato (ex.: débito do timer), avalia limites e grava eventos de alerta.
	 *
	 * @param int $idempresa
	 * @param int $idticket
	 * @param int $iduser
	 */
	public static function afterContractDebit(
		$idempresa,
		$idticket,
		$iduser,
		$idcliente,
		$idempresaUser
	): void {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			if (!in_array('ticket_events', $c->listTables(), true)) {
				return;
			}
			$contrato = ServiceDeskContractHoursService::findContractForClient(
				(int)$idcliente,
				(int)$idempresaUser
			);
			$snap = ServiceDeskContractHoursService::getSnapshot($contrato);
			$pct = $snap['percentUsed'];
			if ($pct === null) {
				return;
			}
			$level = null;
			$msg = '';
			if ($pct > 100.0 + 0.0001) {
				$level = 'critical';
				$msg = 'Contrato com horas excedidas (acima de 100% do total contratado).';
			} elseif ($pct >= 100.0 - 0.0001) {
				$level = 'danger';
				$msg = 'Contrato a 100% das horas contratadas.';
			} elseif ($pct >= 80.0 - 0.0001) {
				$level = 'warning';
				$msg = 'Atenção: utilização do contrato em 80% ou mais.';
			}
			if ($level === null) {
				return;
			}
			$meta = [
				'level' => $level,
				'message' => $msg,
				'percent_used' => round($pct, 2),
				'snapshot' => $snap,
			];
			$te = TableRegistry::get('TicketEvents');
			$te->save(
				$te->newEntity(
					[
						'idempresa' => (int)$idempresa,
						'ticket_id' => (int)$idticket,
						'user_id' => (int)$iduser,
						'type' => 'alert',
						'description' => $msg,
						'metadata' => $meta,
						'created' => Time::now(),
					],
					['validate' => false]
				),
				['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]
			);
		} catch (\Throwable $e) {
		}
	}
}
