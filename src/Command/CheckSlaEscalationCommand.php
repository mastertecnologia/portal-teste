<?php
namespace App\Command;

use App\Service\Ticket\SlaService;
use App\Service\Ticket\WorkflowSlaService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class CheckSlaEscalationCommand extends Command {

	protected static $defaultName = 'CheckSlaEscalation';

	public static function defaultName(): string {
		return 'CheckSlaEscalation';
	}

	public function execute(Arguments $args, ConsoleIo $io): int {
		$workflowEnabled = (bool)Configure::read('Workflow.workflowEnabled', false);
		$workflowSlaEnabled = (bool)Configure::read('Workflow.workflowSlaEnabled', false);
		$workflowAutoEscalationEnabled = (bool)Configure::read('Workflow.workflowAutoEscalationEnabled', false);
		if (!$workflowEnabled || !$workflowSlaEnabled || !$workflowAutoEscalationEnabled) {
			$io->out('Workflow SLA auto-escalation desabilitado por feature flag.');
			return static::CODE_SUCCESS;
		}

		$tickets = TableRegistry::getTableLocator()->get('Tickets');
		$slaService = new WorkflowSlaService($tickets, new SlaService($tickets));
		$closedSituacoes = $this->closedSituacoes();
		$query = $tickets->find()
			->where(['idempresa >' => 0])
			->limit(1000);
		if ($closedSituacoes !== []) {
			$query->where(['situacao NOT IN' => $closedSituacoes]);
		}

		$total = 0;
		$escalated = 0;
		foreach ($query->all() as $ticket) {
			$total++;
			try {
				if ($slaService->checkAndEscalate($ticket)) {
					$escalated++;
				}
			} catch (\Throwable $e) {
				$io->verbose(sprintf('CheckSlaEscalation skip ticket %s: %s', (string)$ticket->get('id'), $e->getMessage()));
				// Nunca derruba o job; log detalhe em verbose para diagnostico sem poluir erro.log.
			}
		}

		$io->out(sprintf('CheckSlaEscalation concluído. Tickets verificados: %d | escalonados: %d', $total, $escalated));
		return static::CODE_SUCCESS;
	}

	/**
	 * @return int[]
	 */
	protected function closedSituacoes(): array {
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
}
