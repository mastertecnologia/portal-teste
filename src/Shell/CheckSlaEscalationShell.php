<?php
namespace App\Shell;

use App\Service\Ticket\SlaRecalculationService;
use App\Service\Ticket\SlaService;
use App\Service\Ticket\TicketSlaCycleService;
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
		$parser->addOption('ticket', [
			'short' => 't',
			'help' => 'Modo diagnóstico: só este ID. Equivale a --ticket-id / CHECK_SLA_TICKET_ID ou primeiro argumento numérico.',
		]);
		$parser->addOption('ticket-id', [
			'help' => 'Igual a --ticket (compatibilidade).',
		]);
		$parser->addOption('ticket_id', [
			'help' => 'Igual a --ticket (underscore).',
		]);
		$parser->addArgument('id', [
			'required' => false,
			'help' => 'ID do ticket (modo diagnóstico), sem opções.',
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

		$onlyTicketId = SlaEscalationBatch::parseDiagnosticTicketId($this->params, $this->args, null);

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
				if (!empty($r['legacy_sync'])) {
					$this->verbose(sprintf('  legacy_sync=%s', (string)$r['legacy_sync']));
				}
				if (!empty($r['deadline_eval']) && is_array($r['deadline_eval'])) {
					foreach ($r['deadline_eval'] as $k => $v) {
						$this->verbose(sprintf(
							'  %s=%s',
							$k,
							is_scalar($v) || $v === null ? (string)$v : '[non-scalar]'
						));
					}
				}
				if (!empty($r['escalation']) && is_array($r['escalation'])) {
					$this->verbose(sprintf('  escalation=%s', json_encode($r['escalation'], JSON_UNESCAPED_UNICODE)));
				}
			} catch (\Throwable $e) {
				$this->verbose(sprintf('CheckSlaEscalation skip ticket %s: %s', $tid, $e->getMessage()));
			}
			try {
				$recalc = new SlaRecalculationService($tickets);
				$cols = $tickets->getSchema()->columns();
				$st = $recalc->evaluateSlaState($ticket, $cols);
				if (!empty($st['violado_for_cycle'])) {
					(new TicketSlaCycleService($tickets))->ensureOverdueEventForViolatedTicket($ticket, true);
				}
			} catch (\Throwable $e) {
			}
		}

		$this->out(sprintf('CheckSlaEscalation concluído. Tickets verificados: %d | escalonados: %d', $total, $escalated));
	}
}
