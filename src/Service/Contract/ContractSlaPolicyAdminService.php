<?php
declare(strict_types=1);

namespace App\Service\Contract;

use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use PDOException;

/**
 * CRUD de {@see \App\Model\Table\WorkflowSlaPoliciesTable} vinculadas a um contrato (escopo contract_id + idcliente).
 */
class ContractSlaPolicyAdminService {

	public function isSchemaReady(): bool {
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();

			return in_array('workflow_sla_policies', $c->listTables(), true)
				&& in_array('contracts', $c->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function listForContract(int $contractId, int $empresaId): array {
		$T = TableRegistry::get('WorkflowSlaPolicies');
		$rows = $T->find()
			->contain(['WorkflowStates', 'EscalateToStates'])
			->where([
				'contract_id' => $contractId,
				'empresa_id' => $empresaId,
			])
			->order(['WorkflowSlaPolicies.id' => 'ASC'])
			->all();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->serializePolicy($row);
		}

		return $out;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract Contrato com id, idcliente
	 * @return array<string, mixed>
	 */
	public function buildFormOptions(int $empresaId, EntityInterface $contract): array {
		$contractId = (int)$contract->get('id');
		$states = [];
		try {
			$st = TableRegistry::get('WorkflowStates')->find()
				->select(['id', 'nome', 'codigo', 'is_final'])
				->order(['nome' => 'ASC'])
				->all();
			foreach ($st as $r) {
				$states[] = [
					'id' => (int)$r->id,
					'nome' => (string)$r->nome,
					'codigo' => (string)$r->codigo,
					'is_final' => (bool)$r->is_final,
				];
			}
		} catch (\Throwable $e) {
		}

		$escalateStates = [];
		foreach ($states as $s) {
			if (empty($s['is_final'])) {
				$escalateStates[] = $s;
			}
		}

		$services = [];
		if ($contractId > 0) {
			try {
				$sv = TableRegistry::get('ContractServices')->find()
					->select(['id', 'service_name', 'tipo_item'])
					->where(['contract_id' => $contractId])
					->order(['service_name' => 'ASC'])
					->all();
				foreach ($sv as $r) {
					$services[] = [
						'id' => (int)$r->id,
						'label' => trim((string)$r->service_name . ' [' . ($r->tipo_item ?? '') . ']'),
					];
				}
			} catch (\Throwable $e) {
			}
		}

		$problemas = [];
		try {
			$Pr = TableRegistry::get('Problemas');
			$q = $Pr->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao' => 'ASC']);
			try {
				if (in_array('idempresa', $Pr->getSchema()->columns(), true)) {
					$q->where(['idempresa' => $empresaId]);
				}
			} catch (\Throwable $e) {
			}
			foreach ($q->toArray() as $pid => $label) {
				$problemas[] = ['id' => (int)$pid, 'label' => (string)$label];
			}
		} catch (\Throwable $e) {
		}

		$queues = [];
		try {
			$Q = TableRegistry::get('Queues');
			$qq = $Q->find()->select(['id', 'name', 'codigo'])->order(['name' => 'ASC']);
			if (in_array('idempresa', $Q->getSchema()->columns(), true)) {
				$qq->where(['idempresa' => $empresaId]);
			}
			foreach ($qq->all() as $r) {
				$cod = trim((string)($r->codigo ?? ''));
				$queues[] = [
					'id' => (int)$r->id,
					'label' => trim((string)$r->name . ($cod !== '' ? ' (' . $cod . ')' : '')),
				];
			}
		} catch (\Throwable $e) {
		}

		$levels = [];
		try {
			$L = TableRegistry::get('SupportLevels');
			foreach ($L->find()->select(['id', 'name', 'sort_order'])->order(['sort_order' => 'ASC', 'name' => 'ASC'])->all() as $r) {
				$levels[] = ['id' => (int)$r->id, 'label' => (string)$r->name];
			}
		} catch (\Throwable $e) {
		}

		return [
			'workflow_states' => $states,
			'escalate_states' => $escalateStates,
			'contract_services' => $services,
			'problemas' => $problemas,
			'queues' => $queues,
			'support_levels' => $levels,
		];
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array{ok: bool, policy?: array<string, mixed>, errors?: string[]}
	 */
	public function create(int $empresaId, int $contractId, int $idcliente, array $data): array {
		$err = $this->validatePayload($empresaId, $contractId, $idcliente, $data, null);
		if ($err !== []) {
			return ['ok' => false, 'errors' => $err];
		}
		$T = TableRegistry::get('WorkflowSlaPolicies');
		$row = $T->newEntity([]);
		$this->patchFields($row, $data, $empresaId, $contractId, $idcliente);
		$row->set('created_at', FrozenTime::now());
		$row->set('updated_at', FrozenTime::now());
		if ($this->policyTableHasAtivo()) {
			$row->set('ativo', true);
		}
		try {
			if (!$T->save($row)) {
				return ['ok' => false, 'errors' => $this->flattenErrors($row->getErrors())];
			}
		} catch (PDOException $e) {
			return ['ok' => false, 'errors' => [$this->friendlyDbError($e)]];
		}
		$row = $T->get($row->id, ['contain' => ['WorkflowStates', 'EscalateToStates']]);

		return ['ok' => true, 'policy' => $this->serializePolicy($row)];
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array{ok: bool, policy?: array<string, mixed>, errors?: string[]}
	 */
	public function update(int $empresaId, int $contractId, int $idcliente, int $policyId, array $data): array {
		$T = TableRegistry::get('WorkflowSlaPolicies');
		try {
			$row = $T->get($policyId);
		} catch (\Throwable $e) {
			return ['ok' => false, 'errors' => ['Política não encontrada.']];
		}
		if ((int)$row->get('empresa_id') !== $empresaId || (int)$row->get('contract_id') !== $contractId) {
			return ['ok' => false, 'errors' => ['Política não pertence a este contrato.']];
		}
		$err = $this->validatePayload($empresaId, $contractId, $idcliente, $data, $policyId);
		if ($err !== []) {
			return ['ok' => false, 'errors' => $err];
		}
		$this->patchFields($row, $data, $empresaId, $contractId, $idcliente);
		$row->set('updated_at', FrozenTime::now());
		try {
			if (!$T->save($row)) {
				return ['ok' => false, 'errors' => $this->flattenErrors($row->getErrors())];
			}
		} catch (PDOException $e) {
			return ['ok' => false, 'errors' => [$this->friendlyDbError($e)]];
		}
		$row = $T->get($policyId, ['contain' => ['WorkflowStates', 'EscalateToStates']]);

		return ['ok' => true, 'policy' => $this->serializePolicy($row)];
	}

	/**
	 * @return array{ok: bool, policy?: array<string, mixed>, errors?: string[]}
	 */
	public function setAtivo(int $empresaId, int $contractId, int $policyId, bool $ativo): array {
		if (!$this->policyTableHasAtivo()) {
			return ['ok' => false, 'errors' => ['Coluna ativo indisponível (execute as migrations).']];
		}
		$T = TableRegistry::get('WorkflowSlaPolicies');
		try {
			$row = $T->get($policyId);
		} catch (\Throwable $e) {
			return ['ok' => false, 'errors' => ['Política não encontrada.']];
		}
		if ((int)$row->get('empresa_id') !== $empresaId || (int)$row->get('contract_id') !== $contractId) {
			return ['ok' => false, 'errors' => ['Política não pertence a este contrato.']];
		}
		$row->set('ativo', $ativo);
		$row->set('updated_at', FrozenTime::now());
		if (!$T->save($row)) {
			return ['ok' => false, 'errors' => $this->flattenErrors($row->getErrors())];
		}
		$row = $T->get($policyId, ['contain' => ['WorkflowStates', 'EscalateToStates']]);

		return ['ok' => true, 'policy' => $this->serializePolicy($row)];
	}

	protected function policyTableHasAtivo(): bool {
		try {
			return in_array('ativo', TableRegistry::get('WorkflowSlaPolicies')->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param array<string, mixed> $data
	 * @return string[]
	 */
	protected function validatePayload(int $empresaId, int $contractId, int $idcliente, array $data, ?int $ignoreId): array {
		$errs = [];
		$wf = (int)($data['workflow_state_id'] ?? 0);
		if ($wf <= 0) {
			$errs[] = 'Informe o estado do workflow.';
		}
		$auto = !empty($data['auto_escalar']);
		$isFinal = !empty($data['is_final']);
		if ($auto && $isFinal) {
			$errs[] = 'Estado final e auto-escalar não podem ficar ativos juntos na mesma política.';
		}
		$escSt = $auto ? (int)($data['escalate_to_state_id'] ?? 0) : 0;
		$escQ = $auto ? (int)($data['escalate_to_queue_id'] ?? 0) : 0;
		$escL = $auto ? (int)($data['escalate_to_support_level_id'] ?? 0) : 0;
		$nM = $auto && !empty($data['notify_manager']);
		$nC = $auto && !empty($data['notify_customer']);
		$nT = $auto && !empty($data['notify_technician']);
		if ($auto && $escSt <= 0 && $escQ <= 0 && $escL <= 0 && !$nM && !$nC && !$nT) {
			$errs[] = 'Com auto-escalar, informe destino (estado/fila/nível) ou notificação.';
		}
		if ($auto && $escSt > 0 && $escSt === $wf) {
			$errs[] = 'O estado de escalonamento não pode ser o mesmo do estado atual.';
		}
		$after = isset($data['escalate_after_minutos']) ? (int)$data['escalate_after_minutos'] : 0;
		if ($after < 0) {
			$errs[] = 'Tolerância após vencimento não pode ser negativa.';
		}
		$resp = $data['resposta_minutos'] ?? null;
		$reso = $data['resolucao_minutos'] ?? null;
		foreach (['resposta_minutos' => $resp, 'resolucao_minutos' => $reso] as $label => $v) {
			if ($v !== null && $v !== '' && (int)$v < 0) {
				$errs[] = $label . ' inválido.';
			}
		}

		$csv = (int)($data['contract_service_id'] ?? 0);
		if ($csv > 0) {
			try {
				$s = TableRegistry::get('ContractServices')->find()
					->select(['id'])
					->where(['id' => $csv, 'contract_id' => $contractId])
					->first();
				if ($s === null) {
					$errs[] = 'Serviço não pertence a este contrato.';
				}
			} catch (\Throwable $e) {
			}
		}

		if ($errs !== []) {
			return $errs;
		}

		$T = TableRegistry::get('WorkflowSlaPolicies');
		$pb = (int)($data['problema_id'] ?? 0);
		$qid = (int)($data['queue_id'] ?? 0);
		$lid = (int)($data['support_level_id'] ?? 0);
		$q = $T->find()
			->where([
				'empresa_id' => $empresaId,
				'workflow_state_id' => $wf,
				'contract_id' => $contractId,
				'idcliente' => $idcliente,
			]);
		if ($csv > 0) {
			$q->andWhere(['contract_service_id' => $csv]);
		} else {
			$q->andWhere(function ($exp, $q2) {
				return $exp->or_([
					'contract_service_id IS' => null,
					'contract_service_id' => 0,
				]);
			});
		}
		if ($pb > 0) {
			$q->andWhere(['problema_id' => $pb]);
		} else {
			$q->andWhere(function ($exp, $q2) {
				return $exp->or_(['problema_id IS' => null, 'problema_id' => 0]);
			});
		}
		if ($qid > 0) {
			$q->andWhere(['queue_id' => $qid]);
		} else {
			$q->andWhere(function ($exp, $q2) {
				return $exp->or_(['queue_id IS' => null, 'queue_id' => 0]);
			});
		}
		if ($lid > 0) {
			$q->andWhere(['support_level_id' => $lid]);
		} else {
			$q->andWhere(function ($exp, $q2) {
				return $exp->or_(['support_level_id IS' => null, 'support_level_id' => 0]);
			});
		}
		if ($ignoreId !== null) {
			$q->andWhere(['id !=' => $ignoreId]);
		}
		if ($q->count() > 0) {
			$errs[] = 'Já existe política com a mesma combinação de escopo para este contrato.';
		}

		return $errs;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	protected function patchFields(EntityInterface $row, array $data, int $empresaId, int $contractId, int $idcliente): void {
		$row->set('empresa_id', $empresaId);
		$row->set('idcliente', $idcliente);
		$row->set('contract_id', $contractId);
		$row->set('workflow_state_id', (int)$data['workflow_state_id']);

		$csv = isset($data['contract_service_id']) && $data['contract_service_id'] !== '' && $data['contract_service_id'] !== null
			? (int)$data['contract_service_id'] : null;
		$row->set('contract_service_id', $csv !== null && $csv > 0 ? $csv : null);

		$pb = isset($data['problema_id']) && $data['problema_id'] !== '' ? (int)$data['problema_id'] : null;
		$row->set('problema_id', $pb !== null && $pb > 0 ? $pb : null);

		$qid = isset($data['queue_id']) && $data['queue_id'] !== '' ? (int)$data['queue_id'] : null;
		$row->set('queue_id', $qid !== null && $qid > 0 ? $qid : null);

		$lid = isset($data['support_level_id']) && $data['support_level_id'] !== '' ? (int)$data['support_level_id'] : null;
		$row->set('support_level_id', $lid !== null && $lid > 0 ? $lid : null);

		$row->set('resposta_minutos', ($data['resposta_minutos'] === null || $data['resposta_minutos'] === '') ? null : (int)$data['resposta_minutos']);
		$row->set('resolucao_minutos', ($data['resolucao_minutos'] === null || $data['resolucao_minutos'] === '') ? null : (int)$data['resolucao_minutos']);
		$row->set('pausa_sla', !empty($data['pausa_sla']));
		$row->set('is_final', !empty($data['is_final']));

		$auto = !empty($data['auto_escalar']);
		$row->set('auto_escalar', $auto);
		if ($auto) {
			$est = isset($data['escalate_to_state_id']) && $data['escalate_to_state_id'] !== '' ? (int)$data['escalate_to_state_id'] : null;
			$row->set('escalate_to_state_id', $est !== null && $est > 0 ? $est : null);
			$eq = isset($data['escalate_to_queue_id']) && $data['escalate_to_queue_id'] !== '' ? (int)$data['escalate_to_queue_id'] : null;
			$row->set('escalate_to_queue_id', $eq !== null && $eq > 0 ? $eq : null);
			$el = isset($data['escalate_to_support_level_id']) && $data['escalate_to_support_level_id'] !== '' ? (int)$data['escalate_to_support_level_id'] : null;
			$row->set('escalate_to_support_level_id', $el !== null && $el > 0 ? $el : null);
			$row->set('escalate_after_minutos', (int)($data['escalate_after_minutos'] ?? 0));
			$row->set('notify_manager', !empty($data['notify_manager']));
			$row->set('notify_customer', !empty($data['notify_customer']));
			$row->set('notify_technician', !empty($data['notify_technician']));
		} else {
			$row->set('escalate_to_state_id', null);
			$row->set('escalate_to_queue_id', null);
			$row->set('escalate_to_support_level_id', null);
			$row->set('escalate_after_minutos', 0);
			$row->set('notify_manager', false);
			$row->set('notify_customer', false);
			$row->set('notify_technician', false);
		}

		if ($this->policyTableHasAtivo() && isset($data['ativo'])) {
			$row->set('ativo', (bool)$data['ativo']);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function serializePolicy(EntityInterface $row): array {
		$st = $row->workflow_state ?? null;
		$toSt = $row->escalate_to_state ?? null;

		return [
			'id' => (int)$row->id,
			'empresa_id' => (int)$row->empresa_id,
			'idcliente' => $row->idcliente !== null ? (int)$row->idcliente : null,
			'contract_id' => $row->contract_id !== null ? (int)$row->contract_id : null,
			'contract_service_id' => $row->contract_service_id !== null ? (int)$row->contract_service_id : null,
			'problema_id' => $row->problema_id !== null ? (int)$row->problema_id : null,
			'queue_id' => $row->queue_id !== null ? (int)$row->queue_id : null,
			'support_level_id' => $row->support_level_id !== null ? (int)$row->support_level_id : null,
			'workflow_state_id' => (int)$row->workflow_state_id,
			'estado_nome' => $st ? (string)$st->nome : null,
			'estado_codigo' => $st ? (string)$st->codigo : null,
			'resposta_minutos' => $row->resposta_minutos !== null ? (int)$row->resposta_minutos : null,
			'resolucao_minutos' => $row->resolucao_minutos !== null ? (int)$row->resolucao_minutos : null,
			'pausa_sla' => (bool)$row->pausa_sla,
			'is_final' => (bool)$row->is_final,
			'auto_escalar' => (bool)$row->auto_escalar,
			'escalate_to_state_id' => $row->escalate_to_state_id !== null ? (int)$row->escalate_to_state_id : null,
			'escalate_to_nome' => $toSt ? (string)$toSt->nome : null,
			'escalate_to_queue_id' => $row->get('escalate_to_queue_id') !== null && $row->get('escalate_to_queue_id') !== '' ? (int)$row->escalate_to_queue_id : null,
			'escalate_to_support_level_id' => $row->get('escalate_to_support_level_id') !== null && $row->get('escalate_to_support_level_id') !== '' ? (int)$row->escalate_to_support_level_id : null,
			'escalate_after_minutos' => $row->escalate_after_minutos !== null ? (int)$row->escalate_after_minutos : 0,
			'notify_manager' => (bool)($row->get('notify_manager') ?? false),
			'notify_customer' => (bool)($row->get('notify_customer') ?? false),
			'notify_technician' => (bool)($row->get('notify_technician') ?? false),
			'ativo' => $this->policyTableHasAtivo() ? (bool)($row->get('ativo') ?? true) : true,
		];
	}

	/**
	 * @param array<string, array<string>> $errors
	 * @return string[]
	 */
	protected function flattenErrors(array $errors): array {
		$out = [];
		$walk = function ($node) use (&$walk, &$out): void {
			if (is_string($node)) {
				$out[] = $node;

				return;
			}
			if (!is_array($node)) {
				return;
			}
			foreach ($node as $child) {
				$walk($child);
			}
		};
		$walk($errors);

		return $out !== [] ? $out : ['Não foi possível salvar.'];
	}

	protected function friendlyDbError(PDOException $e): string {
		$m = $e->getMessage();
		if (stripos($m, 'ux_wf_sla_policy_scope') !== false || stripos($m, 'duplicate') !== false) {
			return 'Já existe política com a mesma combinação de escopo.';
		}

		return 'Erro ao gravar no banco.';
	}
}
