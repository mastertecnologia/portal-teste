<?php
namespace App\Shell;

use App\Service\Ticket\SlaService;
use App\Service\Ticket\WorkflowSlaService;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class CheckSlaEscalationShell extends Shell {

	public function main() {
		$workflowEnabled = (bool)Configure::read('Workflow.workflowEnabled', false);
		$workflowSlaEnabled = (bool)Configure::read('Workflow.workflowSlaEnabled', false);
		$workflowAutoEscalationEnabled = (bool)Configure::read('Workflow.workflowAutoEscalationEnabled', false);
		if (!$workflowEnabled || !$workflowSlaEnabled || !$workflowAutoEscalationEnabled) {
			$this->out('Workflow SLA auto-escalation desabilitado por feature flag.');
			return;
		}

		$tickets = TableRegistry::get('Tickets');
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
				$this->verbose(sprintf('CheckSlaEscalation skip ticket %s: %s', (string)$ticket->get('id'), $e->getMessage()));
			}
		}

		$this->out(sprintf('CheckSlaEscalation concluído. Tickets verificados: %d | escalonados: %d', $total, $escalated));
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
