<?php
namespace App\Shell;

use App\Service\Ticket\SlaService;
use App\Service\Ticket\WorkflowSlaService;
use App\Utility\Ticket\SlaEscalationBatch;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class CheckSlaEscalationShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription(
			'Verifica SLA de workflow e escalona tickets quando aplicável. Use -v para diagnóstico.'
		);
		$parser->addOption('ticket-id', [
			'help' => 'Apenas o ticket informado (modo diagnóstico, sem os filtros do batch).',
		]);

		return $parser;
	}

	public function main() {
		$workflowEnabled = (bool)Configure::read('Workflow.workflowEnabled', false);
		$workflowSlaEnabled = (bool)Configure::read('Workflow.workflowSlaEnabled', false);
		$workflowAutoEscalationEnabled = (bool)Configure::read('Workflow.workflowAutoEscalationEnabled', false);
		if (!$workflowEnabled || !$workflowSlaEnabled || !$workflowAutoEscalationEnabled) {
			$this->out('Workflow SLA auto-escalation desabilitado por feature flag.');

			return;
		}

		$raw = isset($this->params['ticket-id']) ? trim((string)$this->params['ticket-id']) : '';
		$onlyTicketId = ($raw !== '' && ctype_digit($raw)) ? (int)$raw : null;

		$tickets = TableRegistry::get('Tickets');
		$slaService = new WorkflowSlaService($tickets, new SlaService($tickets));

		$query = SlaEscalationBatch::buildCandidateQuery($tickets, $onlyTicketId);

		$this->verbose(
			'CheckSlaEscalation candidatos filtros: ' . SlaEscalationBatch::describeFilters($tickets, $onlyTicketId)
		);
		try {
			$sql = trim((string)$query->sql());
			if ($sql !== '') {
				$this->verbose('CheckSlaEscalation candidatos sql: ' . $sql);
			}
		} catch (\Throwable $e) {
			$this->verbose(sprintf('CheckSlaEscalation sql (indisponível): %s', $e->getMessage()));
		}

		if ($onlyTicketId !== null) {
			$count = $query->count();
			if ($count === 0) {
				$this->err(sprintf('Ticket id=%s não encontrado.', (string)$onlyTicketId));

				return;
			}
		}

		$total = 0;
		$escalated = 0;
		foreach ($query->all() as $ticket) {
			$total++;
			$tid = (string)$ticket->get('id');
			$this->verbose(sprintf('ticket %s candidate_loaded', $tid));
			try {
				$r = $slaService->escalateIfDue($ticket);
				if (($r['applied'] ?? false)) {
					$escalated++;
				}
				$this->verbose(sprintf('ticket %s %s', $tid, (string)($r['code'] ?? 'skipped_processing_error')));
			} catch (\Throwable $e) {
				$this->verbose(sprintf('CheckSlaEscalation skip ticket %s: %s', $tid, $e->getMessage()));
			}
		}

		$this->out(sprintf('CheckSlaEscalation concluído. Tickets verificados: %d | escalonados: %d', $total, $escalated));
	}
}
