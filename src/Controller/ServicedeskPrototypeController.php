<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Controller\Traits\PrototypeApiSecurityTrait;
use App\Service\Ticket\ServicedeskPrototypeDataService;
use App\Service\Ticket\ServicedeskPrototypeScreensService;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Service Desk — protótipo (telas do mockup pgm_erp_completo.html).
 * Dados reais via ORM + ABAC.
 */
class ServicedeskPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;
	use PrototypeApiSecurityTrait;

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
		$this->set([
			'loadServicedeskPrototypeCss' => true,
			'disablePgmAppShellPremium' => true,
		]);
	}

	protected function _erpPrototypeDenyPortalUser(): void {
		$this->Flash->error(__('O protótipo Service Desk é só para a equipe técnica. Saia do portal do cliente ou use Acesso PGM / Master.'));
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
		try {
			$dataSvc = new ServicedeskPrototypeDataService($abac);
			$detail = $dataSvc->buildTicketDetailPayload($this->Tickets, $ticketId, $empresa);
		} catch (\Throwable $e) {
			\Cake\Log\Log::error(sprintf('ServicedeskPrototype ticket(%d): %s', $ticketId, $e->getMessage()));
			throw new NotFoundException(__('Ticket indisponível no momento.'));
		}
		if ($detail === null) {
			throw new NotFoundException(__('Ticket não encontrado ou fora do seu escopo.'));
		}

		$this->set('ticket', $detail);

		$screensSvc = new ServicedeskPrototypeScreensService($abac);
		$this->applyErpShell('fila', __('Ticket #%s', $ticketId), [__('Fila técnica'), __('Ticket')], $screensSvc->navBadges([
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
	 * GET /servicedesk-prototype/csat-export.csv — exporta histórico filtrado.
	 */
	public function csatExportCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$q = $this->request->getQueryParams();
		$where = ['TicketCsatResponses.idempresa' => $empresa];
		if (!empty($q['mes']) && preg_match('/^\d{4}-\d{2}$/', (string)$q['mes'])) {
			$where['TicketCsatResponses.responded_at >='] = $q['mes'] . '-01 00:00:00';
			$where['TicketCsatResponses.responded_at <='] = date('Y-m-t 23:59:59', strtotime($q['mes'] . '-01'));
		}
		if (!empty($q['min_csat']) && (int)$q['min_csat'] > 0) {
			$where['TicketCsatResponses.csat_score >='] = (int)$q['min_csat'];
		}
		if (!empty($q['nps'])) {
			$where['TicketCsatResponses.nps_score IS NOT'] = null;
		}
		if (!empty($q['q'])) {
			$where['TicketCsatResponses.comentario ILIKE'] = '%' . $q['q'] . '%';
		}
		try {
			$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$rows = $tbl->find()->contain(['Clientes'])->where($where)->order(['TicketCsatResponses.responded_at' => 'DESC'])->limit(10000)->all();
		} catch (\Throwable $e) {
			$rows = [];
		}
		$this->autoRender = false;
		$fname = 'csat-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');
		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, ['Quando', 'Ticket', 'Cliente', 'CSAT', 'NPS', 'Comentário'], ';');
		foreach ($rows as $r) {
			$cli = $r->cliente ?? null;
			fputcsv($out, [
				$r->get('responded_at') instanceof \DateTimeInterface ? $r->get('responded_at')->format('d/m/Y H:i') : '',
				(int)$r->get('ticket_id'),
				$cli ? (string)($cli->get('razaosocial') ?? $cli->get('nome') ?? '') : '',
				(int)$r->get('csat_score'),
				$r->get('nps_score') !== null ? (int)$r->get('nps_score') : '',
				(string)($r->get('comentario') ?? ''),
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
	}

	/**
	 * GET /servicedesk-prototype/csat-historico — histórico CSAT/NPS com filtros.
	 */
	public function csatHistorico() {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$mes = trim((string)($query['mes'] ?? '')); // YYYY-MM
		$minCsat = (int)($query['min_csat'] ?? 0);
		$apenasNps = (string)($query['nps'] ?? '') === '1';
		$busca = trim((string)($query['q'] ?? ''));

		$itens = [];
		$kpi = ['total' => 0, 'csat_media' => 0.0, 'promotores' => 0, 'neutros' => 0, 'detratores' => 0, 'nps' => 0];
		try {
			$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('TicketCsatResponses');
			$where = ['TicketCsatResponses.idempresa' => $empresa];
			if ($mes !== '' && preg_match('/^\d{4}-\d{2}$/', $mes)) {
				$where['TicketCsatResponses.responded_at >='] = $mes . '-01 00:00:00';
				$where['TicketCsatResponses.responded_at <='] = date('Y-m-t 23:59:59', strtotime($mes . '-01'));
			}
			if ($minCsat > 0 && $minCsat <= 5) {
				$where['TicketCsatResponses.csat_score >='] = $minCsat;
			}
			if ($apenasNps) {
				$where['TicketCsatResponses.nps_score IS NOT'] = null;
			}
			if ($busca !== '') {
				$where['TicketCsatResponses.comentario ILIKE'] = '%' . $busca . '%';
			}
			$q = $tbl->find()->contain(['Tickets', 'Clientes'])->where($where)->order(['TicketCsatResponses.responded_at' => 'DESC'])->limit(300);
			$soma = 0;
			$npsResps = 0;
			foreach ($q->all() as $r) {
				$kpi['total']++;
				$csat = (int)$r->get('csat_score');
				$soma += $csat;
				$nps = $r->get('nps_score');
				if ($nps !== null && $nps !== '') {
					$npsResps++;
					$n = (int)$nps;
					if ($n >= 9) $kpi['promotores']++;
					elseif ($n <= 6) $kpi['detratores']++;
					else $kpi['neutros']++;
				}
				$cli = $r->cliente ?? null;
				$itens[] = [
					'ticket_id' => (int)$r->get('ticket_id'),
					'csat' => $csat,
					'nps' => $nps !== null ? (int)$nps : null,
					'comentario' => (string)($r->get('comentario') ?? ''),
					'data' => $r->get('responded_at'),
					'cliente' => $cli ? (string)($cli->get('razaosocial') ?? $cli->get('nome') ?? '') : '—',
				];
			}
			$kpi['csat_media'] = $kpi['total'] > 0 ? round($soma / $kpi['total'], 2) : 0;
			$kpi['nps'] = $npsResps > 0 ? (int)round((($kpi['promotores'] - $kpi['detratores']) / $npsResps) * 100) : 0;
		} catch (\Throwable $e) {
		}

		$this->set([
			'csatItens' => $itens,
			'csatKpi' => $kpi,
			'csatFiltros' => ['mes' => $mes, 'min_csat' => $minCsat, 'nps' => $apenasNps, 'q' => $busca],
		]);

		$screensSvc = new ServicedeskPrototypeScreensService(function (\Cake\ORM\Query $q) {});
		$this->applyErpShell('csat', __('Histórico CSAT'), [__('CSAT & NPS'), __('Histórico')], $screensSvc->navBadges([
			'tickets' => $this->Tickets, 'idempresa' => $empresa, 'userId' => (int)$this->Auth->user('id'),
		]));

		return $this->render('display/csat_historico');
	}

	/**
	 * GET /servicedesk-prototype/api/notificacoes — JSON com últimas mudanças
	 * relevantes ao usuário (sino do topbar).
	 */
	public function apiNotificacoes() {
		$this->request->allowMethod(['get']);
		if ($guard = $this->guardApiEquipeGet()) {
			return $guard;
		}
		$this->_trackPrototypeApiHit('ServicedeskPrototype.apiNotificacoes');
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$tbl = \Cake\ORM\TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
			$desde = \Cake\I18n\Time::now()->subDays(7);
			$rows = $tbl->find()
				->where(['idempresa' => $empresa, 'created >=' => $desde])
				->order(['created' => 'DESC'])
				->limit(15)
				->all();
			foreach ($rows as $r) {
				$type = (string)$r->get('source_type');
				$sid = (int)$r->get('source_id');
				$url = null;
				if ($type === 'orcamento') {
					$url = $this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $sid]);
				} elseif ($type === 'os') {
					$url = $this->Url->build(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $sid]);
				} elseif ($type === 'ticket') {
					$url = $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $sid]);
				}
				$out[] = [
					'icon' => $type === 'orcamento' ? '📋' : ($type === 'os' ? '🛠' : ($type === 'ticket' ? '🎟' : '🔔')),
					'title' => sprintf('%s #%d', ucfirst($type), $sid),
					'sub' => (string)($r->get('status_from') ?? '—') . ' → ' . (string)$r->get('status_to'),
					'by' => (string)($r->get('actor_name') ?? ''),
					'at' => $r->get('created') instanceof \DateTimeInterface ? $r->get('created')->format('d/m H:i') : '',
					'url' => $url,
				];
			}
		} catch (\Throwable $e) {
		}

		return $this->response->withStringBody(json_encode(['ok' => true, 'items' => $out, 'count' => count($out)], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * GET /servicedesk-prototype/api/badges — JSON com contagens para o sidebar.
	 * Usado pelo long-poll do shell premium (atualiza a cada 30s).
	 */
	public function apiBadges() {
		$this->request->allowMethod(['get']);
		if ($guard = $this->guardApiEquipeGet()) {
			return $guard;
		}
		$this->_trackPrototypeApiHit('ServicedeskPrototype.apiBadges');
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
			// Hook: notifica solicitante via push (assíncrono via send service; falha silenciosa)
			try {
				(new \App\Service\WebPushSenderService())->sendToUser(
					(int)$row->get('user_id'),
					[
						'title' => $aprovar ? '✓ ' . __('Pedido de acesso aprovado') : '✗ ' . __('Pedido de acesso recusado'),
						'body' => $isAdminStage
							? ($aprovar ? __('Admin liberou seu acesso.') : __('Admin recusou.'))
							: ($aprovar ? __('Manager aprovou. Aguardando admin.') : __('Manager recusou.')),
						'url' => $this->Url->build(['controller' => 'RbacAccessRequests', 'action' => 'visualizarPedidoAcesso', (int)$row->get('id')]),
						'tag' => 'rbac-' . (int)$row->get('id'),
					]
				);
			} catch (\Throwable $e) {
			}
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
				// Hook: notifica owner/idtecnico do ticket via push
				try {
					$tecnico = (int)($row->get('owner_id') ?? $row->get('idtecnico_responsavel') ?? 0);
					if ($tecnico > 0) {
						(new \App\Service\WebPushSenderService())->sendToUser($tecnico, [
							'title' => '✓ ' . __('Ticket #{0} fechado', $id),
							'body' => __('Aprovação concluída. Ticket arquivado.'),
							'url' => $this->Url->build(['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $id]),
							'tag' => 'ticket-' . $id,
						]);
					}
				} catch (\Throwable $e) {
				}
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
		$this->applyErpShell('cmdb', __('CI #{0}', $id), [__('CMDB · Ativos'), __('Detalhe')], $screensSvc->navBadges([
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
		$legacy = $this->legacyRedirectForScreen($page);
		if ($legacy !== null) {
			return $this->redirect($legacy);
		}

		$defs = $this->screenDefinitions();
		if (!isset($defs[$page])) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}
		$meta = $defs[$page];

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

		$navBadges = $screensSvc->navBadges($ctx);
		$curLabel = (string)($meta['breadcrumb'] ?? $this->screenBreadcrumbLabel($page));
		$this->applyErpShell($page, (string)$meta['title'], [$curLabel], $navBadges);

		$kind = (string)($meta['kind'] ?? '');
		try {
			if ($kind === 'executive') {
				$this->set('proto', $dataSvc->buildExecutivePayload($this->Tickets, $empresa, $this->Clientes, $this->Users));
			} elseif ($kind === 'fila') {
				$p = max(1, (int)$this->request->getQuery('page', 1));
				$this->set('filaRef', $dataSvc->buildFilaPagePayload(
					$this->Tickets,
					$empresa,
					$p,
					30,
					$this->request->getQueryParams()
				));
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
						$this->Users,
						$this->request->getQueryParams()
					);
				}
				$this->set('screen', $screen);
			}
		} catch (\Throwable $e) {
			\Cake\Log\Log::error(sprintf(
				'ServicedeskPrototype screen(%s) failed: %s',
				$page,
				$e->getMessage()
			), ['exception' => $e]);
			$this->Flash->warning(__('Não foi possível carregar todos os dados desta tela. Exibindo visão simplificada.'));
			$this->applyScreenFallback($kind, $page, $empresa);
		}

		return $this->render($meta['template']);
	}

	/**
	 * Payload mínimo quando build* falha (evita página em branco).
	 *
	 * @param string $kind
	 * @param string $page
	 */
	protected function applyScreenFallback(string $kind, string $page, int $empresa): void {
		if ($kind === 'executive') {
			$this->set('proto', $this->emptyExecutiveProto());

			return;
		}
		if ($kind === 'fila') {
			$this->set('filaRef', [
				'snap' => [],
				'sla' => ['overdue' => 0, 'near_due' => 0, 'paused' => 0],
				'kpis' => [],
				'violados' => [],
				'avg_by_state' => [],
				'fila' => ['rows' => [], 'page' => 1, 'pages' => 1, 'total' => 0],
				'total_empresa' => 0,
				'gerado_em' => date('d/m/Y H:i'),
				'assignment' => [],
				'filters' => [],
				'filter_options' => ['queues' => [], 'tecnicos' => [], 'niveis' => []],
			]);

			return;
		}
		if ($kind === 'kanban') {
			$this->set('kanban', ['columns' => [], 'filters' => []]);

			return;
		}
		if ($kind === 'screen') {
			$this->set('screen', [
				'title' => $this->screenBreadcrumbLabel($page),
				'subtitle' => '',
				'ref_page' => '',
				'kpis' => [],
				'rows' => [],
				'items' => [],
				'empty' => __('Dados temporariamente indisponíveis.'),
			]);
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function emptyExecutiveProto(): array {
		return [
			'snapshot' => [
				'sla_por_etapa' => ['near_due' => 0, 'paused' => 0],
				'sla_operational_kpis' => [],
			],
			'backlog_abac' => 0,
			'tickets_hoje' => 0,
			'tickets_ontem' => 0,
			'sla_violados_total' => 0,
			'sla_violados_lista' => [],
			'vol_diario_14' => [],
			'por_situacao_aberto' => [],
			'top_clientes' => [],
			'top_assuntos' => [],
			'por_categoria' => [],
			'equipe' => [],
			'assuntos_quentes' => [],
			'tickets_abertos_preview' => [],
			'backlog_empresa' => 0,
			'heatmap' => [
				'rows' => [],
				'hours' => range(8, 18),
				'max' => 1,
				'day_labels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'],
			],
			'satisfacao' => [],
			'financeiro' => [],
			'gerado_em' => date('d/m/Y H:i'),
		];
	}

	/**
	 * Telas SD cujo fluxo canónico permanece no módulo legado (sidebar → legado).
	 *
	 * @param string $page
	 * @return array<string,mixed>|null
	 */
	protected function legacyRedirectForScreen(string $page): ?array {
		$map = [
			'perm' => ['controller' => 'Permissoes', 'action' => 'adminUsers'],
			'config' => ['controller' => 'Config', 'action' => 'index'],
			'integracoes' => ['controller' => 'Config', 'action' => 'index'],
			'fat' => ['controller' => 'Faturamento', 'action' => 'index'],
			'relatorios' => ['controller' => 'Tickets', 'action' => 'historico'],
			'templates' => ['controller' => 'Config', 'action' => 'index'],
			'automacoes-editor' => ['controller' => 'Config', 'action' => 'index'],
			'detalhe-fatura' => ['controller' => 'Faturamento', 'action' => 'index'],
		];

		return $map[$page] ?? null;
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

	protected function screenBreadcrumbLabel(string $page): string {
		$labels = [
			'dashboard' => __('Dashboard executivo'),
			'fila' => __('Fila técnica'),
			'kanban' => __('Kanban'),
			'meus' => __('Meus tickets'),
			'grupo' => __('Meu grupo'),
			'aprovacoes' => __('Aprovações'),
			'cmdb' => __('CMDB · Ativos'),
			'problemas' => __('Problemas'),
			'mudancas' => __('Mudanças'),
			'contratos' => __('Contratos SLA'),
			'fat' => __('Faturamento'),
			'kb' => __('Base conhecimento'),
			'portal' => __('Portal cliente'),
			'calendar' => __('Plantões'),
			'csat' => __('CSAT & NPS'),
			'relatorios' => __('Relatórios'),
			'config' => __('SLA & Config'),
			'perm' => __('Permissões'),
			'integracoes' => __('Integrações'),
			'templates' => __('Templates'),
			'portal-novo' => __('Abrir chamado'),
			'detalhe-kb' => __('Artigo KB'),
			'detalhe-fatura' => __('Detalhe fatura'),
			'automacoes-editor' => __('Automações'),
		];

		return $labels[$page] ?? ucfirst(str_replace('-', ' ', $page));
	}

	protected function erpNavKey(string $page): string {
		if ($page === 'dashboard') {
			return 'sd-dashboard';
		}

		return 'sd-' . str_replace('_', '-', $page);
	}

	/**
	 * @param array<string,int> $navBadges
	 */
	protected function applyErpShell(string $page, string $title, array $trail, array $navBadges = []): void {
		$erpBadges = [
			'sd-aprovacoes' => (int)($navBadges['aprovacoes'] ?? 0),
		];
		try {
			$empresas = $this->loadModel('Empresas');
			$erpBadges['empresas'] = (int)$empresas->find()->count();
		} catch (\Throwable $e) {
		}
		$breadcrumb = [['label' => __('Service Desk')]];
		foreach ($trail as $i => $label) {
			$entry = ['label' => (string)$label];
			if ($i === count($trail) - 1) {
				$entry['cur'] = true;
			}
			$breadcrumb[] = $entry;
		}
		$this->set([
			'title' => $title,
			'erpNavActive' => $this->erpNavKey($page),
			'erpNavBadges' => $erpBadges,
			'erpBreadcrumb' => $breadcrumb,
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'loadServicedeskPrototypeCss' => true,
		]);
	}

	/**
	 * @return array<int,array{id:int,nome:string,cnpj:string,current:bool}>
	 */
	protected function loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($tbl->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
				$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
				if ($nome === '') {
					continue;
				}
				$out[] = [
					'id' => (int)$e->get('id'),
					'nome' => $nome,
					'cnpj' => (string)($e->get('cnpj') ?? ''),
					'current' => (int)$e->get('id') === $active,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}

}
