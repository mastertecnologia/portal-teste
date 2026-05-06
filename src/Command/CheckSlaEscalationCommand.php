<?php
namespace App\Command;

use App\Service\Ticket\SlaService;
use App\Service\Ticket\WorkflowSlaService;
use App\Utility\Ticket\SlaEscalationBatch;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class CheckSlaEscalationCommand extends Command {

	protected static $defaultName = 'CheckSlaEscalation';

	public static function defaultName(): string {
		return 'CheckSlaEscalation';
	}

	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);
		$parser->setDescription(
			'Verifica SLA de workflow e escalona tickets quando aplicável (cron). '
			. 'Use -v para filtros/SQL por ticket.'
		);
		$parser->addOption('ticket', [
			'short' => 't',
			'help' => 'Modo diagnóstico: só este ID. Alternativas: --ticket-id, primeiro argumento posicional, env CHECK_SLA_TICKET_ID.',
		]);
		$parser->addOption('ticket-id', [
			'help' => 'Igual a --ticket (hífen).',
		]);
		$parser->addOption('ticket_id', [
			'help' => 'Igual a --ticket (underscore).',
		]);
		$parser->addArgument('id', [
			'required' => false,
			'help' => 'ID do ticket (modo diagnóstico), posicional.',
		]);

		return $parser;
	}

	public function execute(Arguments $args, ConsoleIo $io): int {
		$workflowEnabled = (bool)Configure::read('Workflow.workflowEnabled', false);
		$workflowSlaEnabled = (bool)Configure::read('Workflow.workflowSlaEnabled', false);
		$workflowAutoEscalationEnabled = (bool)Configure::read('Workflow.workflowAutoEscalationEnabled', false);
		if (!$workflowEnabled || !$workflowSlaEnabled || !$workflowAutoEscalationEnabled) {
			$io->out('Workflow SLA auto-escalation desabilitado por feature flag.');

			return static::CODE_SUCCESS;
		}

		$onlyTicketId = SlaEscalationBatch::parseDiagnosticTicketId(null, null, $args);

		$tickets = TableRegistry::getTableLocator()->get('Tickets');
		$slaService = new WorkflowSlaService($tickets, new SlaService($tickets));

		$query = SlaEscalationBatch::buildCandidateQuery($tickets, $onlyTicketId);

		if ($io->verbosity() >= ConsoleIo::VERBOSE) {
			$io->verbose('CheckSlaEscalation candidatos filtros: ' . SlaEscalationBatch::describeFilters($tickets, $onlyTicketId));
			try {
				$sql = trim((string)$query->sql());
				if ($sql !== '') {
					$io->verbose('CheckSlaEscalation candidatos sql: ' . $sql);
				}
			} catch (\Throwable $e) {
				$io->verbose(sprintf('CheckSlaEscalation sql (indisponível): %s', $e->getMessage()));
			}
		}

		if ($onlyTicketId !== null) {
			$count = $query->count();
			if ($count === 0) {
				$io->err(sprintf('Ticket id=%s não encontrado.', (string)$onlyTicketId));

				return static::CODE_ERROR;
			}
		}

		$total = 0;
		$escalated = 0;
		foreach ($query->all() as $ticket) {
			$total++;
			$tid = (string)$ticket->get('id');
			if ($io->verbosity() >= ConsoleIo::VERBOSE) {
				$io->verbose(sprintf('ticket %s candidate_loaded', $tid));
			}
			try {
				$r = $slaService->escalateIfDue($ticket);
				if (($r['applied'] ?? false)) {
					$escalated++;
				}
				if ($io->verbosity() >= ConsoleIo::VERBOSE) {
					$io->verbose(sprintf('ticket %s %s', $tid, (string)($r['code'] ?? 'skipped_processing_error')));
					if (!empty($r['deadline_eval']) && is_array($r['deadline_eval'])) {
						foreach ($r['deadline_eval'] as $k => $v) {
							$io->verbose(sprintf(
								'  %s=%s',
								$k,
								is_scalar($v) || $v === null ? (string)$v : '[non-scalar]'
							));
						}
					}
				}
			} catch (\Throwable $e) {
				$io->verbose(sprintf('CheckSlaEscalation skip ticket %s: %s', $tid, $e->getMessage()));
			}
		}

		$io->out(sprintf('CheckSlaEscalation concluído. Tickets verificados: %d | escalonados: %d', $total, $escalated));

		return static::CODE_SUCCESS;
	}
}
