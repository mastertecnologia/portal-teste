<?php
namespace App\Controller;

use App\Service\AutentiqueService;
use App\Service\Contract\ContractSlaPolicyAdminService;
use App\Service\ContractLifecycleService;
use App\Service\ContractNotificationService;
use App\Service\ContractPdfService;
use App\Service\ContractRenewalService;
use App\Service\ContractSigningService;
use App\Service\PortalAdvanced\InvoiceGenerationService;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Routing\Router;
/**
 * Gestão de contratos (ERP) — URLs /modulo-contratos/* (spec MODULO_CONTRATOS_COMPLETO).
 * Equipe interna role 0; regras de negócio em App\Service\Contract*.
 */
class ContractManagementController extends AppController {

	public function beforeFilter(Event $event) {
		if ($this->request->getParam('action') === 'webhookAutentique') {
			$this->Auth->allow(['webhookAutentique']);
		}
		parent::beforeFilter($event);
	}

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Contracts');
		$this->loadModel('ContractServices');
		$this->loadModel('ContractSignatories');
		$this->loadModel('ContractRenewals');
		$this->loadModel('ContractTemplates');
		$this->loadModel('Clientes');
		$this->loadModel('Invoices');
	}

	public function isAuthorized($user) {
		if ($this->request->getParam('action') === 'webhookAutentique') {
			return true;
		}
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * @return int
	 */
	protected function _idempresa() {
		return (int)$this->Auth->user('idempresa');
	}

	/**
	 * @return int
	 */
	protected function _userId() {
		return (int)$this->Auth->user('id');
	}

	/**
	 * Legado: users.admin = 1 — pode editar núcleo de contrato ativo / a vencer.
	 *
	 * @return bool
	 */
	protected function _mayEditOperationalContract() {
		return (int)$this->Auth->user('admin') === 1;
	}

	/**
	 * Se a UI pode oferecer link para editar o núcleo (alinhado a assertMayEditCore no edit).
	 *
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return bool
	 */
	protected function _contractMayEditCore($contract) {
		return ContractLifecycleService::mayEditCore($contract, $this->_mayEditOperationalContract());
	}

	/**
	 * E-mail aos signatários com link (após envio Autentique ou reenvio manual).
	 *
	 * @param int $contractId
	 * @param bool $sendEmail
	 * @return void
	 */
	protected function _enviarEmailsLinksAssinaturaSeSolicitado($contractId, $sendEmail) {
		if (!$sendEmail) {
			return;
		}
		try {
			$cFresh = $this->Contracts->get((int)$contractId, ['contain' => ['ContractSignatories']]);
			$r = (new ContractNotificationService())->enviarConvitesAssinaturaTodosComLink($cFresh);
			if ($r['sent'] > 0) {
				$this->Flash->success(__('Enviado(s) {0} e-mail(s) com link de assinatura aos signatários.', $r['sent']));
			}
			if ($r['errors'] !== []) {
				$this->Flash->warning(implode(' ', array_slice($r['errors'], 0, 5)));
			} elseif ($r['sent'] === 0 && $r['errors'] === []) {
				$this->Flash->warning(__('Nenhum e-mail com link foi enviado: confirme links gravados (Autentique) e Contract.notifications (from_email) no configure.'));
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('E-mail: {0}', $e->getMessage()));
		}
	}

	/**
	 * @param int $id
	 * @param array $contain
	 * @return \App\Model\Entity\Contract
	 */
	protected function _getContractOrFail($id, array $contain = []) {
		if (is_array($id)) {
			$id = reset($id);
		}
		if (is_string($id)) {
			$id = trim($id);
		}
		$intId = is_scalar($id) && $id !== '' ? filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
		$idempresa = $this->_idempresa();
		if ($intId === false) {
			throw new NotFoundException(__('Contrato não encontrado.'));
		}
		$id = $intId;
		try {
			$c = $this->Contracts->get($id, ['contain' => $contain]);
		} catch (RecordNotFoundException $e) {
			throw new NotFoundException(__('Contrato não encontrado.'));
		}
		if ((int)$c->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}

		return $c;
	}

	/**
	 * @return string
	 */
	protected function _pdfStorageBaseRealpath() {
		$storage = (string)Configure::read('Contract.pdf.storage_path');
		if ($storage === '') {
			$storage = TMP . 'contracts' . DS;
		}
		$base = realpath($storage);

		return $base !== false ? $base : '';
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	protected function _pathUnderStorage($path) {
		$path = (string)$path;
		if ($path === '' || !is_file($path)) {
			return false;
		}
		$base = $this->_pdfStorageBaseRealpath();
		if ($base === '') {
			return false;
		}
		$real = realpath($path);

		return $real !== false && strpos($real, $base) === 0;
	}

	public function index() {
		$this->set('title', __('Gestão de contratos'));
		$idempresa = $this->_idempresa();
		$cid = (int)$this->request->getQuery('idcliente', 0);
		$st = trim((string)$this->request->getQuery('status', ''));

		$kpis = [
			'ativos' => 0,
			'a_vencer' => 0,
			'aguardando_assinatura' => 0,
			'em_renovacao' => 0,
			'valor_mensal_total' => 0.0,
		];
		try {
			$T = $this->Contracts;
			$kpis['ativos'] = $T->find()->where([
				'idempresa' => $idempresa,
				'status IN' => ['ativo', 'active'],
			])->count();
			$kpis['a_vencer'] = $T->find()->where(['idempresa' => $idempresa, 'status' => 'a_vencer'])->count();
			$kpis['aguardando_assinatura'] = $T->find()->where([
				'idempresa' => $idempresa,
				'status IN' => ['aguardando_assinatura', 'awaiting_signature'],
			])->count();
			$kpis['em_renovacao'] = $T->find()->where(['idempresa' => $idempresa, 'status' => 'em_renovacao'])->count();
			$qSum = $this->Contracts->find();
			$sumRow = $qSum
				->select(['s' => $qSum->func()->sum('monthly_value')])
				->where([
					'idempresa' => $idempresa,
					'status IN' => ['ativo', 'active', 'a_vencer'],
				])
				->first();
			$kpis['valor_mensal_total'] = (float)($sumRow && isset($sumRow->s) ? $sumRow->s : 0);

			$q = $T->find()
				->contain(['Clientes'])
				->where(['Contracts.idempresa' => $idempresa])
				->order(['Contracts.modified' => 'DESC']);
			if ($cid > 0) {
				$q->where(['Contracts.idcliente' => $cid]);
			}
			if ($st !== '') {
				$stNorm = ContractLifecycleService::normalizeStatus($st);
				if ($stNorm === 'ativo') {
					$q->where(['Contracts.status IN' => ['ativo', 'active']]);
				} elseif ($stNorm === 'aguardando_assinatura') {
					$q->where(['Contracts.status IN' => ['aguardando_assinatura', 'awaiting_signature']]);
				} else {
					$q->where(['Contracts.status' => $stNorm]);
				}
			}
			$this->paginate = ['limit' => 30];
			$this->set('contracts', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Tabela contracts indisponível.'));
			$this->set('contracts', []);
		}
		$this->set('kpis', $kpis);
		$this->set('clientesList', $this->_clientesList($idempresa));
		$this->set('statusFilter', $st);
		$this->set('idclienteFilter', $cid);
	}

	public function view($id = null) {
		$c = $this->_getContractOrFail($id, [
			'Clientes',
			'ContractServices',
			'ContractDocuments',
			'ContractTemplates',
			'ParentContracts',
			'ContractSignatories',
			'ContractRenewals' => ['NovoContracts', 'Solicitante', 'Aprovador'],
			'ContractNotifications',
			'Invoices' => function ($q) {
				return $q->order(['Invoices.reference_month' => 'DESC'])->limit(24);
			},
		]);
		$this->set('title', __('Contrato: {0}', $c->name));
		$this->set('contract', $c);
		$this->set('contractMayEditCore', $this->_contractMayEditCore($c));
		$manualStatusOptions = [];
		$statusLabels = \App\Model\Entity\Contract::statusLabelMap();
		foreach ([
			'rascunho',
			'revisao',
			'aguardando_assinatura',
			'ativo',
			'a_vencer',
			'em_renovacao',
			'suspenso',
			'encerrado',
			'cancelado',
			'recusado',
			'assinatura_expirada',
		] as $allowedStatus) {
			$manualStatusOptions[$allowedStatus] = $statusLabels[$allowedStatus] ?? $allowedStatus;
		}
		$this->set('manualStatusOptions', $manualStatusOptions);

		$slaSvc = new ContractSlaPolicyAdminService();
		$wfSlaOk = (bool)Configure::read('Workflow.workflowEnabled', false)
			&& (bool)Configure::read('Workflow.workflowSlaEnabled', false);
		$slaReady = $wfSlaOk && $slaSvc->isSchemaReady();
		$this->set('contractSlaUiEnabled', $slaReady);
		$this->set('contractSlaApiUrl', $slaReady ? Router::url(['controller' => 'ContractManagement', 'action' => 'contractSlaApi', $c->id]) : null);
	}

	/**
	 * JSON: listar opções + políticas SLA do contrato (GET) ou criar/editar/toggle (POST).
	 *
	 * @param int|string|null $id
	 * @return \Cake\Http\Response
	 */
	public function contractSlaApi($id = null) {
		if (!$this->request->is(['get', 'post'])) {
			throw new MethodNotAllowedException();
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');

		$contract = $this->_getContractOrFail($id, []);
		$empresaId = $this->_idempresa();
		$cid = (int)$contract->id;
		$idcliente = (int)($contract->idcliente ?? 0);

		$svc = new ContractSlaPolicyAdminService();
		if (!$svc->isSchemaReady()) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'errors' => ['Schema SLA indisponível.']]));
		}

		if ($this->request->is('get')) {
			return $this->response->withStringBody(json_encode([
				'ok' => true,
				'policies' => $svc->listForContract($cid, $empresaId),
				'options' => $svc->buildFormOptions($empresaId, $contract),
				'contract' => ['id' => $cid, 'idcliente' => $idcliente],
			]));
		}

		$payload = $this->request->getData();
		if (!is_array($payload) || $payload === []) {
			$raw = (string)$this->request->getBody();
			if ($raw !== '') {
				$decoded = json_decode($raw, true);
				$payload = is_array($decoded) ? $decoded : [];
			}
		}
		$op = (string)($payload['op'] ?? '');
		if ($op === 'create') {
			return $this->response->withStringBody(json_encode($svc->create($empresaId, $cid, $idcliente, $payload)));
		}
		if ($op === 'update') {
			$pid = (int)($payload['id'] ?? 0);
			if ($pid <= 0) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'errors' => ['ID inválido.']]));
			}

			return $this->response->withStringBody(json_encode($svc->update($empresaId, $cid, $idcliente, $pid, $payload)));
		}
		if ($op === 'toggle' || $op === 'setAtivo') {
			$pid = (int)($payload['id'] ?? 0);
			$ativo = !empty($payload['ativo']);
			if ($pid <= 0) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'errors' => ['ID inválido.']]));
			}

			return $this->response->withStringBody(json_encode($svc->setAtivo($empresaId, $cid, $pid, $ativo)));
		}

		return $this->response->withStringBody(json_encode(['ok' => false, 'errors' => ['Operação inválida.']]));
	}

	public function add() {
		$this->set('title', __('Novo contrato'));
		$idempresa = $this->_idempresa();
		$renewal = new ContractRenewalService();
		$contract = $this->Contracts->newEntity([
			'idempresa' => $idempresa,
			'code' => $renewal->proximoNumero($idempresa),
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
		]);

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$data['idempresa'] = $idempresa;
			if (empty($data['status'])) {
				$data['status'] = 'rascunho';
			}
			if (empty($data['billing_cycle'])) {
				$data['billing_cycle'] = 'monthly';
			}
			if (empty($data['idcliente'])) {
				$this->Flash->error(__('Selecione o cliente.'));
			} else {
				$contract = $this->Contracts->newEntity($data);
				if ($this->Contracts->save($contract)) {
					$newId = (int)$contract->get('id');
					if ($newId <= 0) {
						$this->log('Contracts->save retornou true sem id na entidade.', 'error');
						$this->Flash->error(__('Contrato gravado mas não foi possível obter o número. Recarregue a lista.'));
						return $this->redirect(['action' => 'index']);
					}
					if ($this->request->getData('gravar_destino') === 'ficha') {
						$this->Flash->success(__('Contrato criado. Abra a ficha para PDF e assinatura.'));
						return $this->redirect(['controller' => 'ContractManagement', 'action' => 'view', $newId]);
					}
					$this->Flash->success(__('Contrato criado. Adicione serviços ou avance para signatários.'));

					return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addServicos', $newId]);
				}
				$this->Flash->error(__('Não foi possível gravar. Verifique os campos.'));
			}
		}

		$this->set('contract', $contract);
		$this->set('contractMayEditCore', !empty($contract->id) ? $this->_contractMayEditCore($contract) : true);
		$this->set('clientesList', $this->_clientesList($idempresa));
		$this->set('templatesList', $this->_templatesList($idempresa));
	}

	public function edit($id = null) {
		$contract = $this->_getContractOrFail($id, ['Clientes']);
		$idempresa = $this->_idempresa();
		$this->set('title', __('Editar contrato'));

		try {
			ContractLifecycleService::assertMayEditCore($contract, $this->_mayEditOperationalContract());
		} catch (\RuntimeException $e) {
			$this->Flash->error($e->getMessage());
			return $this->redirect(['action' => 'view', $id]);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$data['idempresa'] = $idempresa;
			$contract = $this->Contracts->patchEntity($contract, $data);
			if ($this->Contracts->save($contract)) {
				$this->Flash->success(__('Contrato atualizado.'));
				return $this->redirect(['action' => 'view', $id]);
			}
			$this->Flash->error(__('Não foi possível salvar.'));
		}

		$this->set('contract', $contract);
		$this->set('contractMayEditCore', $this->_contractMayEditCore($contract));
		$this->set('clientesList', $this->_clientesList($idempresa));
		$this->set('templatesList', $this->_templatesList($idempresa));
	}

	public function addServicos($id = null) {
		$contract = $this->_getContractOrFail($id, []);
		try {
			$this->Contracts->loadInto($contract, ['ContractServices']);
		} catch (\Throwable $e) {
			$this->log('ContractManagement::addServicos loadInto ContractServices: ' . $e->getMessage(), 'error');
			$contract->contract_services = [];
		}
		$this->set('title', __('Serviços do contrato'));
		$this->set('contract', $contract);
		$this->set('contractMayEditCore', $this->_contractMayEditCore($contract));

		if ($this->request->is('post')) {
			$row = $this->ContractServices->newEntity(array_merge(
				$this->request->getData(),
				['contract_id' => (int)$contract->id]
			));
			if ($this->ContractServices->save($row)) {
				$this->Flash->success(__('Serviço adicionado.'));
				return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addServicos', (int)$contract->id]);
			}
			$this->Flash->error(__('Não foi possível gravar o serviço.'));
			$this->set('service', $row);
		} else {
			$this->set('service', $this->ContractServices->newEntity(['contract_id' => $contract->id]));
		}
	}

	public function conferenciaConsumo($id = null) {
		$contract = $this->_getContractOrFail($id, []);
		try {
			$this->Contracts->loadInto($contract, ['ContractServices', 'Clientes']);
		} catch (\Throwable $e) {
			$contract->contract_services = [];
		}
		$referenceMonth = trim((string)$this->request->getQuery('reference_month', date('Y-m')));
		if (!preg_match('/^\d{4}-\d{2}$/', $referenceMonth)) {
			$referenceMonth = date('Y-m');
		}
		$conference = InvoiceGenerationService::buildConsumptionConferenceForContract($contract, $referenceMonth);

		$this->set('title', __('Conferência de consumo'));
		$this->set('contract', $contract);
		$this->set('referenceMonth', $referenceMonth);
		$this->set('conference', $conference);
		$this->set('contractMayEditCore', $this->_contractMayEditCore($contract));
	}

	public function deleteServico($svcId = null, $contractId = null) {
		$this->request->allowMethod(['post']);
		$svcId = (int)$svcId;
		$contractId = (int)$contractId;

		$svc = $this->ContractServices->find()
			->where(['ContractServices.id' => $svcId])
			->contain(['Contracts'])
			->first();

		if (!$svc || (int)$svc->contract_id !== $contractId) {
			$this->Flash->error(__('Serviço não encontrado.'));
			return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addServicos', $contractId]);
		}

		$idempresa = $this->_idempresa();
		if (isset($svc->contract) && (int)$svc->contract->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}

		if ($this->ContractServices->delete($svc)) {
			$this->Flash->success(__('Serviço removido.'));
		} else {
			$this->Flash->error(__('Não foi possível remover o serviço.'));
		}

		return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addServicos', $contractId]);
	}

	public function addSignatarios($id = null) {
		$contract = $this->_getContractOrFail($id, ['ContractSignatories']);
		$this->set('title', __('Signatários'));
		$this->set('contract', $contract);
		$this->set('contractMayEditCore', $this->_contractMayEditCore($contract));
		$stSig = ContractLifecycleService::normalizeStatus($contract->get('status'));
		$this->set('podeEnviarAssinatura', in_array($stSig, ['rascunho', 'revisao', 'aguardando_assinatura'], true));

		if ($this->request->is('post')) {
			$row = $this->ContractSignatories->newEntity(array_merge(
				$this->request->getData(),
				['contract_id' => (int)$contract->id]
			));
			if ($this->ContractSignatories->save($row)) {
				$this->Flash->success(__('Signatário adicionado.'));
				return $this->redirect(['action' => 'addSignatarios', $id]);
			}
			$this->Flash->error(__('Não foi possível gravar.'));
			$this->set('signatory', $row);
		} else {
			$this->set('signatory', $this->ContractSignatories->newEntity(['contract_id' => $contract->id]));
		}
	}

	public function deleteSignatario($sigId = null, $contractId = null) {
		$this->request->allowMethod(['post']);
		$sigId      = (int)$sigId;
		$contractId = (int)$contractId;

		$sig = $this->ContractSignatories->find()
			->where(['ContractSignatories.id' => $sigId])
			->contain(['Contracts'])
			->first();

		if (!$sig || (int)$sig->contract_id !== $contractId) {
			$this->Flash->error(__('Signatário não encontrado.'));
			return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addSignatarios', $contractId]);
		}

		$idempresa = $this->_idempresa();
		if (isset($sig->contract) && (int)$sig->contract->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}

		if ($this->ContractSignatories->delete($sig)) {
			$this->Flash->success(__('Signatário removido.'));
		} else {
			$this->Flash->error(__('Não foi possível remover o signatário.'));
		}

		return $this->redirect(['controller' => 'ContractManagement', 'action' => 'addSignatarios', $contractId]);
	}

	public function gerarPdf($id = null) {
		$this->request->allowMethod(['post', 'get']);
		$c = $this->_getContractOrFail($id, ['Clientes', 'Empresas', 'ContractTemplates', 'ContractServices']);
		try {
			$svc = new ContractPdfService();
			$tpl = !empty($c->contract_template) ? $c->contract_template : null;
			$path = $svc->gerarEPersistir($this->Contracts, $c, $tpl, (array)$c->contract_services);
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível gerar o PDF.') . ' ' . $e->getMessage());
			return $this->redirect(['action' => 'view', $id]);
		}

		if ($this->request->is('get')) {
			$this->autoRender = false;
			$body = file_get_contents($path);

			return $this->response
				->withType('application/pdf')
				->withDownload(basename($path))
				->withStringBody($body !== false ? $body : '');
		}
		$this->Flash->success(__('PDF gerado.'));
		return $this->redirect(['action' => 'view', $id]);
	}

	public function enviarAssinatura($id = null) {
		$contract = $this->_getContractOrFail($id, ['ContractSignatories', 'Clientes', 'Empresas', 'ContractTemplates', 'ContractServices']);
		$this->set('title', __('Enviar para assinatura'));
		$this->set('contract', $contract);

		if ($this->request->is('post')) {
			$sendEmailSignatarios = (string)$this->request->getData('enviar_email_signatarios', '1') === '1';
			$signing = new ContractSigningService();
			$errs = $signing->validateSignatoriesForSend($contract->contract_signatories ?? []);
			if ($errs !== []) {
				$this->Flash->error(implode(' ', $errs));
				return;
			}
			try {
				$pdf = new ContractPdfService();
				$tpl = !empty($contract->contract_template) ? $contract->contract_template : null;
				if (trim((string)$contract->get('pdf_path')) === '' || !is_file((string)$contract->get('pdf_path'))) {
					$pdf->gerarEPersistir($this->Contracts, $contract, $tpl, (array)$contract->contract_services);
				}
			} catch (\Throwable $e) {
				$this->Flash->error(__('Gere o PDF antes do envio.') . ' ' . $e->getMessage());
				return;
			}

			$aut = new AutentiqueService();
			if ($aut->isEnabled()) {
				$signList = (array)($contract->contract_signatories ?? []);
				$res = $aut->criarDocumento($contract, (string)$contract->get('pdf_path'), $signList);
				if (!empty($res['errors'])) {
					$parts = [];
					foreach ($res['errors'] as $e) {
						if (!is_array($e)) {
							$parts[] = (string)$e;
							continue;
						}
						$msg = trim((string)($e['message'] ?? ''));
						$ext = $e['extensions'] ?? null;
						if (is_array($ext) && $ext !== []) {
							$msg .= ($msg !== '' ? ' — ' : '') . json_encode($ext, JSON_UNESCAPED_UNICODE);
						}
						if ($msg === '' || strtolower($msg) === 'validation') {
							$msg = json_encode($e, JSON_UNESCAPED_UNICODE);
						}
						$parts[] = $msg;
					}
					$this->Flash->error(__('Autentique: ') . implode(' ', $parts));

					return;
				}
				$docId = (string)($res['id'] ?? '');
				$signaturesRaw = is_array($res['signatures'] ?? null) ? $res['signatures'] : [];
				$firstLink = '';
				foreach ($signaturesRaw as $sig) {
					if (!is_array($sig)) {
						continue;
					}
					$sl = AutentiqueService::extractShortLinkFromSignature($sig);
					if ($sl !== null && $sl !== '') {
						$firstLink = $sl;
						break;
					}
				}
				$signing->markAwaitingSignature($this->Contracts, $contract, $docId !== '' ? $docId : null);
				if ($firstLink !== '') {
					$this->Contracts->patchEntity($contract, ['autentique_url' => $firstLink]);
					$this->Contracts->save($contract);
				}
				$signList = array_values((array)($contract->contract_signatories ?? []));
				usort($signList, function ($a, $b) {
					return ((int)$a->get('ordem') ?: 0) <=> ((int)$b->get('ordem') ?: 0);
				});
				$apiSigs = [];
				foreach ($signaturesRaw as $apiSig) {
					if (is_array($apiSig)) {
						$apiSigs[] = $apiSig;
					}
				}
				$normEmail = static function ($e) {
					return strtolower(trim((string)$e));
				};
				$normName = static function ($n) {
					$n = trim((string)$n);

					return function_exists('mb_strtolower') ? mb_strtolower($n, 'UTF-8') : strtolower($n);
				};
				$nApi = count($apiSigs);
				$nSign = count($signList);
				$matchedApi = array_fill(0, $nApi, false);
				$matchedRow = array_fill(0, $nSign, false);
				$applyApiToRow = function ($row, $apiSig) {
					$sl = is_array($apiSig) ? AutentiqueService::extractShortLinkFromSignature($apiSig) : null;
					$shortLink = ($sl !== null && $sl !== '') ? $sl : null;
					$patch = [
						'autentique_signer_id' => isset($apiSig['public_id']) ? $apiSig['public_id'] : null,
						'status' => 'enviado',
					];
					if ($shortLink !== null) {
						$patch['link_assinatura'] = $shortLink;
					}
					$this->ContractSignatories->patchEntity($row, $patch);
				};
				for ($i = 0; $i < $nSign; $i++) {
					$row = $signList[$i];
					$em = $normEmail($row->get('email') ?? '');
					if ($em === '') {
						continue;
					}
					for ($j = 0; $j < $nApi; $j++) {
						if ($matchedApi[$j]) {
							continue;
						}
						if ($normEmail($apiSigs[$j]['email'] ?? '') === $em) {
							$applyApiToRow($row, $apiSigs[$j]);
							$matchedApi[$j] = true;
							$matchedRow[$i] = true;
							break;
						}
					}
				}
				for ($i = 0; $i < $nSign; $i++) {
					if ($matchedRow[$i]) {
						continue;
					}
					$row = $signList[$i];
					$nm = $normName($row->get('nome') ?? '');
					if ($nm === '') {
						continue;
					}
					for ($j = 0; $j < $nApi; $j++) {
						if ($matchedApi[$j]) {
							continue;
						}
						if ($normName($apiSigs[$j]['name'] ?? '') === $nm) {
							$applyApiToRow($row, $apiSigs[$j]);
							$matchedApi[$j] = true;
							$matchedRow[$i] = true;
							break;
						}
					}
				}
				$unmatchedRows = [];
				$unmatchedApiIdx = [];
				for ($i = 0; $i < $nSign; $i++) {
					if (!$matchedRow[$i]) {
						$unmatchedRows[] = $i;
					}
				}
				for ($j = 0; $j < $nApi; $j++) {
					if (!$matchedApi[$j]) {
						$unmatchedApiIdx[] = $j;
					}
				}
				$uR = count($unmatchedRows);
				$uA = count($unmatchedApiIdx);
				if ($uR > 0 && $uR === $uA) {
					for ($k = 0; $k < $uR; $k++) {
						$applyApiToRow($signList[$unmatchedRows[$k]], $apiSigs[$unmatchedApiIdx[$k]]);
					}
				}
				if ($nApi > 0) {
					$distinctLinks = [];
					foreach ($apiSigs as $s) {
						$l = AutentiqueService::extractShortLinkFromSignature($s);
						if ($l !== null && $l !== '') {
							$distinctLinks[$l] = true;
						}
					}
					if (count($distinctLinks) === 1) {
						$keys = array_keys($distinctLinks);
						$onlyLink = (string)reset($keys);
						foreach ($signList as $row) {
							if (trim((string)($row->get('link_assinatura') ?? '')) === '') {
								$this->ContractSignatories->patchEntity($row, [
									'link_assinatura' => $onlyLink,
								]);
							}
						}
					}
				}
				if ($signList !== []) {
					$saved = $this->ContractSignatories->saveMany($signList, ['atomic' => false]);
					if ($saved === false) {
						foreach ($signList as $row) {
							if (!$row->isDirty()) {
								continue;
							}
							if ($this->ContractSignatories->save($row)) {
								continue;
							}
							$this->log(
								sprintf(
									'ContractSignatory id=%s contract_id=%s save falhou: %s',
									(string)$row->get('id'),
									(string)$row->get('contract_id'),
									json_encode($row->getErrors(), JSON_UNESCAPED_UNICODE)
								),
								'warning'
							);
						}
					}
				}
				$signing->logEvent((int)$contract->id, 'envio_autentique', ['doc_id' => $docId], $this->_userId());
				try {
					$c2 = $this->Contracts->get($contract->id, ['contain' => ['Clientes']]);
					(new ContractNotificationService())->notificarNovoContrato($c2);
				} catch (\Throwable $e) {
				}
				$this->Flash->success(__('Contrato enviado à Autentique para assinatura.'));
				$this->_enviarEmailsLinksAssinaturaSeSolicitado((int)$id, $sendEmailSignatarios);

				return $this->redirect(['action' => 'view', $id]);
			}

			$signing->markAwaitingSignature($this->Contracts, $contract, null);
			$signing->logEvent((int)$contract->id, 'envio_assinatura_preparado', ['stub' => true], $this->_userId());
			try {
				$c2 = $this->Contracts->get($contract->id, ['contain' => ['Clientes']]);
				(new ContractNotificationService())->notificarNovoContrato($c2);
			} catch (\Throwable $e) {
			}
			$this->Flash->success(__('Contrato marcado como aguardando assinatura (sem API Autentique — ligue CONTRACT_AUTENTIQUE_ENABLED).'));
			$this->_enviarEmailsLinksAssinaturaSeSolicitado((int)$id, $sendEmailSignatarios);

			return $this->redirect(['action' => 'view', $id]);
		}
	}

	public function aprovar($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		$target = trim((string)$this->request->getData('target_status', 'aguardando_assinatura'));
		if ($target === '') {
			$target = 'aguardando_assinatura';
		}
		try {
			$life = new ContractLifecycleService();
			$life->aprovarInternamente($this->Contracts, $c, $this->_userId(), $target);
			$this->Flash->success(__('Aprovação registada.'));
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}
		return $this->redirect(['action' => 'view', $id]);
	}

	public function suspender($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		try {
			(new ContractLifecycleService())->suspender($this->Contracts, $c);
			$this->Flash->success(__('Contrato suspenso.'));
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}
		return $this->redirect(['action' => 'view', $id]);
	}

	public function cancelar($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		$motivo = trim((string)$this->request->getData('motivo', ''));
		if ($motivo === '') {
			$this->Flash->error(__('Para cancelar o contrato, informe o motivo.'));
			return $this->redirect(['action' => 'view', $id]);
		}
		try {
			(new ContractLifecycleService())->cancelar($this->Contracts, $c, $motivo, true);
			$this->Flash->success(__('Contrato cancelado.'));
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}
		return $this->redirect(['action' => 'view', $id]);
	}

	public function updateStatus($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		$newStatus = ContractLifecycleService::normalizeStatus(trim((string)$this->request->getData('status', '')));
		if (!in_array($newStatus, \App\Model\Table\ContractsTable::allowedStatusValues(), true)) {
			$this->Flash->error(__('Status inválido.'));
			return $this->redirect(['action' => 'view', $id]);
		}
		$this->Contracts->patchEntity($c, ['status' => $newStatus], ['fields' => ['status']]);
		if ($this->Contracts->save($c)) {
			$this->Flash->success(__('Status do contrato atualizado manualmente.'));
		} else {
			$this->Flash->error(__('Não foi possível atualizar o status do contrato.'));
		}

		return $this->redirect(['action' => 'view', $id]);
	}

	public function delete($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		$motivo = trim((string)$this->request->getData('motivo', ''));
		if ($motivo === '') {
			$this->Flash->error(__('Para excluir o contrato, informe o motivo.'));
			return $this->redirect(['action' => 'index']);
		}
		$statusNorm = ContractLifecycleService::normalizeStatus((string)$c->get('status'));
		if (!in_array($statusNorm, ['rascunho', 'cancelado'], true)) {
			$this->Flash->error(__('Só é permitido excluir contratos em rascunho ou cancelados.'));
			return $this->redirect(['action' => 'index']);
		}
		try {
			$ok = $this->Contracts->getConnection()->transactional(function () use ($c, $motivo) {
				$this->Contracts->patchEntity($c, [
					'motivo_cancelamento' => $motivo,
					'cancelado_em' => date('Y-m-d H:i:s'),
				], ['fields' => ['motivo_cancelamento', 'cancelado_em']]);
				if (!$this->Contracts->save($c)) {
					throw new \RuntimeException('Falha ao registar motivo de exclusão.');
				}

				return (bool)$this->Contracts->delete($c);
			});
			if ($ok) {
				$this->Flash->success(__('Contrato excluído.'));
			} else {
				$this->Flash->error(__('Não foi possível excluir o contrato.'));
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível excluir o contrato: {0}', $e->getMessage()));
		}

		return $this->redirect(['action' => 'index']);
	}

	public function reenviarLink($id = null) {
		$this->request->allowMethod(['post']);
		$contract = $this->_getContractOrFail($id, ['ContractSignatories']);
		$cid = (int)$contract->id;
		$sigId = (int)$this->request->getData('signatory_id', 0);
		$svc = new ContractNotificationService();

		if ($sigId > 0) {
			foreach ($contract->contract_signatories ?? [] as $s) {
				if ((int)$s->id !== $sigId) {
					continue;
				}
				try {
					$svc->enviarConviteAssinaturaEmail($contract, $s);
					$this->Flash->success(__('E-mail com link de assinatura reenviado para {0}.', $s->nome));
				} catch (\Throwable $e) {
					$this->Flash->error($e->getMessage());
				}

				return $this->redirect(['action' => 'view', $cid]);
			}
			$this->Flash->error(__('Signatário não encontrado.'));
			return $this->redirect(['action' => 'view', $cid]);
		}

		$cFresh = $this->Contracts->get($cid, ['contain' => ['ContractSignatories']]);
		$r = $svc->enviarConvitesAssinaturaTodosComLink($cFresh);
		if ($r['sent'] > 0) {
			$this->Flash->success(__('Reenviados {0} e-mail(s) com link de assinatura.', $r['sent']));
		}
		if ($r['errors'] !== []) {
			$this->Flash->warning(implode(' ', array_slice($r['errors'], 0, 5)));
		} elseif ($r['sent'] === 0 && $r['errors'] === []) {
			$this->Flash->warning(__('Nenhum e-mail enviado. É necessário link por signatário (Autentique) e e-mail remetente configurado (Contract.notifications).'));
		}

		return $this->redirect(['action' => 'view', $cid]);
	}

	public function verRenovacoes($id = null) {
		$this->_getContractOrFail($id);
		return $this->redirect('/modulo-contratos/view/' . (int)$id . '#renovacoes');
	}

	public function aprovarRenovacao($id = null) {
		$rid = (int)$id;
		$idempresa = $this->_idempresa();
		if ($rid <= 0) {
			throw new NotFoundException();
		}
		try {
			$renewal = $this->ContractRenewals->get($rid, ['contain' => ['Contracts']]);
		} catch (\Throwable $e) {
			throw new NotFoundException();
		}
		if ((int)$renewal->contract->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}
		$this->set('renewal', $renewal);
		$this->set('contract', $renewal->contract);
		$this->set('title', __('Aprovar renovação'));

		if ($this->request->is('post')) {
			$svc = new ContractRenewalService();
			try {
				$svc->aprovarRenovacao($renewal, $this->request->getData(), $this->_userId());
				$this->Flash->success(__('Renovação aprovada; novo contrato em rascunho.'));
				return $this->redirect(['action' => 'view', $renewal->contract_id]);
			} catch (\Throwable $e) {
				$this->Flash->error($e->getMessage());
			}
		}
	}

	public function recusarRenovacao($id = null) {
		$this->request->allowMethod(['post']);
		$rid = (int)$id;
		$idempresa = $this->_idempresa();
		if ($rid <= 0) {
			throw new NotFoundException();
		}
		try {
			$renewal = $this->ContractRenewals->get($rid, ['contain' => ['Contracts']]);
		} catch (\Throwable $e) {
			throw new NotFoundException();
		}
		if ((int)$renewal->contract->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}
		$cid = (int)$renewal->contract_id;
		try {
			(new ContractRenewalService())->recusarRenovacao($renewal, $this->request->getData('observacoes'));
			$this->Flash->success(__('Renovação recusada.'));
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}
		return $this->redirect(['action' => 'view', $cid]);
	}

	public function solicitarRenovacao($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractOrFail($id);
		$svc = new ContractRenewalService();
		$r = $svc->solicitarRenovacao($c, $this->_userId());
		if ($r) {
			$this->Flash->success(__('Pedido de renovação registado.'));
		} else {
			$this->Flash->warning(__('Já existe renovação pendente ou aprovada.'));
		}
		return $this->redirect(['action' => 'view', $id]);
	}

	public function downloadPdf($id = null) {
		$c = $this->_getContractOrFail($id);
		$path = (string)$c->get('pdf_path');
		if (!$this->_pathUnderStorage($path)) {
			throw new NotFoundException();
		}
		$code = preg_replace('/[^\w\-\.]+/', '_', (string)$c->get('code'));
		$this->autoRender = false;
		$body = file_get_contents($path);

		return $this->response
			->withType('application/pdf')
			->withHeader('Content-Disposition', 'attachment; filename="Contrato-' . $code . '.pdf"')
			->withStringBody($body !== false ? $body : '');
	}

	public function downloadSigned($id = null) {
		$c = $this->_getContractOrFail($id);
		$path = (string)$c->get('signed_pdf_path');
		if (!$this->_pathUnderStorage($path)) {
			throw new NotFoundException();
		}
		$code = preg_replace('/[^\w\-\.]+/', '_', (string)$c->get('code'));
		$this->autoRender = false;
		$body = file_get_contents($path);

		return $this->response
			->withType('application/pdf')
			->withHeader('Content-Disposition', 'attachment; filename="Contrato-assinado-' . $code . '.pdf"')
			->withStringBody($body !== false ? $body : '');
	}

	public function exportar() {
		$this->autoRender = false;
		$idempresa = $this->_idempresa();
		$rows = $this->Contracts->find()
			->contain(['Clientes'])
			->where(['Contracts.idempresa' => $idempresa])
			->order(['Contracts.code' => 'ASC'])
			->all();

		$csv = fopen('php://temp', 'r+');
		fputcsv($csv, ['code', 'name', 'status', 'cliente', 'start_date', 'end_date', 'monthly_value']);
		foreach ($rows as $c) {
			$cli = '';
			if (!empty($c->cliente)) {
				$cli = (string)($c->cliente->razaosocial ?: $c->cliente->nome);
			}
			fputcsv($csv, [
				$c->code,
				$c->name,
				$c->status,
				$cli,
				$c->start_date ? $c->start_date->format('Y-m-d') : '',
				$c->end_date ? $c->end_date->format('Y-m-d') : '',
				$c->monthly_value,
			]);
		}
		rewind($csv);
		$body = stream_get_contents($csv);
		fclose($csv);

		return $this->response
			->withType('text/csv')
			->withDownload('contratos-export.csv')
			->withStringBody($body !== false ? $body : '');
	}

	public function webhookAutentique() {
		$this->autoRender = false;
		if ($this->request->is('get') || $this->request->is('head')) {
			$msg = 'Webhook Autentique: endpoint ativo. A Autentique deve enviar eventos em POST (JSON). '
				. 'Abrir esta página no browser só confirma a rota; não substitui o POST.';

			return $this->response
				->withType('text/plain; charset=UTF-8')
				->withStringBody($this->request->is('head') ? '' : $msg);
		}
		$raw = (string)file_get_contents('php://input');
		$sig = (string)$this->request->getHeaderLine('X-Autentique-Signature');
		if ($sig === '') {
			$sig = (string)$this->request->getHeaderLine('x-autentique-signature');
		}
		$auth = new AutentiqueService();
		if (!$auth->validarWebhook($raw, $sig)) {
			return $this->response->withStatus(403)->withType('json')
				->withStringBody(json_encode(['ok' => false, 'error' => 'invalid_signature']));
		}
		try {
			$data = json_decode($raw, true);
			if (is_array($data)) {
				$auth->applyWebhookEvent($data);
			}
		} catch (\Throwable $e) {
		}

		return $this->response->withType('json')->withStringBody(json_encode(['ok' => true]));
	}

	/**
	 * @param int $idempresa
	 * @return array
	 */
	protected function _clientesList($idempresa) {
		return $this->Clientes->find('list', [
			'keyField' => 'id',
			'valueField' => function ($row) {
				return $row->razaosocial ?: $row->nome ?: ('#' . $row->id);
			},
		])
			->where(['Clientes.idempresa' => $idempresa])
			->order(['Clientes.razaosocial' => 'ASC', 'Clientes.nome' => 'ASC'])
			->toArray();
	}

	/**
	 * @param int $idempresa
	 * @return array
	 */
	protected function _templatesList($idempresa) {
		return $this->ContractTemplates->find('list', [
			'keyField' => 'id',
			'valueField' => 'nome',
		])
			->where(['ContractTemplates.idempresa' => $idempresa, 'ContractTemplates.ativo' => true])
			->order(['ContractTemplates.nome' => 'ASC'])
			->toArray();
	}
}
