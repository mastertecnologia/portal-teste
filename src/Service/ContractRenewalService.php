<?php
namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Fluxo de renovação: pedido em contract_renewals e clonagem do contrato (contrato_pai_id).
 */
class ContractRenewalService {

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param int|null $userId
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function solicitarRenovacao($contract, $userId = null) {
		$table = TableRegistry::get('ContractRenewals');
		$existing = $table->find()
			->where([
				'contract_id' => $contract->get('id'),
				'status' => ['pendente', 'aprovada'],
			])
			->first();
		if ($existing) {
			return null;
		}

		$vf = $contract->get('end_date');
		$vfStr = ($vf instanceof \DateTimeInterface) ? $vf->format('Y-m-d') : (string)$vf;
		$novaFim = $vfStr !== '' ? date('Y-m-d', strtotime($vfStr . ' +1 year')) : null;

		$row = $table->newEntity([
			'contract_id' => $contract->get('id'),
			'status' => 'pendente',
			'solicitado_por' => $userId,
			'solicitado_em' => date('Y-m-d H:i:s'),
			'nova_vigencia_inicio' => date('Y-m-d'),
			'nova_vigencia_fim' => $novaFim,
			'novo_valor_mensal' => $contract->get('monthly_value'),
		]);

		$saved = $table->save($row);

		return $saved ?: null;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $renewal
	 * @param array $novos vigencia_inicio, vigencia_fim, valor_mensal (opcionais)
	 * @param int $userId
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function aprovarRenovacao($renewal, array $novos, $userId) {
		$contracts = TableRegistry::get('Contracts');
		$services = TableRegistry::get('ContractServices');
		$renewals = TableRegistry::get('ContractRenewals');

		$original = $contracts->get($renewal->get('contract_id'), [
			'contain' => ['ContractServices'],
		]);

		$novoData = $original->toArray();
		$this->stripNestedAssociations($novoData);
		unset($novoData['id'], $novoData['created'], $novoData['modified']);

		$novoData['code'] = $this->proximoNumero((int)$original->get('idempresa'));
		$novoData['status'] = 'rascunho';
		$novoData['contrato_pai_id'] = $original->get('id');
		$novoData['versao'] = 1;
		$novoData['start_date'] = $novos['vigencia_inicio'] ?? $renewal->get('nova_vigencia_inicio');
		$novoData['end_date'] = $novos['vigencia_fim'] ?? $renewal->get('nova_vigencia_fim');
		$novoData['monthly_value'] = $novos['valor_mensal'] ?? $renewal->get('novo_valor_mensal');

		foreach ([
			'autentique_doc_id', 'autentique_status', 'autentique_url',
			'pdf_path', 'signed_pdf_path', 'signed_file_url', 'signature_provider',
			'sent_for_signature_at', 'fully_signed_at',
			'aprovado_por', 'aprovado_em',
			'assinado_em', 'cancelado_em', 'motivo_cancelamento',
		] as $k) {
			$novoData[$k] = null;
		}

		$novo = $contracts->newEntity($novoData, ['validate' => false]);
		$saved = $contracts->save($novo);
		if (!$saved) {
			throw new \RuntimeException('Não foi possível gravar o novo contrato.');
		}

		foreach ($original->contract_services ?? [] as $svc) {
			$sd = $svc->toArray();
			unset($sd['id'], $sd['created'], $sd['modified']);
			$sd['contract_id'] = $saved->get('id');
			$se = $services->newEntity($sd, ['validate' => false]);
			$services->save($se);
		}

		$renewals->patchEntity($renewal, [
			'status' => 'aprovada',
			'novo_contract_id' => $saved->get('id'),
			'aprovado_por' => $userId,
			'aprovado_em' => date('Y-m-d H:i:s'),
		]);
		$renewals->save($renewal);

		$contracts->patchEntity($original, ['status' => 'em_renovacao']);
		$contracts->save($original);

		try {
			$forMail = $contracts->get($saved->get('id'), ['contain' => ['Clientes']]);
			$notif = new ContractNotificationService();
			$notif->notificarRenovacaoAprovada($forMail);
		} catch (\Throwable $e) {
		}

		return $saved;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	protected function stripNestedAssociations(array &$data) {
		foreach ([
			'cliente', 'empresa', 'contract_services', 'contract_documents',
			'contract_consumptions', 'invoices', 'attendance_histories',
			'contract_template', 'parent_contract', 'child_contracts',
			'contract_signatories', 'contract_autentique_logs',
			'contract_renewals', 'contract_notifications',
		] as $k) {
			unset($data[$k]);
		}
	}

	/**
	 * @param int $idempresa
	 * @return string
	 */
	public function proximoNumero($idempresa) {
		$contracts = TableRegistry::get('Contracts');
		$ultimo = $contracts->find()
			->select(['code'])
			->where(['idempresa' => $idempresa])
			->order(['Contracts.id' => 'DESC'])
			->first();
		$ano = date('Y');
		if (!$ultimo || !$ultimo->get('code')) {
			return 'CONT-0001/' . $ano;
		}
		$code = (string)$ultimo->get('code');
		if (preg_match('/(\d+)/', $code, $m)) {
			return sprintf('CONT-%04d/%s', (int)$m[1] + 1, $ano);
		}

		return 'CONT-0001/' . $ano;
	}

	/**
	 * Recusa pedido de renovação.
	 *
	 * @param \Cake\Datasource\EntityInterface $renewal
	 * @param string|null $obs
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function recusarRenovacao($renewal, $obs = null) {
		$table = TableRegistry::get('ContractRenewals');
		if ($renewal->get('status') !== 'pendente') {
			throw new \RuntimeException('Só é possível recusar renovação pendente.');
		}
		$patch = ['status' => 'recusada'];
		if ($obs !== null && $obs !== '') {
			$patch['observacoes'] = $obs;
		}
		$table->patchEntity($renewal, $patch);

		return $table->save($renewal);
	}
}
