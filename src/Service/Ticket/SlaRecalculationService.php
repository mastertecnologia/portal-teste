<?php
namespace App\Service\Ticket;

use Cake\I18n\Time;
use Cake\ORM\Table;

/**
 * Recalcula sla_percentual_consumido e sla_status para tickets com colunas enterprise.
 */
class SlaRecalculationService {

	/** @var Table */
	protected $tickets;

	public function __construct(Table $ticketsTable) {
		$this->tickets = $ticketsTable;
	}

	/**
	 * @param int|null $idempresa Filtrar empresa ou null para todas
	 * @return array{updated:int, skipped:int, errors:int}
	 */
	public function recalculateAll(?int $idempresa = null): array {
		$cols = $this->tickets->getSchema()->columns();
		if (!in_array('sla_status', $cols, true) || !in_array('data_limite_resolucao', $cols, true)) {
			return ['updated' => 0, 'skipped' => 0, 'errors' => 0];
		}

		$q = $this->tickets->find()
			->where([
				'sla_resolucao_minutos IS NOT' => null,
				'sla_resolucao_minutos >' => 0,
				'data_limite_resolucao IS NOT' => null,
			]);
		if ($idempresa !== null && $idempresa > 0) {
			$q->where(['idempresa' => $idempresa]);
		}

		$updated = 0;
		$skipped = 0;
		$errors = 0;
		foreach ($q->all() as $ticket) {
			try {
				if ($this->recalculateOne($ticket, $cols)) {
					$updated++;
				} else {
					$skipped++;
				}
			} catch (\Throwable $e) {
				$errors++;
			}
		}

		return ['updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
	}

	/**
	 * Avalia violação e percentual sem persistir.
	 *
	 * @param \Cake\Datasource\EntityInterface $ticket
	 * @param string[]|null $cols
	 * @return array{violado: bool, violado_for_cycle: bool, pct: float, status: string}
	 */
	public function evaluateSlaState($ticket, ?array $cols = null): array {
		$cols = $cols ?? $this->tickets->getSchema()->columns();
		$default = ['violado' => false, 'violado_for_cycle' => false, 'pct' => 0.0, 'status' => 'dentro_sla'];
		if (!in_array('sla_status', $cols, true)) {
			return $default;
		}

		if (!empty($ticket->get('sla_resolucao_pausado'))) {
			return $default;
		}

		$now = Time::now();
		$created = $this->toTime($ticket->get('created'));
		if ($created === null) {
			return $default;
		}

		$resM = (int)($ticket->get('sla_resolucao_minutos') ?? 0);
		$respM = (int)($ticket->get('sla_resposta_minutos') ?? 0);
		if ($resM <= 0) {
			return $default;
		}

		$deadlineRes = $this->toTime($ticket->get('data_limite_resolucao'));
		$deadlineResp = in_array('data_limite_resposta', $cols, true)
			? $this->toTime($ticket->get('data_limite_resposta'))
			: null;

		$sit = (int)($ticket->get('situacao') ?? 0);
		$closed = $this->closedSituacoes();
		$isClosed = $closed !== [] && in_array($sit, $closed, true);

		$elapsedMin = max(0, ((int)$now->format('U') - (int)$created->format('U')) / 60);
		$pctResolucao = min(999.99, ($elapsedMin / $resM) * 100);

		$pctResposta = 0.0;
		$firstResp = in_array('data_primeira_resposta', $cols, true)
			? $this->toTime($ticket->get('data_primeira_resposta'))
			: null;
		if ($firstResp === null && $respM > 0) {
			$pctResposta = min(999.99, ($elapsedMin / $respM) * 100);
		}

		$pct = round(max($pctResolucao, $pctResposta), 2);

		$violado = false;
		if ($deadlineRes !== null && $now > $deadlineRes) {
			$violado = true;
		}
		if ($pctResolucao > 100.0001) {
			$violado = true;
		}
		if ($firstResp === null && $deadlineResp !== null && $now > $deadlineResp) {
			$violado = true;
		}
		if ($pctResposta > 100.0001 && $firstResp === null) {
			$violado = true;
		}

		$violadoForCycle = false;
		if (!$isClosed) {
			if ($deadlineRes !== null && $now > $deadlineRes) {
				$violadoForCycle = true;
			}
			if ($firstResp === null && $deadlineResp !== null && $now > $deadlineResp) {
				$violadoForCycle = true;
			}
		} else {
			$resolv = in_array('data_resolucao', $cols, true) ? $this->toTime($ticket->get('data_resolucao')) : null;
			if ($resolv !== null && $deadlineRes !== null && $resolv > $deadlineRes) {
				$violadoForCycle = true;
			}
		}

		if ($isClosed) {
			$resolv = in_array('data_resolucao', $cols, true) ? $this->toTime($ticket->get('data_resolucao')) : null;
			if ($resolv !== null && $deadlineRes !== null) {
				$violado = $resolv > $deadlineRes;
				$elapsedClosed = max(0, ((int)$resolv->format('U') - (int)$created->format('U')) / 60);
				$pct = round(min(999.99, ($elapsedClosed / $resM) * 100), 2);
			} elseif ($deadlineRes !== null) {
				$violado = $now > $deadlineRes;
			}
		}

		$status = 'dentro_sla';
		if ($violado) {
			$status = 'violado';
		} elseif ($pct >= 80) {
			$status = 'em_risco';
		}

		return [
			'violado' => $violado,
			'violado_for_cycle' => $violadoForCycle,
			'pct' => $pct,
			'status' => $status,
		];
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $ticket
	 * @param string[] $cols
	 */
	public function recalculateOne($ticket, array $cols = null): bool {
		$cols = $cols ?? $this->tickets->getSchema()->columns();
		if (!in_array('sla_status', $cols, true)) {
			return false;
		}

		if (!empty($ticket->get('sla_resolucao_pausado'))) {
			return false;
		}

		$resM = (int)($ticket->get('sla_resolucao_minutos') ?? 0);
		if ($resM <= 0) {
			return false;
		}
		$created = $this->toTime($ticket->get('created'));
		if ($created === null) {
			return false;
		}

		$state = $this->evaluateSlaState($ticket, $cols);

		$ticket->set('sla_percentual_consumido', $state['pct']);
		$ticket->set('sla_status', $state['status']);

		$saveFields = ['sla_percentual_consumido', 'sla_status'];
		$saveFields = array_values(array_intersect($saveFields, $cols));

		$ok = (bool)$this->tickets->save($ticket, ['fields' => $saveFields]);
		if ($ok && !empty($state['violado_for_cycle'])) {
			try {
				$cycleSvc = new TicketSlaCycleService($this->tickets);
				$cycleSvc->ensureOverdueEventForViolatedTicket($ticket, true);
			} catch (\Throwable $e) {
			}
		}

		return $ok;
	}

	/**
	 * @param mixed $v
	 * @return Time|null
	 */
	protected function toTime($v) {
		if ($v === null || $v === '') {
			return null;
		}
		if ($v instanceof Time) {
			return $v;
		}
		if ($v instanceof \DateTimeInterface) {
			return new Time($v->format('Y-m-d H:i:s'));
		}
		if (is_string($v)) {
			try {
				return new Time($v);
			} catch (\Throwable $e) {
				return null;
			}
		}

		return null;
	}

	/**
	 * @return int[]
	 */
	protected function closedSituacoes(): array {
		if (!defined('C_TicketSituacaoResolvido') || !defined('C_TicketSituacaoFechado')) {
			return [];
		}
		$out = [(int)C_TicketSituacaoResolvido, (int)C_TicketSituacaoFechado];
		if (defined('C_TicketSituacaoCancelado')) {
			$out[] = (int)C_TicketSituacaoCancelado;
		}

		return $out;
	}
}
