<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Ciclo de SLA (ticket_sla_cycles): início, pausa, retomada, encerramento, recálculo e marcação de atraso.
 * Usa {@see TicketSlaEventService} para auditoria em ticket_sla_events + espelho timeline/histórico.
 */
class TicketSlaCycleService {

	public const EVENT_CYCLE_STARTED = 'cycle_started';
	public const EVENT_CYCLE_PAUSED = 'cycle_paused';
	public const EVENT_CYCLE_RESUMED = 'cycle_resumed';
	public const EVENT_CYCLE_FINISHED = 'cycle_finished';
	public const EVENT_CYCLE_RECALCULATED = 'cycle_recalculated';
	public const EVENT_SLA_MARKED_OVERDUE = 'sla_marked_overdue';
	public const EVENT_SLA_AUTO_ESCALATED = 'sla_auto_escalated';
	public const EVENT_SLA_RESUMED = 'sla_resumed';
	public const EVENT_CYCLE_SUPERSEDED = 'cycle_superseded';
	public const EVENT_FIRST_RESPONSE = 'first_response';

	public const META_SLA_OVERDUE_EVENT_LOGGED = 'sla_overdue_event_logged';

	/** @var Table */
	protected $tickets;

	/** @var Table */
	protected $cycles;

	/** @var TicketSlaEventService */
	protected $events;

	public function __construct(
		?Table $ticketsTable = null,
		?Table $cyclesTable = null,
		?TicketSlaEventService $eventService = null
	) {
		$this->tickets = $ticketsTable ?? TableRegistry::get('Tickets');
		$this->cycles = $cyclesTable ?? TableRegistry::get('TicketSlaCycles');
		$this->events = $eventService ?? new TicketSlaEventService();
	}

	/**
	 * Regista evento SLA (delegação).
	 */
	public function logEvent(
		int $ticketId,
		int $idempresa,
		string $eventType,
		?int $ticketSlaCycleId = null,
		?int $workflowSlaPolicyId = null,
		?array $payload = null,
		?int $createdByUserId = null,
		string $source = TicketSlaEventService::SOURCE_DEFAULT
	): ?EntityInterface {
		return $this->events->logEvent(
			$ticketId,
			$idempresa,
			$eventType,
			$ticketSlaCycleId,
			$workflowSlaPolicyId,
			$payload,
			$createdByUserId,
			$source
		);
	}

	/**
	 * Inicia novo ciclo; encerra silenciosamente ciclos abertos anteriores no mesmo ticket.
	 *
	 * @param array<string, mixed> $options metadata (merge), workflow_sla_policy_id, user_id
	 */
	public function startCycle(EntityInterface $ticket, array $options = []): ?EntityInterface {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return null;
		}
		$ticketId = (int)($ticket->get('id') ?? 0);
		$empresaId = (int)($ticket->get('idempresa') ?? 0);
		if ($ticketId <= 0 || $empresaId <= 0) {
			return null;
		}
		$userId = isset($options['user_id']) ? (int)$options['user_id'] : null;
		if ($userId !== null && $userId <= 0) {
			$userId = null;
		}
		$this->closeOpenCyclesSilent($ticketId, $empresaId, $userId);

		$snap = $this->snapshotFromTicket($ticket);
		$policyId = isset($options['workflow_sla_policy_id']) ? (int)$options['workflow_sla_policy_id'] : null;
		if ($policyId !== null && $policyId <= 0) {
			$policyId = null;
		}

		$meta = $this->normalizeNewMetadata($options['metadata'] ?? null);
		$row = [
			'ticket_id' => $ticketId,
			'idempresa' => $empresaId,
			'cycle_number' => $this->nextCycleNumber($ticketId),
			'workflow_state_id' => $snap['workflow_state_id'],
			'workflow_sla_policy_id' => $policyId,
			'idcliente' => $snap['idcliente'],
			'contract_id' => $snap['contract_id'],
			'contract_service_id' => $snap['contract_service_id'],
			'problema_id' => $snap['problema_id'],
			'queue_id' => $snap['queue_id'],
			'support_level_id' => $snap['support_level_id'],
			'sla_resposta_minutos' => $snap['sla_resposta_minutos'],
			'sla_resolucao_minutos' => $snap['sla_resolucao_minutos'],
			'data_limite_resposta' => $snap['data_limite_resposta'],
			'data_limite_resolucao' => $snap['data_limite_resolucao'],
			'started_at' => Time::now(),
			'ended_at' => null,
			'metadata' => $meta,
			'created_at' => Time::now(),
			'updated_at' => Time::now(),
		];
		if ($this->cycleTableHasColumn('deadline_at')) {
			$row['deadline_at'] = $snap['data_limite_resolucao'];
		}
		if ($this->cycleTableHasColumn('total_paused_seconds')) {
			$row['total_paused_seconds'] = 0;
		}
		$entity = $this->cycles->newEntity($row, ['validate' => false]);

		$this->cycles->save($entity, ['checkRules' => false]);
		if ($entity->getErrors() || (int)($entity->get('id') ?? 0) <= 0) {
			return null;
		}

		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_CYCLE_STARTED,
			(int)$entity->get('id'),
			$policyId,
			['cycle_number' => (int)$entity->get('cycle_number')],
			$userId
		);

		return $entity;
	}

	/**
	 * Pausa o ciclo aberto (metadata.phase = paused).
	 */
	public function pauseCycle(int $ticketId, int $empresaId, ?int $userId = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		$cycle = $this->findOpenCycle($ticketId);
		if ($cycle === null) {
			return false;
		}
		if ((int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}
		$meta = $this->decodeMetadata($cycle);
		if (($meta['phase'] ?? 'active') === 'paused') {
			return true;
		}

		$nowIso = Time::now()->format('c');
		$meta['phase'] = 'paused';
		$meta['pause_started_at'] = $nowIso;
		if (!isset($meta['pause_segments']) || !is_array($meta['pause_segments'])) {
			$meta['pause_segments'] = [];
		}
		$meta['pause_segments'][] = ['from' => $nowIso, 'to' => null];

		$cycle->set('metadata', $meta);
		$cycle->set('updated_at', Time::now());
		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_CYCLE_PAUSED,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			['pause_started_at' => $nowIso],
			$userId
		);

		return true;
	}

	/**
	 * Retoma ciclo pausado (fecha segmento de pausa em metadata.pause_segments).
	 */
	public function resumeCycle(int $ticketId, int $empresaId, ?int $userId = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		$cycle = $this->findOpenCycle($ticketId);
		if ($cycle === null) {
			return false;
		}
		if ((int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}
		$meta = $this->decodeMetadata($cycle);
		if (($meta['phase'] ?? 'active') !== 'paused') {
			return false;
		}

		$now = Time::now();
		$nowIso = $now->format('c');
		$pauseSecs = 0;
		if (!empty($meta['pause_started_at'])) {
			try {
				$t0 = new Time($meta['pause_started_at']);
				$pauseSecs = max(0, $now->getTimestamp() - $t0->getTimestamp());
			} catch (\Throwable $e) {
			}
		}
		$meta['total_paused_seconds'] = (int)($meta['total_paused_seconds'] ?? 0) + $pauseSecs;
		$meta['phase'] = 'active';
		unset($meta['pause_started_at']);
		if (isset($meta['pause_segments']) && is_array($meta['pause_segments'])) {
			for ($i = count($meta['pause_segments']) - 1; $i >= 0; $i--) {
				$seg = $meta['pause_segments'][$i];
				if (is_array($seg) && isset($seg['to']) && $seg['to'] === null) {
					$meta['pause_segments'][$i]['to'] = $nowIso;
					break;
				}
			}
		}

		$cycle->set('metadata', $meta);
		if ($this->cycleTableHasColumn('total_paused_seconds')) {
			$colPrev = (int)($cycle->get('total_paused_seconds') ?? 0);
			$cycle->set('total_paused_seconds', $colPrev + $pauseSecs);
		}
		$cycle->set('updated_at', $now);
		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_CYCLE_RESUMED,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			['resumed_at' => $nowIso],
			$userId
		);

		return true;
	}

	/**
	 * Encerra o ciclo aberto (ended_at preenchido).
	 */
	public function finishCycle(int $ticketId, int $empresaId, ?int $userId = null, ?array $payload = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		$cycle = $this->findOpenCycle($ticketId);
		if ($cycle === null) {
			return false;
		}
		if ((int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}

		$meta = $this->decodeMetadata($cycle);
		$meta['phase'] = 'finished';
		$now = Time::now();
		$cycle->set('ended_at', $now);
		$cycle->set('metadata', $meta);
		$cycle->set('updated_at', $now);
		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$logPayload = array_merge(['finished_at' => $now->format('c')], $payload ?? []);
		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_CYCLE_FINISHED,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			$logPayload,
			$userId
		);

		return true;
	}

	/**
	 * Atualiza prazos/minutos do ciclo aberto a partir do ticket atual (sem alterar o ticket).
	 */
	public function recalculateCycle(int $ticketId, int $empresaId, EntityInterface $ticket, ?int $userId = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		if ((int)($ticket->get('id') ?? 0) !== $ticketId || (int)($ticket->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}
		$cycle = $this->findOpenCycle($ticketId);
		if ($cycle === null) {
			return false;
		}
		if ((int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}

		$snap = $this->snapshotFromTicket($ticket);
		$cycle->set('workflow_state_id', $snap['workflow_state_id']);
		$cycle->set('idcliente', $snap['idcliente']);
		$cycle->set('contract_id', $snap['contract_id']);
		$cycle->set('contract_service_id', $snap['contract_service_id']);
		$cycle->set('problema_id', $snap['problema_id']);
		$cycle->set('queue_id', $snap['queue_id']);
		$cycle->set('support_level_id', $snap['support_level_id']);
		$cycle->set('sla_resposta_minutos', $snap['sla_resposta_minutos']);
		$cycle->set('sla_resolucao_minutos', $snap['sla_resolucao_minutos']);
		$cycle->set('data_limite_resposta', $snap['data_limite_resposta']);
		$cycle->set('data_limite_resolucao', $snap['data_limite_resolucao']);
		if ($this->cycleTableHasColumn('deadline_at')) {
			$cycle->set('deadline_at', $snap['data_limite_resolucao']);
		}

		$meta = $this->decodeMetadata($cycle);
		$meta['recalculated_at'] = Time::now()->format('c');
		$cycle->set('metadata', $meta);
		$cycle->set('updated_at', Time::now());

		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_CYCLE_RECALCULATED,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			[
				'sla_resposta_minutos' => $snap['sla_resposta_minutos'],
				'sla_resolucao_minutos' => $snap['sla_resolucao_minutos'],
			],
			$userId
		);

		return true;
	}

	/**
	 * Marca ciclo (ou ciclo aberto) como atraso no metadata; não altera sla_status do ticket.
	 */
	public function markOverdue(int $ticketId, int $empresaId, ?int $cycleId = null, ?int $userId = null, ?array $extra = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		if ($cycleId !== null && $cycleId > 0) {
			try {
				$cycle = $this->cycles->get($cycleId);
			} catch (\Throwable $e) {
				return false;
			}
			if ((int)($cycle->get('ticket_id') ?? 0) !== $ticketId || (int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
				return false;
			}
		} else {
			$cycle = $this->findOpenCycle($ticketId);
			if ($cycle === null) {
				return false;
			}
			if ((int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
				return false;
			}
		}

		$meta = $this->decodeMetadata($cycle);
		if (!empty($meta[self::META_SLA_OVERDUE_EVENT_LOGGED])) {
			return true;
		}

		$nowIso = Time::now()->format('c');
		$meta['overdue'] = true;
		if (empty($meta['overdue_at'])) {
			$meta['overdue_at'] = $nowIso;
		}
		if ($extra !== null && $extra !== []) {
			$meta['overdue_detail'] = $extra;
		}
		$meta[self::META_SLA_OVERDUE_EVENT_LOGGED] = true;

		$cycle->set('metadata', $meta);
		$dlRes = $cycle->get('data_limite_resolucao');
		if ($this->cycleTableHasColumn('deadline_at') && $dlRes !== null && $dlRes !== '') {
			$cycle->set('deadline_at', $dlRes);
		}
		$cycle->set('updated_at', Time::now());
		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_SLA_MARKED_OVERDUE,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			array_merge(['overdue_at' => (string)($meta['overdue_at'] ?? $nowIso)], $extra ?? []),
			$userId
		);

		return true;
	}

	/**
	 * Garante evento de SLA vencido (uma vez por ciclo) quando o prazo foi ultrapassado.
	 * Não altera sla_status do ticket.
	 */
	public function ensureOverdueEventForViolatedTicket(EntityInterface $ticket, bool $deadlineBreached): void {
		if (!$deadlineBreached) {
			return;
		}
		$ticketId = (int)($ticket->get('id') ?? 0);
		$empresaId = (int)($ticket->get('idempresa') ?? 0);
		if ($ticketId <= 0 || $empresaId <= 0) {
			return;
		}
		$extra = ['detection' => 'deadline'];
		try {
			$cols = $this->tickets->getSchema()->columns();
			if (in_array('data_limite_resolucao', $cols, true)) {
				$raw = $ticket->get('data_limite_resolucao');
				if ($raw !== null && $raw !== '') {
					$extra['data_limite_resolucao'] = $raw instanceof \DateTimeInterface
						? $raw->format('c')
						: (string)$raw;
				}
			}
		} catch (\Throwable $e) {
		}
		$this->markOverdue($ticketId, $empresaId, null, null, $extra);
	}

	/**
	 * Após retomar SLA no ticket (prazos estendidos), alinha o ciclo aberto e regista sla_resumed.
	 * Não cria novo ciclo. Idempotente se prazos já coincidem com o ticket.
	 */
	public function syncOpenCycleAfterResumeFromTicket(EntityInterface $ticket, ?int $userId = null): bool {
		if (!$this->hasTable('ticket_sla_cycles')) {
			return false;
		}
		$ticketId = (int)($ticket->get('id') ?? 0);
		$empresaId = (int)($ticket->get('idempresa') ?? 0);
		if ($ticketId <= 0 || $empresaId <= 0) {
			return false;
		}
		$cycle = $this->findOpenCycle($ticketId);
		if ($cycle === null || (int)($cycle->get('idempresa') ?? 0) !== $empresaId) {
			return false;
		}
		$meta = $this->decodeMetadata($cycle);
		if (($meta['phase'] ?? 'active') !== 'active') {
			return false;
		}

		$snap = $this->snapshotFromTicket($ticket);
		$hash = sha1(
			(string)($snap['data_limite_resposta'] ?? '')
			. '|' . (string)($snap['data_limite_resolucao'] ?? '')
		);
		$curRes = $cycle->get('data_limite_resolucao');
		$tgtRes = $snap['data_limite_resolucao'];
		$sameRes = ($curRes === null && $tgtRes === null)
			|| ($curRes !== null && $tgtRes !== null && (string)$curRes === (string)$tgtRes);
		if ($sameRes
			&& ($meta['resume_deadline_sync_hash'] ?? '') === $hash) {
			return true;
		}

		$cycle->set('data_limite_resposta', $snap['data_limite_resposta']);
		$cycle->set('data_limite_resolucao', $snap['data_limite_resolucao']);
		if ($this->cycleTableHasColumn('deadline_at')) {
			$cycle->set('deadline_at', $snap['data_limite_resolucao']);
		}
		$meta['resume_deadline_sync_hash'] = $hash;
		$meta['last_sla_resume_sync_at'] = Time::now()->format('c');
		$cycle->set('metadata', $meta);
		$cycle->set('updated_at', Time::now());
		$this->cycles->save($cycle, ['checkRules' => false]);
		if ($cycle->getErrors()) {
			return false;
		}

		$payload = [
			'data_limite_resposta' => $snap['data_limite_resposta'] instanceof \DateTimeInterface
				? $snap['data_limite_resposta']->format('c')
				: $snap['data_limite_resposta'],
			'data_limite_resolucao' => $snap['data_limite_resolucao'] instanceof \DateTimeInterface
				? $snap['data_limite_resolucao']->format('c')
				: $snap['data_limite_resolucao'],
			'total_paused_seconds_meta' => (int)($meta['total_paused_seconds'] ?? 0),
		];
		if ($this->cycleTableHasColumn('total_paused_seconds')) {
			$payload['total_paused_seconds_column'] = (int)($cycle->get('total_paused_seconds') ?? 0);
		}
		$this->events->logEvent(
			$ticketId,
			$empresaId,
			self::EVENT_SLA_RESUMED,
			(int)$cycle->get('id'),
			$this->intOrNull($cycle->get('workflow_sla_policy_id')),
			$payload,
			$userId
		);

		return true;
	}

	protected function closeOpenCyclesSilent(int $ticketId, int $empresaId, ?int $userId): void {
		$open = $this->cycles->find()
			->where(['ticket_id' => $ticketId, 'ended_at IS' => null])
			->order(['id' => 'ASC'])
			->all();
		foreach ($open as $c) {
			if ((int)($c->get('idempresa') ?? 0) !== $empresaId) {
				continue;
			}
			$meta = $this->decodeMetadata($c);
			$meta['phase'] = 'finished';
			$meta['closed_reason'] = 'superseded';
			$now = Time::now();
			$c->set('ended_at', $now);
			$c->set('metadata', $meta);
			$c->set('updated_at', $now);
			$this->cycles->save($c, ['checkRules' => false]);
			if (!$c->getErrors() && (int)($c->get('id') ?? 0) > 0) {
				$this->events->logEvent(
					$ticketId,
					$empresaId,
					self::EVENT_CYCLE_SUPERSEDED,
					(int)$c->get('id'),
					$this->intOrNull($c->get('workflow_sla_policy_id')),
					['superseded_at' => $now->format('c')],
					$userId
				);
			}
		}
	}

	/**
	 * Ciclo em aberto (ended_at nulo), mais recente.
	 */
	public function findOpenCycle(int $ticketId): ?EntityInterface {
		try {
			$row = $this->cycles->find()
				->where(['ticket_id' => $ticketId, 'ended_at IS' => null])
				->order(['id' => 'DESC'])
				->first();
		} catch (\Throwable $e) {
			return null;
		}

		return $row ?: null;
	}

	protected function nextCycleNumber(int $ticketId): int {
		try {
			$last = $this->cycles->find()
				->select(['cycle_number'])
				->where(['ticket_id' => $ticketId])
				->order(['cycle_number' => 'DESC'])
				->first();
		} catch (\Throwable $e) {
			return 1;
		}
		if ($last === null) {
			return 1;
		}

		return max(1, (int)($last->get('cycle_number') ?? 0) + 1);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function snapshotFromTicket(EntityInterface $ticket): array {
		try {
			$tcols = $this->tickets->getSchema()->columns();
		} catch (\Throwable $e) {
			$tcols = [];
		}

		$pickInt = function (array $names) use ($ticket, $tcols): ?int {
			foreach ($names as $n) {
				if ($tcols !== [] && !in_array($n, $tcols, true)) {
					continue;
				}
				$v = $ticket->get($n);
				if ($v === null || $v === '') {
					continue;
				}
				$i = (int)$v;

				return $i > 0 ? $i : null;
			}

			return null;
		};

		$pickScalar = function (string $col) use ($ticket, $tcols) {
			if ($tcols !== [] && !in_array($col, $tcols, true)) {
				return null;
			}
			$v = $ticket->get($col);

			return ($v === null || $v === '') ? null : $v;
		};

		return [
			'workflow_state_id' => $pickInt(['workflow_state_id']),
			'idcliente' => $pickInt(['idcliente']),
			'contract_id' => $pickInt(['contract_id']),
			'contract_service_id' => $pickInt(['contract_service_id']),
			'problema_id' => $pickInt(['problema_id', 'idproblema']),
			'queue_id' => $pickInt(['queue_id']),
			'support_level_id' => $pickInt(['support_level_id']),
			'sla_resposta_minutos' => $tcols === [] || in_array('sla_resposta_minutos', $tcols, true)
				? $this->nullableInt($ticket->get('sla_resposta_minutos')) : null,
			'sla_resolucao_minutos' => $tcols === [] || in_array('sla_resolucao_minutos', $tcols, true)
				? $this->nullableInt($ticket->get('sla_resolucao_minutos')) : null,
			'data_limite_resposta' => $tcols === [] || in_array('data_limite_resposta', $tcols, true)
				? $pickScalar('data_limite_resposta') : null,
			'data_limite_resolucao' => $tcols === [] || in_array('data_limite_resolucao', $tcols, true)
				? $pickScalar('data_limite_resolucao') : null,
		];
	}

	/**
	 * @param mixed $v
	 */
	protected function nullableInt($v): ?int {
		if ($v === null || $v === '') {
			return null;
		}

		return (int)$v;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function decodeMetadata(EntityInterface $cycle): array {
		$raw = $cycle->get('metadata');
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && trim($raw) !== '') {
			$dec = json_decode($raw, true);

			return is_array($dec) ? $dec : [];
		}

		return [];
	}

	/**
	 * @param mixed $v
	 */
	protected function intOrNull($v): ?int {
		if ($v === null || $v === '') {
			return null;
		}
		$i = (int)$v;

		return $i > 0 ? $i : null;
	}

	/**
	 * @param array<string, mixed>|null $extra
	 * @return array<string, mixed>
	 */
	protected function normalizeNewMetadata(?array $extra): array {
		$base = [
			'phase' => 'active',
			'pause_segments' => [],
		];
		if ($extra === null || $extra === []) {
			return $base;
		}

		return array_merge($base, $extra);
	}

	protected function cycleTableHasColumn(string $column): bool {
		try {
			return in_array($column, $this->cycles->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function hasTable(string $name): bool {
		try {
			return in_array($name, ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
