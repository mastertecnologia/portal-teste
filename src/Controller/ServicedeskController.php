<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Routing\Router;

/**
 * Central de Atendimento (Service Desk): mesma lógica de Tickets, layout dedicado e URL /servicedesk.
 */
class ServicedeskController extends TicketsController {

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
		$this->viewBuilder()->setLayout('servicedesk');
		$this->viewBuilder()->setTemplatePath('Tickets');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Service Desk — Painel operacional');
		$this->set('hideLayoutPageTitle', true);
		$this->set('reactBoot', $this->_reactBoot('tech_operacional', null, $this->_servicedeskBootExtra()));
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
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
			$this->set('reactBoot', $this->_reactBoot('client_index', null, array_replace_recursive($extra, [
				'queryAssunto' => $assunto,
				'querySituacao' => $situacao,
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
			'paths' => [
				'indexTecnico' => $sd,
				'indexCliente' => $sd,
				'servicedeskUrl' => $sd,
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
