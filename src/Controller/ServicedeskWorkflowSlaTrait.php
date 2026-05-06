<?php
namespace App\Controller;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * API JSON + página React para administração de workflow_states, workflow_transitions e workflow_sla_policies.
 * Usado por {@see ServicedeskController}.
 */
trait ServicedeskWorkflowSlaTrait {

	protected function _wfTechOr403(): bool {
		if (!$this->Auth->user() || (int)$this->Auth->user('role') !== 0) {
			$this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);

			return false;
		}

		return true;
	}

	protected function _wfSessionEmpresaId(): int {
		return (int)$this->Auth->user('idempresa');
	}

	protected function _wfPoliciesTable() {
		try {
			return TableRegistry::get('WorkflowSlaPolicies');
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function _wfStatesTable() {
		try {
			return TableRegistry::get('WorkflowStates');
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function _wfTransitionsTable() {
		try {
			return TableRegistry::get('WorkflowTransitions');
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function _wfSerializePolicy($row): array {
		if (!$row) {
			return [];
		}
		$eid = $row->get('empresa_id');
		$empresaNome = null;
		if ($eid !== null && $eid !== '') {
			$em = $row->get('empresa');
			$empresaNome = $em ? (string)($em->nomefantasia ?? $em->fantasia ?? $em->razaosocial ?? '') : null;
		}
		$st = $row->workflow_state ?? $row->workflow_states ?? null;
		$toSt = $row->escalate_to_state ?? $row->escalate_to_states ?? null;

		return [
			'id' => (int)$row->id,
			'empresa_id' => $eid === null || $eid === '' ? null : (int)$eid,
			'empresa_nome' => $empresaNome,
			'scope' => $eid === null || $eid === '' ? 'global' : 'empresa',
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
			'escalate_after_minutos' => $row->escalate_after_minutos !== null ? (int)$row->escalate_after_minutos : 0,
			'created_at' => $row->created_at ? $row->created_at->format('c') : null,
			'updated_at' => $row->updated_at ? $row->updated_at->format('c') : null,
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{0:bool,1:array<string>}
	 */
	protected function _wfValidatePolicyPayload(array $data, ?int $ignoreId = null): array {
		$errs = [];
		$global = !empty($data['is_global']);
		$eid = array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null;
		if (!$global && ($eid === null || $eid === '' || (int)$eid <= 0)) {
			$errs[] = 'empresa_obrigatoria';
		}
		if ($global) {
			$data['empresa_id'] = null;
		} else {
			$data['empresa_id'] = (int)$eid;
			if ($data['empresa_id'] !== $this->_wfSessionEmpresaId()) {
				$errs[] = 'empresa_invalida';
			}
		}
		$wfSid = (int)($data['workflow_state_id'] ?? 0);
		if ($wfSid <= 0) {
			$errs[] = 'workflow_state_obrigatorio';
		}
		$resp = $data['resposta_minutos'] ?? null;
		$reso = $data['resolucao_minutos'] ?? null;
		foreach (['resposta_minutos' => $resp, 'resolucao_minutos' => $reso] as $k => $v) {
			if ($v === null || $v === '') {
				continue;
			}
			if ((int)$v < 0) {
				$errs[] = $k . '_negativo';
			}
		}
		$auto = !empty($data['auto_escalar']);
		$escTo = isset($data['escalate_to_state_id']) ? (int)$data['escalate_to_state_id'] : 0;
		if ($auto && $escTo <= 0) {
			$errs[] = 'escalate_to_obrigatorio';
		}
		if ($auto && $escTo === $wfSid) {
			$errs[] = 'escalate_to_igual_origem';
		}
		if ($escTo > 0) {
			$stt = $this->_wfStatesTable();
			if ($stt) {
				$target = $stt->find()->where(['id' => $escTo])->first();
				if ($target && !empty($target->is_final) && !Configure::read('Workflow.allowEscalateToFinalInSlaPolicy', false)) {
					$errs[] = 'escalate_to_final_nao_permitido';
				}
			}
		}
		$after = isset($data['escalate_after_minutos']) ? (int)$data['escalate_after_minutos'] : 0;
		if ($after < 0) {
			$errs[] = 'escalate_after_negativo';
		}

		if ($errs !== []) {
			return [false, $errs];
		}
		$table = $this->_wfPoliciesTable();
		if ($table === null) {
			return [false, ['tabela_indisponivel']];
		}
		$q = $table->find()
			->where(['workflow_state_id' => $wfSid]);
		if ($global) {
			$q->where(['empresa_id IS' => null]);
		} else {
			$q->where(['empresa_id' => (int)$data['empresa_id']]);
		}
		if ($ignoreId !== null) {
			$q->where(['id !=' => $ignoreId]);
		}
		if ($q->first()) {
			return [false, ['duplicado_estado_empresa']];
		}

		return [true, []];
	}

	public function workflowSlaAdmin() {
		if (!$this->Auth->user() || (int)$this->Auth->user('role') !== 0) {
			return $this->redirect(['action' => 'index']);
		}
		$this->viewBuilder()->setLayout('servicedesk');
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Workflow & SLA');
		$this->set('hideLayoutPageTitle', true);
		$w = rtrim((string)$this->request->getAttribute('webroot'), '/') . '/';
		$this->set('reactAppExtraCss', [$w . 'dist/css/pages/pgm-servicedesk-premium.css']);
		$this->set('reactAppBreadcrumbs', [
			['title' => 'Service Desk', 'url' => ['action' => 'index'], 'options' => []],
			['title' => 'Workflow & SLA', 'url' => [], 'options' => ['class' => 'breadcrumb-item active']],
		]);
		$extra = $this->_servicedeskBootExtra();
		$sd = \Cake\Routing\Router::url(['controller' => 'Servicedesk', 'action' => 'index']);
		$this->set('reactBoot', $this->_reactBoot('tech_workflow_sla_admin', null, array_replace_recursive($extra, [
			'paths' => [
				'workflowSlaPolicies' => $w . 'servicedesk/workflow-sla',
				'workflowSlaPolicyBase' => $w . 'servicedesk/workflow-sla/',
				'workflowStates' => $w . 'servicedesk/workflow-states',
				'workflowSlaStates' => $w . 'servicedesk/workflow-states',
				'workflowTransitions' => $w . 'servicedesk/workflow-transitions',
				'workflowSlaTransitions' => $w . 'servicedesk/workflow-transitions',
				'workflowTransitionBase' => $w . 'servicedesk/workflow-transitions/',
				'workflowSlaLogs' => $w . 'servicedesk/workflow-sla-logs',
				'workflowSlaEmpresas' => $w . 'servicedesk/workflow-sla-empresas',
				'servicedeskUrl' => $sd,
			],
		])));
	}

	public function workflowSla() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$id = $this->request->getParam('id');
		$table = $this->_wfPoliciesTable();
		if ($table === null) {
			if ($id === null || $id === '') {
				if ($this->request->is('get')) {
					return $this->jsonResponse(['ok' => true, 'policies' => [], 'prioridade' => '']);
				}

				return $this->jsonResponse(['ok' => false, 'errors' => ['tabela_indisponivel']], 422);
			}
			if ($this->request->is('get')) {
				return $this->jsonResponse(['ok' => true, 'policy' => null]);
			}

			return $this->jsonResponse(['ok' => false, 'errors' => ['tabela_indisponivel']], 422);
		}
		$eidSession = $this->_wfSessionEmpresaId();

		if ($id === null || $id === '') {
			if ($this->request->is('get')) {
				try {
					$q = trim((string)$this->request->getQuery('q', ''));
					$fEmp = $this->request->getQuery('empresa_id');
					$fState = $this->request->getQuery('workflow_state_id');
					$fAuto = $this->request->getQuery('auto_escalar');
					$fPausa = $this->request->getQuery('pausa_sla');
					$fFinal = $this->request->getQuery('is_final');

					$query = $table->find()
						->contain(['WorkflowStates', 'Empresas', 'EscalateToStates'])
						->where([
							'OR' => [
								['WorkflowSlaPolicies.empresa_id' => $eidSession],
								['WorkflowSlaPolicies.empresa_id IS' => null],
							],
						]);
					if ($fEmp !== null && $fEmp !== '' && $fEmp !== 'all') {
						if ($fEmp === 'global') {
							$query->where(['WorkflowSlaPolicies.empresa_id IS' => null]);
						} elseif (ctype_digit((string)$fEmp)) {
							$query->where(['WorkflowSlaPolicies.empresa_id' => (int)$fEmp]);
						}
					}
					if ($fState !== null && $fState !== '' && ctype_digit((string)$fState)) {
						$query->where(['WorkflowSlaPolicies.workflow_state_id' => (int)$fState]);
					}
					if ($fAuto === '1' || $fAuto === '0') {
						$query->where(['WorkflowSlaPolicies.auto_escalar' => $fAuto === '1']);
					}
					if ($fPausa === '1' || $fPausa === '0') {
						$query->where(['WorkflowSlaPolicies.pausa_sla' => $fPausa === '1']);
					}
					if ($fFinal === '1' || $fFinal === '0') {
						$query->where(['WorkflowSlaPolicies.is_final' => $fFinal === '1']);
					}
					if ($q !== '') {
						$qq = '%' . addcslashes($q, '%_\\') . '%';
						$query->where([
							'OR' => [
								'WorkflowStates.nome LIKE' => $qq,
								'WorkflowStates.codigo LIKE' => $qq,
								'Empresas.nomefantasia LIKE' => $qq,
								'Empresas.razaosocial LIKE' => $qq,
							],
						]);
					}
					$rows = $query->order(['WorkflowSlaPolicies.empresa_id' => 'DESC', 'WorkflowSlaPolicies.id' => 'ASC'])->all();
					$list = [];
					foreach ($rows as $row) {
						$list[] = $this->_wfSerializePolicy($row);
					}

					return $this->jsonResponse(['ok' => true, 'policies' => $list, 'prioridade' => 'Regras da empresa vigente na sessão sobrescrevem regras globais (empresa_id nulo).']);
				} catch (\Throwable $e) {
					Log::warning('workflowSla GET list: ' . $e->getMessage());

					return $this->jsonResponse(['ok' => true, 'policies' => [], 'prioridade' => '']);
				}
			}
			if ($this->request->is('post')) {
				$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
				[$ok, $errs] = $this->_wfValidatePolicyPayload($body, null);
				if (!$ok) {
					return $this->jsonResponse(['ok' => false, 'errors' => $errs], 422);
				}
				$ent = $table->newEntity([
					'empresa_id' => !empty($body['is_global']) ? null : (int)$body['empresa_id'],
					'workflow_state_id' => (int)$body['workflow_state_id'],
					'resposta_minutos' => $body['resposta_minutos'] ?? null,
					'resolucao_minutos' => $body['resolucao_minutos'] ?? null,
					'pausa_sla' => !empty($body['pausa_sla']),
					'is_final' => !empty($body['is_final']),
					'auto_escalar' => !empty($body['auto_escalar']),
					'escalate_to_state_id' => !empty($body['escalate_to_state_id']) ? (int)$body['escalate_to_state_id'] : null,
					'escalate_after_minutos' => isset($body['escalate_after_minutos']) ? (int)$body['escalate_after_minutos'] : 0,
					'created_at' => FrozenTime::now(),
					'updated_at' => FrozenTime::now(),
				]);
				if (!$table->save($ent)) {
					return $this->jsonResponse(['ok' => false, 'errors' => $ent->getErrors()], 422);
				}
				$ent = $table->get($ent->id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

				return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($ent)], 201);
			}
		} else {
			$id = (int)$id;
			if ($this->request->is('get')) {
				try {
					$row = $table->get($id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
				}
				$eid = $row->empresa_id;
				if ($eid !== null && (int)$eid !== $eidSession) {
					return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
				}

				return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($row)]);
			}
			if ($this->request->is(['patch', 'put', 'post'])) {
				try {
					$row = $table->get($id);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
				}
				$eid = $row->empresa_id;
				if ($eid !== null && (int)$eid !== $eidSession) {
					return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
				}
				$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
				$merged = array_merge($row->toArray(), $body);
				[$ok, $errs] = $this->_wfValidatePolicyPayload($merged, $id);
				if (!$ok) {
					return $this->jsonResponse(['ok' => false, 'errors' => $errs], 422);
				}
				$table->patchEntity($row, [
					'empresa_id' => !empty($body['is_global']) ? null : (int)($body['empresa_id'] ?? $row->empresa_id),
					'workflow_state_id' => (int)($body['workflow_state_id'] ?? $row->workflow_state_id),
					'resposta_minutos' => array_key_exists('resposta_minutos', $body) ? $body['resposta_minutos'] : $row->resposta_minutos,
					'resolucao_minutos' => array_key_exists('resolucao_minutos', $body) ? $body['resolucao_minutos'] : $row->resolucao_minutos,
					'pausa_sla' => array_key_exists('pausa_sla', $body) ? (bool)$body['pausa_sla'] : $row->pausa_sla,
					'is_final' => array_key_exists('is_final', $body) ? (bool)$body['is_final'] : $row->is_final,
					'auto_escalar' => array_key_exists('auto_escalar', $body) ? (bool)$body['auto_escalar'] : $row->auto_escalar,
					'escalate_to_state_id' => array_key_exists('escalate_to_state_id', $body) ? $body['escalate_to_state_id'] : $row->escalate_to_state_id,
					'escalate_after_minutos' => array_key_exists('escalate_after_minutos', $body) ? (int)$body['escalate_after_minutos'] : $row->escalate_after_minutos,
					'updated_at' => FrozenTime::now(),
				]);
				if (!$table->save($row)) {
					return $this->jsonResponse(['ok' => false, 'errors' => $row->getErrors()], 422);
				}
				$row = $table->get($id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

				return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($row)]);
			}
			if ($this->request->is('delete')) {
				try {
					$row = $table->get($id);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
				}
				$eid = $row->empresa_id;
				if ($eid !== null && (int)$eid !== $eidSession) {
					return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
				}
				if ($table->delete($row)) {
					return $this->jsonResponse(['ok' => true]);
				}

				return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
			}
		}
		$this->jsonResponse(['ok' => false, 'error' => 'method'], 405);
	}

	public function workflowStates() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$table = $this->_wfStatesTable();
		if ($table === null) {
			return $this->jsonResponse(['ok' => true, 'states' => []]);
		}
		$rows = $table->find()->order(['nome' => 'ASC'])->all();
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id' => (int)$r->id,
				'nome' => (string)$r->nome,
				'codigo' => (string)$r->codigo,
				'is_inicial' => (bool)$r->is_inicial,
				'is_final' => (bool)$r->is_final,
			];
		}

		return $this->jsonResponse(['ok' => true, 'states' => $out]);
	}

	public function workflowTransitions() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$table = $this->_wfTransitionsTable();
		if ($table === null) {
			if ($this->request->is('get')) {
				return $this->jsonResponse(['ok' => true, 'transitions' => []]);
			}

			return $this->jsonResponse(['ok' => false, 'errors' => ['tabela_indisponivel']], 422);
		}
		$eidSession = $this->_wfSessionEmpresaId();
		if ($this->request->is('get')) {
			$stateNames = [];
			$stTable = $this->_wfStatesTable();
			if ($stTable !== null) {
				foreach ($stTable->find()->select(['id', 'nome'])->all() as $st) {
					$stateNames[(int)$st->id] = (string)$st->nome;
				}
			}
			$rows = $table->find()
				->where([
					'OR' => [
						['WorkflowTransitions.empresa_id' => $eidSession],
						['WorkflowTransitions.empresa_id IS' => null],
					],
				])
				->order(['WorkflowTransitions.id' => 'ASC'])
				->all();
			$out = [];
			foreach ($rows as $r) {
				$eid = $r->empresa_id;
				$fid = (int)$r->from_state_id;
				$tid = (int)$r->to_state_id;
				$out[] = [
					'id' => (int)$r->id,
					'from_state_id' => $fid,
					'to_state_id' => $tid,
					'from_nome' => $stateNames[$fid] ?? null,
					'to_nome' => $stateNames[$tid] ?? null,
					'empresa_id' => $eid === null || $eid === '' ? null : (int)$eid,
					'scope' => $eid === null || $eid === '' ? 'global' : 'empresa',
				];
			}

			return $this->jsonResponse(['ok' => true, 'transitions' => $out]);
		}
		if ($this->request->is('post')) {
			$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
			$from = (int)($body['from_state_id'] ?? 0);
			$to = (int)($body['to_state_id'] ?? 0);
			$isGlobal = !empty($body['is_global']);
			$empresaId = $isGlobal ? null : $eidSession;
			if ($from <= 0 || $to <= 0 || $from === $to) {
				return $this->jsonResponse(['ok' => false, 'errors' => ['transicao_invalida']], 422);
			}
			$dupQ = $table->find()->where(['from_state_id' => $from, 'to_state_id' => $to]);
			if ($isGlobal) {
				$dupQ->where(['empresa_id IS' => null]);
			} else {
				$dupQ->where(['empresa_id' => $eidSession]);
			}
			$dup = $dupQ->first();
			if ($dup) {
				return $this->jsonResponse(['ok' => false, 'errors' => ['duplicado']], 422);
			}
			$ent = $table->newEntity([
				'from_state_id' => $from,
				'to_state_id' => $to,
				'empresa_id' => $empresaId,
				'created_at' => FrozenTime::now(),
			]);
			if (!$table->save($ent)) {
				return $this->jsonResponse(['ok' => false, 'errors' => $ent->getErrors()], 422);
			}
			$ent = $table->get($ent->id);

			return $this->jsonResponse([
				'ok' => true,
				'transition' => [
					'id' => (int)$ent->id,
					'from_state_id' => (int)$ent->from_state_id,
					'to_state_id' => (int)$ent->to_state_id,
					'empresa_id' => $ent->empresa_id !== null ? (int)$ent->empresa_id : null,
					'scope' => $ent->empresa_id ? 'empresa' : 'global',
				],
			], 201);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'method'], 405);
	}

	public function workflowTransition() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$id = (int)$this->request->getParam('id');
		if ($id <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'bad_request'], 400);
		}
		$table = $this->_wfTransitionsTable();
		if ($table === null) {
			return $this->jsonResponse(['ok' => false, 'errors' => ['tabela_indisponivel']], 422);
		}
		$eidSession = $this->_wfSessionEmpresaId();
		try {
			$row = $table->get($id);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		$eid = $row->empresa_id;
		if ($eid !== null && (int)$eid !== $eidSession) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if ($this->request->is('delete')) {
			if ($table->delete($row)) {
				return $this->jsonResponse(['ok' => true]);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
		}
		if ($this->request->is(['patch', 'put', 'post'])) {
			$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
			$from = (int)($body['from_state_id'] ?? $row->from_state_id);
			$to = (int)($body['to_state_id'] ?? $row->to_state_id);
			$isGlobal = array_key_exists('is_global', $body) ? !empty($body['is_global']) : ($row->empresa_id === null);
			$empresaId = $isGlobal ? null : $eidSession;
			if ($from <= 0 || $to <= 0 || $from === $to) {
				return $this->jsonResponse(['ok' => false, 'errors' => ['transicao_invalida']], 422);
			}
			$dupQ = $table->find()->where([
				'id !=' => $id,
				'from_state_id' => $from,
				'to_state_id' => $to,
			]);
			if ($isGlobal) {
				$dupQ->where(['empresa_id IS' => null]);
			} else {
				$dupQ->where(['empresa_id' => $eidSession]);
			}
			$dup = $dupQ->first();
			if ($dup) {
				return $this->jsonResponse(['ok' => false, 'errors' => ['duplicado']], 422);
			}
			$row->set('from_state_id', $from);
			$row->set('to_state_id', $to);
			$row->set('empresa_id', $empresaId);
			if (!$table->save($row)) {
				return $this->jsonResponse(['ok' => false, 'errors' => $row->getErrors()], 422);
			}

			return $this->jsonResponse(['ok' => true, 'transition' => [
				'id' => (int)$row->id,
				'from_state_id' => (int)$row->from_state_id,
				'to_state_id' => (int)$row->to_state_id,
				'empresa_id' => $row->empresa_id !== null ? (int)$row->empresa_id : null,
				'scope' => $row->empresa_id ? 'empresa' : 'global',
			]]);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'method'], 405);
	}

	public function workflowSlaLogs() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		try {
			$logs = TableRegistry::get('WorkflowSlaEscalationLogs');
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => true, 'logs' => []]);
		}
		$eidSession = $this->_wfSessionEmpresaId();
		$limit = (int)$this->request->getQuery('limit', 80);
		$limit = max(1, min(200, $limit));
		$rows = $logs->find()
			->where(['empresa_id' => $eidSession])
			->order(['created_at' => 'DESC', 'id' => 'DESC'])
			->limit($limit)
			->all();
		$out = [];
		foreach ($rows as $r) {
			$out[] = [
				'id' => (int)$r->id,
				'ticket_id' => (int)$r->ticket_id,
				'empresa_id' => (int)$r->empresa_id,
				'workflow_state_from' => $r->workflow_state_from !== null ? (int)$r->workflow_state_from : null,
				'workflow_state_to' => $r->workflow_state_to !== null ? (int)$r->workflow_state_to : null,
				'reason_code' => $r->reason_code,
				'created_at' => $r->created_at ? $r->created_at->format('c') : null,
			];
		}

		return $this->jsonResponse(['ok' => true, 'logs' => $out]);
	}

	public function workflowSlaDuplicate() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$id = (int)$this->request->getParam('id');
		if ($id <= 0 || !$this->request->is('post')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'bad_request'], 400);
		}
		$table = $this->_wfPoliciesTable();
		if ($table === null) {
			return $this->jsonResponse(['ok' => false, 'error' => 'schema'], 500);
		}
		$eidSession = $this->_wfSessionEmpresaId();
		try {
			$row = $table->get($id);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		$eid = $row->empresa_id;
		if ($eid !== null && (int)$eid !== $eidSession) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
		$newStateId = isset($body['workflow_state_id']) ? (int)$body['workflow_state_id'] : (int)$row->workflow_state_id;
		if ($newStateId <= 0) {
			return $this->jsonResponse(['ok' => false, 'errors' => ['workflow_state_obrigatorio']], 422);
		}
		$copy = $table->newEntity([
			'empresa_id' => $row->empresa_id,
			'workflow_state_id' => $newStateId,
			'resposta_minutos' => $row->resposta_minutos,
			'resolucao_minutos' => $row->resolucao_minutos,
			'pausa_sla' => (bool)$row->pausa_sla,
			'is_final' => (bool)$row->is_final,
			'auto_escalar' => (bool)$row->auto_escalar,
			'escalate_to_state_id' => $row->escalate_to_state_id,
			'escalate_after_minutos' => $row->escalate_after_minutos,
			'created_at' => FrozenTime::now(),
			'updated_at' => FrozenTime::now(),
		]);
		if (!$table->save($copy)) {
			$err = $copy->getErrors();

			return $this->jsonResponse(['ok' => false, 'errors' => $err !== [] ? $err : ['duplicado_estado_empresa']], 422);
		}
		$copy = $table->get($copy->id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

		return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($copy)], 201);
	}

	public function workflowSlaEmpresasOptions() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		try {
			$eidSession = $this->_wfSessionEmpresaId();
			$rows = $this->Empresas->find()
				->select(['id', 'nomefantasia', 'razaosocial'])
				->where(['id' => $eidSession])
				->order(['nomefantasia' => 'ASC', 'razaosocial' => 'ASC'])
				->all();
			$out = [];
			foreach ($rows as $r) {
				$nf = trim((string)($r->nomefantasia ?? ''));
				$rz = trim((string)($r->razaosocial ?? ''));
				$label = $nf !== '' ? $nf : ($rz !== '' ? $rz : ('Empresa #' . $r->id));
				$out[] = [
					'id' => (int)$r->id,
					'label' => $label,
					'nome' => $label,
				];
			}

			return $this->jsonResponse(['ok' => true, 'empresas' => $out]);
		} catch (\Throwable $e) {
			Log::warning('workflowSlaEmpresasOptions: ' . $e->getMessage());

			return $this->jsonResponse(['ok' => true, 'empresas' => []]);
		}
	}
}
