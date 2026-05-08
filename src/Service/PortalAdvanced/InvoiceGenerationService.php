<?php
namespace App\Service\PortalAdvanced;

use App\Service\ContractLifecycleService;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Geração mensal de faturas (tabela invoices) a partir de contratos ativos e consumos.
 * Idempotente por (contract_id + reference_month). Não altera faturamento/faturas legados.
 */
class InvoiceGenerationService {
	private const PERIOD_BUSINESS = 'business';
	private const PERIOD_AFTER_HOURS = 'after_hours';
	private const PERIOD_WEEKEND_HOLIDAY = 'weekend_holiday';

	/**
	 * @param string $referenceMonth Formato YYYY-MM
	 * @param int|null $idempresa Filtrar contratos por empresa ou null para todos
	 * @param bool $notifyStaff Enfileira notificação interna por fatura criada
	 * @return array{created:int, skipped:int, errors:int, messages:string[]}
	 */
	public static function generateMonthly(string $referenceMonth, ?int $idempresa = null, bool $notifyStaff = true): array {
		$out = ['created' => 0, 'skipped' => 0, 'errors' => 0, 'messages' => []];
		if (!preg_match('/^\d{4}-\d{2}$/', $referenceMonth)) {
			$out['errors']++;
			$out['messages'][] = 'reference_month inválido; use YYYY-MM.';

			return $out;
		}

		try {
			$Contracts = TableRegistry::get('Contracts');
			$Invoices = TableRegistry::get('Invoices');
			$Items = TableRegistry::get('InvoiceItems');
			$Consumptions = TableRegistry::get('ContractConsumptions');
		} catch (\Throwable $e) {
			$out['errors']++;
			$out['messages'][] = 'Tabelas do módulo avançado indisponíveis: ' . $e->getMessage();

			return $out;
		}

		$q = $Contracts->find()
			->where(['status IN' => ContractLifecycleService::statusesEligibleForBilling()]);
		if ($idempresa !== null && $idempresa > 0) {
			$q->where(['idempresa' => $idempresa]);
		}

		foreach ($q->all() as $contract) {
			$cid = (int)$contract->id;
			if ($cid <= 0) {
				continue;
			}

			$n = $Invoices->find()
				->where(['contract_id' => $cid, 'reference_month' => $referenceMonth])
				->count();
			if ($n > 0) {
				$out['skipped']++;
				continue;
			}

			$billingCalc = self::calculateOverageAmount($contract, $referenceMonth);
			$overageH = (float)$billingCalc['legacy_overage_hours'];
			$rate = (float)$billingCalc['legacy_rate'];
			$monthly = (float)($contract->monthly_value ?? 0);
			$overageAmount = (float)$billingCalc['amount'];

			$subtotal = round($monthly, 2);
			$additions = round($overageAmount, 2);
			$total = round($subtotal + $additions, 2);

			$code = 'INV-' . $referenceMonth . '-' . $cid;
			$issue = date('Y-m-d');
			$due = date('Y-m-d', strtotime($issue . ' +10 days'));

			$conn = ConnectionManager::get('default');
			try {
				$conn->transactional(function () use (
					$Invoices,
					$Items,
					$contract,
					$cid,
					$referenceMonth,
					$code,
					$issue,
					$due,
					$subtotal,
					$additions,
					$total,
					$overageH,
					$rate,
					$monthly,
					&$out,
					$notifyStaff
				) {
					$inv = $Invoices->newEntity([
						'contract_id' => $cid,
						'idcliente' => (int)$contract->idcliente,
						'idempresa' => $contract->idempresa !== null ? (int)$contract->idempresa : null,
						'code' => $code,
						'reference_month' => $referenceMonth,
						'issue_date' => $issue,
						'due_date' => $due,
						'subtotal' => $subtotal,
						'discounts' => 0,
						'additions' => $additions,
						'taxes' => 0,
						'total' => $total,
						'status' => 'pending',
						'billing_type' => 'contract',
					]);
					if (!$Invoices->save($inv)) {
						throw new \RuntimeException(json_encode($inv->getErrors(), JSON_UNESCAPED_UNICODE));
					}
					$iid = (int)$inv->id;

					$item1 = $Items->newEntity([
						'invoice_id' => $iid,
						'item_type' => 'contract_monthly',
						'description' => 'Mensalidade — ' . (string)$contract->name,
						'quantity' => 1,
						'unit_value' => $monthly,
						'total_value' => $subtotal,
						'source_reference' => 'contract:' . $cid,
					]);
					if (!$Items->save($item1)) {
						throw new \RuntimeException('item mensalidade: ' . json_encode($item1->getErrors()));
					}

					if ($overageH > 0.00001 && $additions > 0) {
						$item2 = $Items->newEntity([
							'invoice_id' => $iid,
							'item_type' => 'overage_hours',
							'description' => 'Horas excedentes (' . $referenceMonth . ')',
							'quantity' => $overageH,
							'unit_value' => $rate,
							'total_value' => $additions,
							'source_reference' => 'consumption:' . $referenceMonth,
						]);
						if (!$Items->save($item2)) {
							throw new \RuntimeException('item excedente: ' . json_encode($item2->getErrors()));
						}
					}

					AuditLogService::log(
						null,
						'Invoice',
						$iid,
						'generated',
						null,
						['contract_id' => $cid, 'reference_month' => $referenceMonth, 'code' => $code, 'total' => $total]
					);

					$out['created']++;
					$out['messages'][] = 'Criada fatura ' . $code . ' (id=' . $iid . ').';

					if ($notifyStaff && $contract->idempresa) {
						AdvancedNotificationService::notifyEmpresaStaff(
							(int)$contract->idempresa,
							'portal_advanced.invoice_generated',
							'Fatura gerada',
							'Fatura ' . $code . ' referente a ' . $referenceMonth . ' (total ' . $total . ').',
							null,
							'Invoice',
							(string)$iid,
							['invoice_id' => $iid, 'contract_id' => $cid]
						);
					}
				});
			} catch (\Throwable $e) {
				$out['errors']++;
				$out['messages'][] = 'Contrato ' . $cid . ': ' . $e->getMessage();
			}
		}

		return $out;
	}

	/**
	 * Conferência de consumo por contrato/competência (somente auditoria, sem gerar fatura).
	 *
	 * @param object $contract Entidade contracts (com id)
	 * @param string $referenceMonth Formato YYYY-MM
	 * @return array{
	 *  rows:array<int,array<string,mixed>>,
	 *  unlinked_rows:array<int,array<string,mixed>>,
	 *  totals:array<string,float|int>,
	 *  status:string,
	 *  overage_amount:float
	 * }
	 */
	public static function buildConsumptionConferenceForContract($contract, string $referenceMonth): array {
		$Contracts = TableRegistry::get('Contracts');
		$Consumptions = TableRegistry::get('ContractConsumptions');

		$services = $Contracts->ContractServices->find()
			->where(['contract_id' => (int)$contract->id])
			->all()
			->toList();
		$servicesById = [];
		foreach ($services as $service) {
			$servicesById[(int)$service->id] = $service;
		}

		$schema = $Consumptions->getSchema();
		$hasConsumedAmount = $schema->hasColumn('consumed_amount');
		$hasConsumedQuantity = $schema->hasColumn('consumed_quantity');
		$hasSourceType = $schema->hasColumn('source_type');
		$hasSourceId = $schema->hasColumn('source_id');
		$hasSourceHash = $schema->hasColumn('source_hash');
		$hasServiceOrderId = $schema->hasColumn('service_order_id');

		$hasWorkedAt = $schema->hasColumn('worked_at');
		$hasCreated = $schema->hasColumn('created');
		$hasCreatedAt = $schema->hasColumn('created_at');
		$select = ['id', 'ticket_id', 'contract_service_id', 'period_type', 'consumed_hours'];
		if ($hasWorkedAt) {
			$select[] = 'worked_at';
		}
		if ($hasCreated) {
			$select[] = 'created';
		}
		if ($hasCreatedAt) {
			$select[] = 'created_at';
		}
		if ($hasConsumedAmount) {
			$select[] = 'consumed_amount';
		}
		if ($hasConsumedQuantity) {
			$select[] = 'consumed_quantity';
		}
		if ($hasSourceType) {
			$select[] = 'source_type';
		}
		if ($hasSourceId) {
			$select[] = 'source_id';
		}
		if ($hasSourceHash) {
			$select[] = 'source_hash';
		}
		if ($hasServiceOrderId) {
			$select[] = 'service_order_id';
		}

		$qRows = $Consumptions->find()
			->select($select)
			->where([
				'contract_id' => (int)$contract->id,
				'reference_month' => $referenceMonth,
			]);
		if ($hasWorkedAt) {
			$qRows->order(['worked_at' => 'ASC']);
		} elseif ($hasCreated) {
			$qRows->order(['created' => 'ASC']);
		} elseif ($hasCreatedAt) {
			$qRows->order(['created_at' => 'ASC']);
		}
		$rows = $qRows->order(['id' => 'ASC'])->all()->toList();

		$grouped = [];
		$unlinkedRows = [];
		$totalConsumed = 0.0;
		$totalLegacyAmount = 0.0;
		$serviceChronologicalRows = [];
		foreach ($rows as $row) {
			$serviceId = (int)($row->get('contract_service_id') ?? 0);
			$sourceType = $hasSourceType ? (string)($row->get('source_type') ?? '') : '';
			$sourceId = $hasSourceId ? (string)($row->get('source_id') ?? '') : '';
			$sourceHash = $hasSourceHash ? (string)($row->get('source_hash') ?? '') : '';
			$serviceOrderId = $hasServiceOrderId ? (int)($row->get('service_order_id') ?? 0) : 0;
			$period = self::normalizePeriodType((string)$row->get('period_type'));
			$hours = max(0, (float)($row->get('consumed_hours') ?? 0));
			$quantity = $hasConsumedQuantity ? max(0, (float)($row->get('consumed_quantity') ?? 0)) : $hours;
			$consumedAmount = $hasConsumedAmount ? max(0, (float)($row->get('consumed_amount') ?? 0)) : 0.0;

			$totalConsumed += $hours;
			$totalLegacyAmount += $consumedAmount;

			if ($serviceId <= 0 || !isset($servicesById[$serviceId])) {
				$unlinkedRows[] = [
					'service_name' => 'Sem vínculo de item',
					'unidade' => '—',
					'period_type' => $period,
					'consumed' => $quantity,
					'ticket_id' => (int)($row->get('ticket_id') ?? 0),
					'service_order_id' => $serviceOrderId,
					'source_type' => $sourceType !== '' ? $sourceType : 'legacy',
					'source_id' => $sourceId,
					'source_hash' => $sourceHash,
					'status' => 'fallback_legado',
					'legacy_amount' => $consumedAmount,
				];
				continue;
			}

			$service = $servicesById[$serviceId];
			$unit = strtolower(trim((string)($service->unidade ?? '')));
			$serviceChronologicalRows[$serviceId][] = [
				'unit' => $unit,
				'period_type' => self::isHourUnit($unit) ? $period : self::PERIOD_BUSINESS,
				'consumed' => self::isHourUnit($unit) ? $hours : $quantity,
				'ticket_id' => (int)($row->get('ticket_id') ?? 0),
				'service_order_id' => $serviceOrderId,
				'source_type' => $sourceType !== '' ? $sourceType : 'legacy',
				'source_id' => $sourceId,
				'source_hash' => $sourceHash,
			];
		}

		$rowsOut = [];
		$totalIncluded = 0.0;
		$totalOverage = 0.0;
		$totalOverageAmount = 0.0;
		foreach ($serviceChronologicalRows as $serviceId => $chronRows) {
			$service = $servicesById[(int)$serviceId];
			$unit = strtolower(trim((string)($service->unidade ?? '')));
			$serviceIncluded = max(0, (float)($service->max_hours ?? 0));
			$remainingAllowance = $serviceIncluded;
			foreach ($chronRows as $entry) {
				$consumed = max(0, (float)$entry['consumed']);
				if ($consumed <= 0) {
					continue;
				}
				$includedUsed = min($remainingAllowance, $consumed);
				$remainingAllowance -= $includedUsed;
				$overage = max(0, $consumed - $includedUsed);

				$groupKey = $serviceId . '|' . (self::isHourUnit($unit) ? (string)$entry['period_type'] : self::PERIOD_BUSINESS);
				if (!isset($grouped[$groupKey])) {
					$grouped[$groupKey] = [
						'service_id' => $serviceId,
						'service_name' => (string)($service->service_name ?? ('#' . $serviceId)),
						'tipo_item' => (string)($service->tipo_item ?? ''),
						'unidade' => (string)($service->unidade ?? ''),
						'franquia' => $serviceIncluded,
						'consumed' => 0.0,
						'included_used' => 0.0,
						'overage' => 0.0,
						'period_type' => self::isHourUnit($unit) ? (string)$entry['period_type'] : self::PERIOD_BUSINESS,
						'sources' => [],
					];
				}
				$grouped[$groupKey]['consumed'] += $consumed;
				$grouped[$groupKey]['included_used'] += $includedUsed;
				$grouped[$groupKey]['overage'] += $overage;
				$grouped[$groupKey]['sources'][] = [
					'ticket_id' => (int)$entry['ticket_id'],
					'service_order_id' => (int)$entry['service_order_id'],
					'source_type' => (string)$entry['source_type'],
					'source_id' => (string)$entry['source_id'],
					'source_hash' => (string)$entry['source_hash'],
				];

				$totalIncluded += $includedUsed;
				$totalOverage += $overage;
			}
		}

		foreach ($grouped as $item) {
			$service = $servicesById[(int)$item['service_id']];
			$unit = strtolower(trim((string)($service->unidade ?? '')));
			$rate = self::resolveItemRate($service, $unit, (string)$item['period_type']);
			$overageAmount = round(max(0, (float)$item['overage']) * max(0, $rate), 2);
			$totalOverageAmount += $overageAmount;
			$rowsOut[] = [
				'service_id' => (int)$item['service_id'],
				'service_name' => $item['service_name'],
				'tipo_item' => $item['tipo_item'],
				'unidade' => $item['unidade'],
				'franquia' => (float)$item['franquia'],
				'consumed' => (float)$item['consumed'],
				'included_used' => (float)$item['included_used'],
				'overage' => (float)$item['overage'],
				'period_type' => $item['period_type'],
				'rate' => $rate,
				'overage_amount' => $overageAmount,
				'sources' => $item['sources'],
				'status' => (float)$item['overage'] > 0.00001 ? 'novo_calculo_item' : 'sem_cobranca',
				'origin' => 'itemizado_cronologico',
			];
		}

		$status = 'novo_calculo_item';
		if ($rowsOut === [] && $unlinkedRows !== []) {
			$status = 'fallback_legado';
		} elseif ($rowsOut === [] && $unlinkedRows === []) {
			$status = 'sem_cobranca';
		}

		return [
			'rows' => $rowsOut,
			'unlinked_rows' => $unlinkedRows,
			'totals' => [
				'total_consumed' => round(max(0, $totalConsumed), 4),
				'total_included' => round(max(0, $totalIncluded), 4),
				'total_overage' => round(max(0, $totalOverage), 4),
				'total_overage_amount' => round(max(0, $totalOverageAmount), 2),
				'total_legacy_amount' => round(max(0, $totalLegacyAmount), 2),
			],
			'status' => $status,
			'overage_amount' => round(max(0, $totalOverageAmount), 2),
		];
	}

	/**
	 * Calcula excedente do mês considerando regras por item/período quando houver dados.
	 * Mantém fallback legado para contratos antigos (consumo agregado por horas do contrato).
	 *
	 * @param object $contract
	 * @param string $referenceMonth
	 * @return array{amount:float,legacy_overage_hours:float,legacy_rate:float}
	 */
	private static function calculateOverageAmount($contract, string $referenceMonth): array {
		$Contracts = TableRegistry::get('Contracts');
		$Consumptions = TableRegistry::get('ContractConsumptions');

		$qSum = $Consumptions->find();
		$rowSum = $qSum
			->select(['h' => $qSum->func()->sum('consumed_hours')])
			->where(['contract_id' => (int)$contract->id, 'reference_month' => $referenceMonth])
			->first();
		$consumedH = max(0, (float)($rowSum && isset($rowSum->h) ? $rowSum->h : 0));
		$includedH = max(0, (float)($contract->included_hours ?? 0));
		$legacyOverageH = max(0, $consumedH - $includedH);
		$legacyRate = max(0, (float)($contract->overage_hour_value ?? 0));
		$legacyAmount = round($legacyOverageH * $legacyRate, 2);

		$services = $Contracts->ContractServices->find()
			->where(['contract_id' => (int)$contract->id])
			->all()
			->toList();
		if ($services === []) {
			self::logLegacyFallback((int)$contract->id, $referenceMonth, 'Contrato sem itens em contract_services.');
			return [
				'amount' => $legacyAmount,
				'legacy_overage_hours' => $legacyOverageH,
				'legacy_rate' => $legacyRate,
			];
		}
		$servicesById = [];
		foreach ($services as $service) {
			$servicesById[(int)$service->id] = $service;
		}

		$schema = $Consumptions->getSchema();
		if (!$schema->hasColumn('contract_service_id') || !$schema->hasColumn('period_type')) {
			self::logLegacyFallback((int)$contract->id, $referenceMonth, 'Schema de consumo sem contract_service_id/period_type.');
			return [
				'amount' => $legacyAmount,
				'legacy_overage_hours' => $legacyOverageH,
				'legacy_rate' => $legacyRate,
			];
		}

		$hasConsumedAmount = $schema->hasColumn('consumed_amount');
		$hasConsumedHours = $schema->hasColumn('consumed_hours');
		$hasConsumedQuantity = $schema->hasColumn('consumed_quantity');

		$hasWorkedAt = $schema->hasColumn('worked_at');
		$hasCreated = $schema->hasColumn('created');
		$hasCreatedAt = $schema->hasColumn('created_at');
		$select = ['contract_service_id', 'period_type'];
		if ($hasConsumedHours) {
			$select[] = 'consumed_hours';
		}
		if ($hasConsumedQuantity) {
			$select[] = 'consumed_quantity';
		}
		if ($hasConsumedAmount) {
			$select[] = 'consumed_amount';
		}
		if ($hasWorkedAt) {
			$select[] = 'worked_at';
		}
		if ($hasCreated) {
			$select[] = 'created';
		}
		if ($hasCreatedAt) {
			$select[] = 'created_at';
		}
		$qRows = $Consumptions->find()
			->select($select)
			->where(['contract_id' => (int)$contract->id, 'reference_month' => $referenceMonth])
			;
		if ($hasWorkedAt) {
			$qRows->order(['worked_at' => 'ASC']);
		} elseif ($hasCreated) {
			$qRows->order(['created' => 'ASC']);
		} elseif ($hasCreatedAt) {
			$qRows->order(['created_at' => 'ASC']);
		}
		$rows = $qRows->order(['id' => 'ASC'])->all()->toList();
		if ($rows === []) {
			self::logLegacyFallback((int)$contract->id, $referenceMonth, 'Sem linhas de consumo para o mês.');
			return [
				'amount' => $legacyAmount,
				'legacy_overage_hours' => $legacyOverageH,
				'legacy_rate' => $legacyRate,
			];
		}

		$itemAmount = 0.0;
		$pricedRows = 0;
		$serviceRows = [];
		foreach ($rows as $row) {
			$serviceId = (int)($row->get('contract_service_id') ?? 0);
			if ($serviceId <= 0 || !isset($servicesById[$serviceId])) {
				continue;
			}
			$service = $servicesById[$serviceId];
			$unit = strtolower(trim((string)($service->unidade ?? '')));

			$quantity = $hasConsumedQuantity
				? max(0, (float)($row->get('consumed_quantity') ?? 0))
				: max(0, (float)($row->get('consumed_hours') ?? 0));
			if (self::isHourUnit($unit)) {
				$quantity = max(0, (float)($row->get('consumed_hours') ?? 0));
			}
			if ($quantity <= 0) {
				continue;
			}

			$serviceRows[$serviceId][] = [
				'quantity' => $quantity,
				'period_type' => self::normalizePeriodType((string)$row->get('period_type')),
				'unit' => $unit,
			];
		}

		foreach ($serviceRows as $serviceId => $rowsByService) {
			$service = $servicesById[$serviceId];
			$included = max(0, (float)($service->max_hours ?? 0));
			$remainingAllowance = $included;
			foreach ($rowsByService as $entry) {
				$quantity = max(0, (float)$entry['quantity']);
				if ($quantity <= 0) {
					continue;
				}
				$includedUsed = min($remainingAllowance, $quantity);
				$remainingAllowance -= $includedUsed;
				$chargeQty = max(0, $quantity - $includedUsed);
				if ($chargeQty <= 0) {
					continue;
				}
				$rate = self::resolveItemRate($service, (string)$entry['unit'], (string)$entry['period_type']);
				if ($rate <= 0) {
					continue;
				}
				$itemAmount += $chargeQty * $rate;
				$pricedRows++;
			}
		}
		$itemAmount = round(max(0, $itemAmount), 2);
		if ($pricedRows > 0) {
			return [
				'amount' => $itemAmount,
				'legacy_overage_hours' => $legacyOverageH,
				'legacy_rate' => $legacyRate,
			];
		}

		if ($hasConsumedAmount) {
			$fallbackAmount = 0.0;
			foreach ($rows as $row) {
				$fallbackAmount += max(0, (float)($row->get('consumed_amount') ?? 0));
			}
			$fallbackAmount = round($fallbackAmount, 2);
			if ($fallbackAmount > 0) {
				self::logLegacyFallback((int)$contract->id, $referenceMonth, 'Itemização indisponível; usando consumed_amount agregado.');
				return [
					'amount' => $fallbackAmount,
					'legacy_overage_hours' => $legacyOverageH,
					'legacy_rate' => $legacyRate,
				];
			}
		}

		self::logLegacyFallback((int)$contract->id, $referenceMonth, 'Sem linhas itemizadas válidas; aplicando cálculo legado.');
		return [
			'amount' => $legacyAmount,
			'legacy_overage_hours' => $legacyOverageH,
			'legacy_rate' => $legacyRate,
		];
	}

	/**
	 * Resolve valor de excedente por item com fallback para valor_unitario.
	 *
	 * @param object $service
	 * @param string $unit
	 * @param string $periodType
	 * @return float
	 */
	private static function resolveItemRate($service, string $unit, string $periodType): float {
		$fallback = max(0, (float)($service->valor_unitario ?? 0));
		if (!self::isHourUnit($unit)) {
			$unitRate = max(0, (float)($service->unit_overage_rate ?? 0));

			return $unitRate > 0 ? $unitRate : $fallback;
		}
		$periodType = self::normalizePeriodType($periodType);

		if ($periodType === self::PERIOD_AFTER_HOURS) {
			$specific = max(0, (float)($service->after_hours_rate ?? 0));
			if ($specific > 0) {
				return $specific;
			}
		}
		if ($periodType === self::PERIOD_WEEKEND_HOLIDAY) {
			$specific = max(0, (float)($service->weekend_holiday_rate ?? 0));
			if ($specific > 0) {
				return $specific;
			}
		}
		$specific = max(0, (float)($service->business_hour_rate ?? 0));
		if ($specific > 0) {
			return $specific;
		}

		return $fallback;
	}

	private static function isHourUnit($unit): bool {
		$unit = strtolower(trim((string)$unit));

		return in_array($unit, ['h', 'hora', 'horas'], true);
	}

	private static function normalizePeriodType($periodType): string {
		$periodType = strtolower(trim((string)$periodType));
		if (in_array($periodType, [
			self::PERIOD_BUSINESS,
			self::PERIOD_AFTER_HOURS,
			self::PERIOD_WEEKEND_HOLIDAY,
		], true)) {
			return $periodType;
		}

		return self::PERIOD_BUSINESS;
	}

	private static function logLegacyFallback(int $contractId, string $referenceMonth, string $reason): void {
		Log::debug(sprintf(
			'InvoiceGenerationService fallback legado: contract_id=%d reference_month=%s reason=%s',
			$contractId,
			$referenceMonth,
			$reason
		));
	}
}
