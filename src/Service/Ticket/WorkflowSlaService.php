<?php
namespace App\Service\Ticket;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class WorkflowSlaService {

	/** @var Table */
	protected $tickets;

	/** @var SlaService */
	protected $slaService;

	/** @var WorkflowService|null */
	protected $workflowService;

	public function __construct(Table $ticketsTable, ?SlaService $slaService = null, ?WorkflowService $workflowService = null) {
		$this->tickets = $ticketsTable;
		$this->slaService = $slaService ?: new SlaService($ticketsTable);
		$this->workflowService = $workflowService;
	}

	public function applyStateSla(EntityInterface $ticket, int $empresaId, int $stateId): array {
		$changed = [];
		$wasSlaPausedBeforeApply = (bool)$ticket->get('sla_resposta_pausado') || (bool)$ticket->get('sla_resolucao_pausado');
		if (!$this->isWorkflowSlaEnabledForEmpresa($empresaId)) {
			return $this->slaService->syncPolicyForTicket($ticket, $empresaId);
		}
		$policy = $this->findPolicy($empresaId, $stateId);
		if ($policy === null) {
			return $this->slaService->syncPolicyForTicket($ticket, $empresaId);
		}
		$cols = $this->safeColumns();
		if ($cols === []) {
			return [];
		}
		$now = Time::now();

		if ((bool)$policy->is_final) {
			$this->setIfExists($ticket, $cols, 'sla_resposta_pausado', false, $changed);
			$this->setIfExists($ticket, $cols, 'sla_resolucao_pausado', false, $changed);
			$this->setIfExists($ticket, $cols, 'paused_at', null, $changed);
			$this->setIfExists($ticket, $cols, 'finished_at', $now, $changed);
			return array_values(array_unique($changed));
		}

		$isPauseState = (bool)$policy->pausa_sla;
		if ($isPauseState) {
			$this->setIfExists($ticket, $cols, 'sla_resposta_pausado', true, $changed);
			$this->setIfExists($ticket, $cols, 'sla_resolucao_pausado', true, $changed);
			// Fonte da âncora para resumeSlaWithoutReset: tickets.paused_at (compartilhado com timer
			// agregado na pendência; aqui preenche só se ainda não houver âncora).
			if (!$wasSlaPausedBeforeApply && in_array('paused_at', $cols, true)) {
				$pa = $ticket->get('paused_at');
				if ($pa === null || $pa === '') {
					$this->setIfExists($ticket, $cols, 'paused_at', clone $now, $changed);
				}
			}

			return array_values(array_unique($changed));
		}

		$wasPaused = (bool)$ticket->get('sla_resposta_pausado') || (bool)$ticket->get('sla_resolucao_pausado');
		if ($wasPaused) {
			$this->resumeSlaWithoutReset($ticket, $cols, $changed, $now);
		}

		$this->setIfExists($ticket, $cols, 'sla_resposta_pausado', false, $changed);
		$this->setIfExists($ticket, $cols, 'sla_resolucao_pausado', false, $changed);

		$pendCode = defined('C_TicketSituacaoPendente') ? (int)constant('C_TicketSituacaoPendente') : null;
		if ($pendCode !== null
			&& in_array('paused_at', $cols, true)
			&& (int)($ticket->get('situacao') ?? 0) !== $pendCode
		) {
			$pa = $ticket->get('paused_at');
			if ($pa !== null && $pa !== '') {
				$this->setIfExists($ticket, $cols, 'paused_at', null, $changed);
			}
		}

		if ($this->hasSlaStarted($ticket)) {
			return array_values(array_unique($changed));
		}

		$bh = new BusinessHoursService();
		$respostaMin = max(0, (int)($policy->resposta_minutos ?? 0));
		$resolucaoMin = max(0, (int)($policy->resolucao_minutos ?? 0));

		if (in_array('sla_resposta_minutos', $cols, true)) {
			$this->setIfExists($ticket, $cols, 'sla_resposta_minutos', $respostaMin, $changed);
		}
		if (in_array('sla_resolucao_minutos', $cols, true)) {
			$this->setIfExists($ticket, $cols, 'sla_resolucao_minutos', $resolucaoMin, $changed);
		}
		if (in_array('data_limite_resposta', $cols, true) && $ticket->get('data_limite_resposta') === null && $respostaMin > 0) {
			$this->setIfExists($ticket, $cols, 'data_limite_resposta', $bh->addBusinessMinutes($now, $respostaMin, $empresaId), $changed);
		}
		if (in_array('data_limite_resolucao', $cols, true) && $ticket->get('data_limite_resolucao') === null && $resolucaoMin > 0) {
			$this->setIfExists($ticket, $cols, 'data_limite_resolucao', $bh->addBusinessMinutes($now, $resolucaoMin, $empresaId), $changed);
		}
		if (in_array('sla_status', $cols, true) && !$ticket->get('sla_status')) {
			$this->setIfExists($ticket, $cols, 'sla_status', 'dentro_sla', $changed);
		}

		return array_values(array_unique($changed));
	}

	public function checkAndEscalate(EntityInterface $ticket): bool {
		try {
			$empresaId = (int)($ticket->get('idempresa') ?? 0);
			$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
			if ($empresaId <= 0 || $stateId <= 0) {
				return false;
			}
			if (!$this->isWorkflowAutoEscalationEnabledForEmpresa($empresaId)) {
				return false;
			}
			$policy = $this->findPolicy($empresaId, $stateId);
			if ($policy === null || !(bool)$policy->auto_escalar || (bool)$policy->is_final) {
				return false;
			}
			$toStateId = (int)($policy->escalate_to_state_id ?? 0);
			if ($toStateId <= 0 || $toStateId === $stateId) {
				return false;
			}
			$deadline = $this->readTime($ticket->get('data_limite_resolucao'));
			if ($deadline === null) {
				return false;
			}
			$afterMin = max(0, (int)($policy->escalate_after_minutos ?? 0));
			$bhEsc = new BusinessHoursService();
			$compareAt = $bhEsc->addBusinessMinutes($deadline, $afterMin, $empresaId);
			if (Time::now()->getTimestamp() <= $compareAt->getTimestamp()) {
				return false;
			}
			$workflow = $this->workflowService ?: new WorkflowService($this->tickets, $this->slaService, $this);
			$target = $workflow->getStateById($toStateId);
			if ($target === null || !empty($target['is_final'])) {
				return false;
			}
			if (!$workflow->canTransition($empresaId, $stateId, $toStateId)) {
				return false;
			}
			$workflow->applyTransition($ticket, $toStateId, $empresaId);
			$changed = array_values(array_unique($ticket->getDirty()));
			if ($changed !== []) {
				$this->tickets->save($ticket, ['fields' => $changed, 'atomic' => false]);
			}
			$this->logEscalation((int)$ticket->get('id'), $empresaId, $stateId, $toStateId);
			return true;
		} catch (\Throwable $e) {
			try {
				\Cake\Log\Log::warning('WorkflowSlaService::checkAndEscalate: ' . $e->getMessage());
			} catch (\Throwable $e2) {
			}

			return false;
		}
	}

	public function buildPayload(EntityInterface $ticket, int $empresaId): array {
		$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
		$base = [
			'enabled' => false,
			'isPaused' => false,
			'isFinal' => false,
			'deadlineResolucao' => $this->isoTime($ticket->get('data_limite_resolucao')),
			'autoEscalate' => false,
			'isOverdue' => false,
		];
		if ($empresaId <= 0 || $stateId <= 0 || !$this->isWorkflowSlaEnabledForEmpresa($empresaId)) {
			return $base;
		}
		$policy = $this->findPolicy($empresaId, $stateId);
		if ($policy === null) {
			return $base;
		}
		$deadline = $this->readTime($ticket->get('data_limite_resolucao'));
		$now = Time::now();
		$isPaused = (bool)$ticket->get('sla_resposta_pausado') || (bool)$ticket->get('sla_resolucao_pausado');
		$isFinal = (bool)$policy->is_final;
		$isOverdue = false;
		$remainingMin = null;
		if ($deadline !== null) {
			$deltaSec = $deadline->getTimestamp() - $now->getTimestamp();
			$remainingMin = (int)floor($deltaSec / 60);
			$isOverdue = !$isPaused && !$isFinal && $deltaSec < 0;
		}

		return [
			'enabled' => true,
			'isPaused' => $isPaused,
			'isFinal' => $isFinal,
			'deadlineResolucao' => $this->isoTime($ticket->get('data_limite_resolucao')),
			'autoEscalate' => (bool)$policy->auto_escalar && (int)$policy->escalate_to_state_id > 0,
			'isOverdue' => $isOverdue,
			'remainingMinutes' => $remainingMin,
			'totalMinutes' => (int)($ticket->get('sla_resolucao_minutos') ?? 0),
		];
	}

	protected function resumeSlaWithoutReset(EntityInterface $ticket, array $cols, array &$changed, Time $now): void {
		$pausedAt = $this->readTime($ticket->get('paused_at'));
		if ($pausedAt === null) {
			return;
		}
		$pauseSeconds = max(0, $now->getTimestamp() - $pausedAt->getTimestamp());
		if ($pauseSeconds <= 0) {
			return;
		}
		if (in_array('data_limite_resposta', $cols, true)) {
			$dlResp = $this->readTime($ticket->get('data_limite_resposta'));
			if ($dlResp !== null) {
				$this->setIfExists($ticket, $cols, 'data_limite_resposta', $dlResp->addSeconds($pauseSeconds), $changed);
			}
		}
		if (in_array('data_limite_resolucao', $cols, true)) {
			$dlRes = $this->readTime($ticket->get('data_limite_resolucao'));
			if ($dlRes !== null) {
				$this->setIfExists($ticket, $cols, 'data_limite_resolucao', $dlRes->addSeconds($pauseSeconds), $changed);
			}
		}
	}

	protected function hasSlaStarted(EntityInterface $ticket): bool {
		return $ticket->get('data_limite_resposta') !== null || $ticket->get('data_limite_resolucao') !== null;
	}

	protected function setIfExists(EntityInterface $ticket, array $cols, string $field, $value, array &$changed): void {
		if (!in_array($field, $cols, true)) {
			return;
		}
		if ($ticket->get($field) === $value) {
			return;
		}
		$ticket->set($field, $value);
		$changed[] = $field;
	}

	protected function findPolicy(int $empresaId, int $stateId) {
		if ($empresaId <= 0 || $stateId <= 0) {
			return null;
		}
		$table = $this->loadPoliciesTable();
		if ($table === null) {
			return null;
		}
		// Preferir linha da empresa; depois fallback global (empresa_id NULL). CASE evita NULLS FIRST em ORDER BY DESC no PostgreSQL.
		$q = $table->find();
		$rank = $q->newExpr('CASE WHEN empresa_id IS NULL THEN 0 ELSE 1 END');

		return $q
			->where([
				'workflow_state_id' => $stateId,
				'OR' => [
					['empresa_id' => $empresaId],
					['empresa_id IS' => null],
				],
			])
			->order([$rank => 'DESC', $table->aliasField('id') => 'ASC'])
			->first();
	}

	protected function loadPoliciesTable(): ?Table {
		try {
			return TableRegistry::get('WorkflowSlaPolicies');
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function safeColumns(): array {
		try {
			return $this->tickets->getSchema()->columns();
		} catch (\Throwable $e) {
			return [];
		}
	}

	protected function isoTime($raw): ?string {
		$t = $this->readTime($raw);
		return $t ? $t->format('c') : null;
	}

	protected function readTime($raw): ?Time {
		if ($raw instanceof Time) {
			return $raw;
		}
		if ($raw instanceof \DateTimeInterface) {
			return new Time($raw->format('Y-m-d H:i:s'));
		}
		if (is_string($raw) && trim($raw) !== '') {
			try {
				return new Time($raw);
			} catch (\Throwable $e) {
				return null;
			}
		}
		return null;
	}

	protected function isWorkflowSlaEnabledForEmpresa(int $empresaId): bool {
		if ($empresaId <= 0) {
			return false;
		}
		if (!Configure::read('Workflow.workflowEnabled', false)) {
			return false;
		}
		if (!Configure::read('Workflow.workflowSlaEnabled', false)) {
			return false;
		}
		$enabledEmpresas = (array)Configure::read('Workflow.enabledEmpresas', []);
		if ($enabledEmpresas === []) {
			return true;
		}
		return in_array($empresaId, array_map('intval', $enabledEmpresas), true);
	}

	protected function isWorkflowAutoEscalationEnabledForEmpresa(int $empresaId): bool {
		if (!$this->isWorkflowSlaEnabledForEmpresa($empresaId)) {
			return false;
		}
		if (!Configure::read('Workflow.workflowAutoEscalationEnabled', false)) {
			return false;
		}
		return true;
	}

	protected function logEscalation(int $ticketId, int $empresaId, int $fromStateId, int $toStateId): void {
		try {
			\Cake\Log\Log::warning(sprintf(
				'SLA estourado - auto escalonado | ticket=%d empresa=%d from_state=%d to_state=%d',
				$ticketId,
				$empresaId,
				$fromStateId,
				$toStateId
			));
		} catch (\Throwable $e) {
		}
	}
}
