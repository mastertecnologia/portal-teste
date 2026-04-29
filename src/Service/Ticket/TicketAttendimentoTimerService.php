<?php
namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;

/**
 * Timer de atendimento no próprio ticket (started_at / total_seconds / paused_at / finished_at).
 * Regras alinhadas ao status canônico tickets.situacao (TicketConstants).
 */
class TicketAttendimentoTimerService {

	/** @var array<string,bool>|null */
	protected static $hasColsCache = [];

	public static function columnsReady(Table $ticketsTable): bool {
		$key = spl_object_hash($ticketsTable);
		if (!array_key_exists($key, self::$hasColsCache)) {
			try {
				$c = $ticketsTable->getSchema()->columns();
				self::$hasColsCache[$key] = in_array('started_at', $c, true)
					&& in_array('total_seconds', $c, true);
			} catch (\Throwable $e) {
				self::$hasColsCache[$key] = false;
			}
		}

		return self::$hasColsCache[$key];
	}

	/**
	 * Ajusta campos do timer quando situacao muda (entidade ainda com situacao antiga em memória
	 * — passar $newSituacao explicitamente).
	 */
	public static function applyOnSituacaoChange(
		Table $ticketsTable,
		EntityInterface $ticket,
		int $oldSituacao,
		int $newSituacao
	): void {
		if (!self::columnsReady($ticketsTable)) {
			return;
		}
		$now = Time::now();
		$pend = (int)constant('C_TicketSituacaoPendente');
		$exec = (int)constant('C_TicketSituacaoEmandamento');
		$res = (int)constant('C_TicketSituacaoResolvido');
		$fec = (int)constant('C_TicketSituacaoFechado');

		$started = self::_readTime($ticket, 'started_at');
		$total = (int)($ticket->get('total_seconds') ?? 0);

		if ($newSituacao === $exec) {
			if ($started !== null) {
				$total += max(0, $now->getTimestamp() - $started->getTimestamp());
			}
			$ticket->set('total_seconds', $total);
			$ticket->set('started_at', clone $now);
			$ticket->set('paused_at', null);
			if (in_array('finished_at', $ticketsTable->getSchema()->columns(), true)) {
				$ticket->set('finished_at', null);
			}

			return;
		}

		if ($newSituacao === $pend) {
			if ($started !== null) {
				$total += max(0, $now->getTimestamp() - $started->getTimestamp());
			}
			$ticket->set('total_seconds', $total);
			$ticket->set('started_at', null);
			$ticket->set('paused_at', clone $now);

			return;
		}

		if ($newSituacao === $res || $newSituacao === $fec) {
			if ($started !== null) {
				$total += max(0, $now->getTimestamp() - $started->getTimestamp());
			}
			$ticket->set('total_seconds', $total);
			$ticket->set('started_at', null);
			$ticket->set('paused_at', null);
			if (in_array('finished_at', $ticketsTable->getSchema()->columns(), true)) {
				$ticket->set('finished_at', clone $now);
			}
		}
	}

	/**
	 * Segundos decorridos para exibição (total_seconds + segmento aberto em execução).
	 */
	public static function elapsedSecondsForDisplay(Table $ticketsTable, $ticket, int $serverNowUnix): int {
		if (!self::columnsReady($ticketsTable)) {
			return 0;
		}
		$exec = (int)constant('C_TicketSituacaoEmandamento');
		$sit = (int)($ticket->get('situacao') ?? 0);
		$total = (int)($ticket->get('total_seconds') ?? 0);
		if ($sit !== $exec) {
			return max(0, $total);
		}
		$started = self::_readTime($ticket, 'started_at');
		if ($started === null) {
			return max(0, $total);
		}

		return max(0, $total + max(0, $serverNowUnix - $started->getTimestamp()));
	}

	/**
	 * @return \Cake\I18n\Time|\Cake\I18n\FrozenTime|null
	 */
	protected static function _readTime(EntityInterface $ticket, string $field) {
		$v = $ticket->get($field);
		if ($v === null || $v === '') {
			return null;
		}
		if ($v instanceof Time) {
			return $v;
		}
		if ($v instanceof \DateTimeInterface) {
			return new Time($v->format('Y-m-d H:i:s'));
		}
		if (is_string($v) && $v !== '') {
			try {
				return new Time($v);
			} catch (\Throwable $e) {
				return null;
			}
		}

		return null;
	}
}
