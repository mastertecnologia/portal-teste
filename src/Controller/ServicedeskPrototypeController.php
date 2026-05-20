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
	 * GET /servicedesk-prototype/api/badges — JSON com contagens para o sidebar.
	 * Usado pelo long-poll do shell premium (atualiza a cada 30s).
	 */
	public function apiBadges() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$userId = (int)$this->Auth->user('id');

		$abac = function (\Cake\ORM\Query $q): void {
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		};
		$svc = new ServicedeskPrototypeScreensService($abac);
		$badges = $svc->navBadges([
			'tickets' => $this->Tickets,
			'idempresa' => $empresa,
			'userId' => $userId,
		]);

		// Sidebar do erp_prototype usa chaves como 'sd-aprovacoes' / 'empresas'.
		$out = [
			'sd-aprovacoes' => (int)($badges['aprovacoes'] ?? 0),
		];
		try {
			$empresas = $this->loadModel('Empresas');
			$out['empresas'] = (int)$empresas->find()->count();
		} catch (\Throwable $e) {
		}

		return $this->response->withStringBody(json_encode(['ok' => true, 'badges' => $out, 'ts' => time()], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * POST /servicedesk-prototype/aprovacao/:source_type/:source_id/:decisao
	 * Aplica decisão (aprovar/reprovar) na pendência da fila SD.
	 *
	 * @param string|null $sourceType ex.: rbac, orcamento, ren, tkt-res
	 * @param string|null $sourceId   id do registro de origem
	 * @param string|null $decisao    aprovar | reprovar
	 */
	public function aprovacao($sourceType = null, $sourceId = null, $decisao = null) {
		$this->request->allowMethod(['post']);
		$sourceType = (string)$sourceType;
		$sourceId = (int)$sourceId;
		$decisao = (string)$decisao;
		if ($sourceId <= 0 || !in_array($decisao, ['aprovar', 'reprovar'], true)) {
			$this->Flash->error(__('Decisão inválida.'));

			return $this->redirect(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes']);
		}
		$nota = trim((string)$this->request->getData('nota'));
		$user = (array)$this->Auth->user();
		try {
			switch ($sourceType) {
				case 'rbac':
					$this->aprovacaoRbac($sourceId, $decisao === 'aprovar', $nota, $user);
					break;
				case 'orc':
					$this->aprovacaoOrcamento($sourceId, $decisao === 'aprovar', $user);
					break;
				case 'ren':
					$this->aprovacaoRenovacao($sourceId, $decisao === 'aprovar', $nota, $user);
					break;
				case 'tkt-res':
					$this->aprovacaoTicketFechamento($sourceId, $decisao === 'aprovar', $user);
					break;
				default:
					$this->Flash->error(__('Tipo de aprovação não suportado nesta tela.'));

					return $this->redirect(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes']);
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro ao decidir: {0}', $e->getMessage()));
		}

		return $this->redirect(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes']);
	}

	protected function aprovacaoRbac(int $id, bool $aprovar, string $nota, array $user): void {
		$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('RbacAccessRequests');
		$row = $tbl->find()->where(['id' => $id])->first();
		if ($row === null) {
			$this->Flash->error(__('Pedido RBAC não encontrado.'));

			return;
		}
		$status = (string)$row->get('status');
		$isAdminStage = in_array($status, ['pending_admin', 'manager_approved'], true);
		$isAdmin = !empty($user['admin']);
		$wf = new \App\Service\RbacApprovalWorkflowService();

		if ($isAdminStage) {
			if (!$isAdmin) {
				$this->Flash->error(__('Pedido em fila admin: apenas administradores podem decidir.'));

				return;
			}
			if ($aprovar) {
				$wf->approveAdmin($row, $user, $nota);
			} else {
				$wf->rejectAdmin($row, $user, $nota);
			}
			$msg = $aprovar
				? __('Admin aprovou o pedido (etapa final). Acesso liberado.')
				: __('Admin recusou o pedido.');
		} else {
			if (!$wf->managerCanReview($user, (int)$row->get('user_id'))) {
				$this->Flash->error(__('Você não pode revisar este pedido (não está na equipe do solicitante).'));

				return;
			}
			if ($aprovar) {
				$wf->approveManager($row, $user, $nota);
			} else {
				$wf->rejectManager($row, $user, $nota);
			}
			$msg = $aprovar
				? __('Manager aprovou. Pedido encaminhado à fila admin.')
				: __('Manager recusou o pedido.');
		}
		if ($tbl->save($row)) {
			// Avança para fila admin automaticamente após manager aprovar
			if (!$isAdminStage && $aprovar) {
				$wf->enqueueForAdmin($row);
				$tbl->save($row);
			}
			(new \App\Service\RbacAccessRequestService())->syncApprovalInbox($row);
			$this->Flash->success($msg);
		}
	}

	protected function aprovacaoOrcamento(int $id, bool $aprovar, array $user): void {
		$orcs = \Cake\ORM\TableRegistry::getTableLocator()->get('Orcamentos');
		$row = $orcs->find()->where(['id' => $id, 'idempresa' => (int)($user['idempresa'] ?? 0)])->first();
		if ($row === null) {
			$this->Flash->error(__('Orçamento fora do escopo.'));

			return;
		}
		$row->set('status', $aprovar
			? (defined('C_OrcamentoStatusAprovado') ? (int)C_OrcamentoStatusAprovado : 2)
			: (defined('C_OrcamentoStatusRecusado') ? (int)C_OrcamentoStatusRecusado : 3));
		if ($orcs->save($row)) {
			$this->Flash->success(__('Orçamento #{0} {1}.', (int)$row->get('id'), $aprovar ? __('aprovado') : __('recusado')));
		}
	}

	protected function aprovacaoRenovacao(int $id, bool $aprovar, string $nota, array $user): void {
		$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('ContractRenewals');
		$row = $tbl->find()->where(['id' => $id])->first();
		if ($row === null) {
			$this->Flash->error(__('Renovação não encontrada.'));

			return;
		}
		$row->set('status', $aprovar ? 'aprovada' : 'recusada');
		$row->set('aprovado_em', date('Y-m-d H:i:s'));
		$row->set('aprovado_por', (int)($user['id'] ?? 0));
		if ($nota !== '') {
			$row->set('observacoes', $nota);
		}
		if ($tbl->save($row)) {
			$this->Flash->success(__('Renovação {0}.', $aprovar ? __('aprovada') : __('recusada')));
		}
	}

	protected function aprovacaoTicketFechamento(int $id, bool $aprovar, array $user): void {
		$row = $this->Tickets->find()->where(['id' => $id, 'idempresa' => (int)($user['idempresa'] ?? 0)])->first();
		if ($row === null) {
			$this->Flash->error(__('Ticket fora do escopo.'));

			return;
		}
		if (!$aprovar) {
			if (defined('C_TicketSituacaoEmandamento')) {
				$row->set('situacao', (int)C_TicketSituacaoEmandamento);
				$this->Tickets->save($row);
			}
			$this->Flash->info(__('Ticket #{0} retornado para execução.', $id));

			return;
		}
		if (defined('C_TicketSituacaoFechado')) {
			$row->set('situacao', (int)C_TicketSituacaoFechado);
			$row->set('data_fechamento', date('Y-m-d H:i:s'));
			if ($this->Tickets->save($row)) {
				$this->Flash->success(__('Ticket #{0} fechado.', $id));
			}
		}
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
