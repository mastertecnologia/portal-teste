<?php
namespace App\Utility\Ticket;

use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Seleção de tickets para CheckSlaEscalation (batch + modo diagnóstico por ID).
 */
final class SlaEscalationBatch {

	private function __construct() {
	}

	/**
	 * @return int[]
	 */
	public static function closedSituacoes(): array {
		static $ticketConstantsBoot = false;
		if (!$ticketConstantsBoot) {
			$ticketConstantsBoot = true;
			$path = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'TicketConstants.php';
			if (is_file($path)) {
				require_once $path;
			}
		}
		$out = [];
		if (defined('C_TicketSituacaoResolvido')) {
			$out[] = (int)C_TicketSituacaoResolvido;
		}
		if (defined('C_TicketSituacaoFechado')) {
			$out[] = (int)C_TicketSituacaoFechado;
		}
		if (defined('C_TicketSituacaoCancelado')) {
			$out[] = (int)C_TicketSituacaoCancelado;
		}

		return array_values(array_unique($out));
	}

	public static function hasWorkflowStateColumn(Table $tickets): bool {
		try {
			return in_array('workflow_state_id', $tickets->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param int|null $onlyTicketId Se > 0, ignora filtros batch e retorna apenas esse id.
	 *
	 * @return \Cake\ORM\Query|Query
	 */
	public static function buildCandidateQuery(Table $tickets, ?int $onlyTicketId = null) {
		if ($onlyTicketId !== null && $onlyTicketId > 0) {
			return $tickets->find()
				->where([$tickets->aliasField('id') => $onlyTicketId])
				->limit(1);
		}

		$query = $tickets->find()
			->where(['idempresa >' => 0]);

		$closed = self::closedSituacoes();
		$hasWf = self::hasWorkflowStateColumn($tickets);
		if ($closed !== []) {
			if ($hasWf) {
				$query->where([
					'OR' => [
						['situacao NOT IN' => $closed],
						[
							'workflow_state_id IS NOT' => null,
							'workflow_state_id >' => 0,
						],
					],
				]);
			} else {
				$query->where(['situacao NOT IN' => $closed]);
			}
		}

		try {
			$cols = $tickets->getSchema()->columns();
			if (in_array('data_limite_resolucao', $cols, true)) {
				$query->orderAsc($tickets->aliasField('data_limite_resolucao'));
			}
		} catch (\Throwable $e) {
		}
		$query->orderAsc($tickets->aliasField('id'));

		return $query->limit(1000);
	}

	/**
	 * Texto estável para -v / produção (não substitui inspeção de SQL real).
	 */
	public static function describeFilters(Table $tickets, ?int $onlyTicketId): string {
		if ($onlyTicketId !== null && $onlyTicketId > 0) {
			return sprintf('modo_diagnostico ticket_id=%d (sem filtro situacao / limite 1000)', $onlyTicketId);
		}
		$closed = self::closedSituacoes();
		$hasWf = self::hasWorkflowStateColumn($tickets);
		$parts = ['idempresa>0'];
		if ($closed === []) {
			$parts[] = 'situacao: sem filtro (constantes fechado vazias)';
		} elseif ($hasWf) {
			$parts[] = sprintf(
				'Situacao: (situacao NOT IN [%s]) OR (workflow_state_id IS NOT NULL AND workflow_state_id>0)',
				implode(',', $closed)
			);
		} else {
			$parts[] = sprintf('situacao NOT IN [%s]', implode(',', $closed));
		}
		try {
			$cols = $tickets->getSchema()->columns();
			if (in_array('data_limite_resolucao', $cols, true)) {
				$parts[] = 'ORDER BY data_limite_resolucao ASC, id ASC';
			} else {
				$parts[] = 'ORDER BY id ASC';
			}
		} catch (\Throwable $e) {
			$parts[] = 'ORDER BY id ASC';
		}
		$parts[] = 'LIMIT 1000';

		return implode(' | ', $parts);
	}
}
