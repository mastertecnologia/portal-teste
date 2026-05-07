<?php
namespace App\Service\Ticket;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
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

		// Com SLA já iniciado mas minutos do ticket ≠ policy do estado (ex.: legado 4320 vs policy 1),
		// recalcular prazos; sem divergência, manter deadlines (ex.: extensão após pausa).
		// Null em resposta/resolução na policy = dimensão não gerida aqui (não tratar como 0).
		$bh = new BusinessHoursService();
		$rawPolResp = $policy->get('resposta_minutos');
		$rawPolRes = $policy->get('resolucao_minutos');
		$policyRespostaDefined = $rawPolResp !== null && $rawPolResp !== '';
		$policyResolucaoDefined = $rawPolRes !== null && $rawPolRes !== '';
		$respostaMin = $policyRespostaDefined ? max(0, (int)$rawPolResp) : null;
		$resolucaoMin = $policyResolucaoDefined ? max(0, (int)$rawPolRes) : null;

		$hasStarted = $this->hasSlaStarted($ticket);
		$canRespMin = in_array('sla_resposta_minutos', $cols, true);
		$canResMin = in_array('sla_resolucao_minutos', $cols, true);

		$respMisaligned = false;
		if ($canRespMin && $policyRespostaDefined && $respostaMin !== null) {
			$sr = $ticket->get('sla_resposta_minutos');
			if ($sr === null || $sr === '') {
				$respMisaligned = $hasStarted;
			} else {
				$respMisaligned = ((int)$sr !== $respostaMin);
			}
		}

		$resMisaligned = false;
		if ($canResMin && $policyResolucaoDefined && $resolucaoMin !== null) {
			$sz = $ticket->get('sla_resolucao_minutos');
			if ($sz === null || $sz === '') {
				$resMisaligned = $hasStarted;
			} else {
				$resMisaligned = ((int)$sz !== $resolucaoMin);
			}
		}

		if ($hasStarted && !$respMisaligned && !$resMisaligned) {
			return array_values(array_unique($changed));
		}

		if ($canRespMin && $policyRespostaDefined && $respostaMin !== null) {
			$this->setIfExists($ticket, $cols, 'sla_resposta_minutos', $respostaMin, $changed);
		}
		if ($canResMin && $policyResolucaoDefined && $resolucaoMin !== null) {
			$this->setIfExists($ticket, $cols, 'sla_resolucao_minutos', $resolucaoMin, $changed);
		}

		if ($policyRespostaDefined && $respostaMin !== null && in_array('data_limite_resposta', $cols, true)) {
			if ($respMisaligned) {
				if ($respostaMin > 0) {
					$this->setIfExists(
						$ticket,
						$cols,
						'data_limite_resposta',
						$bh->addBusinessMinutes($now, $respostaMin, $empresaId),
						$changed
					);
				} else {
					$this->clearTimestampIfNeeded($ticket, $cols, 'data_limite_resposta', $changed);
				}
			} elseif (!$hasStarted && $respostaMin > 0 && $ticket->get('data_limite_resposta') === null) {
				$this->setIfExists(
					$ticket,
					$cols,
					'data_limite_resposta',
					$bh->addBusinessMinutes($now, $respostaMin, $empresaId),
					$changed
				);
			}
		}

		if ($policyResolucaoDefined && $resolucaoMin !== null && in_array('data_limite_resolucao', $cols, true)) {
			if ($resMisaligned) {
				if ($resolucaoMin > 0) {
					$this->setIfExists(
						$ticket,
						$cols,
						'data_limite_resolucao',
						$bh->addBusinessMinutes($now, $resolucaoMin, $empresaId),
						$changed
					);
				} else {
					$this->clearTimestampIfNeeded($ticket, $cols, 'data_limite_resolucao', $changed);
				}
			} elseif (!$hasStarted && $resolucaoMin > 0 && $ticket->get('data_limite_resolucao') === null) {
				$this->setIfExists(
					$ticket,
					$cols,
					'data_limite_resolucao',
					$bh->addBusinessMinutes($now, $resolucaoMin, $empresaId),
					$changed
				);
			}
		}

		if (
			in_array('sla_status', $cols, true)
			&& (!$ticket->get('sla_status') || $respMisaligned || $resMisaligned)
		) {
			$this->setIfExists($ticket, $cols, 'sla_status', 'dentro_sla', $changed);
		}

		return array_values(array_unique($changed));
	}

	/**
	 * Mesma avaliação e efeitos de {@see checkAndEscalate()}, com código de diagnóstico para jobs (-v).
	 *
	 * @return array{applied: bool, code: string, deadline_eval?: array<string, mixed>, legacy_sync?: string}
	 */
	public function escalateIfDue(EntityInterface $ticket): array {
		$empresaId = (int)($ticket->get('idempresa') ?? 0);
		$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
		if ($empresaId <= 0 || $stateId <= 0) {
			return ['applied' => false, 'code' => 'skipped_no_workflow_state'];
		}
		if (!$this->isWorkflowAutoEscalationEnabledForEmpresa($empresaId)) {
			return ['applied' => false, 'code' => 'skipped_auto_escalar_false'];
		}
		$policy = $this->findPolicy($empresaId, $stateId);
		if ($policy === null) {
			return ['applied' => false, 'code' => 'skipped_no_policy'];
		}
		if (!(bool)$policy->auto_escalar || (bool)$policy->is_final) {
			return ['applied' => false, 'code' => 'skipped_auto_escalar_false'];
		}
		$toStateId = (int)($policy->escalate_to_state_id ?? 0);
		if ($toStateId <= 0 || $toStateId === $stateId) {
			return ['applied' => false, 'code' => 'skipped_transition_not_allowed'];
		}
		if ((int)$ticket->get('workflow_state_id') === $toStateId) {
			return ['applied' => false, 'code' => 'skipped_already_at_target'];
		}
		$ticketCols = $this->safeColumns();
		if (in_array('sla_escalated_at', $ticketCols, true) && $ticket->get('sla_escalated_at') !== null && $ticket->get('sla_escalated_at') !== '') {
			return ['applied' => false, 'code' => 'skipped_already_escalated'];
		}
		$deadline = $this->readTime($ticket->get('data_limite_resolucao'));
		if ($deadline === null) {
			return ['applied' => false, 'code' => 'skipped_no_deadline'];
		}
		$afterMin = max(0, (int)($policy->escalate_after_minutos ?? 0));
		$bhEsc = new BusinessHoursService();
		$compareAt = $bhEsc->addBusinessMinutes($deadline, $afterMin, $empresaId);
		$now = Time::now();
		if ($now->getTimestamp() <= $compareAt->getTimestamp()) {
			return [
				'applied' => false,
				'code' => 'skipped_deadline_not_reached',
				'deadline_eval' => $this->describeDeadlineEvaluation(
					$now,
					$deadline,
					$compareAt,
					$afterMin,
					$empresaId,
					$bhEsc
				),
			];
		}
		$workflow = $this->workflowService ?: new WorkflowService($this->tickets, $this->slaService, $this);
		$target = $workflow->getStateById($toStateId);
		if ($target === null || !empty($target['is_final'])) {
			return ['applied' => false, 'code' => 'skipped_transition_not_allowed'];
		}
		if (!$workflow->canTransition($empresaId, $stateId, $toStateId)) {
			return ['applied' => false, 'code' => 'skipped_transition_not_allowed'];
		}
		$legacyProbe = $workflow->legacySituacaoForWorkflowStateId($toStateId);
		if ($legacyProbe === null) {
			try {
				\Cake\Log\Log::warning(sprintf(
					'WorkflowSlaService skipped_legacy_status_sync ticket=%d to_state=%d (codigo sem situacao legado; escalonamento cancelado)',
					(int)$ticket->get('id'),
					$toStateId
				));
			} catch (\Throwable $e) {
			}

			return [
				'applied' => false,
				'code' => 'skipped_legacy_status_sync',
				'legacy_sync' => 'legacy_status_not_mapped',
			];
		}
		$legacySync = 'legacy_status_synced';
		try {
			$this->tickets->getConnection()->transactional(function () use ($ticket, $empresaId, $stateId, $toStateId, $workflow, $legacyProbe, $ticketCols): void {
				$workflow->applyTransition($ticket, $toStateId, $empresaId);
				if ((int)$ticket->get('workflow_state_id') !== $toStateId) {
					throw new \RuntimeException('legacy_transition_noop');
				}
				$ticket->set('situacao', (int)$legacyProbe);
				if (method_exists($ticket, 'dirty')) {
					$ticket->dirty('situacao', true);
				}
				$changed = array_values(array_unique($ticket->getDirty()));
				if ($changed === []) {
					throw new \RuntimeException('no_dirty_after_escalation');
				}
				if (in_array('sla_escalated_at', $ticketCols, true)) {
					$ticket->set('sla_escalated_at', FrozenTime::now());
					$changed[] = 'sla_escalated_at';
					$changed = array_values(array_unique($changed));
				}
				if (!$this->tickets->save($ticket, ['fields' => $changed, 'atomic' => false])) {
					throw new \RuntimeException('save_failed_escalation');
				}
			});
		} catch (\Throwable $e) {
			$msg = $e->getMessage();
			if ($msg === 'legacy_transition_noop') {
				try {
					\Cake\Log\Log::warning(sprintf(
						'WorkflowSlaService skipped_legacy_status_sync ticket=%d to_state=%d (applyTransition nao alterou workflow_state_id)',
						(int)$ticket->get('id'),
						$toStateId
					));
				} catch (\Throwable $e2) {
				}

				return ['applied' => false, 'code' => 'skipped_legacy_status_sync', 'legacy_sync' => 'skipped_legacy_status_sync'];
			}
			try {
				\Cake\Log\Log::warning('WorkflowSlaService::escalateIfDue: ' . $msg);
			} catch (\Throwable $e2) {
			}

			return ['applied' => false, 'code' => 'skipped_transition_not_allowed', 'legacy_sync' => $legacySync];
		}
		$this->logEscalation((int)$ticket->get('id'), $empresaId, $stateId, $toStateId);
		try {
			$this->persistEscalationLogRow((int)$ticket->get('id'), $empresaId, $stateId, $toStateId, 'escalated');
		} catch (\Throwable $e) {
		}
		try {
			\Cake\Log\Log::info(sprintf('Ticket %d escalado por SLA (empresa=%d %d→%d)', (int)$ticket->get('id'), $empresaId, $stateId, $toStateId));
		} catch (\Throwable $e) {
		}

		return ['applied' => true, 'code' => 'escalated', 'legacy_sync' => $legacySync];
	}

	public function checkAndEscalate(EntityInterface $ticket): bool {
		try {
			return $this->escalateIfDue($ticket)['applied'];
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
			'totalMinutes' => $this->slaResolucaoTotalMinutesForUi($ticket, $policy),
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

	protected function clearTimestampIfNeeded(EntityInterface $ticket, array $cols, string $field, array &$changed): void {
		if (!in_array($field, $cols, true)) {
			return;
		}
		$v = $ticket->get($field);
		if ($v === null || $v === '') {
			return;
		}
		$this->setIfExists($ticket, $cols, $field, null, $changed);
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

	/**
	 * Minutos de resolução para o cartão SLA na UI: política do estado em vigor;
	 * se não houver valor na política, usa o campo do ticket (compatível com dados antigos).
	 */
	protected function slaResolucaoTotalMinutesForUi(EntityInterface $ticket, EntityInterface $policy): int {
		$rawPolicy = $policy->get('resolucao_minutos');
		if ($rawPolicy !== null && $rawPolicy !== '') {
			return max(0, (int)$rawPolicy);
		}

		return max(0, (int)($ticket->get('sla_resolucao_minutos') ?? 0));
	}

	protected function findPolicy(int $empresaId, int $stateId) {
		$empresaId = (int)$empresaId;
		$stateId = (int)$stateId;
		if ($empresaId <= 0 || $stateId <= 0) {
			return null;
		}
		$table = $this->loadPoliciesTable();
		if ($table === null) {
			return null;
		}
		// Preferir política da empresa; depois fallback global (empresa_id NULL).
		// Não usar Expression como chave em order([]): em PHP causa "Illegal offset type".
		$specific = $table->find()
			->where([
				'workflow_state_id' => $stateId,
				'empresa_id' => $empresaId,
			])
			->order([$table->aliasField('id') => 'ASC'])
			->first();
		if ($specific !== null) {
			return $specific;
		}

		return $table->find()
			->where([
				'workflow_state_id' => $stateId,
				'empresa_id IS' => null,
			])
			->order([$table->aliasField('id') => 'ASC'])
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

	/**
	 * Explica skipped_deadline_not_reached para CLI -v (só leitura, não muda regra).
	 *
	 * @return array<string, scalar>
	 */
	protected function describeDeadlineEvaluation(
		Time $now,
		Time $deadlineOriginal,
		Time $businessDeadline,
		int $escalateAfterMinutos,
		int $empresaId,
		BusinessHoursService $bh
	): array {
		$nowTs = $now->getTimestamp();
		$bizTs = $businessDeadline->getTimestamp();
		$wallRem = $bizTs > $nowTs ? (int)floor(($bizTs - $nowTs) / 60) : 0;
		$bizRem = null;
		try {
			$bizRem = $bh->countBusinessMinutesBetween($now, $businessDeadline, $empresaId);
		} catch (\Throwable $e) {
			$bizRem = '(indisponível: ' . $e->getMessage() . ')';
		}

		return [
			'now' => $now->format('Y-m-d H:i:s'),
			'deadline' => $deadlineOriginal->format('Y-m-d H:i:s'),
			'business_deadline' => $businessDeadline->format('Y-m-d H:i:s'),
			'escalate_after_minutos' => $escalateAfterMinutos,
			'timezone' => BusinessHoursService::TIMEZONE . ' (php default: ' . date_default_timezone_get() . ')',
			'business_hours_add_minutes' => sprintf(
				'addBusinessMinutes(deadline=%s, after=%d, idempresa=%d)',
				$deadlineOriginal->format('Y-m-d H:i:s'),
				$escalateAfterMinutos,
				$empresaId
			),
			'business_hours_result' => $businessDeadline->format('c'),
			'idempresa' => $empresaId,
			'now_ts' => $nowTs,
			'business_deadline_ts' => $bizTs,
			'decision' => $nowTs <= $bizTs ? 'now_ts<=business_deadline_ts (hold escalation)' : 'unexpected',
			'wall_minutes_remaining' => $wallRem,
			'business_minutes_remaining' => $bizRem,
		];
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

	protected function persistEscalationLogRow(int $ticketId, int $empresaId, int $fromStateId, int $toStateId, string $reasonCode): void {
		try {
			$logs = TableRegistry::get('WorkflowSlaEscalationLogs');
		} catch (\Throwable $e) {
			return;
		}
		$e = $logs->newEntity([
			'ticket_id' => $ticketId,
			'empresa_id' => $empresaId,
			'workflow_state_from' => $fromStateId,
			'workflow_state_to' => $toStateId,
			'reason_code' => $reasonCode,
			'created_at' => FrozenTime::now(),
		]);
		$logs->save($e);
	}
}
