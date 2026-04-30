<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\RbacAccessRequestService;
use App\Utility\RbacChecker;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacAccessGrantsController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('RbacAccessGrants');
	}

	public function isAuthorized($user) {
		$user = (array)$user;
		$uid = (int)($user['id'] ?? 0);
		if ($uid <= 0 || (int)($user['role'] ?? -1) !== 0) {
			return false;
		}
		if (!empty($user['admin'])) {
			return true;
		}
		$action = (string)$this->request->getParam('action');
		$map = ['index' => ['rbac.grants.view'], 'revogar' => ['rbac.grants.revoke'], 'renovar' => ['rbac.grants.renew']];
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

	public function index() {
		$this->set('title', 'Acessos concedidos');
		try {
			$query = $this->RbacAccessGrants->find()->order(['id' => 'DESC']);
			$this->paginate = ['limit' => 40, 'maxLimit' => 100];
			$this->set('rows', $this->paginate($query));
		} catch (\Throwable $e) {
			$this->set('grantTableMissing', true);
		}
	}

	public function revogar($id = null) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException('Invalid id');
		}
		$san = new RbacAccessRequestService();
		$reason = $san->sanitizeText((string)$this->request->getData('revoke_reason'), 500);
		try {
			$tbl = $this->RbacAccessGrants;
			$tbl->getConnection()->transactional(function () use ($tbl, $id, $reason) {
				$g = $tbl->find()->where(['id' => $id])->epilog('FOR UPDATE')->first();
				if (!$g || (string)$g->status !== 'active') {
					throw new \RuntimeException('Grant inativo.');
				}
				if (!empty($g->applied_role_assignment)) {
					$t = TableRegistry::get('RbacUsersRoles');
					$ur = $t->find()->where(['user_id' => (int)$g->user_id, 'role_id' => (int)$g->role_id])->first();
					if ($ur) {
						$t->delete($ur);
					}
				}
				$g->status = 'revoked';
				$g->revoked_at = FrozenTime::now();
				$g->revoked_by = (int)$this->Auth->user('id');
				$g->revoke_reason = $reason !== '' ? $reason : null;
				if (!$tbl->save($g)) {
					throw new \RuntimeException('Falha ao salvar.');
				}
				$san = new RbacAccessRequestService();
				$san->logAudit([
					'actor_user_id' => (int)$this->Auth->user('id'),
					'target_user_id' => (int)$g->user_id,
					'access_request_id' => (int)$g->access_request_id,
					'action_type' => 'access_grant_revoked',
					'metadata_json' => json_encode(['grant_id' => (int)$g->id]),
					'ip' => (string)$this->request->clientIp(),
					'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
					'created' => FrozenTime::now(),
				]);
			});
			$this->Flash->success('Revogacao concluida.');
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}

		return $this->redirect(['action' => 'index']);
	}

	public function renovar($id = null) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		$raw = trim((string)$this->request->getData('expires_at'));
		if ($id <= 0 || $raw === '') {
			throw new \Cake\Http\Exception\NotFoundException('Invalid');
		}
		try {
			$dt = FrozenTime::createFromFormat('Y-m-d H:i:s', $raw);
			if ($dt === false) {
				throw new \InvalidArgumentException('Use Y-m-d H:i:s');
			}
			$g = $this->RbacAccessGrants->get($id);
			if ((string)$g->status !== 'active') {
				throw new \RuntimeException('Grant inativo.');
			}
			$g->expires_at = $dt;
			$this->RbacAccessGrants->save($g);
			(new RbacAccessRequestService())->logAudit([
				'actor_user_id' => (int)$this->Auth->user('id'),
				'target_user_id' => (int)$g->user_id,
				'access_request_id' => (int)$g->access_request_id,
				'action_type' => 'access_grant_renewed',
				'metadata_json' => json_encode(['expires_at' => $raw]),
				'ip' => (string)$this->request->clientIp(),
				'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
				'created' => FrozenTime::now(),
			]);
			$this->Flash->success('Data atualizada.');
		} catch (\Throwable $e) {
			$this->Flash->error($e->getMessage());
		}

		return $this->redirect(['action' => 'index']);
	}
}
