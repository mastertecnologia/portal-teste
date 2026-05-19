<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\AccessDiagnosticService;
use App\Service\RbacApprovalWorkflowService;
use App\Service\RbacAccessNotificationService;
use App\Service\RbacAccessRequestService;
use App\Service\RbacGrantService;
use App\Utility\RbacChecker;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacAccessRequestsController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('RbacAccessRequests');
		$this->loadModel('Users');
		$this->loadModel('RbacChangeAuditLogs');
	}

	public function isAuthorized($user) {
		$user = (array)$user;
		$uid = (int)($user['id'] ?? 0);
		if ($uid <= 0) {
			return false;
		}
		$action = (string)$this->request->getParam('action');
		$map = [
			'solicitarAcesso' => ['rbac.requests.create'],
			'meusPedidosAcesso' => ['rbac.requests.view_own'],
			'pedidosAcesso' => ['rbac.requests.view_all'],
			'visualizarPedidoAcesso' => [
				'rbac.requests.view_all',
				'rbac.requests.view_own',
				'rbac.requests.manager_review',
				'rbac.requests.approve_manager',
				'rbac.requests.admin_review',
				'rbac.requests.approve_admin',
				'rbac.requests.grant',
			],
			'pedidosAcessoManager' => ['rbac.requests.manager_review'],
			'pedidosAcessoAdmin' => ['rbac.requests.admin_review'],
			'aprovarManagerPedidoAcesso' => ['rbac.requests.approve_manager'],
			'rejeitarManagerPedidoAcesso' => ['rbac.requests.reject'],
			'aprovarAdminPedidoAcesso' => ['rbac.requests.approve_admin'],
			'rejeitarAdminPedidoAcesso' => ['rbac.requests.reject'],
			'previewGrantExistingRole' => ['rbac.requests.grant'],
			'executeGrantExistingRole' => ['rbac.requests.grant'],
			'auditLogs' => ['rbac.requests.audit'],
		];
		if (!isset($map[$action])) {
			return false;
		}
		foreach ($map[$action] as $code) {
			if (RbacChecker::userHasPermissionCode($uid, $code)) {
				return true;
			}
		}

		return false;
	}

	public function solicitarAcesso($supportCode = null) {
		$this->request->allowMethod(['post']);
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0) {
			$this->Flash->error('Faça login para solicitar acesso.');

			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}
		$rb = (array)Configure::read('Rbac');
		$diag = isset($rb['diagnostics']) && is_array($rb['diagnostics']) ? $rb['diagnostics'] : [];
		if (empty($diag['allow_user_access_requests'])) {
			$this->Flash->error('Solicitações de acesso estão desativadas.');

			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$supportCode = trim((string)$supportCode);
		$session = $this->request->getSession();
		$cap = (array)$session->read(RbacAccessRequestService::SESSION_ACCESS_REQUEST_CAPTURE);
		if ($supportCode === '' || empty($cap) || (int)($cap['user_id'] ?? 0) !== $uid || (string)($cap['support_code'] ?? '') !== $supportCode) {
			$this->Flash->error('Código de suporte inválido ou expirado.');

			return $this->redirect(['controller' => 'Users', 'action' => 'accessDenied']);
		}
		$ttlMin = isset($diag['support_code_ttl_minutes']) ? (int)$diag['support_code_ttl_minutes'] : 60;
		$ts = (int)($cap['ts'] ?? 0);
		if ($ts <= 0 || (time() - $ts) > (max(1, $ttlMin) * 60)) {
			$this->Flash->error('Código de suporte expirado.');

			return $this->redirect(['controller' => 'Users', 'action' => 'accessDenied']);
		}
		$user = (array)$this->Auth->user();
		$diagOut = [];
		try {
			$svcDiag = new AccessDiagnosticService();
			$diagOut = $svcDiag->diagnoseFromDenialCapture($cap, $user) ?: [];
		} catch (\Throwable $e) {
			$diagOut = [];
		}
		$msg = trim((string)$this->request->getData('requester_message'));
		$svc = new RbacAccessRequestService();
		$res = $svc->createOrReuseFromCapture($uid, $cap, $diagOut, $msg !== '' ? $msg : null);
		if (!empty($res['rate_limited'])) {
			$rate = $svc->checkRateLimit($uid);
			$svc->logAudit([
				'actor_user_id' => $uid,
				'target_user_id' => $uid,
				'action_type' => 'access_request_rate_limited',
				'metadata_json' => json_encode(['support_code' => $supportCode, 'count' => $rate['count'], 'limit' => $rate['limit']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'ip' => (string)$this->request->clientIp(),
				'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
				'created' => FrozenTime::now(),
			]);
			$this->Flash->error('Limite de solicitações atingido. Tente novamente mais tarde.');

			return $this->redirect(['action' => 'meusPedidosAcesso']);
		}
		$row = $res['row'];
		if ($row && !empty($res['created'])) {
			$svc->logAudit([
				'actor_user_id' => $uid,
				'target_user_id' => $uid,
				'access_request_id' => (int)$row->id,
				'action_type' => 'access_request_created',
				'metadata_json' => json_encode(['support_code' => $supportCode], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'ip' => (string)$this->request->clientIp(),
				'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
				'created' => FrozenTime::now(),
			]);
			try {
				$notif = new RbacAccessNotificationService();
				$notif->notifyEvent('access_request_created', [
					'target_user_id' => $uid,
					'access_request_id' => (int)$row->id,
					'routing' => [
						'rota_resumo' => (string)($row->controller ?? '') . '#' . (string)($row->action ?? ''),
					],
				], true);
			} catch (\Throwable $e) {
			}
		}
		if ($res['reused']) {
			$this->Flash->success('Já existe um pedido pendente para esta rota. Acompanhe em "Meus pedidos".');
		} elseif ($res['created']) {
			$session->delete(RbacAccessRequestService::SESSION_ACCESS_REQUEST_CAPTURE);
			$this->Flash->success('Pedido de acesso enviado com sucesso.');
		} else {
			$this->Flash->error('Não foi possível criar o pedido de acesso.');
		}

		return $this->redirect(['action' => 'meusPedidosAcesso']);
	}

	public function meusPedidosAcesso() {
		$uid = (int)$this->Auth->user('id');
		$this->set('title', 'Meus pedidos de acesso');
		$query = $this->RbacAccessRequests->find()
			->where(['user_id' => $uid])
			->order(['id' => 'DESC']);
		$this->paginate = ['limit' => 40, 'maxLimit' => 100];
		$this->set('rows', $this->paginate($query));
	}

	public function pedidosAcesso() {
		$this->set('title', 'Pedidos de acesso (admin)');
		$query = $this->RbacAccessRequests->find()
			->order(['id' => 'DESC']);
		$this->paginate = ['limit' => 40, 'maxLimit' => 100];
		$this->set('rows', $this->paginate($query));
	}

	public function pedidosAcessoManager() {
		$this->set('title', 'Pedidos de acesso â€” fila manager');
		$actor = (array)$this->Auth->user();
		$query = $this->RbacAccessRequests->find()->where(['status' => 'pending_manager'])->order(['id' => 'DESC']);
		if (empty($actor['admin'])) {
			$empresa = (int)($actor['idempresa'] ?? 0);
			if ($empresa <= 0) {
				$query->where(['1 = 0']);
			} else {
				$sub = $this->Users->find()->select(['id'])->where(['idempresa' => $empresa]);
				$query->where(['user_id IN' => $sub]);
			}
		}
		$this->set('rows', $query->all());
		$this->render('pedidos_acesso');
	}

	public function pedidosAcessoAdmin() {
		$this->set('title', 'Pedidos de acesso â€” fila admin');
		$rows = $this->RbacAccessRequests->find()->where(['status IN' => ['manager_approved', 'pending_admin']])->order(['id' => 'DESC'])->all();
		$this->set('rows', $rows);
		$this->render('pedidos_acesso');
	}

	public function visualizarPedidoAcesso($id = null) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido inválido.');
		}
		$row = $this->RbacAccessRequests->find()
			->where(['RbacAccessRequests.id' => $id])
			->first();
		if (!$row) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido não encontrado.');
		}
		if (!$this->_viewerCanSeeRequestRow($row)) {
			throw new \Cake\Http\Exception\ForbiddenException('Sem permissão para este pedido.');
		}
		$this->set('title', 'Pedido de acesso #' . $id);
		$vid = (int)$this->Auth->user('id');
		$privilegedRequestView = RbacChecker::userHasPermissionCode($vid, 'rbac.requests.view_all')
			|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.admin_review')
			|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_admin')
			|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.grant');
		if (!$privilegedRequestView) {
			$wfTmp = new RbacApprovalWorkflowService();
			if (RbacChecker::userHasPermissionCode($vid, 'rbac.requests.manager_review')
				|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_manager')) {
				$privilegedRequestView = $wfTmp->managerCanReview((array)$this->Auth->user(), (int)$row->user_id);
			}
		}

		$diag = null;
		if ($privilegedRequestView) {
			try {
				$user = $this->Users->find()->where(['id' => (int)$row->user_id])->first();
				if ($user) {
					$svc = new AccessDiagnosticService();
					$diag = $svc->diagnoseWithSimulatorContext(
						$user->toArray(),
						(string)$row->controller,
						(string)$row->action,
						[
							'prefix' => (string)($row->prefix ?? ''),
							'plugin' => (string)($row->plugin ?? ''),
						]
					);
				}
			} catch (\Throwable $e) {
				$diag = null;
			}
		}

		$wf = new RbacApprovalWorkflowService();
		$canActAsManager = ((string)$row->status === 'pending_manager')
			&& RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_manager')
			&& $wf->managerCanReview((array)$this->Auth->user(), (int)$row->user_id);

		$canActAsAdmin = in_array((string)$row->status, ['pending_admin', 'manager_approved'], true)
			&& RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_admin');

		$canPreviewGrant = ((string)$row->status === 'admin_approved')
			&& RbacChecker::userHasPermissionCode($vid, 'rbac.requests.grant');

		$this->set(compact('row', 'diag', 'privilegedRequestView', 'canActAsManager', 'canActAsAdmin', 'canPreviewGrant'));
	}

	public function aprovarPedidoAcesso($id = null) {
		return $this->aprovarAdminPedidoAcesso($id);
	}

	public function rejeitarPedidoAcesso($id = null) {
		return $this->rejeitarAdminPedidoAcesso($id);
	}

	public function aprovarManagerPedidoAcesso($id = null) {
		return $this->_reviewManager($id, true);
	}

	public function rejeitarManagerPedidoAcesso($id = null) {
		return $this->_reviewManager($id, false);
	}

	public function aprovarAdminPedidoAcesso($id = null) {
		return $this->_reviewAdmin($id, true);
	}

	public function rejeitarAdminPedidoAcesso($id = null) {
		return $this->_reviewAdmin($id, false);
	}

	public function previewGrantExistingRole($id = null) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido inválido.');
		}
		$this->set('title', 'Pré-visualizar liberação automática');
		$request = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
		if (!$request) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido não encontrado.');
		}
		if (!$this->_viewerCanSeeRequestRow($request)) {
			throw new \Cake\Http\Exception\ForbiddenException('Sem permissão para este pedido.');
		}
		$roles = TableRegistry::get('RbacRoles')->find()->where(['active' => true])->order(['name' => 'ASC'])->all();
		$roleId = (int)$this->request->getQuery('role_id');
		$preview = null;
		if ($roleId > 0) {
			try {
				$svc = new RbacGrantService();
				$preview = $svc->previewAssignExistingRole($id, $roleId, (array)$this->Auth->user());
			} catch (\Throwable $e) {
				$this->Flash->error($e->getMessage());
			}
		}
		$this->set(compact('request', 'roles', 'preview', 'roleId'));
	}

	public function executeGrantExistingRole($id = null) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		$rowBeforeGate = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
		if (!$rowBeforeGate || !$this->_viewerCanSeeRequestRow($rowBeforeGate)) {
			throw new \Cake\Http\Exception\ForbiddenException('Sem permissão para este pedido.');
		}

		$roleId = (int)$this->request->getData('role_id');
		$just = trim((string)$this->request->getData('justification'));
		$svc = new RbacGrantService();
		try {
			$rowBefore = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
			$result = $svc->executeAssignExistingRole($id, $roleId, (array)$this->Auth->user(), $just);
			$rowAfter = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
			if (!empty($result['applied'])) {
				$svcReq = new RbacAccessRequestService();
				$safeJust = $svcReq->sanitizeText($just, 300);
				$svcReq->logAudit([
					'actor_user_id' => (int)$this->Auth->user('id'),
					'target_user_id' => (int)($result['user_id'] ?? 0),
					'access_request_id' => $id,
					'action_type' => 'role_assigned',
					'before_json' => json_encode($this->_requestAuditSnapshot($rowBefore), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
					'after_json' => json_encode($this->_requestAuditSnapshot($rowAfter), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
					'metadata_json' => json_encode(['role_id' => $roleId, 'justification' => $safeJust], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
					'ip' => (string)$this->request->clientIp(),
					'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
					'created' => FrozenTime::now(),
				]);
				$this->Flash->success('Liberação automática executada com sucesso.');
				try {
					$n = new RbacAccessNotificationService();
					$ctx = [
						'target_user_id' => (int)($result['user_id'] ?? 0),
						'access_request_id' => $id,
						'routing' => ['role_aplicado' => 'sim', 'grant_id_interno' => (string)$id],
					];
					$n->notifyEvent('access_granted', $ctx, true);
				} catch (\Throwable $e) {
				}
			} else {
				$this->Flash->success('Papel já estava aplicado anteriormente (idempotente).');
			}
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}

		return $this->redirect(['action' => 'visualizarPedidoAcesso', $id]);
	}

	public function auditLogs() {
		$this->set('title', 'Auditoria de mudanças RBAC');
		$query = $this->RbacChangeAuditLogs->find()->order(['id' => 'DESC']);
		$this->paginate = ['limit' => 80, 'maxLimit' => 200];
		$this->set('rows', $this->paginate($query));
	}

	protected function _reviewManager($id, bool $approve) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido inválido.');
		}
		$row = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
		if (!$row) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido não encontrado.');
		}
		if ($row->status !== 'pending_manager') {
			$this->Flash->error('Pedido já foi revisado.');

			return $this->redirect(['action' => 'visualizarPedidoAcesso', $id]);
		}
		$wf = new RbacApprovalWorkflowService();
		if (!$wf->managerCanReview((array)$this->Auth->user(), (int)$row->user_id)) {
			$this->Flash->error('Você não pode revisar pedidos fora da sua equipe.');

			return $this->redirect(['action' => 'pedidosAcessoManager']);
		}
		$before = $row->toArray();
		$svcReq = new RbacAccessRequestService();
		$response = $svcReq->sanitizeText((string)$this->request->getData('manager_response'), 500);
		if ($approve) {
			$wf->approveManager($row, (array)$this->Auth->user(), $response);
		} else {
			$wf->rejectManager($row, (array)$this->Auth->user(), $response);
		}
		$saved = $this->RbacAccessRequests->save($row);
		if ($saved) {
			$svcReq->syncApprovalInbox($row);
			$svcReq->logAudit([
				'actor_user_id' => (int)$this->Auth->user('id'),
				'target_user_id' => (int)$row->user_id,
				'access_request_id' => (int)$row->id,
				'action_type' => $approve ? 'access_request_manager_approved' : 'access_request_manager_rejected',
				'before_json' => json_encode($this->_requestAuditSnapshot((object)$before), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'after_json' => json_encode($this->_requestAuditSnapshot($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'ip' => (string)$this->request->clientIp(),
				'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
				'created' => FrozenTime::now(),
			]);
			if ($approve) {
				$stageBefore = $row->toArray();
				$wf->enqueueForAdmin($row);
				if ($this->RbacAccessRequests->save($row)) {
					$svcReq->syncApprovalInbox($row);
					$svcReq->logAudit([
						'actor_user_id' => (int)$this->Auth->user('id'),
						'target_user_id' => (int)$row->user_id,
						'access_request_id' => (int)$row->id,
						'action_type' => 'access_request_sent_to_admin_queue',
						'before_json' => json_encode($this->_requestAuditSnapshot((object)$stageBefore), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
						'after_json' => json_encode($this->_requestAuditSnapshot($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
						'ip' => (string)$this->request->clientIp(),
						'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
						'created' => FrozenTime::now(),
					]);
				}
				$this->Flash->success('Manager aprovou. Pedido enviado para fila admin.');
				try {
					(new RbacAccessNotificationService())->notifyEvent('manager_approved', [
						'target_user_id' => (int)$row->user_id,
						'access_request_id' => (int)$row->id,
						'routing' => ['etapa' => 'fila_admin'],
					], true);
				} catch (\Throwable $e) {
				}
			} else {
				$this->Flash->success('Manager rejeitou o pedido.');
				try {
					(new RbacAccessNotificationService())->notifyEvent('manager_rejected', [
						'target_user_id' => (int)$row->user_id,
						'access_request_id' => (int)$row->id,
					], false);
				} catch (\Throwable $e) {
				}
			}
		} else {
			$this->Flash->error('Não foi possível revisar o pedido.');
		}

		return $this->redirect(['action' => 'visualizarPedidoAcesso', $id]);
	}

	protected function _reviewAdmin($id, bool $approve) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido inválido.');
		}
		$row = $this->RbacAccessRequests->find()->where(['id' => $id])->first();
		if (!$row) {
			throw new \Cake\Http\Exception\NotFoundException('Pedido não encontrado.');
		}
		if (!in_array((string)$row->status, ['pending_admin', 'manager_approved'], true)) {
			$this->Flash->error('Pedido não está na fila admin.');

			return $this->redirect(['action' => 'visualizarPedidoAcesso', $id]);
		}
		$before = $row->toArray();
		$svcReq = new RbacAccessRequestService();
		$resp = $svcReq->sanitizeText((string)$this->request->getData('admin_response'), 500);
		$wf = new RbacApprovalWorkflowService();
		if ($approve) {
			$wf->approveAdmin($row, (array)$this->Auth->user(), $resp);
		} else {
			$wf->rejectAdmin($row, (array)$this->Auth->user(), $resp);
		}
		$saved = $this->RbacAccessRequests->save($row);
		if ($saved) {
			$svcReq->syncApprovalInbox($row);
			$svcReq->logAudit([
				'actor_user_id' => (int)$this->Auth->user('id'),
				'target_user_id' => (int)$row->user_id,
				'access_request_id' => (int)$row->id,
				'action_type' => $approve ? 'access_request_approved' : 'access_request_rejected',
				'before_json' => json_encode($this->_requestAuditSnapshot((object)$before), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'after_json' => json_encode($this->_requestAuditSnapshot($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'metadata_json' => json_encode(['note' => 'Admin review'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'ip' => (string)$this->request->clientIp(),
				'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
				'created' => FrozenTime::now(),
			]);
			$this->Flash->success($approve ? 'Admin aprovou o pedido.' : 'Admin rejeitou o pedido.');
			try {
				$n = new RbacAccessNotificationService();
				$base = [
					'target_user_id' => (int)$row->user_id,
					'access_request_id' => (int)$row->id,
					'routing' => [
						'rota_pedido' => (string)$row->controller . '#' . (string)$row->action,
					],
				];
				if ($approve) {
					$n->notifyEvent('admin_approved', $base, true);
				} else {
					$n->notifyEvent('admin_rejected', $base, true);
				}
			} catch (\Throwable $e) {
			}
		} else {
			$this->Flash->error('Não foi possível revisar o pedido.');
		}

		return $this->redirect(['action' => 'visualizarPedidoAcesso', $id]);
	}

	protected function _viewerCanSeeRequestRow($row): bool {
		$viewer = (array)$this->Auth->user();
		$vid = (int)($viewer['id'] ?? 0);
		if ($vid <= 0) {
			return false;
		}
		if (RbacChecker::userHasPermissionCode($vid, 'rbac.requests.view_all')) {
			return true;
		}
		if (RbacChecker::userHasPermissionCode($vid, 'rbac.requests.view_own') && (int)$row->user_id === $vid) {
			return true;
		}
		if (RbacChecker::userHasPermissionCode($vid, 'rbac.requests.admin_review')
			|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_admin')
			|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.grant')) {
			return true;
		}
		$wf = new RbacApprovalWorkflowService();

		return (RbacChecker::userHasPermissionCode($vid, 'rbac.requests.manager_review')
				|| RbacChecker::userHasPermissionCode($vid, 'rbac.requests.approve_manager'))
			&& $wf->managerCanReview($viewer, (int)$row->user_id);
	}

	protected function _requestAuditSnapshot($row): array {
		$arr = is_array($row) ? $row : (is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array)$row);

		return [
			'id' => (int)($arr['id'] ?? 0),
			'user_id' => (int)($arr['user_id'] ?? 0),
			'status' => (string)($arr['status'] ?? ''),
			'support_code' => (string)($arr['support_code'] ?? ''),
			'controller' => (string)($arr['controller'] ?? ''),
			'action' => (string)($arr['action'] ?? ''),
			'manager_reviewed_by' => isset($arr['manager_reviewed_by']) ? (int)$arr['manager_reviewed_by'] : null,
			'admin_reviewed_by' => isset($arr['admin_reviewed_by']) ? (int)$arr['admin_reviewed_by'] : null,
			'modified' => (string)($arr['modified'] ?? ''),
		];
	}
}
