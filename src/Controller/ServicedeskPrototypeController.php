<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Ticket\ServicedeskPrototypeDataService;
use App\Service\Ticket\ServicedeskPrototypeScreensService;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Service Desk — protótipo (telas do mockup pgm_erp_completo.html).
 * Dados reais via ORM + ABAC.
 */
class ServicedeskPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Tickets');
		$this->loadModel('Clientes');
		$this->loadModel('Users');
	}

	public function beforeFilter(Event $event) {
		$redirect = $this->request->getRequestTarget();
		$staffLogin = [
			'controller' => 'Users',
			'action' => 'acessoEmpresa',
			'prefix' => false,
			'?' => ['redirect' => $redirect],
		];
		$this->Auth->setConfig('loginAction', $staffLogin);
		$this->Auth->setConfig('unauthorizedRedirect', $staffLogin);

		parent::beforeFilter($event);
		$this->viewBuilder()->setLayout('servicedesk_prototype');
	}

	public function isAuthorized($user) {
		if (empty($user)) {
			return false;
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			$this->Flash->error(__('O protótipo Service Desk é só para a equipe técnica. Saia do portal do cliente ou use Acesso PGM / Master.'));

			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		return $this->screen('dashboard');
	}

	public function fila() {
		return $this->screen('fila');
	}

	/**
	 * GET /servicedesk-prototype/ticket/:id
	 *
	 * @param string|int $id
	 * @return \Cake\Http\Response|null
	 */
	public function ticket($id) {
		$ticketId = (int)$id;
		if ($ticketId <= 0) {
			throw new NotFoundException(__('Ticket inválido.'));
		}

		$empresa = (int)$this->Auth->user('idempresa');
		$abac = function (\Cake\ORM\Query $q): void {
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		};
		$dataSvc = new ServicedeskPrototypeDataService($abac);
		$detail = $dataSvc->buildTicketDetailPayload($this->Tickets, $ticketId, $empresa);
		if ($detail === null) {
			throw new NotFoundException(__('Ticket não encontrado ou fora do seu escopo.'));
		}

		$this->set('title', __('Service Desk — Ticket #%s (β)', $ticketId));
		$this->set('sdpNavActive', 'fila');
		$this->set('ticket', $detail);

		$screensSvc = new ServicedeskPrototypeScreensService($abac);
		$this->set('sdpNavBadges', $screensSvc->navBadges([
			'tickets' => $this->Tickets,
			'idempresa' => $empresa,
			'userId' => (int)$this->Auth->user('id'),
		]));

		return $this->render('display/ticket');
	}

	/**
	 * GET /servicedesk-prototype/:page
	 *
	 * @param string $page
	 * @return \Cake\Http\Response|null
	 */
	public function view($page = 'dashboard') {
		return $this->screen($page);
	}

	/**
	 * @param string $page
	 * @return \Cake\Http\Response|null
	 */
	protected function screen(string $page) {
		$defs = $this->screenDefinitions();
		if (!isset($defs[$page])) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}
		$meta = $defs[$page];
		$this->set('title', $meta['title']);
		$this->set('sdpNavActive', $page);

		$empresa = (int)$this->Auth->user('idempresa');
		$userId = (int)$this->Auth->user('id');
		$userName = trim((string)$this->Auth->user('name'));
		if ($userName === '') {
			$userName = trim((string)$this->Auth->user('username'));
		}

		$abac = function (\Cake\ORM\Query $q): void {
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		};
		$dataSvc = new ServicedeskPrototypeDataService($abac);
		$screensSvc = new ServicedeskPrototypeScreensService($abac);

		$ctx = [
			'tickets' => $this->Tickets,
			'clientes' => $this->Clientes,
			'users' => $this->Users,
			'idempresa' => $empresa,
			'userId' => $userId,
			'userName' => $userName,
			'query' => $this->request->getQueryParams(),
		];

		$this->set('sdpNavBadges', $screensSvc->navBadges($ctx));

		$kind = (string)($meta['kind'] ?? '');
		if ($kind === 'executive') {
			$this->set('proto', $dataSvc->buildExecutivePayload($this->Tickets, $empresa, $this->Clientes, $this->Users));
		} elseif ($kind === 'fila') {
			$p = max(1, (int)$this->request->getQuery('page', 1));
			$this->set('filaRef', $dataSvc->buildFilaPagePayload($this->Tickets, $empresa, $p, 30));
		} elseif ($kind === 'kanban') {
			$this->set('kanban', $dataSvc->buildKanbanPayload(
				$this->Tickets,
				$empresa,
				$this->request->getQueryParams()
			));
		} elseif ($kind === 'screen') {
			$screen = $screensSvc->build($page, $ctx);
			if ($page === 'relatorios') {
				$screen['charts'] = $dataSvc->buildRelatoriosPayload(
					$this->Tickets,
					$empresa,
					$this->Clientes,
					$this->Users
				);
			}
			$this->set('screen', $screen);
		}

		return $this->render($meta['template']);
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	protected function screenDefinitions(): array {
		$screen = function (string $title): array {
			return [
				'title' => $title,
				'template' => 'display/screen',
				'kind' => 'screen',
			];
		};

		return [
			'dashboard' => [
				'title' => __('Service Desk — Dashboard (β)'),
				'template' => 'display/dashboard',
				'kind' => 'executive',
			],
			'fila' => [
				'title' => __('Service Desk — Fila técnica (β)'),
				'template' => 'display/fila',
				'kind' => 'fila',
			],
			'kanban' => [
				'title' => __('Service Desk — Kanban (β)'),
				'template' => 'display/kanban',
				'kind' => 'kanban',
			],
			'meus' => $screen(__('Service Desk — Meus tickets (β)')),
			'grupo' => $screen(__('Service Desk — Meu grupo (β)')),
			'aprovacoes' => $screen(__('Service Desk — Aprovações (β)')),
			'cmdb' => $screen(__('Service Desk — CMDB · Ativos (β)')),
			'problemas' => $screen(__('Service Desk — Problemas (β)')),
			'mudancas' => $screen(__('Service Desk — Mudanças (β)')),
			'contratos' => $screen(__('Service Desk — Contratos SLA (β)')),
			'fat' => $screen(__('Service Desk — Faturamento (β)')),
			'kb' => $screen(__('Service Desk — Base de conhecimento (β)')),
			'portal' => $screen(__('Service Desk — Portal cliente (β)')),
			'calendar' => $screen(__('Service Desk — Plantões (β)')),
			'csat' => $screen(__('Service Desk — CSAT & NPS (β)')),
			'relatorios' => $screen(__('Service Desk — Relatórios (β)')),
			'config' => $screen(__('Service Desk — SLA & Config (β)')),
			'perm' => $screen(__('Service Desk — Permissões (β)')),
			'integracoes' => $screen(__('Service Desk — Integrações (β)')),
			'templates' => $screen(__('Service Desk — Templates (β)')),
			'portal-novo' => $screen(__('Service Desk — Abrir chamado (β)')),
			'detalhe-kb' => $screen(__('Service Desk — Artigo KB (β)')),
			'detalhe-fatura' => $screen(__('Service Desk — Detalhe fatura (β)')),
			'automacoes-editor' => $screen(__('Service Desk — Automações (β)')),
		];
	}

}
