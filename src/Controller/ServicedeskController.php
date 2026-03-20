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

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
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
			$this->viewBuilder()->setLayout('servicedesk');
			$this->set('title', 'Service Desk — Acesso');
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
		$w = $this->request->getAttribute('webroot');
		$sd = Router::url(['controller' => 'Servicedesk', 'action' => 'index'], true);
		return [
			'servicedesk' => true,
			'paths' => [
				'indexTecnico' => $sd,
				'indexCliente' => $sd,
				'servicedeskUrl' => $sd,
				'erpDashboard' => Router::url(['controller' => 'Users', 'action' => 'dashboard'], true),
				'ticketsClassicIndex' => Router::url(['controller' => 'Tickets', 'action' => 'index'], true),
				'ticketsClassicCliente' => Router::url(['controller' => 'Tickets', 'action' => 'indexcliente'], true),
				'ticketEditQuery' => '?sd=1',
				'ticketViewQuery' => '?sd=1',
			],
		];
	}
}
