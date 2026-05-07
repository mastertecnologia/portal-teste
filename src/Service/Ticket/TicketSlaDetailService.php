<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Dados agregados de SLA para exibição no ticket (contrato, política, prazos, ciclos, eventos).
 */
class TicketSlaDetailService {

	/**
	 * @return array<string, mixed>
	 */
	public function build(EntityInterface $ticket, int $empresaId, array $slaByState): array {
		$tid = (int)($ticket->get('id') ?? 0);
		$base = [
			'schemaReady' => false,
			'hasSlaFeature' => !empty($slaByState['enabled']),
			'contract' => null,
			'appliedPolicy' => null,
			'resolvedPolicyId' => null,
			'cycleOpen' => null,
			'queue' => null,
			'technician' => null,
			'slaResposta' => [
				'targetMinutes' => null,
				'deadlineIso' => null,
				'remainingMinutes' => null,
			],
			'slaResolucao' => [
				'targetMinutes' => null,
				'deadlineIso' => null,
				'remainingMinutes' => null,
			],
			'status' => ['code' => 'none', 'label' => '—'],
			'pausedSecondsTotal' => 0,
			'cycles' => [],
			'events' => [],
			'escalationLogs' => [],
			'urls' => [
				'workflowSlaAdmin' => null,
				'workflowSlaLogs' => null,
				'workflowSlaPolicy' => null,
			],
			'actions' => ['canPause' => false, 'canResume' => false],
		];
		if ($tid <= 0 || $empresaId <= 0) {
			return $base;
		}
		try {
			$hasCycles = in_array('ticket_sla_cycles', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
			$hasEvents = in_array('ticket_sla_events', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
			$base['schemaReady'] = $hasCycles && $hasEvents;
		} catch (\Throwable $e) {
			return $base;
		}

		$resolver = new SlaPolicyResolverService(null, TableRegistry::get('Tickets'));
		$polEntity = $resolver->resolveForTicket($ticket);
		if ($polEntity !== null) {
			$base['resolvedPolicyId'] = (int)$polEntity->id;
			$base['appliedPolicy'] = $this->serializePolicy($polEntity);
		}

		$cSvc = new TicketSlaCycleService(TableRegistry::get('Tickets'));
		$open = $cSvc->findOpenCycle($tid);
		if ($open !== null) {
			$meta = $this->decodeCycleMeta($open);
			$base['cycleOpen'] = $this->serializeCycle($open, $meta);
			$base['pausedSecondsTotal'] = $this->pauseSegmentsTotalSeconds($meta);
			if ($base['appliedPolicy'] === null && $open->get('workflow_sla_policy_id')) {
				$base['appliedPolicy'] = $this->loadPolicyById((int)$open->get('workflow_sla_policy_id'));
			}
		}

		$cid = $this->intOrNull($ticket->get('contract_id'));
		if ($cid !== null && in_array('contracts', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true)) {
			try {
				$row = TableRegistry::get('Contracts')->find()
					->select(['id', 'code', 'name'])
					->where(['id' => $cid])
					->first();
				if ($row !== null) {
					$base['contract'] = [
						'id' => (int)$row->id,
						'code' => (string)($row->code ?? ''),
						'name' => (string)($row->name ?? ''),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		$qid = $this->intOrNull($ticket->get('queue_id'));
		if ($qid !== null) {
			try {
				$q = TableRegistry::get('Queues')->find()->select(['id', 'name', 'codigo'])->where(['id' => $qid])->first();
				if ($q !== null) {
					$base['queue'] = [
						'id' => (int)$q->id,
						'name' => trim((string)$q->name . ($q->codigo ? ' (' . $q->codigo . ')' : '')),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		$tecId = (int)($ticket->get('idtecnico_responsavel') ?? 0);
		if ($tecId <= 0) {
			$cols = TableRegistry::get('Tickets')->getSchema()->columns();
			if (in_array('owner_id', $cols, true)) {
				$tecId = (int)($ticket->get('owner_id') ?? 0);
			}
		}
		if ($tecId > 0) {
			try {
				$u = TableRegistry::get('Users')->find()->select(['id', 'name'])->where(['id' => $tecId])->first();
				if ($u !== null) {
					$base['technician'] = ['id' => (int)$u->id, 'name' => (string)$u->name];
				}
			} catch (\Throwable $e) {
			}
		}

		$now = Time::now();
		$isPaused = !empty($slaByState['isPaused']);
		$isFinal = !empty($slaByState['isFinal']);
		$isOverdue = !empty($slaByState['isOverdue']);

		$base['slaResposta'] = $this->axisFromTicket($ticket, 'sla_resposta_minutos', 'data_limite_resposta', $now, $isPaused);
		$base['slaResolucao'] = $this->axisFromTicket($ticket, 'sla_resolucao_minutos', 'data_limite_resolucao', $now, $isPaused);

		if (!empty($slaByState['enabled'])) {
			if ($isFinal) {
				$base['status'] = ['code' => 'final', 'label' => 'SLA finalizado (estado final)'];
			} elseif ($isPaused) {
				$base['status'] = ['code' => 'paused', 'label' => 'SLA pausado'];
			} elseif ($isOverdue) {
				$base['status'] = ['code' => 'overdue', 'label' => 'SLA em atraso'];
			} else {
				$base['status'] = ['code' => 'running', 'label' => 'SLA em andamento'];
			}
		}

		try {
			$cycles = TableRegistry::get('TicketSlaCycles')->find()
				->where(['ticket_id' => $tid, 'idempresa' => $empresaId])
				->order(['id' => 'DESC'])
				->limit(20)
				->all();
			foreach ($cycles as $c) {
				$m = $this->decodeCycleMeta($c);
				$base['cycles'][] = $this->serializeCycle($c, $m);
			}
		} catch (\Throwable $e) {
		}

		try {
			$ev = TableRegistry::get('TicketSlaEvents')->find()
				->where(['ticket_id' => $tid, 'idempresa' => $empresaId])
				->order(['id' => 'DESC'])
				->limit(40)
				->all();
			foreach ($ev as $e) {
				$base['events'][] = [
					'id' => (int)$e->id,
					'event_type' => (string)$e->event_type,
					'ticket_sla_cycle_id' => $e->ticket_sla_cycle_id !== null ? (int)$e->ticket_sla_cycle_id : null,
					'workflow_sla_policy_id' => $e->workflow_sla_policy_id !== null ? (int)$e->workflow_sla_policy_id : null,
					'created_at' => $e->created_at ? $e->created_at->format('c') : null,
					'payload' => $e->payload,
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			if (in_array('workflow_sla_escalation_logs', ConnectionManager::get('default')->getSchemaCollection()->listTables(), true)) {
				$logs = TableRegistry::get('WorkflowSlaEscalationLogs')->find()
					->where(['ticket_id' => $tid, 'empresa_id' => $empresaId])
					->order(['created_at' => 'DESC', 'id' => 'DESC'])
					->limit(25)
					->all();
				foreach ($logs as $lg) {
					$base['escalationLogs'][] = [
						'id' => (int)$lg->id,
						'reason_code' => (string)($lg->reason_code ?? ''),
						'workflow_state_from' => $lg->workflow_state_from !== null ? (int)$lg->workflow_state_from : null,
						'workflow_state_to' => $lg->workflow_state_to !== null ? (int)$lg->workflow_state_to : null,
						'created_at' => $lg->created_at ? $lg->created_at->format('c') : null,
					];
				}
			}
		} catch (\Throwable $e) {
		}

		return $base;
	}

	/**
	 * @param array<string, mixed> $slaByState
	 * @param array<string, mixed>|null $cycleOpenSerialized
	 */
	public function computeActions(EntityInterface $ticket, array $slaByState, ?array $cycleOpenSerialized, int $role): array {
		$can = ['canPause' => false, 'canResume' => false];
		if ($role !== 0) {
			return $can;
		}
		if (empty($slaByState['enabled'])) {
			return $can;
		}
		if (!empty($slaByState['isFinal'])) {
			return $can;
		}
		$sit = (int)($ticket->get('situacao') ?? 0);
		$closed = defined('C_TicketSituacaoResolvido') && defined('C_TicketSituacaoFechado')
			&& ($sit === (int)C_TicketSituacaoResolvido || $sit === (int)C_TicketSituacaoFechado);
		if ($closed) {
			return $can;
		}
		$ticketPaused = (bool)$ticket->get('sla_resposta_pausado') || (bool)$ticket->get('sla_resolucao_pausado');
		$cyclePaused = $cycleOpenSerialized !== null && ($cycleOpenSerialized['phase'] ?? '') === 'paused';
		$can['canPause'] = $cycleOpenSerialized !== null && !$ticketPaused && !$cyclePaused;
		$can['canResume'] = $ticketPaused || $cyclePaused;

		return $can;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	protected function serializePolicy(EntityInterface $p): array {
		$st = $p->workflow_state ?? null;
		$estadoNome = $st ? (string)$st->nome : null;
		$wfSid = (int)($p->workflow_state_id ?? 0);
		if ($estadoNome === null && $wfSid > 0) {
			try {
				$row = TableRegistry::get('WorkflowStates')->get($wfSid);
				$estadoNome = (string)$row->nome;
			} catch (\Throwable $e) {
			}
		}

		return [
			'id' => (int)$p->id,
			'workflow_state_id' => $wfSid,
			'estado_nome' => $estadoNome,
			'resposta_minutos' => $p->resposta_minutos !== null ? (int)$p->resposta_minutos : null,
			'resolucao_minutos' => $p->resolucao_minutos !== null ? (int)$p->resolucao_minutos : null,
			'pausa_sla' => (bool)($p->pausa_sla ?? false),
			'is_final' => (bool)($p->is_final ?? false),
			'auto_escalar' => (bool)($p->auto_escalar ?? false),
			'contract_id' => $p->contract_id !== null ? (int)$p->contract_id : null,
			'contract_service_id' => $p->contract_service_id !== null ? (int)$p->contract_service_id : null,
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	protected function loadPolicyById(int $id): ?array {
		if ($id <= 0) {
			return null;
		}
		try {
			$T = TableRegistry::get('WorkflowSlaPolicies');
			$row = $T->get($id, ['contain' => ['WorkflowStates']]);
		} catch (\Throwable $e) {
			return null;
		}

		return $this->serializePolicy($row);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function serializeCycle(EntityInterface $c, array $meta): array {
		return [
			'id' => (int)$c->id,
			'cycle_number' => (int)($c->cycle_number ?? 0),
			'workflow_state_id' => $c->workflow_state_id !== null ? (int)$c->workflow_state_id : null,
			'workflow_sla_policy_id' => $c->workflow_sla_policy_id !== null ? (int)$c->workflow_sla_policy_id : null,
			'sla_resposta_minutos' => $c->sla_resposta_minutos !== null ? (int)$c->sla_resposta_minutos : null,
			'sla_resolucao_minutos' => $c->sla_resolucao_minutos !== null ? (int)$c->sla_resolucao_minutos : null,
			'data_limite_resposta' => $c->data_limite_resposta ? $c->data_limite_resposta->format('c') : null,
			'data_limite_resolucao' => $c->data_limite_resolucao ? $c->data_limite_resolucao->format('c') : null,
			'started_at' => $c->started_at ? $c->started_at->format('c') : null,
			'ended_at' => $c->ended_at ? $c->ended_at->format('c') : null,
			'phase' => (string)($meta['phase'] ?? 'active'),
			'open' => $c->ended_at === null || $c->ended_at === '',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function decodeCycleMeta(EntityInterface $cycle): array {
		$raw = $cycle->get('metadata');
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && trim($raw) !== '') {
			$d = json_decode($raw, true);

			return is_array($d) ? $d : [];
		}

		return [];
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	protected function pauseSegmentsTotalSeconds(array $meta): int {
		$segments = $meta['pause_segments'] ?? [];
		if (!is_array($segments)) {
			return 0;
		}
		$total = 0;
		$now = time();
		foreach ($segments as $seg) {
			if (!is_array($seg) || empty($seg['from'])) {
				continue;
			}
			$from = strtotime((string)$seg['from']);
			if ($from === false) {
				continue;
			}
			$toRaw = $seg['to'] ?? null;
			$to = ($toRaw === null || $toRaw === '') ? $now : strtotime((string)$toRaw);
			if ($to === false) {
				$to = $now;
			}
			if ($to > $from) {
				$total += ($to - $from);
			}
		}

		return $total;
	}

	/**
	 * @return array{targetMinutes: int|null, deadlineIso: string|null, remainingMinutes: int|null}
	 */
	protected function axisFromTicket(EntityInterface $ticket, string $minCol, string $deadCol, Time $now, bool $isPaused): array {
		$out = ['targetMinutes' => null, 'deadlineIso' => null, 'remainingMinutes' => null];
		try {
			$cols = TableRegistry::get('Tickets')->getSchema()->columns();
			if (!in_array($minCol, $cols, true) || !in_array($deadCol, $cols, true)) {
				return $out;
			}
		} catch (\Throwable $e) {
			return $out;
		}
		$rawM = $ticket->get($minCol);
		if ($rawM !== null && $rawM !== '') {
			$out['targetMinutes'] = max(0, (int)$rawM);
		}
		$dl = $ticket->get($deadCol);
		if ($dl === null || $dl === '') {
			return $out;
		}
		try {
			$t = $dl instanceof Time ? $dl : new Time($dl);
			$out['deadlineIso'] = $t->format('c');
			$delta = $t->getTimestamp() - $now->getTimestamp();
			$out['remainingMinutes'] = (int)floor($delta / 60);
		} catch (\Throwable $e) {
		}

		return $out;
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
}
