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

	/**
	 * Id de política na URL (/workflow-sla/:id). Só inteiros > 0; caso contrário null (lista/POST em …/workflow-sla-policies).
	 * Sem isso, id 0 ou false fazia cair no ramo de recurso único e POST de criação devolvia 404 not_found.
	 */
	protected function _wfWorkflowSlaRequestPolicyId(): ?int {
		$raw = $this->request->getParam('id');
		if ($raw === null || $raw === '' || $raw === false) {
			return null;
		}
		if (!is_numeric($raw)) {
			return null;
		}
		$n = (int)$raw;
		if ($n <= 0) {
			return null;
		}

		return $n;
	}

	protected function _wfSessionEmpresaId(): int {
		return (int)$this->Auth->user('idempresa');
	}

	/**
	 * IDs permitidos por WORKFLOW_EMPRESAS (env + config/workflow.php).
	 * Vazio = sem filtro (todas as empresas ativas na UI).
	 *
	 * @return array<int,int>
	 */
	protected function _wfWorkflowEmpresaFilterIds(): array {
		$ids = [];
		$from = Configure::read('Workflow.enabledEmpresas');
		if (is_string($from) && trim($from) !== '') {
			foreach (explode(',', $from) as $part) {
				$i = (int)trim($part);
				if ($i > 0) {
					$ids[] = $i;
				}
			}
		} elseif (is_array($from)) {
			foreach ($from as $v) {
				$i = (int)$v;
				if ($i > 0) {
					$ids[] = $i;
				}
			}
		}
		if ($ids !== []) {
			return array_values(array_unique($ids));
		}
		$raw = env('WORKFLOW_EMPRESAS', '');
		if (!is_string($raw) || trim($raw) === '') {
			return [];
		}
		foreach (explode(',', $raw) as $part) {
			$i = (int)trim($part);
			if ($i > 0) {
				$ids[] = $i;
			}
		}

		return array_values(array_unique($ids));
	}

	/**
	 * Filtros ORM "empresa ativa" a tentar em ordem (PostgreSQL boolean vs int não podem ir no mesmo OR).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function _wfEmpresaWhereAtivoAttempts(): array {
		return [
			[
				'OR' => [
					['inativa IS' => null],
					['inativa' => false],
				],
			],
			[
				'OR' => [
					['inativa IS' => null],
					['inativa' => 0],
					['inativa' => '0'],
				],
			],
		];
	}

	/**
	 * Rótulo COALESCE(nomefantasia, razaosocial) em PHP (evita SQL dialect).
	 *
	 * @param array<string,mixed> $r
	 */
	protected function _wfEmpresaLabelFromRow(array $r): string {
		$nf = trim((string)($r['nomefantasia'] ?? ''));
		$rz = trim((string)($r['razaosocial'] ?? ''));

		return $nf !== '' ? $nf : ($rz !== '' ? $rz : ('Empresa #' . (int)($r['id'] ?? 0)));
	}

	/**
	 * Linhas de empresas para o select SLA (ativas + opcional WORKFLOW_EMPRESAS).
	 *
	 * @param array<int,int> $enabledConfigured
	 * @return array<int,array<string,mixed>>
	 */
	protected function _wfEmpresaRowsForWorkflowSlaDropdown(array $enabledConfigured): array {
		$enabledConfigured = array_values(array_filter(array_map('intval', $enabledConfigured), function ($id) {
			return $id > 0;
		}));
		$attempts = $this->_wfEmpresaWhereAtivoAttempts();
		$all = [];
		foreach ($attempts as $idx => $where) {
			try {
				$q = $this->Empresas->find()
					->enableHydration(false)
					->select(['id', 'nomefantasia', 'razaosocial', 'inativa'])
					->where($where);
				if ($enabledConfigured !== []) {
					$q->where(['id IN' => $enabledConfigured]);
				}
				$candidate = $q->all()->toArray();
				if ($candidate !== []) {
					$all = $candidate;
					break;
				}
			} catch (\Throwable $e) {
				Log::warning('workflowSla empresas where attempt ' . (string)$idx . ': ' . $e->getMessage());
			}
		}
		if ($all === [] && $enabledConfigured !== []) {
			Log::warning('workflowSlaEmpresasOptions: nenhuma empresa no recorte WORKFLOW_EMPRESAS; repetindo sem filtro de IDs.');
			foreach ($attempts as $idx => $where) {
				try {
					$q = $this->Empresas->find()
						->enableHydration(false)
						->select(['id', 'nomefantasia', 'razaosocial', 'inativa'])
						->where($where);
					$candidate = $q->all()->toArray();
					if ($candidate !== []) {
						$all = $candidate;
						break;
					}
				} catch (\Throwable $e) {
					Log::warning('workflowSla empresas retry attempt ' . (string)$idx . ': ' . $e->getMessage());
				}
			}
		}
		if ($all === []) {
			Log::warning('workflowSlaEmpresasOptions: critérios inativa falharam ou vazios; listando cadastro bruto (limite 500).');
			try {
				$all = $this->Empresas->find()
					->enableHydration(false)
					->select(['id', 'nomefantasia', 'razaosocial', 'inativa'])
					->order(['id' => 'ASC'])
					->limit(500)
					->all()
					->toArray();
			} catch (\Throwable $e) {
				Log::error('workflowSlaEmpresasOptions fallback bruto: ' . $e->getMessage());
				$all = [];
			}
		}
		usort($all, function (array $a, array $b): int {
			$la = $this->_wfEmpresaLabelFromRow($a);
			$lb = $this->_wfEmpresaLabelFromRow($b);

			return strcasecmp($la, $lb);
		});

		return $all;
	}

	/**
	 * Empresas ativas elegíveis no CRUD de políticas SLA (admin interno).
	 * Não restringe por WORKFLOW_EMPRESAS: políticas e tickets podem existir para qualquer empresa ativa.
	 *
	 * @return array<int,int>
	 */
	protected function _wfSlaAdminSelectableEmpresaIds(): array {
		try {
			$activeIds = [];
			foreach ($this->_wfEmpresaWhereAtivoAttempts() as $idx => $where) {
				try {
					$q = $this->Empresas->find()
						->enableHydration(false)
						->select(['id'])
						->where($where);
					foreach ($q->all() as $r) {
						$activeIds[] = (int)$r['id'];
					}
					if ($activeIds !== []) {
						break;
					}
				} catch (\Throwable $e) {
					Log::warning('Workflow SLA admin selectable ids attempt ' . (string)$idx . ': ' . $e->getMessage());
				}
			}
			if ($activeIds === []) {
				try {
					foreach ($this->Empresas->find()->enableHydration(false)->select(['id'])->order(['id' => 'ASC'])->limit(500)->all() as $r) {
						$activeIds[] = (int)$r['id'];
					}
					if ($activeIds !== []) {
						Log::warning('Workflow SLA admin: critérios inativa vazios; usando todas as cadastradas (limite 500).');
					}
				} catch (\Throwable $e) {
					Log::warning('Workflow SLA admin fallback ids: ' . $e->getMessage());
				}
			}
			sort($activeIds);

			return array_values(array_unique($activeIds));
		} catch (\Throwable $e) {
			Log::warning('Workflow SLA admin _wfSlaAdminSelectableEmpresaIds: ' . $e->getMessage());

			return [];
		}
	}

	/**
	 * Técnico pode ver/editar policy desta empresa (ou global) na admin SLA.
	 */
	protected function _wfPolicyEmpresaAllowedForAdmin(?int $empresaId): bool {
		if ($empresaId === null) {
			return true;
		}
		$allowed = $this->_wfSlaAdminSelectableEmpresaIds();

		return in_array((int)$empresaId, $allowed, true);
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

	protected function _wfWorkflowStateNome(int $stateId): string {
		if ($stateId <= 0) {
			return '';
		}
		$stt = $this->_wfStatesTable();
		if ($stt === null) {
			return '';
		}
		$r = $stt->find()->select(['nome'])->where(['id' => $stateId])->first();

		return $r ? trim((string)$r->nome) : '';
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
			'escalate_to_queue_id' => $row->get('escalate_to_queue_id') !== null && $row->get('escalate_to_queue_id') !== '' ? (int)$row->escalate_to_queue_id : null,
			'escalate_to_support_level_id' => $row->get('escalate_to_support_level_id') !== null && $row->get('escalate_to_support_level_id') !== '' ? (int)$row->escalate_to_support_level_id : null,
			'notify_manager' => (bool)($row->get('notify_manager') ?? false),
			'notify_customer' => (bool)($row->get('notify_customer') ?? false),
			'notify_technician' => (bool)($row->get('notify_technician') ?? false),
			'created_at' => $row->created_at ? $row->created_at->format('c') : null,
			'updated_at' => $row->updated_at ? $row->updated_at->format('c') : null,
		];
	}

	/**
	 * Mensagens legíveis para códigos de validação de política SLA (UI + API).
	 *
	 * @param array<int|string> $codes
	 * @return array<int,string>
	 */
	protected function _wfPolicyValidationErrorMessages(array $codes): array {
		$map = [
			'empresa_obrigatoria' => 'Selecione a empresa ou marque regra global.',
			'empresa_invalida' => 'Empresa inválida ou inativa para esta operação.',
			'workflow_state_obrigatorio' => 'Selecione o estado do workflow.',
			'resposta_minutos_negativo' => 'Minutos de resposta não podem ser negativos.',
			'resolucao_minutos_negativo' => 'Minutos de resolução não podem ser negativos.',
			'escalate_to_obrigatorio' => 'Com auto-escalar ativo, informe estado de destino, fila, nível e/ou ao menos uma notificação.',
			'escalate_to_igual_origem' => 'O destino do escalonamento não pode ser o mesmo estado atual.',
			'escalate_to_final_nao_permitido' => 'Não é permitido escalar para um estado final.',
			'escalate_to_transicao_invalida' => 'Não existe transição de workflow do estado atual para o destino escolhido (global ou da empresa).',
			'escalate_after_negativo' => 'Tolerância após vencimento não pode ser negativa.',
			'estado_final_auto_escalar_conflito' => 'Um estado final não pode auto-escalar. Desmarque Estado final ou Auto-escalar.',
			'duplicado_estado_empresa' => 'Já existe política para esta empresa e estado.',
			'duplicado_estado_global' => 'Já existe política global para este estado.',
			'tabela_indisponivel' => 'Tabela de políticas indisponível no servidor.',
		];
		$out = [];
		foreach ($codes as $c) {
			$key = is_string($c) ? $c : (string)$c;
			$out[] = $map[$key] ?? $key;
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $entityErrors Resultado de `Entity::getErrors()`.
	 * @return array<string,mixed>
	 */
	protected function _wfPolicySaveErrorResponse(array $entityErrors): array {
		$msgs = [];
		foreach ($entityErrors as $rules) {
			if (!is_array($rules)) {
				continue;
			}
			foreach ($rules as $msg) {
				if (is_string($msg)) {
					$msgs[] = $msg;
				}
			}
		}

		return [
			'ok' => false,
			'errors' => $entityErrors,
			'error_messages' => $msgs !== [] ? $msgs : ['Não foi possível gravar a política.'],
		];
	}

	/**
	 * @param array<int|string> $errs
	 * @return array<string,mixed>
	 */
	protected function _wfPolicyValidationErrorResponse(array $errs): array {
		return [
			'ok' => false,
			'errors' => $errs,
			'error_messages' => $this->_wfPolicyValidationErrorMessages($errs),
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{0:bool,1:array<string>}
	 */
	protected function _wfValidatePolicyPayload(array $data, ?int $ignoreId = null): array {
		$errs = [];
		if (array_key_exists('is_global', $data)) {
			$global = !empty($data['is_global']);
		} else {
			$eidInfer = array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null;
			$global = ($eidInfer === null || $eidInfer === '');
		}
		$eid = array_key_exists('empresa_id', $data) ? $data['empresa_id'] : null;
		if (!$global && ($eid === null || $eid === '' || (int)$eid <= 0)) {
			$errs[] = 'empresa_obrigatoria';
		}
		if ($global) {
			$data['empresa_id'] = null;
		} else {
			$data['empresa_id'] = (int)$eid;
			$allowed = $this->_wfSlaAdminSelectableEmpresaIds();
			if ($allowed === [] || !in_array((int)$data['empresa_id'], $allowed, true)) {
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
		$isFinal = !empty($data['is_final']);
		if ($auto && $isFinal) {
			$errs[] = 'estado_final_auto_escalar_conflito';
		}
		$escTo = 0;
		if ($auto) {
			$escTo = isset($data['escalate_to_state_id']) ? (int)$data['escalate_to_state_id'] : 0;
		}
		$escQueue = $auto ? (int)($data['escalate_to_queue_id'] ?? 0) : 0;
		$escLevel = $auto ? (int)($data['escalate_to_support_level_id'] ?? 0) : 0;
		$hasNotify = $auto && (!empty($data['notify_manager']) || !empty($data['notify_customer']) || !empty($data['notify_technician']));
		$hasRoute = $escTo > 0 || $escQueue > 0 || $escLevel > 0;
		if ($auto && !$hasRoute && !$hasNotify) {
			$errs[] = 'escalate_to_obrigatorio';
		}
		if ($auto && $escTo > 0 && $escTo === $wfSid) {
			$errs[] = 'escalate_to_igual_origem';
		}
		/*
		 * Estado final como destino: permitido por padrão sempre que houver transição em workflow_transitions
		 * (ex.: Em execução → Resolvido). Bloqueio só ocorre se Workflow.disallowEscalateToFinalInSlaPolicy=true (opt-in).
		 * Mantém Workflow.allowEscalateToFinalInSlaPolicy como override legado.
		 */
		if ($escTo > 0) {
			$stt = $this->_wfStatesTable();
			$blockFinal = (bool)Configure::read('Workflow.disallowEscalateToFinalInSlaPolicy', false);
			$legacyAllow = (bool)Configure::read('Workflow.allowEscalateToFinalInSlaPolicy', true);
			if ($stt && $blockFinal && !$legacyAllow) {
				$target = $stt->find()->where(['id' => $escTo])->first();
				if ($target && !empty($target->is_final)) {
					$errs[] = 'escalate_to_final_nao_permitido';
				}
			}
		}
		if ($auto && $escTo > 0 && $errs === []) {
			$transTable = $this->_wfTransitionsTable();
			if ($transTable !== null) {
				$tq = $transTable->find()
					->where([
						'from_state_id' => $wfSid,
						'to_state_id' => $escTo,
					]);
				if ($global) {
					$tq->where(['empresa_id IS' => null]);
				} else {
					$empPol = (int)$data['empresa_id'];
					$tq->where([
						'OR' => [
							['empresa_id IS' => null],
							['empresa_id' => $empPol],
						],
					]);
				}
				if (!$tq->first()) {
					$fn = $this->_wfWorkflowStateNome($wfSid);
					$tn = $this->_wfWorkflowStateNome($escTo);
					$errs[] = sprintf(
						'Não existe transição configurada de %s para %s.',
						$fn !== '' ? $fn : ('estado #' . $wfSid),
						$tn !== '' ? $tn : ('estado #' . $escTo)
					);
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
			return [false, [$global ? 'duplicado_estado_global' : 'duplicado_estado_empresa']];
		}

		return [true, []];
	}

	public function workflowSlaAdmin() {
		if (!$this->Auth->user() || (int)$this->Auth->user('role') !== 0) {
			return $this->redirect(['action' => 'index']);
		}
		/* Mesmo shell do ERP que Histórico/Operacional (layout default + turbo-frame), não o layout full-page servicedesk. */
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Workflow & SLA');
		$this->set('hideLayoutPageTitle', true);
		$this->set('hideServicedeskOpenTicketCta', true);
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
				'workflowSlaPolicies' => $w . 'servicedesk/workflow-sla-policies',
				'workflowSlaPolicyBase' => $w . 'servicedesk/workflow-sla/',
				'workflowStates' => $w . 'servicedesk/workflow-states',
				'workflowSlaStates' => $w . 'servicedesk/workflow-states',
				'workflowStateBase' => $w . 'servicedesk/workflow-states/',
				'workflowTransitions' => $w . 'servicedesk/workflow-transitions',
				'workflowSlaTransitions' => $w . 'servicedesk/workflow-transitions',
				'workflowTransitionBase' => $w . 'servicedesk/workflow-transitions/',
				'workflowSlaLogs' => $w . 'servicedesk/workflow-sla-logs',
				'workflowSlaEmpresas' => $w . 'servicedesk/workflow-sla-empresas',
				'servicedeskUrl' => $sd,
			],
		])));
	}

	/**
	 * GET lista + POST criar em /servicedesk/workflow-sla-policies (alinha ao DashedRoute: workflow-sla-policies → workflowSlaPolicies).
	 */
	public function workflowSlaPolicies() {
		return $this->workflowSla();
	}

	public function workflowSla() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$id = $this->_wfWorkflowSlaRequestPolicyId();
		$table = $this->_wfPoliciesTable();
		if ($table === null) {
			if ($id === null) {
				if ($this->request->is('get')) {
					return $this->jsonResponse(['ok' => true, 'policies' => [], 'prioridade' => '']);
				}

				return $this->jsonResponse($this->_wfPolicyValidationErrorResponse(['tabela_indisponivel']), 422);
			}
			if ($this->request->is('get')) {
				return $this->jsonResponse(['ok' => true, 'policy' => null]);
			}

			return $this->jsonResponse($this->_wfPolicyValidationErrorResponse(['tabela_indisponivel']), 422);
		}
		if ($id === null) {
			if ($this->request->is('get')) {
				try {
					$q = trim((string)$this->request->getQuery('q', ''));
					$fEmp = $this->request->getQuery('empresa_id');
					$fState = $this->request->getQuery('workflow_state_id');
					$fAuto = $this->request->getQuery('auto_escalar');
					$fPausa = $this->request->getQuery('pausa_sla');
					$fFinal = $this->request->getQuery('is_final');

					$query = $table->find()
						->contain(['WorkflowStates', 'Empresas', 'EscalateToStates']);
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

					return $this->jsonResponse(['ok' => true, 'policies' => $list, 'prioridade' => 'Regras por empresa sobrescrevem regras globais (empresa_id nulo).']);
				} catch (\Throwable $e) {
					Log::warning('workflowSla GET list: ' . $e->getMessage());

					return $this->jsonResponse(['ok' => true, 'policies' => [], 'prioridade' => '']);
				}
			}
			if ($this->request->is('post')) {
				try {
					$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
					[$ok, $errs] = $this->_wfValidatePolicyPayload($body, null);
					if (!$ok) {
						return $this->jsonResponse($this->_wfPolicyValidationErrorResponse($errs), 422);
					}
					$autoOn = !empty($body['auto_escalar']);
					$respIn = $body['resposta_minutos'] ?? null;
					$resoIn = $body['resolucao_minutos'] ?? null;
					$ent = $table->newEntity([
						'empresa_id' => !empty($body['is_global']) ? null : (int)$body['empresa_id'],
						'workflow_state_id' => (int)$body['workflow_state_id'],
						'resposta_minutos' => ($respIn === null || $respIn === '') ? null : (int)$respIn,
						'resolucao_minutos' => ($resoIn === null || $resoIn === '') ? null : (int)$resoIn,
						'pausa_sla' => !empty($body['pausa_sla']),
						'is_final' => !empty($body['is_final']),
						'auto_escalar' => $autoOn,
						'escalate_to_state_id' => $autoOn && !empty($body['escalate_to_state_id']) ? (int)$body['escalate_to_state_id'] : null,
						'escalate_to_queue_id' => $autoOn && !empty($body['escalate_to_queue_id']) ? (int)$body['escalate_to_queue_id'] : null,
						'escalate_to_support_level_id' => $autoOn && !empty($body['escalate_to_support_level_id']) ? (int)$body['escalate_to_support_level_id'] : null,
						'notify_manager' => $autoOn && !empty($body['notify_manager']),
						'notify_customer' => $autoOn && !empty($body['notify_customer']),
						'notify_technician' => $autoOn && !empty($body['notify_technician']),
						'escalate_after_minutos' => $autoOn ? (isset($body['escalate_after_minutos']) ? (int)$body['escalate_after_minutos'] : 0) : 0,
						'created_at' => FrozenTime::now(),
						'updated_at' => FrozenTime::now(),
					]);
					if (!$table->save($ent)) {
						return $this->jsonResponse($this->_wfPolicySaveErrorResponse($ent->getErrors()), 422);
					}
					$ent = $table->get($ent->id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

					return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($ent)], 201);
				} catch (\Throwable $e) {
					Log::warning('workflowSla POST create: ' . $e->getMessage());
					$detail = Configure::read('debug') ? $e->getMessage() : 'Erro interno ao gravar a política.';

					return $this->jsonResponse([
						'ok' => false,
						'error' => 'server_error',
						'error_message' => 'Erro ao gravar a política.',
						'error_messages' => [$detail],
					], 500);
				}
			}
		} else {
			if ($this->request->is('get')) {
				try {
					$row = $table->get($id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found', 'error_message' => 'Política não encontrada.'], 404);
				}
				$eid = $row->empresa_id;
				$eidInt = $eid === null || $eid === '' ? null : (int)$eid;
				if (!$this->_wfPolicyEmpresaAllowedForAdmin($eidInt)) {
					return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
				}

				return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($row)]);
			}
			if ($this->request->is(['patch', 'put', 'post'])) {
				try {
					$row = $table->get($id);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found', 'error_message' => 'Política não encontrada.'], 404);
				}
				$eid = $row->empresa_id;
				$eidInt = $eid === null || $eid === '' ? null : (int)$eid;
				if (!$this->_wfPolicyEmpresaAllowedForAdmin($eidInt)) {
					return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
				}
				$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
				$merged = array_merge($row->toArray(), $body);
				[$ok, $errs] = $this->_wfValidatePolicyPayload($merged, $id);
				if (!$ok) {
					return $this->jsonResponse($this->_wfPolicyValidationErrorResponse($errs), 422);
				}
				$autoOn = !empty($merged['auto_escalar']);
				$isGlobalPatch = array_key_exists('is_global', $body)
					? !empty($body['is_global'])
					: ($row->empresa_id === null || $row->empresa_id === '');
				$escToPatch = null;
				$escQueuePatch = null;
				$escLevelPatch = null;
				$notifyMgrPatch = false;
				$notifyCliPatch = false;
				$notifyTecPatch = false;
				if ($autoOn) {
					if (array_key_exists('escalate_to_state_id', $body) && $body['escalate_to_state_id'] !== '' && $body['escalate_to_state_id'] !== null) {
						$escToPatch = (int)$body['escalate_to_state_id'];
					} else {
						$escToPatch = $row->escalate_to_state_id !== null ? (int)$row->escalate_to_state_id : null;
					}
					if (array_key_exists('escalate_to_queue_id', $body) && $body['escalate_to_queue_id'] !== '' && $body['escalate_to_queue_id'] !== null) {
						$escQueuePatch = (int)$body['escalate_to_queue_id'];
					} else {
						$escQueuePatch = $row->get('escalate_to_queue_id') !== null && $row->get('escalate_to_queue_id') !== '' ? (int)$row->escalate_to_queue_id : null;
					}
					if (array_key_exists('escalate_to_support_level_id', $body) && $body['escalate_to_support_level_id'] !== '' && $body['escalate_to_support_level_id'] !== null) {
						$escLevelPatch = (int)$body['escalate_to_support_level_id'];
					} else {
						$lv = $row->get('escalate_to_support_level_id');
						$escLevelPatch = $lv !== null && $lv !== '' ? (int)$lv : null;
					}
					$notifyMgrPatch = array_key_exists('notify_manager', $body) ? (bool)$body['notify_manager'] : (bool)($row->get('notify_manager') ?? false);
					$notifyCliPatch = array_key_exists('notify_customer', $body) ? (bool)$body['notify_customer'] : (bool)($row->get('notify_customer') ?? false);
					$notifyTecPatch = array_key_exists('notify_technician', $body) ? (bool)$body['notify_technician'] : (bool)($row->get('notify_technician') ?? false);
				}
				$escAfterPatch = $autoOn
					? (array_key_exists('escalate_after_minutos', $body) ? (int)$body['escalate_after_minutos'] : (int)($row->escalate_after_minutos ?? 0))
					: 0;
				$respPatch = array_key_exists('resposta_minutos', $body) ? $body['resposta_minutos'] : $row->resposta_minutos;
				$resoPatch = array_key_exists('resolucao_minutos', $body) ? $body['resolucao_minutos'] : $row->resolucao_minutos;
				$table->patchEntity($row, [
					'empresa_id' => $isGlobalPatch ? null : (int)($body['empresa_id'] ?? $row->empresa_id),
					'workflow_state_id' => (int)($body['workflow_state_id'] ?? $row->workflow_state_id),
					'resposta_minutos' => ($respPatch === null || $respPatch === '') ? null : (int)$respPatch,
					'resolucao_minutos' => ($resoPatch === null || $resoPatch === '') ? null : (int)$resoPatch,
					'pausa_sla' => array_key_exists('pausa_sla', $body) ? (bool)$body['pausa_sla'] : $row->pausa_sla,
					'is_final' => array_key_exists('is_final', $body) ? (bool)$body['is_final'] : $row->is_final,
					'auto_escalar' => array_key_exists('auto_escalar', $body) ? (bool)$body['auto_escalar'] : $row->auto_escalar,
					'escalate_to_state_id' => $escToPatch,
					'escalate_to_queue_id' => $escQueuePatch,
					'escalate_to_support_level_id' => $escLevelPatch,
					'notify_manager' => $notifyMgrPatch,
					'notify_customer' => $notifyCliPatch,
					'notify_technician' => $notifyTecPatch,
					'escalate_after_minutos' => $escAfterPatch,
					'updated_at' => FrozenTime::now(),
				]);
				if (!$table->save($row)) {
					return $this->jsonResponse($this->_wfPolicySaveErrorResponse($row->getErrors()), 422);
				}
				$row = $table->get($id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

				return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($row)]);
			}
			if ($this->request->is('delete')) {
				try {
					$row = $table->get($id);
				} catch (\Throwable $e) {
					return $this->jsonResponse(['ok' => false, 'error' => 'not_found', 'error_message' => 'Política não encontrada.'], 404);
				}
				$eid = $row->empresa_id;
				$eidInt = $eid === null || $eid === '' ? null : (int)$eid;
				if (!$this->_wfPolicyEmpresaAllowedForAdmin($eidInt)) {
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

	protected function _wfSerializeState($row): array {
		if (!$row) {
			return [];
		}
		$codigo = $this->_wfNormalizeStateCodigo((string)$row->codigo);
		$wfSvc = new \App\Service\Ticket\WorkflowService(TableRegistry::get('Tickets'));

		return [
			'id' => (int)$row->id,
			'nome' => (string)$row->nome,
			'codigo' => $codigo,
			'is_inicial' => (bool)$row->is_inicial,
			'is_final' => (bool)$row->is_final,
			'legacy_situacao_mapped' => $wfSvc->isLegacySituacaoMappedCodigo($codigo),
		];
	}

	protected function _wfNormalizeStateCodigo(string $codigo): string {
		$k = strtolower(trim($codigo));
		$k = preg_replace('/\s+/u', '_', $k);
		$k = preg_replace('/[^a-z0-9_]/', '', $k);
		if ($k === 'em_execucao' || $k === 'em-execucao' || $k === 'em_execução') {
			return 'emandamento';
		}

		return $k;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{0:bool,1:array<int|string>,2:array<string,mixed>}
	 */
	protected function _wfValidateStatePayload(array $data, ?int $ignoreId = null): array {
		$errs = [];
		$nome = trim((string)($data['nome'] ?? ''));
		if ($nome === '') {
			$errs[] = 'nome_obrigatorio';
		}
		$codigo = $this->_wfNormalizeStateCodigo((string)($data['codigo'] ?? ''));
		if ($codigo === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $codigo)) {
			$errs[] = 'codigo_invalido';
		}
		$table = $this->_wfStatesTable();
		if ($table !== null && $codigo !== '') {
			$dupQ = $table->find()->where(['codigo' => $codigo]);
			if ($ignoreId !== null && $ignoreId > 0) {
				$dupQ->where(['id !=' => $ignoreId]);
			}
			if ($dupQ->first()) {
				$errs[] = 'codigo_duplicado';
			}
		}
		$isInicial = !empty($data['is_inicial']);
		$isFinal = !empty($data['is_final']);
		if ($isInicial && $isFinal) {
			$errs[] = 'inicial_final_conflito';
		}

		return [$errs === [], $errs, [
			'nome' => $nome,
			'codigo' => $codigo,
			'is_inicial' => $isInicial,
			'is_final' => $isFinal,
		]];
	}

	/**
	 * @param array<int|string> $codes
	 * @return array<int,string>
	 */
	protected function _wfStateValidationErrorMessages(array $codes): array {
		$map = [
			'nome_obrigatorio' => 'Informe o nome do estado.',
			'codigo_invalido' => 'Código inválido (use slug minúsculo: letras, números e _).',
			'codigo_duplicado' => 'Já existe um estado com este código.',
			'inicial_final_conflito' => 'Um estado não pode ser inicial e final ao mesmo tempo.',
			'estado_em_uso_tickets' => 'Não é possível excluir: há tickets neste estado.',
			'estado_unico_inicial' => 'Deve existir ao menos um estado inicial.',
			'tabela_indisponivel' => 'Tabela workflow_states indisponível.',
		];
		$out = [];
		foreach ($codes as $c) {
			$key = is_string($c) ? $c : (string)$c;
			$out[] = $map[$key] ?? $key;
		}

		return $out;
	}

	/**
	 * @param array<int|string> $errs
	 * @return array<string,mixed>
	 */
	protected function _wfStateValidationErrorResponse(array $errs): array {
		return [
			'ok' => false,
			'errors' => $errs,
			'error_messages' => $this->_wfStateValidationErrorMessages($errs),
		];
	}

	protected function _wfEnsureSingleInitialState(int $stateId): void {
		$table = $this->_wfStatesTable();
		if ($table === null || $stateId <= 0) {
			return;
		}
		$table->updateAll(
			['is_inicial' => false],
			['id !=' => $stateId, 'is_inicial' => true]
		);
	}

	protected function _wfCountTicketsOnState(int $stateId): int {
		if ($stateId <= 0) {
			return 0;
		}
		try {
			$tickets = TableRegistry::get('Tickets');
			$cols = $tickets->getSchema()->columns();
			if (!in_array('workflow_state_id', $cols, true)) {
				return 0;
			}

			return (int)$tickets->find()
				->where(['workflow_state_id' => $stateId])
				->count();
		} catch (\Throwable $e) {
			return 0;
		}
	}

	public function workflowStates() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$table = $this->_wfStatesTable();
		if ($table === null) {
			if ($this->request->is('get')) {
				return $this->jsonResponse(['ok' => true, 'states' => []]);
			}

			return $this->jsonResponse($this->_wfStateValidationErrorResponse(['tabela_indisponivel']), 422);
		}
		if ($this->request->is('get')) {
			$rows = $table->find()->order(['nome' => 'ASC'])->all();
			$out = [];
			foreach ($rows as $r) {
				$out[] = $this->_wfSerializeState($r);
			}

			return $this->jsonResponse(['ok' => true, 'states' => $out]);
		}
		if ($this->request->is('post')) {
			$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
			[$ok, $errs, $clean] = $this->_wfValidateStatePayload($body);
			if (!$ok) {
				return $this->jsonResponse($this->_wfStateValidationErrorResponse($errs), 422);
			}
			$ent = $table->newEntity($clean + ['created_at' => FrozenTime::now()]);
			if (!$table->save($ent)) {
				return $this->jsonResponse($this->_wfPolicySaveErrorResponse($ent->getErrors()), 422);
			}
			if (!empty($clean['is_inicial'])) {
				$this->_wfEnsureSingleInitialState((int)$ent->id);
				$ent = $table->get($ent->id);
			}

			return $this->jsonResponse(['ok' => true, 'state' => $this->_wfSerializeState($ent)], 201);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'method'], 405);
	}

	public function workflowState() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$id = (int)$this->request->getParam('id');
		if ($id <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'bad_request'], 400);
		}
		$table = $this->_wfStatesTable();
		if ($table === null) {
			return $this->jsonResponse($this->_wfStateValidationErrorResponse(['tabela_indisponivel']), 422);
		}
		try {
			$row = $table->get($id);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if ($this->request->is('delete')) {
			if ($this->_wfCountTicketsOnState($id) > 0) {
				return $this->jsonResponse($this->_wfStateValidationErrorResponse(['estado_em_uso_tickets']), 422);
			}
			if ((bool)$row->is_inicial) {
				$otherInitial = $table->find()
					->where(['is_inicial' => true, 'id !=' => $id])
					->count();
				if ($otherInitial <= 0) {
					return $this->jsonResponse($this->_wfStateValidationErrorResponse(['estado_unico_inicial']), 422);
				}
			}
			if ($table->delete($row)) {
				return $this->jsonResponse(['ok' => true]);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
		}
		if ($this->request->is(['patch', 'put', 'post'])) {
			$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
			$merge = [
				'nome' => array_key_exists('nome', $body) ? $body['nome'] : $row->nome,
				'codigo' => array_key_exists('codigo', $body) ? $body['codigo'] : $row->codigo,
				'is_inicial' => array_key_exists('is_inicial', $body) ? $body['is_inicial'] : $row->is_inicial,
				'is_final' => array_key_exists('is_final', $body) ? $body['is_final'] : $row->is_final,
			];
			[$ok, $errs, $clean] = $this->_wfValidateStatePayload($merge, $id);
			if (!$ok) {
				return $this->jsonResponse($this->_wfStateValidationErrorResponse($errs), 422);
			}
			$row = $table->patchEntity($row, $clean);
			if (!$table->save($row)) {
				return $this->jsonResponse($this->_wfPolicySaveErrorResponse($row->getErrors()), 422);
			}
			if (!empty($clean['is_inicial'])) {
				$this->_wfEnsureSingleInitialState($id);
				$row = $table->get($id);
			}

			return $this->jsonResponse(['ok' => true, 'state' => $this->_wfSerializeState($row)]);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'method'], 405);
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
		$ticketFilter = (int)$this->request->getQuery('ticket_id', 0);
		$q = $logs->find()->where(['empresa_id' => $eidSession]);
		if ($ticketFilter > 0) {
			$q->where(['ticket_id' => $ticketFilter]);
		}
		$rows = $q->order(['created_at' => 'DESC', 'id' => 'DESC'])
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
		try {
			$row = $table->get($id);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found', 'error_message' => 'Política não encontrada.'], 404);
		}
		$eid = $row->empresa_id;
		$eidInt = $eid === null || $eid === '' ? null : (int)$eid;
		if (!$this->_wfPolicyEmpresaAllowedForAdmin($eidInt)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = (array)$this->request->input('json_decode', true) + $this->request->getData();
		$newStateId = isset($body['workflow_state_id']) ? (int)$body['workflow_state_id'] : (int)$row->workflow_state_id;
		if ($newStateId <= 0) {
			return $this->jsonResponse($this->_wfPolicyValidationErrorResponse(['workflow_state_obrigatorio']), 422);
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
			'escalate_to_queue_id' => $row->get('escalate_to_queue_id'),
			'escalate_to_support_level_id' => $row->get('escalate_to_support_level_id'),
			'notify_manager' => (bool)($row->get('notify_manager') ?? false),
			'notify_customer' => (bool)($row->get('notify_customer') ?? false),
			'notify_technician' => (bool)($row->get('notify_technician') ?? false),
			'escalate_after_minutos' => $row->escalate_after_minutos,
			'created_at' => FrozenTime::now(),
			'updated_at' => FrozenTime::now(),
		]);
		if (!$table->save($copy)) {
			$err = $copy->getErrors();
			if ($err !== []) {
				return $this->jsonResponse($this->_wfPolicySaveErrorResponse($err), 422);
			}

			return $this->jsonResponse($this->_wfPolicyValidationErrorResponse(['duplicado_estado_empresa']), 422);
		}
		$copy = $table->get($copy->id, ['contain' => ['WorkflowStates', 'Empresas', 'EscalateToStates']]);

		return $this->jsonResponse(['ok' => true, 'policy' => $this->_wfSerializePolicy($copy)], 201);
	}

	public function workflowSlaEmpresasOptions() {
		$this->autoRender = false;
		if (!$this->_wfTechOr403()) {
			return;
		}
		$enabledConfigured = $this->_wfWorkflowEmpresaFilterIds();
		$debugOn = (bool)Configure::read('debug') || (string)$this->request->getQuery('verbose') === '1';
		try {
			$list = $this->_wfEmpresaRowsForWorkflowSlaDropdown([]);
			$out = [];
			foreach ($list as $r) {
				if (!is_array($r)) {
					continue;
				}
				$label = $this->_wfEmpresaLabelFromRow($r);
				$eid = (int)($r['id'] ?? 0);
				if ($eid <= 0) {
					continue;
				}
				$nf = trim((string)($r['nomefantasia'] ?? ''));
				$rz = trim((string)($r['razaosocial'] ?? ''));
				$out[] = [
					'id' => $eid,
					'label' => $label,
					'nome' => $label,
					'nomefantasia' => $nf,
					'razaosocial' => $rz,
				];
			}
			$response = ['ok' => true, 'empresas' => $out];
			if ($debugOn) {
				$wfDbg = [];
				foreach ($list as $r) {
					if (!is_array($r)) {
						continue;
					}
					$eid = (int)($r['id'] ?? 0);
					$nf = trim((string)($r['nomefantasia'] ?? ''));
					$rz = trim((string)($r['razaosocial'] ?? ''));
					$wfDbg[] = [
						'id' => $eid,
						'inativa' => $r['inativa'] ?? null,
						'nomefantasia' => $nf,
						'razaosocial' => $rz,
					];
				}
				$response['debug'] = [
					'workflowEmpresas' => $wfDbg,
					'count' => count($out),
					'workflowEmpresasConfigured' => $enabledConfigured,
					'allowedEmpresaIds' => $this->_wfSlaAdminSelectableEmpresaIds(),
				];
			}

			return $this->jsonResponse($response, 200);
		} catch (\Throwable $e) {
			Log::error('workflowSlaEmpresasOptions: ' . $e->getMessage());

			$response = ['ok' => false, 'empresas' => [], 'error' => 'empresas_query'];
			if ($debugOn) {
				$response['debug'] = [
					'exception' => $e->getMessage(),
					'count' => 0,
					'workflowEmpresasConfigured' => $enabledConfigured,
				];
			}

			return $this->jsonResponse($response, 200);
		}
	}
}
