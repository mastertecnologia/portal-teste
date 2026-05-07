<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use ArrayObject;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Liga o motor de ciclos SLA (ticket_sla_cycles / events) aos saves de tickets.
 * Chamado a partir de {@see \App\Model\Table\TicketsTable::afterSave}.
 */
class TicketSlaFlowService {

	/** @var Table */
	protected $tickets;

	/** @var TicketSlaCycleService */
	protected $cycles;

	public function __construct(?Table $ticketsTable = null, ?TicketSlaCycleService $cycleService = null) {
		$this->tickets = $ticketsTable ?? TableRegistry::get('Tickets');
		$this->cycles = $cycleService ?? new TicketSlaCycleService($this->tickets);
	}

	public function afterTicketSaved(EntityInterface $entity, ArrayObject $options): void {
		if (!empty($options['skipTicketSlaFlow'])) {
			return;
		}
		$ticketId = (int)($entity->get('id') ?? 0);
		$empresaId = (int)($entity->get('idempresa') ?? 0);
		if ($ticketId <= 0 || $empresaId <= 0) {
			return;
		}
		if (!$this->slaCyclesTableExists()) {
			return;
		}

		$userId = $this->currentRequestUserId();
		$isNewSave = !empty($options['_slaWasNew']);
		/** @var EntityInterface|null $prev */
		$prev = isset($options['_slaPrev']) && $options['_slaPrev'] instanceof EntityInterface
			? $options['_slaPrev'] : null;

		$nowClosed = $this->isClosedForSla($entity);
		$wasClosed = $prev !== null && $this->isClosedForSla($prev);

		if ($nowClosed && !$wasClosed && !$isNewSave) {
			$this->cycles->finishCycle($ticketId, $empresaId, $userId, ['reason' => 'ticket_finalized']);

			return;
		}

		if (!$nowClosed && $wasClosed && !$isNewSave) {
			$this->cycles->startCycle($entity, [
				'user_id' => $userId,
				'workflow_sla_policy_id' => $this->resolvePolicyId($entity),
				'metadata' => ['trigger' => 'ticket_reopened'],
			]);
			if ($prev !== null && $this->firstResponseInstantiated($prev, $entity)) {
				$this->logFirstResponse($ticketId, $empresaId, $userId, $entity);
			}

			return;
		}

		if ($nowClosed) {
			return;
		}

		if ($isNewSave) {
			$this->cycles->startCycle($entity, [
				'user_id' => $userId,
				'workflow_sla_policy_id' => $this->resolvePolicyId($entity),
				'metadata' => ['trigger' => 'ticket_created'],
			]);

			return;
		}

		if ($prev === null) {
			return;
		}

		$queueChanged = $this->scalarFieldChanged($prev, $entity, 'queue_id');
		$techChanged = $this->techChanged($prev, $entity);
		$wfChanged = $this->scalarFieldChanged($prev, $entity, 'workflow_state_id');
		$probChanged = $this->problemaChanged($prev, $entity);
		$contractChanged = $this->scalarFieldChanged($prev, $entity, 'contract_id')
			|| $this->scalarFieldChanged($prev, $entity, 'contract_service_id');
		$firstRespNow = $this->firstResponseInstantiated($prev, $entity);

		if ($queueChanged || $techChanged) {
			$trigger = 'assignee_change';
			if ($queueChanged && $techChanged) {
				$trigger = 'queue_and_assignee';
			} elseif ($queueChanged) {
				$trigger = 'queue_change';
			}
			$meta = ['trigger' => $trigger];
			if ($queueChanged) {
				$meta['from_queue_id'] = $this->positiveIntOrNull($prev->get('queue_id'));
				$meta['to_queue_id'] = $this->positiveIntOrNull($entity->get('queue_id'));
			}
			if ($techChanged) {
				$meta['from_tecnico_id'] = $this->techId($prev);
				$meta['to_tecnico_id'] = $this->techId($entity);
			}
			$this->cycles->startCycle($entity, [
				'user_id' => $userId,
				'workflow_sla_policy_id' => $this->resolvePolicyId($entity),
				'metadata' => $meta,
			]);
			if ($firstRespNow) {
				$this->logFirstResponse($ticketId, $empresaId, $userId, $entity);
			}

			return;
		}

		if ($wfChanged || $probChanged || $contractChanged) {
			$trigger = 'workflow_state_change';
			if ($probChanged && !$wfChanged && !$contractChanged) {
				$trigger = 'problema_change';
			} elseif ($contractChanged && !$wfChanged && !$probChanged) {
				$trigger = 'contract_change';
			} elseif ($wfChanged && ($probChanged || $contractChanged)) {
				$trigger = 'context_change';
			}
			if (!$this->cycles->recalculateCycle($ticketId, $empresaId, $entity, $userId)) {
				$this->cycles->startCycle($entity, [
					'user_id' => $userId,
					'workflow_sla_policy_id' => $this->resolvePolicyId($entity),
					'metadata' => ['trigger' => $trigger],
				]);
			}
			if ($firstRespNow) {
				$this->logFirstResponse($ticketId, $empresaId, $userId, $entity);
			}

			return;
		}

		if ($firstRespNow) {
			$this->logFirstResponse($ticketId, $empresaId, $userId, $entity);
			$this->cycles->recalculateCycle($ticketId, $empresaId, $entity, $userId);
		}
	}

	protected function logFirstResponse(int $ticketId, int $empresaId, ?int $userId, EntityInterface $entity): void {
		$open = $this->cycles->findOpenCycle($ticketId);
		$cid = $open !== null ? (int)$open->get('id') : 0;
		$cycleId = $cid > 0 ? $cid : null;
		$raw = $entity->get('data_primeira_resposta');
		$iso = null;
		if ($raw !== null && $raw !== '') {
			try {
				$iso = $raw instanceof \DateTimeInterface ? $raw->format('c') : (string)$raw;
			} catch (\Throwable $e) {
				$iso = (string)$raw;
			}
		}
		$this->cycles->logEvent(
			$ticketId,
			$empresaId,
			TicketSlaCycleService::EVENT_FIRST_RESPONSE,
			$cycleId,
			$this->resolvePolicyId($entity),
			['data_primeira_resposta' => $iso],
			$userId
		);
	}

	protected function resolvePolicyId(EntityInterface $ticket): ?int {
		try {
			$resolver = new SlaPolicyResolverService(null, $this->tickets);
			$p = $resolver->resolveForTicket($ticket);
			if ($p === null) {
				return null;
			}
			$id = (int)($p->get('id') ?? 0);

			return $id > 0 ? $id : null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function isClosedForSla(EntityInterface $e): bool {
		if ($this->isFinalSituacao($e->get('situacao'))) {
			return true;
		}
		$wf = (int)($e->get('workflow_state_id') ?? 0);

		return $wf > 0 && $this->isWorkflowStateFinal($wf);
	}

	/**
	 * @param mixed $situacao
	 */
	protected function isFinalSituacao($situacao): bool {
		if (!defined('C_TicketSituacaoResolvido') || !defined('C_TicketSituacaoFechado')) {
			return false;
		}
		$s = (int)$situacao;

		return $s === (int)C_TicketSituacaoResolvido || $s === (int)C_TicketSituacaoFechado;
	}

	protected function isWorkflowStateFinal(int $stateId): bool {
		try {
			$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
			if (!in_array('workflow_states', $tables, true)) {
				return false;
			}
			$st = TableRegistry::get('WorkflowStates')->get($stateId, ['fields' => ['id', 'is_final']]);

			return (bool)$st->get('is_final');
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function slaCyclesTableExists(): bool {
		try {
			return in_array(
				'ticket_sla_cycles',
				ConnectionManager::get('default')->getSchemaCollection()->listTables(),
				true
			);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function scalarFieldChanged(EntityInterface $prev, EntityInterface $cur, string $field): bool {
		try {
			if (!in_array($field, $this->tickets->getSchema()->columns(), true)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}

		return (string)$prev->get($field) !== (string)$cur->get($field);
	}

	protected function techChanged(EntityInterface $prev, EntityInterface $cur): bool {
		return $this->techId($prev) !== $this->techId($cur);
	}

	protected function techId(EntityInterface $e): ?int {
		try {
			$cols = $this->tickets->getSchema()->columns();
		} catch (\Throwable $e) {
			return null;
		}
		if (in_array('idtecnico_responsavel', $cols, true)) {
			return $this->positiveIntOrNull($e->get('idtecnico_responsavel'));
		}
		if (in_array('owner_id', $cols, true)) {
			return $this->positiveIntOrNull($e->get('owner_id'));
		}

		return null;
	}

	/**
	 * @param mixed $v
	 */
	protected function positiveIntOrNull($v): ?int {
		if ($v === null || $v === '') {
			return null;
		}
		$i = (int)$v;

		return $i > 0 ? $i : null;
	}

	protected function problemaChanged(EntityInterface $prev, EntityInterface $cur): bool {
		return $this->problemaKey($prev) !== $this->problemaKey($cur);
	}

	protected function problemaKey(EntityInterface $e): string {
		try {
			$cols = $this->tickets->getSchema()->columns();
		} catch (\Throwable $e) {
			return '';
		}
		foreach (['problema_id', 'idproblema'] as $f) {
			if (!in_array($f, $cols, true)) {
				continue;
			}
			$v = $e->get($f);
			if ($v === null || $v === '') {
				continue;
			}

			return $f . ':' . (string)(int)$v;
		}

		return '';
	}

	protected function firstResponseInstantiated(EntityInterface $prev, EntityInterface $cur): bool {
		try {
			if (!in_array('data_primeira_resposta', $this->tickets->getSchema()->columns(), true)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}
		$a = $prev->get('data_primeira_resposta');
		$b = $cur->get('data_primeira_resposta');
		$wasEmpty = $a === null || $a === '';
		$nowSet = $b !== null && $b !== '';

		return $wasEmpty && $nowSet;
	}

	protected function currentRequestUserId(): ?int {
		$req = Router::getRequest();
		if ($req instanceof ServerRequest) {
			$uid = $req->getSession()->read('Auth.User.id');
			if ($uid !== null && $uid !== '') {
				return (int)$uid;
			}
		}

		return null;
	}
}
