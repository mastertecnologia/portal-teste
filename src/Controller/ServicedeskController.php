<?php
namespace App\Controller;

use App\Service\Ticket\SlaReportingService;
use Cake\Event\Event;
use Cake\Routing\Router;

/**
 * Central de Atendimento (Service Desk): mesma lógica de Tickets, layout dedicado e URL /servicedesk.
 */
class ServicedeskController extends TicketsController {

	use ServicedeskWorkflowSlaTrait;

	public function beforeFilter(Event $event) {
		$this->Auth->allow(['index']);
		parent::beforeFilter($event);
	}

	/**
	 * Painel operacional (React): métricas SLA / backlog — somente técnico (role 0).
	 */
	public function operacional() {
		if (!$this->Auth->user()) {
			return $this->redirect(['action' => 'index']);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->redirect(['action' => 'index']);
		}
		/* Mesmo shell do ERP que Histórico (layout default + turbo-frame), não o layout full-page servicedesk. */
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Painel operacional');
		$this->set('reactAppBreadcrumbs', [
			[
				'title' => 'Painel operacional',
				'url' => [],
				'options' => ['class' => 'breadcrumb-item active'],
			],
		]);
		$w = $this->request->getAttribute('webroot');
		$this->set('reactAppExtraCss', [$w . 'dist/css/pages/pgm-servicedesk-premium.css']);
		$this->set('reactBoot', $this->_reactBoot('tech_operacional', null, $this->_servicedeskBootExtra()));
	}

	/**
	 * Relatório de SLA (filtros + KPIs + tabelas por fila/técnico/consumo de horas).
	 */
	public function slaRelatorio() {
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('sla_relatorio');
		$this->set('title', 'Relatório de SLA');

		$idempresa = (int)$this->Auth->user('idempresa');
		$this->loadModel('Clicontratos');
		$this->loadModel('Problemas');

		$f = SlaReportingService::parseFilters($this->request->getQueryParams(), $idempresa);
		$cols = $this->Tickets->getSchema()->columns();

		$clientesList = $this->_slaRelatorioClientesList();
		if ($f['idcliente'] !== null && !isset($clientesList[(int)$f['idcliente']])) {
			$f['idcliente'] = null;
		}
		$tecnicosList = $this->_slaRelatorioTecnicosList();
		if ($f['idtecnico'] !== null && !isset($tecnicosList[(int)$f['idtecnico']])) {
			$f['idtecnico'] = null;
		}
		if ($f['queue_id'] !== null) {
			$qid = (int)$f['queue_id'];
			$okQueue = $this->Queues->find()->where(['id' => $qid])->count() > 0;
			if (!$okQueue) {
				$f['queue_id'] = null;
			}
		}
		if ($f['problema_id'] !== null) {
			$pid = (int)$f['problema_id'];
			if ($this->Problemas->find()->where(['id' => $pid])->count() === 0) {
				$f['problema_id'] = null;
			}
		}
		$clicontratosList = $this->_slaRelatorioClicontratosList($clientesList);
		if ($f['idclicontrato'] !== null && !isset($clicontratosList[(int)$f['idclicontrato']])) {
			$f['idclicontrato'] = null;
		}
		$contratosHorasList = $this->_slaRelatorioContratosHorasList();
		if ($f['id_contrato_horas'] !== null && !isset($contratosHorasList[(int)$f['id_contrato_horas']])) {
			$f['id_contrato_horas'] = null;
		}
		if ($f['ticket_contract_id'] !== null && !in_array('contract_id', $cols, true)) {
			$f['ticket_contract_id'] = null;
		}

		$report = SlaReportingService::buildReport(function () {
			$q = $this->Tickets->find();
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');

			return $q;
		}, $this->Tickets, $cols, $f);

		$sitLabels = $this->_slaRelatorioSitLabels();
		$queuesList = $this->Queues->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->order(['name' => 'ASC'])
			->toArray();

		$problemasList = $this->_slaRelatorioProblemasList();

		$this->set('slaFiltros', $f);
		$this->set('slaReport', $report);
		$this->set('slaClientesList', $clientesList);
		$this->set('slaTecnicosList', $tecnicosList);
		$this->set('slaQueuesList', $queuesList);
		$this->set('slaProblemasList', $problemasList);
		$this->set('slaClicontratosList', $clicontratosList);
		$this->set('slaContratosHorasList', $contratosHorasList);
		$this->set('slaSitLabels', $sitLabels);
		$this->set('slaSchemaCols', $cols);
	}

	/**
	 * @return array<int,string>
	 */
	protected function _slaRelatorioClientesList(): array {
		$clientesJur = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '2']])
			->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesJur, 'Clientes');
		$clientesFis = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '1']])
			->order(['nome']);
		$this->Abac->applyToQuery($clientesFis, 'Clientes');
		$list = [];
		foreach ($clientesJur->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->razaosocial;
		}
		foreach ($clientesFis->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->nome;
		}

		return $list;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _slaRelatorioTecnicosList(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$roleFunc = defined('C_RoleFuncionario') ? (int)C_RoleFuncionario : 0;
		$qry = $this->Empresasusers->find('all', ['contain' => ['Users']])
			->where([
				'Empresasusers.idempresa' => $empresa,
				'Users.role' => $roleFunc,
				'Users.inativo' => 0,
			]);
		$list = [];
		foreach ($qry->order(['Users.name' => 'ASC'])->toArray() as $r) {
			$u = $r->user ?? $r->users ?? null;
			if (!$u) {
				continue;
			}
			$nm = trim((string)($u->name ?? ''));
			if ($nm === '') {
				$nm = trim((string)($u->username ?? ''));
			}
			if ($nm === '') {
				$nm = 'Usuário #' . (int)$u->id;
			}
			$list[(int)$u->id] = $nm;
		}

		return $list;
	}

	/**
	 * @param array<int,string> $clientesList
	 * @return array<int,string>
	 */
	protected function _slaRelatorioClicontratosList(array $clientesList): array {
		$idempresa = (int)$this->Auth->user('idempresa');
		$cids = array_keys($clientesList);
		if ($cids === [] || !in_array('idempresa', $this->Clicontratos->getSchema()->columns(), true)) {
			return [];
		}
		$rows = $this->Clicontratos->find('all')
			->where(['Clicontratos.idempresa' => $idempresa, 'Clicontratos.idcliente IN' => $cids])
			->order(['Clicontratos.id' => 'DESC'])
			->limit(500)
			->toArray();
		$list = [];
		foreach ($rows as $r) {
			$d = (string)($r->descricao ?? '');
			if (mb_strlen($d) > 55) {
				$d = mb_substr($d, 0, 52) . '…';
			}
			$list[(int)$r->id] = 'CL #' . (int)$r->id . ($d !== '' ? ' — ' . $d : '');
		}

		return $list;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _slaRelatorioContratosHorasList(): array {
		if (!in_array('idempresa', $this->ContratosHoras->getSchema()->columns(), true)) {
			return [];
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$rows = $this->ContratosHoras->find('all')
			->where(['ContratosHoras.idempresa' => $idempresa])
			->order(['ContratosHoras.id' => 'DESC'])
			->limit(400)
			->toArray();
		$list = [];
		foreach ($rows as $r) {
			$id = (int)$r->get('id');
			$idc = (int)$r->get('idcliente');
			$list[$id] = 'Horas #' . $id . ' (cliente ' . $idc . ')';
		}

		return $list;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _slaRelatorioProblemasList(): array {
		$schema = $this->Problemas->getSchema();
		$probCols = $schema->columns();
		$labelCol = 'id';
		foreach (['descricao', 'nome', 'titulo'] as $cand) {
			if (in_array($cand, $probCols, true)) {
				$labelCol = $cand;
				break;
			}
		}
		$rows = $this->Problemas->find()
			->select(['id', $labelCol])
			->order([$labelCol => 'ASC'])
			->limit(800)
			->all();
		$list = [];
		foreach ($rows as $r) {
			$lb = (string)($r->get($labelCol) ?? '');
			if ($lb === '' || $labelCol === 'id') {
				$lb = 'Problema #' . (int)$r->get('id');
			}
			$list[(int)$r->get('id')] = $lb;
		}

		return $list;
	}

	/**
	 * @return array<int,string>
	 */
	protected function _slaRelatorioSitLabels(): array {
		$pend = defined('C_TicketSituacaoPendente') ? (int)C_TicketSituacaoPendente : 0;
		$em = defined('C_TicketSituacaoEmandamento') ? (int)C_TicketSituacaoEmandamento : 1;
		$res = defined('C_TicketSituacaoResolvido') ? (int)C_TicketSituacaoResolvido : 2;
		$fec = defined('C_TicketSituacaoFechado') ? (int)C_TicketSituacaoFechado : 3;
		$out = [
			$pend => $this->_ticketSituacaoTexto($pend),
			$em => $this->_ticketSituacaoTexto($em),
			$res => $this->_ticketSituacaoTexto($res),
			$fec => $this->_ticketSituacaoTexto($fec),
		];
		if (defined('C_TicketSituacaoRespondido')) {
			$rsp = (int)C_TicketSituacaoRespondido;
			$out[$rsp] = $this->_ticketSituacaoTexto($rsp);
		}
		if (defined('C_TicketSituacaoCancelado')) {
			$cx = (int)C_TicketSituacaoCancelado;
			$out[$cx] = $this->_ticketSituacaoTexto($cx);
		}

		return $out;
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
		if (in_array($action, [
			'workflowSlaAdmin',
			'workflowSlaPolicies',
			'workflowSla',
			'workflowStates',
			'workflowTransitions',
			'workflowTransition',
			'workflowSlaLogs',
			'workflowSlaDuplicate',
			'workflowSlaEmpresasOptions',
		], true)) {
			return !empty($user) && (int)$user['role'] === 0;
		}
		if ($action === 'slaRelatorio') {
			return !empty($user) && (int)$user['role'] === 0;
		}
		if ($action === 'operacional') {
			return !empty($user) && (int)$user['role'] === 0;
		}
		if ($action === 'index') {
			if (empty($user)) {
				return true;
			}
			return in_array((int)$user['role'], [0, 1], true);
		}
		if (in_array($action, ['edit', 'view', 'add', 'assuntoTicket', 'cancelar', 'downloadAnexo', 'imprimir'], true)) {
			return parent::isAuthorized($user);
		}
		return parent::isAuthorized($user);
	}

	/**
	 * /servicedesk — visitante vê tela de escolha; logado vê fila (técnico ou cliente).
	 */
	public function index() {
		if (!$this->Auth->user()) {
			$this->viewBuilder()->setLayout('servicedesk_login');
			$this->set('title', 'Service Desk — Login');
			$this->viewBuilder()->setTemplate('login');
			return;
		}
		$role = (int)$this->Auth->user('role');
		$this->viewBuilder()->setLayout('servicedesk');
		// react_app.ctp fica em Template/Tickets/ — sem isso o Cake busca Servicedesk/react_app.ctp e quebra.
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Service Desk');
		$this->set('hideLayoutPageTitle', true);

		$extra = $this->_servicedeskBootExtra();
		if ($role === 1) {
			$assunto = $this->request->getQuery('assunto');
			$situacao = $this->request->getQuery('situacao');
			// Cliente: nunca herda inline técnico (ClientTicketList; flag false no boot).
			$this->set('reactBoot', $this->_reactBoot('client_index', null, array_replace_recursive($extra, [
				'queryAssunto' => $assunto,
				'querySituacao' => $situacao,
				'inlineAssignment' => false,
			])));
			return;
		}
		$this->set('reactBoot', $this->_reactBoot('tech_index', null, $extra));
	}

	protected function _servicedeskBootExtra(): array {
		// Caminhos relativos à raiz (sem Router::url(..., true)) evitam /portal/portal/… quando fullBaseUrl ou proxy inclui o subdiretório.
		$sd = Router::url(['controller' => 'Servicedesk', 'action' => 'index']);
		return [
			'servicedesk' => true,
			// Técnico /servicedesk: UI grid inline (boot.inlineAssignment); PATCH em paths continuam só como URLs.
			'inlineAssignment' => true,
			'paths' => [
				'indexTecnico' => $sd,
				'indexCliente' => $sd,
				'servicedeskUrl' => $sd,
				'workflowSlaAdmin' => Router::url(['controller' => 'Servicedesk', 'action' => 'workflowSlaAdmin']),
				'ticketsOperacional' => Router::url(['controller' => 'Servicedesk', 'action' => 'operacional']),
				'addTicket' => Router::url(['controller' => 'Servicedesk', 'action' => 'add']),
				'erpDashboard' => Router::url(['controller' => 'Users', 'action' => 'dashboard']),
				'ticketsClassicIndex' => Router::url(['controller' => 'Tickets', 'action' => 'index']),
				'ticketsClassicCliente' => Router::url(['controller' => 'Tickets', 'action' => 'indexcliente']),
				'ticketEditQuery' => '?sd=1',
				'ticketViewQuery' => '?sd=1',
			],
		];
	}

	/**
	 * Views de ticket ficam em Template/Tickets/; com controller Servicedesk o Cake buscaria Template/Servicedesk/*.ctp.
	 */
	protected function _servicedeskUseTicketsTemplates(): void {
		$this->viewBuilder()->setTemplatePath('Tickets');
	}

	/** Layout shell do Service Desk quando a ação vai renderizar HTML (não download/redirect só). */
	protected function _servicedeskShellLayoutIfRendering(): void {
		if ($this->autoRender === false) {
			return;
		}
		if ($this->viewBuilder()->getLayout() === 'print') {
			return;
		}
		$this->viewBuilder()->setLayout('servicedesk');
	}

	public function add($assunto = null) {
		$this->_servicedeskUseTicketsTemplates();
		$this->viewBuilder()->setTemplate('add');
		$this->viewBuilder()->setLayout('servicedesk');
		parent::add($assunto);
	}

	public function view($idticket = null) {
		$this->_servicedeskUseTicketsTemplates();
		parent::view($idticket);
		$this->_servicedeskShellLayoutIfRendering();
	}

	public function edit($idticket = null) {
		$this->_servicedeskUseTicketsTemplates();
		parent::edit($idticket);
		if ($this->request->getQuery('classic') !== '1' && !$this->request->is(['post', 'put'])) {
			$this->set('hideServicedeskOpenTicketCta', true);
		}
		$this->_servicedeskShellLayoutIfRendering();
	}

	public function cancelar($idticket = null) {
		$this->_servicedeskUseTicketsTemplates();
		parent::cancelar($idticket);
		$this->_servicedeskShellLayoutIfRendering();
	}

	public function imprimir($idticket = null) {
		$this->_servicedeskUseTicketsTemplates();
		parent::imprimir($idticket);
	}
}
