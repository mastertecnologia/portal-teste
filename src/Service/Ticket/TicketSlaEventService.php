<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Persistência em ticket_sla_events e espelho opcional em ticket_events (type=sla) e ticket_histories (tipo_evento=sla_ciclo).
 * Falhas no espelho são ignoradas para não quebrar o fluxo principal.
 */
class TicketSlaEventService {

	public const SOURCE_DEFAULT = 'ticket_sla';

	/** @var Table */
	protected $slaEvents;

	/** @var Table|null */
	protected $ticketEvents;

	/** @var Table|null */
	protected $ticketHistories;

	public function __construct(
		?Table $ticketSlaEventsTable = null,
		?Table $ticketEventsTable = null,
		?Table $ticketHistoriesTable = null
	) {
		$this->slaEvents = $ticketSlaEventsTable ?? TableRegistry::get('TicketSlaEvents');
		$this->ticketEvents = $ticketEventsTable;
		$this->ticketHistories = $ticketHistoriesTable;
	}

	/**
	 * @return EntityInterface|null Entidade gravada em ticket_sla_events ou null se tabela indisponível / save falhou.
	 */
	public function logEvent(
		int $ticketId,
		int $idempresa,
		string $eventType,
		?int $ticketSlaCycleId = null,
		?int $workflowSlaPolicyId = null,
		?array $payload = null,
		?int $createdByUserId = null,
		string $source = self::SOURCE_DEFAULT
	): ?EntityInterface {
		if (!$this->hasTable('ticket_sla_events')) {
			$this->mirrorOnlyTimelineAndHistory(
				$ticketId,
				$idempresa,
				$eventType,
				$ticketSlaCycleId,
				$payload,
				$createdByUserId
			);

			return null;
		}

		$entity = $this->slaEvents->newEntity([
			'ticket_id' => $ticketId,
			'idempresa' => $idempresa,
			'ticket_sla_cycle_id' => $ticketSlaCycleId,
			'event_type' => $eventType,
			'source' => $source,
			'workflow_sla_policy_id' => $workflowSlaPolicyId,
			'payload' => $payload,
			'created_by_user_id' => $createdByUserId,
			'created_at' => Time::now(),
		], ['validate' => false]);
		$this->slaEvents->save($entity, ['checkRules' => false]);
		if ($entity->getErrors()) {
			return null;
		}

		$this->mirrorTicketTimeline(
			$ticketId,
			$idempresa,
			$eventType,
			$ticketSlaCycleId,
			$payload,
			$createdByUserId
		);
		$this->mirrorTicketHistory(
			$ticketId,
			$createdByUserId,
			$eventType,
			$payload,
			$ticketSlaCycleId
		);

		return $entity;
	}

	protected function mirrorOnlyTimelineAndHistory(
		int $ticketId,
		int $idempresa,
		string $eventType,
		?int $ticketSlaCycleId,
		?array $payload,
		?int $createdByUserId
	): void {
		$this->mirrorTicketTimeline(
			$ticketId,
			$idempresa,
			$eventType,
			$ticketSlaCycleId,
			$payload,
			$createdByUserId
		);
		$this->mirrorTicketHistory(
			$ticketId,
			$createdByUserId,
			$eventType,
			$payload,
			$ticketSlaCycleId
		);
	}

	protected function mirrorTicketTimeline(
		int $ticketId,
		int $idempresa,
		string $eventType,
		?int $ticketSlaCycleId,
		?array $payload,
		?int $userId
	): void {
		if (!$this->hasTable('ticket_events')) {
			return;
		}
		try {
			$te = $this->ticketEvents ?? TableRegistry::get('TicketEvents');
		} catch (\Throwable $e) {
			return;
		}

		$desc = sprintf('SLA: %s', $eventType);
		if ($ticketSlaCycleId !== null && $ticketSlaCycleId > 0) {
			$desc .= sprintf(' (ciclo id %d)', $ticketSlaCycleId);
		}
		$metadata = [
			'sla_ref' => 'ticket_sla',
			'event_type' => $eventType,
			'ticket_sla_cycle_id' => $ticketSlaCycleId,
			'detail' => $payload,
		];
		$row = [
			'idempresa' => $idempresa,
			'ticket_id' => $ticketId,
			'user_id' => $userId,
			'type' => 'sla',
			'description' => $desc,
			'seconds_spent' => 0,
			'is_billed' => false,
			'created' => Time::now(),
		];
		if (in_array('metadata', $te->getSchema()->columns(), true)) {
			$row['metadata'] = $metadata;
		}
		try {
			$ev = $te->newEntity($row, ['validate' => false, 'markClean' => true]);
			$te->save($ev, ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
		} catch (\Throwable $e) {
		}
	}

	protected function mirrorTicketHistory(
		int $ticketId,
		?int $usuarioId,
		string $eventType,
		?array $payload,
		?int $ticketSlaCycleId
	): void {
		if (!$this->hasTable('ticket_histories')) {
			return;
		}
		try {
			$hist = $this->ticketHistories ?? TableRegistry::get('TicketHistories');
		} catch (\Throwable $e) {
			return;
		}
		$snap = [
			'event_type' => $eventType,
			'ticket_sla_cycle_id' => $ticketSlaCycleId,
			'payload' => $payload,
		];
		TicketHistoryLogger::log(
			$hist,
			$ticketId,
			$usuarioId,
			'sla_ciclo',
			null,
			json_encode($snap, JSON_UNESCAPED_UNICODE),
			sprintf('SLA ciclo: %s', $eventType),
			'sistema'
		);
	}

	protected function hasTable(string $name): bool {
		try {
			return in_array($name, ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
