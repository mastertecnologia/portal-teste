<?php
namespace App\Service\PortalAdvanced;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

/**
 * Geração mensal de faturas (tabela invoices) a partir de contratos ativos e consumos.
 * Idempotente por (contract_id + reference_month). Não altera faturamento/faturas legados.
 */
class InvoiceGenerationService {

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
			->where(['status IN' => ['active', 'ativo']]);
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

			$qSum = $Consumptions->find();
			$rowSum = $qSum
				->select(['h' => $qSum->func()->sum('consumed_hours')])
				->where(['contract_id' => $cid, 'reference_month' => $referenceMonth])
				->first();
			$consumedH = (float)($rowSum && isset($rowSum->h) ? $rowSum->h : 0);

			$includedH = (float)($contract->included_hours ?? 0);
			$overageH = max(0, $consumedH - $includedH);
			$rate = (float)($contract->overage_hour_value ?? 0);
			$monthly = (float)($contract->monthly_value ?? 0);
			$overageAmount = round($overageH * $rate, 2);

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
}
