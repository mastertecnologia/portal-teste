<?php
namespace App\Service\PortalAdvanced;

use App\Service\ContractLifecycleService;
use App\Service\Ticket\BusinessHoursService;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class ContractConsumptionRecorderService {
	private const PERIOD_BUSINESS = 'business';
	private const PERIOD_AFTER_HOURS = 'after_hours';
	private const PERIOD_WEEKEND_HOLIDAY = 'weekend_holiday';

	public static function recordFromTicketWorklog(
		int $ticketId,
		int $idcliente,
		int $idempresa,
		float $hours,
		?\DateTimeInterface $workedAt = null,
		?string $sourceType = null,
		?string $sourceId = null,
		?int $serviceOrderId = null
	) {
		$hours = max(0, $hours);
		if ($ticketId <= 0 || $idcliente <= 0 || $idempresa <= 0 || $hours <= 0) {
			return;
		}

		try {
			$Consumptions = TableRegistry::get('ContractConsumptions');
			$Tickets = TableRegistry::get('Tickets');
			$Contracts = TableRegistry::get('Contracts');
		} catch (\Throwable $e) {
			Log::debug('ContractConsumptionRecorderService: tabelas indisponíveis para registo de consumo.');
			return;
		}

		$schema = $Consumptions->getSchema();
		$canWriteService = $schema->hasColumn('contract_service_id');
		$canWritePeriod = $schema->hasColumn('period_type');
		$canWriteQty = $schema->hasColumn('consumed_quantity');
		$canWriteSourceType = $schema->hasColumn('source_type');
		$canWriteSourceId = $schema->hasColumn('source_id');
		$canWriteSourceHash = $schema->hasColumn('source_hash');

		$ticket = $Tickets->find()
			->select(['id', 'idcliente', 'idempresa', 'contract_id', 'contract_service_id'])
			->where(['Tickets.id' => $ticketId])
			->first();
		if (!$ticket) {
			return;
		}

		$contractId = (int)($ticket->get('contract_id') ?? 0);
		if ($contractId <= 0) {
			$contract = $Contracts->find()
				->select(['id'])
				->where([
					'idcliente' => $idcliente,
					'idempresa' => $idempresa,
					'status IN' => ContractLifecycleService::statusesEligibleForBilling(),
				])
				->order(['modified' => 'DESC', 'id' => 'DESC'])
				->first();
			$contractId = (int)($contract->get('id') ?? 0);
		}
		if ($contractId <= 0) {
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: sem contract_id para ticket=%d cliente=%d empresa=%d.',
				$ticketId,
				$idcliente,
				$idempresa
			));
			return;
		}

		$serviceId = null;
		$serviceUnit = null;
		$services = $Contracts->ContractServices->find()
			->select(['id', 'contract_id', 'unidade'])
			->where(['contract_id' => $contractId])
			->all()
			->toList();
		$servicesById = [];
		$hourServiceIds = [];
		foreach ($services as $service) {
			$sid = (int)$service->get('id');
			$servicesById[$sid] = $service;
			if (self::isHourUnit((string)$service->get('unidade'))) {
				$hourServiceIds[] = $sid;
			}
		}
		$ticketServiceId = (int)($ticket->get('contract_service_id') ?? 0);
		if ($ticketServiceId > 0 && isset($servicesById[$ticketServiceId])) {
			$serviceId = $ticketServiceId;
			$serviceUnit = (string)$servicesById[$ticketServiceId]->get('unidade');
		} elseif (count($hourServiceIds) === 1) {
			$serviceId = (int)$hourServiceIds[0];
			$serviceUnit = (string)$servicesById[$serviceId]->get('unidade');
		} elseif (count($hourServiceIds) > 1) {
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: múltiplos itens de hora sem vínculo explícito ticket=%d contract=%d.',
				$ticketId,
				$contractId
			));
		} else {
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: item de contrato não determinado ticket=%d contract=%d.',
				$ticketId,
				$contractId
			));
		}

		$workedAt = $workedAt ?: new \DateTimeImmutable('now', new \DateTimeZone(BusinessHoursService::TIMEZONE));
		$periodType = self::resolvePeriodType($workedAt, $idempresa);
		if ($serviceUnit !== null && !self::isHourUnit($serviceUnit)) {
			$periodType = self::PERIOD_BUSINESS;
		}
		$referenceMonth = $workedAt->format('Y-m');
		$sourceType = self::normalizeSourceType($sourceType);
		$sourceId = self::normalizeSourceId($sourceId);
		$sourceHash = null;
		if ($sourceId === null) {
			$sourceHash = self::buildSourceHash(
				$contractId,
				$ticketId,
				$serviceOrderId,
				$workedAt,
				$hours,
				$sourceType
			);
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: source_hash gerado (sem source_id forte) ticket=%d contract=%d source_type=%s.',
				$ticketId,
				$contractId,
				$sourceType
			));
		}

		$existing = self::findExistingBySource(
			$Consumptions,
			$contractId,
			$sourceType,
			$sourceId,
			$sourceHash,
			$canWriteSourceType,
			$canWriteSourceId,
			$canWriteSourceHash
		);
		if ($existing) {
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: consumo duplicado ignorado contract=%d ticket=%d source_type=%s source_id=%s.',
				$contractId,
				$ticketId,
				$sourceType,
				$sourceId ?? '(null)'
			));
			return $existing;
		}
		if ($sourceId === null) {
			Log::debug(sprintf(
				'ContractConsumptionRecorderService: idempotência forte indisponível; usando hash ticket=%d contract=%d.',
				$ticketId,
				$contractId
			));
		}

		$data = [
			'contract_id' => $contractId,
			'ticket_id' => $ticketId,
			'service_order_id' => $serviceOrderId,
			'reference_month' => $referenceMonth,
			'consumed_hours' => round($hours, 4),
			'consumed_amount' => 0,
		];
		if ($canWriteService) {
			$data['contract_service_id'] = $serviceId;
		}
		if ($canWritePeriod) {
			$data['period_type'] = $periodType;
		}
		if ($canWriteQty) {
			$data['consumed_quantity'] = round($hours, 4);
		}
		if ($canWriteSourceType) {
			$data['source_type'] = $sourceType;
		}
		if ($canWriteSourceId) {
			$data['source_id'] = $sourceId;
		}
		if ($canWriteSourceHash) {
			$data['source_hash'] = $sourceHash;
		}

		$row = $Consumptions->newEntity($data);
		if (!$Consumptions->save($row)) {
			Log::warning('ContractConsumptionRecorderService save falhou: ' . json_encode($row->getErrors(), JSON_UNESCAPED_UNICODE));
			return null;
		}
		return $row;
	}

	private static function isHourUnit(string $unit): bool {
		$unit = strtolower(trim($unit));

		return in_array($unit, ['h', 'hora', 'horas'], true);
	}

	private static function resolvePeriodType(\DateTimeInterface $at, int $idempresa): string {
		try {
			$classification = (new BusinessHoursService())->classifyBilling($at, $idempresa);
		} catch (\Throwable $e) {
			Log::debug('ContractConsumptionRecorderService: falha ao classificar período; usando business.');
			return self::PERIOD_BUSINESS;
		}
		if ($classification === 'holiday') {
			return self::PERIOD_WEEKEND_HOLIDAY;
		}
		if ($classification === 'extra') {
			return self::PERIOD_AFTER_HOURS;
		}
		if ($classification !== 'commercial') {
			Log::debug('ContractConsumptionRecorderService: classificação de período desconhecida; usando business.');
		}

		return self::PERIOD_BUSINESS;
	}

	private static function normalizeSourceType(?string $sourceType): string {
		$sourceType = strtolower(trim((string)$sourceType));
		if ($sourceType === '') {
			return 'ticket_worklog';
		}
		return substr($sourceType, 0, 50);
	}

	private static function normalizeSourceId(?string $sourceId): ?string {
		$sourceId = trim((string)$sourceId);
		if ($sourceId === '') {
			return null;
		}
		return substr($sourceId, 0, 100);
	}

	private static function buildSourceHash(
		int $contractId,
		int $ticketId,
		?int $serviceOrderId,
		\DateTimeInterface $workedAt,
		float $hours,
		string $sourceType
	): string {
		$basis = implode('|', [
			(string)$contractId,
			(string)$ticketId,
			(string)($serviceOrderId ?? 0),
			$workedAt->format('Y-m-d H:i:s'),
			number_format(max(0, $hours), 4, '.', ''),
			$sourceType,
		]);
		return sha1($basis);
	}

	private static function findExistingBySource(
		$Consumptions,
		int $contractId,
		string $sourceType,
		?string $sourceId,
		?string $sourceHash,
		bool $canWriteSourceType,
		bool $canWriteSourceId,
		bool $canWriteSourceHash
	) {
		if ($sourceId !== null && $canWriteSourceType && $canWriteSourceId) {
			$existing = $Consumptions->find()
				->where([
					'contract_id' => $contractId,
					'source_type' => $sourceType,
					'source_id' => $sourceId,
				])
				->first();
			if ($existing) {
				return $existing;
			}
		}
		if ($sourceHash !== null && $canWriteSourceHash) {
			return $Consumptions->find()
				->where([
					'contract_id' => $contractId,
					'source_hash' => $sourceHash,
				])
				->first();
		}
		return null;
	}
}
