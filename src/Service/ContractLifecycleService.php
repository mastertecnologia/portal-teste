<?php
namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Regras de ciclo de vida do contrato (status, aprovação, suspensão, cancelamento).
 * Alinhado a MODULO_CONTRATOS_COMPLETO (fluxo §14); convive com aliases EN (active, draft, awaiting_signature).
 */
class ContractLifecycleService {

	/**
	 * Estados considerados “ativos” para faturação mensal e consumo típico.
	 *
	 * @return string[]
	 */
	public static function statusesEligibleForBilling() {
		return ['active', 'ativo'];
	}

	/**
	 * Estados em que alertas de vencimento / shell tratam como operacionais.
	 *
	 * @return string[]
	 */
	public static function statusesOpenForOperationalAlerts() {
		return ['active', 'ativo', 'a_vencer'];
	}

	/**
	 * Normaliza aliases EN → vocabulário canónico PT usado na validação ORM.
	 *
	 * @param string|null $status
	 * @return string
	 */
	public static function normalizeStatus($status) {
		$s = strtolower(trim((string)$status));
		$map = [
			'active' => 'ativo',
			'draft' => 'rascunho',
			'awaiting_signature' => 'aguardando_assinatura',
		];

		return isset($map[$s]) ? $map[$s] : $s;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return bool
	 */
	public static function mayEditCore(EntityInterface $contract) {
		$st = self::normalizeStatus($contract->get('status'));

		return in_array($st, [
			'rascunho',
			'revisao',
			'recusado',
			'aguardando_assinatura',
		], true);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return void
	 */
	public static function assertMayEditCore(EntityInterface $contract) {
		if (!self::mayEditCore($contract)) {
			throw new \RuntimeException('Contrato não está em estado editável (rascunho, revisão, recusado ou aguardando assinatura).');
		}
	}

	/**
	 * Aprovação interna: regista aprovador e prepara envio para assinatura (status).
	 *
	 * @param \Cake\ORM\Table $contracts ContractsTable
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param int $userId
	 * @param string $targetStatus Normalizado: aguardando_assinatura ou ativo (ex.: contrato sem assinatura digital)
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function aprovarInternamente(Table $contracts, EntityInterface $contract, $userId, $targetStatus = 'aguardando_assinatura') {
		$userId = (int)$userId;
		$cur = self::normalizeStatus($contract->get('status'));
		if (!in_array($cur, ['rascunho', 'revisao'], true)) {
			throw new \RuntimeException('Apenas rascunho ou revisão podem ser aprovados neste fluxo.');
		}
		$target = self::normalizeStatus($targetStatus);
		if (!in_array($target, ['aguardando_assinatura', 'ativo'], true)) {
			throw new \RuntimeException('Destino de aprovação inválido.');
		}
		$contracts->patchEntity($contract, [
			'status' => $target,
			'aprovado_por' => $userId,
			'aprovado_em' => date('Y-m-d H:i:s'),
		]);

		return $contracts->save($contract);
	}

	/**
	 * @param \Cake\ORM\Table $contracts
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function suspender(Table $contracts, EntityInterface $contract) {
		$cur = self::normalizeStatus($contract->get('status'));
		if (!in_array($cur, ['ativo', 'a_vencer'], true)) {
			throw new \RuntimeException('Apenas contrato ativo ou a vencer pode ser suspenso.');
		}
		$contracts->patchEntity($contract, ['status' => 'suspenso']);

		return $contracts->save($contract);
	}

	/**
	 * @param \Cake\ORM\Table $contracts
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param string $motivo
	 * @param bool $notifyClient
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function cancelar(Table $contracts, EntityInterface $contract, $motivo = '', $notifyClient = true) {
		$cur = self::normalizeStatus($contract->get('status'));
		if (in_array($cur, ['encerrado', 'cancelado'], true)) {
			throw new \RuntimeException('Contrato já encerrado ou cancelado.');
		}
		$contracts->patchEntity($contract, [
			'status' => 'cancelado',
			'cancelado_em' => date('Y-m-d H:i:s'),
			'motivo_cancelamento' => $motivo !== '' ? $motivo : null,
		]);
		$saved = $contracts->save($contract);
		if ($saved && $notifyClient) {
			try {
				$withCliente = $contracts->get($contract->get('id'), ['contain' => ['Clientes']]);
				$n = new ContractNotificationService();
				$n->notificarCanceladoCliente($withCliente, $motivo);
			} catch (\Throwable $e) {
			}
		}

		return $saved;
	}

	/**
	 * Reativa contrato suspenso.
	 *
	 * @param \Cake\ORM\Table $contracts
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function reativar(Table $contracts, EntityInterface $contract) {
		if (self::normalizeStatus($contract->get('status')) !== 'suspenso') {
			throw new \RuntimeException('Apenas contrato suspenso pode ser reativado.');
		}
		$contracts->patchEntity($contract, ['status' => 'ativo']);

		return $contracts->save($contract);
	}

	/**
	 * Novo contrato em rascunho com código gerado.
	 *
	 * @param int $idempresa
	 * @param int $idcliente
	 * @param array $data Campos adicionais permitidos pelo entity
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function newDraft($idempresa, $idcliente, array $data = []) {
		$renewal = new ContractRenewalService();
		$code = $renewal->proximoNumero((int)$idempresa);
		$defaults = [
			'idempresa' => (int)$idempresa,
			'idcliente' => (int)$idcliente,
			'code' => $code,
			'name' => 'Novo contrato',
			'type' => 'servico',
			'status' => 'rascunho',
			'start_date' => date('Y-m-d'),
			'end_date' => date('Y-m-d', strtotime('+1 year')),
			'billing_cycle' => 'monthly',
			'monthly_value' => 0,
			'sla_hours' => 0,
			'included_hours' => 0,
			'overage_hour_value' => 0,
			'auto_renew' => false,
		];
		$contracts = TableRegistry::get('Contracts');

		return $contracts->newEntity(array_merge($defaults, $data));
	}
}
