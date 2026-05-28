<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Web Push — gerenciamento de inscrições.
 *
 * Endpoints:
 *   GET  /web-push                 — tela "Ativar notificações"
 *   GET  /web-push/vapid           — JSON com chave pública
 *   POST /web-push/subscribe       — recebe subscription do browser
 *   POST /web-push/unsubscribe     — remove subscription
 *
 * VAPID keys via .env:
 *   WEB_PUSH_VAPID_PUBLIC=BPiZ...
 *   WEB_PUSH_VAPID_PRIVATE=... (só lado servidor / nunca expor)
 */
class WebPushController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('WebPushSubscriptions');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		// CSRF protege POST; precisamos liberar API JSON do bloqueio Security
		if (isset($this->Security)) {
			$this->Security->setConfig('unlockedActions', ['subscribe', 'unsubscribe']);
		}
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['id'] ?? 0) <= 0) {
			return false;
		}

		// Cliente do portal não recebe notificações por enquanto (apenas equipe)
		if ((int)($user['role'] ?? -1) !== 0) {
			return false;
		}

		return true;
	}

	public function index() {
		$uid = (int)$this->Auth->user('id');
		$publicKey = (string)Configure::read('WebPush.public_key', getenv('WEB_PUSH_VAPID_PUBLIC') ?: '');
		$ativas = 0;
		try {
			$ativas = $this->WebPushSubscriptions->find()
				->where(['user_id' => $uid, 'inativo' => 0])
				->count();
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Notificações Push'),
			'erpNavActive' => '',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Notificações'), 'cur' => true],
			],
			'erpEmpresas' => [],
			'vapidPublic' => $publicKey,
			'subscriptionsAtivas' => $ativas,
			'hasPublicKey' => $publicKey !== '',
		]);
	}

	public function vapid() {
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$key = (string)Configure::read('WebPush.public_key', getenv('WEB_PUSH_VAPID_PUBLIC') ?: '');

		return $this->response->withStringBody(json_encode(['ok' => $key !== '', 'public_key' => $key]));
	}

	public function subscribe() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0) {
			return $this->response->withStatus(401)->withStringBody(json_encode(['ok' => false, 'error' => 'auth_required']));
		}
		$endpoint = (string)$this->request->getData('endpoint');
		$p256dh = (string)$this->request->getData('keys.p256dh');
		$auth = (string)$this->request->getData('keys.auth');
		if ($endpoint === '' || $p256dh === '' || $auth === '') {
			return $this->response->withStatus(400)->withStringBody(json_encode(['ok' => false, 'error' => 'invalid_payload']));
		}
		$hash = hash('sha256', $endpoint);
		try {
			$existing = $this->WebPushSubscriptions->find()
				->where(['endpoint_hash' => $hash])
				->first();
			if ($existing !== null) {
				$existing->set('user_id', $uid);
				$existing->set('idempresa', (int)$this->Auth->user('idempresa'));
				$existing->set('p256dh', $p256dh);
				$existing->set('auth', $auth);
				$existing->set('inativo', 0);
				$existing->set('last_seen_at', date('Y-m-d H:i:s'));
				$this->WebPushSubscriptions->save($existing);
			} else {
				$entity = $this->WebPushSubscriptions->newEntity([
					'user_id' => $uid,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'endpoint' => $endpoint,
					'endpoint_hash' => $hash,
					'p256dh' => $p256dh,
					'auth' => $auth,
					'user_agent' => substr((string)$this->request->getEnv('HTTP_USER_AGENT'), 0, 255),
					'last_seen_at' => date('Y-m-d H:i:s'),
					'inativo' => 0,
				]);
				$this->WebPushSubscriptions->save($entity);
			}

			return $this->response->withStringBody(json_encode(['ok' => true]));
		} catch (\Throwable $e) {
			return $this->response->withStatus(500)->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	public function unsubscribe() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$endpoint = (string)$this->request->getData('endpoint');
		if ($endpoint === '') {
			return $this->response->withStatus(400)->withStringBody(json_encode(['ok' => false]));
		}
		$hash = hash('sha256', $endpoint);
		try {
			$row = $this->WebPushSubscriptions->find()->where(['endpoint_hash' => $hash])->first();
			if ($row !== null) {
				$row->set('inativo', 1);
				$this->WebPushSubscriptions->save($row);
			}

			return $this->response->withStringBody(json_encode(['ok' => true]));
		} catch (\Throwable $e) {
			return $this->response->withStatus(500)->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * POST /web-push/test — envia notificação de teste ao usuário logado.
	 */
	public function test() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0) {
			return $this->response->withStatus(401)->withStringBody(json_encode(['ok' => false, 'error' => 'auth_required']));
		}
		$nome = trim((string)$this->Auth->user('name')) ?: __('você');
		$result = (new \App\Service\WebPushSenderService())->sendToUser($uid, [
			'title' => '🔔 Olá, ' . $nome . '!',
			'body' => __('Notificação de teste do PGM ERP às {0}.', date('H:i:s')),
			'url' => $this->Url->build(['controller' => 'WebPush', 'action' => 'index']),
			'tag' => 'pgm-test',
		]);

		return $this->response->withStringBody(json_encode($result));
	}
}
