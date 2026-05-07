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

		try {
			$cols = $tickets->getSchema()->columns();
			if (in_array('sla_escalated_at', $cols, true)) {
				$query->where([
					'OR' => [
						['sla_escalated_at IS' => null],
						['sla_escalated_at' => ''],
					],
				]);
			}
			if (in_array('data_limite_resolucao', $cols, true)) {
				$query->where(['data_limite_resolucao IS NOT' => null]);
			}
			if (in_array('sla_resolucao_pausado', $cols, true)) {
				$query->where([
					'OR' => [
						['sla_resolucao_pausado IS' => null],
						['sla_resolucao_pausado' => false],
					],
				]);
			}
		} catch (\Throwable $e) {
		}

		// Somente tickets “abertos” no legado (situacao). O OR anterior incluía qualquer ticket
		// com workflow_state_id > 0 mesmo quando situacao já era resolvido/fechado — risco de escalar fechados.
		$closed = self::closedSituacoes();
		if ($closed !== []) {
			$query->where(['situacao NOT IN' => $closed]);
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
		$parts = ['idempresa>0'];
		if ($closed === []) {
			$parts[] = 'situacao: sem filtro (constantes fechado vazias)';
		} else {
			$parts[] = sprintf('situacao NOT IN [%s]', implode(',', $closed));
		}
		try {
			$cols = $tickets->getSchema()->columns();
			if (in_array('data_limite_resolucao', $cols, true)) {
				$parts[] = 'sla_escalated_at vazio | data_limite_resolucao NOT NULL | sla_resolucao_pausado não true';
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

	/**
	 * Resolve o ID do ticket para modo diagnóstico (compatível com Shell Cake 3 e Command Cake 4+).
	 * Ordem: opções nomeadas → argumentos posicionais (Command/Shell) → env CHECK_SLA_TICKET_ID.
	 *
	 * @param array<string,mixed>|null $shellParams $this->params no Shell
	 * @param array<int,mixed>|null    $shellPositionalArgs $this->args no Shell
	 * @param object|null              $commandArguments  objeto Arguments do Command (se houver)
	 */
	public static function parseDiagnosticTicketId(?array $shellParams, ?array $shellPositionalArgs, $commandArguments = null): ?int {
		$asPositiveInt = function ($v): ?int {
			if ($v === null || $v === false || $v === '') {
				return null;
			}
			$s = trim((string)$v);
			if ($s === '' || !ctype_digit($s)) {
				return null;
			}
			$i = (int)$s;

			return $i > 0 ? $i : null;
		};

		$optionKeys = ['ticket', 'ticket_id', 'ticket-id'];

		if (is_array($shellParams)) {
			foreach ($optionKeys as $k) {
				if (array_key_exists($k, $shellParams)) {
					$id = $asPositiveInt($shellParams[$k]);
					if ($id !== null) {
						return $id;
					}
				}
			}
		}

		if (is_object($commandArguments) && method_exists($commandArguments, 'getOption')) {
			foreach ($optionKeys as $k) {
				$id = $asPositiveInt($commandArguments->getOption($k));
				if ($id !== null) {
					return $id;
				}
			}
		}

		if (is_object($commandArguments)) {
			if (method_exists($commandArguments, 'getArgument')) {
				foreach (['id', 'ticket', 'ticket_id'] as $name) {
					$id = $asPositiveInt($commandArguments->getArgument($name));
					if ($id !== null) {
						return $id;
					}
				}
			}
			if (method_exists($commandArguments, 'getArgumentAt')) {
				for ($i = 0; $i < 4; $i++) {
					$id = $asPositiveInt($commandArguments->getArgumentAt($i));
					if ($id !== null) {
						return $id;
					}
				}
			}
			if (method_exists($commandArguments, 'getArguments')) {
				$all = $commandArguments->getArguments();
				if (is_array($all)) {
					foreach ($all as $a) {
						$id = $asPositiveInt($a);
						if ($id !== null) {
							return $id;
						}
					}
				}
			}
		}

		if (is_array($shellPositionalArgs) && isset($shellPositionalArgs[0])) {
			$id = $asPositiveInt($shellPositionalArgs[0]);
			if ($id !== null) {
				return $id;
			}
		}

		$env = getenv('CHECK_SLA_TICKET_ID');

		return $asPositiveInt($env !== false ? $env : null);
	}
}
