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
	 * GET /servicedesk-prototype/ci/:id — detalhe de Configuration Item (CMDB).
	 *
	 * @param string|int $id
	 * @return \Cake\Http\Response|null
	 */
	public function ci($id) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new NotFoundException(__('CI inválido.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$assets = \Cake\ORM\TableRegistry::getTableLocator()->get('Assets');
		$asset = null;
		try {
			$asset = $assets->find()
				->contain(['Clientes'])
				->where(['Assets.id' => $id, 'Assets.idempresa' => $empresa])
				->first();
		} catch (\Throwable $e) {
		}
		if ($asset === null) {
			throw new NotFoundException(__('CI não encontrado ou fora do seu escopo.'));
		}

		$ticketsAtivos = [];
		try {
			if (\Cake\ORM\TableRegistry::getTableLocator()->get('Assets')->getConnection()->getSchemaCollection()->listTables() && in_array('ticket_assets', \Cake\ORM\TableRegistry::getTableLocator()->get('Assets')->getConnection()->getSchemaCollection()->listTables(), true)) {
				$ta = \Cake\ORM\TableRegistry::getTableLocator()->get('TicketAssets');
				$tids = $ta->find()->select(['ticket_id'])->where(['asset_id' => $id])->extract('ticket_id')->toList();
				$closed = [];
				if (defined('C_TicketSituacaoFechado')) {
					$closed[] = (int)C_TicketSituacaoFechado;
				}
				if (defined('C_TicketSituacaoResolvido')) {
					$closed[] = (int)C_TicketSituacaoResolvido;
				}
				if ($tids !== []) {
					$where = ['Tickets.id IN' => $tids];
					if ($closed !== []) {
						$where['Tickets.situacao NOT IN'] = $closed;
					}
					$q = $this->Tickets->find()
						->select(['id', 'solicitacao', 'situacao', 'prioridade'])
						->where($where)
						->limit(20);
					foreach ($q->all() as $t) {
						$ticketsAtivos[] = [
							'id' => (int)$t->get('id'),
							'assunto' => (string)$t->get('solicitacao'),
							'situacao' => (string)$t->get('situacao'),
							'prioridade' => (string)$t->get('prioridade'),
						];
					}
				}
			}
		} catch (\Throwable $e) {
		}

		$cliente = $asset->cliente ?? null;
		$this->set([
			'title' => __('CI #{0}', $id),
			'sdpNavActive' => 'cmdb',
			'ci' => [
				'id' => (int)$asset->get('id'),
				'tag' => 'CI-' . str_pad((string)$asset->get('id'), 4, '0', STR_PAD_LEFT),
				'descricao' => (string)($asset->get('descricao') ?? ''),
				'tipo' => (string)($asset->get('tipo') ?? $asset->get('categoria') ?? ''),
				'host' => (string)($asset->get('hostname') ?? $asset->get('identificador') ?? ''),
				'modelo' => (string)($asset->get('modelo') ?? ''),
				'fabricante' => (string)($asset->get('fabricante') ?? ''),
				'serial' => (string)($asset->get('numero_serie') ?? $asset->get('serial') ?? ''),
				'cliente' => $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—',
			],
			'ciTickets' => $ticketsAtivos,
		]);

		$screensSvc = new ServicedeskPrototypeScreensService(function (\Cake\ORM\Query $q) {});
		$this->set('sdpNavBadges', $screensSvc->navBadges([
			'tickets' => $this->Tickets,
			'idempresa' => $empresa,
			'userId' => (int)$this->Auth->user('id'),
		]));

		return $this->render('display/ci');
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
