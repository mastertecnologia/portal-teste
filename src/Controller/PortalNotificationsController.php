<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\ClienteDomain\InfrastructureGuard;
use App\Utility\ClienteDomainEventType;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * API JSON para o sino de notificações (camada nova; não altera NotificacoesController legado).
 */
class PortalNotificationsController extends AppController {

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function preferences() {
		$this->set('title', 'Preferências de notificações');
		if (!InfrastructureGuard::isReady()) {
			$this->Flash->error('Execute a migration do módulo de notificações para usar esta tela.');

			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$uid = (int)$this->Auth->user('id');
		$codes = ClienteDomainEventType::preferenceEventTypes();
		$rows = [];
		try {
			$Prefs = TableRegistry::get('PortalNotificationPreferences');
			$saved = $Prefs->find()->where(['user_id' => $uid])->indexBy('event_type')->toArray();
		} catch (\Throwable $e) {
			$saved = [];
		}
		foreach ($codes as $code => $label) {
			$p = $saved[$code] ?? null;
			$rows[] = [
				'code' => $code,
				'label' => $label,
				'send_in_app' => $p ? (int)$p->send_in_app === 1 : true,
				'send_email' => $p ? (int)$p->send_email === 1 : false,
			];
		}
		$this->set('prefRows', $rows);
	}

	public function savePreferences() {
		$this->request->allowMethod(['post']);
		if (!InfrastructureGuard::isReady()) {
			$this->Flash->error('Módulo de notificações indisponível.');

			return $this->redirect(['action' => 'preferences']);
		}
		$uid = (int)$this->Auth->user('id');
		$allowed = array_keys(ClienteDomainEventType::preferenceEventTypes());
		$codes = (array)$this->request->getData('codes');
		$prefs = (array)$this->request->getData('prefs');
		try {
			$Prefs = TableRegistry::get('PortalNotificationPreferences');
			foreach ($codes as $i => $code) {
				$code = (string)$code;
				if (!in_array($code, $allowed, true)) {
					continue;
				}
				$row = $prefs[$i] ?? [];
				$inApp = !empty($row['send_in_app']);
				$email = !empty($row['send_email']);
				$e = $Prefs->find()->where(['user_id' => $uid, 'event_type' => $code])->first();
				if ($e === null) {
					$e = $Prefs->newEntity(['user_id' => $uid, 'event_type' => $code]);
				}
				$e->send_in_app = $inApp ? 1 : 0;
				$e->send_email = $email ? 1 : 0;
				$Prefs->save($e);
			}
			$this->Flash->success('Preferências salvas.');
		} catch (\Throwable $ex) {
			$this->Flash->error('Não foi possível salvar as preferências.');
		}

		return $this->redirect(['action' => 'preferences']);
	}

	public function unreadCount() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$this->response = $this->response->withType('json');
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0 || !InfrastructureGuard::isReady()) {
			return $this->response->withStringBody(json_encode(['count' => 0]));
		}
		try {
			$n = TableRegistry::get('PortalInternalNotifications')
				->find()
				->where(['user_id' => $uid, 'is_read' => 0])
				->count();
		} catch (\Throwable $e) {
			$n = 0;
		}

		return $this->response->withStringBody(json_encode(['count' => (int)$n]));
	}

	public function listJson() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$this->response = $this->response->withType('json');
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0 || !InfrastructureGuard::isReady()) {
			return $this->response->withStringBody(json_encode(['items' => []]));
		}
		try {
			$rows = TableRegistry::get('PortalInternalNotifications')
				->find()
				->where(['user_id' => $uid])
				->order(['created' => 'DESC'])
				->limit(30)
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}
		$items = [];
		foreach ($rows as $r) {
			$au = $r->action_url ? (string)$r->action_url : '';
			$fullUrl = null;
			if ($au !== '') {
				$fullUrl = $this->_absolutizeNotificationActionUrl($au);
			}
			$items[] = [
				'id' => (int)$r->id,
				'type' => (string)$r->type,
				'title' => (string)$r->title,
				'message' => (string)$r->message,
				'action_url' => $fullUrl,
				'is_read' => (int)$r->is_read === 1,
				'created' => $r->created ? $r->created->format('c') : null,
				'created_human' => $r->created ? $r->created->i18nFormat("dd/MM/yyyy HH:mm") : '',
			];
		}

		return $this->response->withStringBody(json_encode(['items' => $items], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * action_url no BD costuma ser caminho absoluto no host já com o base da app (ex.: /portal/clicontratos/edit/1),
	 * gerado por Router::url([...], false) em web ou cron. Router::url($au, true) duplicaria o prefixo
	 * (…/portal/portal/… → MissingController Portal).
	 */
	protected function _absolutizeNotificationActionUrl(string $au): string {
		$au = trim($au);
		if ($au === '') {
			return '';
		}
		if (preg_match('#^https?://#i', $au)) {
			return $this->_stripDuplicateAppBaseInUrl($au);
		}
		$fullBase = rtrim((string)Router::fullBaseUrl(), '/');
		if ($au[0] !== '/') {
			return Router::url($au, true);
		}
		$p = parse_url($fullBase);
		$scheme = $p['scheme'] ?? 'http';
		$host = $p['host'] ?? '';
		if ($host === '') {
			return Router::url($au, true);
		}
		$port = !empty($p['port']) ? ':' . $p['port'] : '';
		$origin = $scheme . '://' . $host . $port;
		$basePath = isset($p['path']) ? rtrim($p['path'], '/') : '';

		if ($basePath !== '' && (strpos($au, $basePath . '/') === 0 || $au === $basePath)) {
			return $this->_stripDuplicateAppBaseInUrl($origin . $au);
		}

		return $this->_stripDuplicateAppBaseInUrl($fullBase . $au);
	}

	/**
	 * Remove padrão /{base}/{base}/ quando fullBaseUrl já foi aplicado por engano.
	 */
	protected function _stripDuplicateAppBaseInUrl(string $url): string {
		$p = parse_url(Router::fullBaseUrl());
		$basePath = isset($p['path']) ? trim($p['path'], '/') : '';
		if ($basePath === '') {
			return $url;
		}
		$dup = '/' . $basePath . '/' . $basePath . '/';

		return strpos($url, $dup) !== false ? str_replace($dup, '/' . $basePath . '/', $url) : $url;
	}

	public function markRead($id = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$this->response = $this->response->withType('json');
		$uid = (int)$this->Auth->user('id');
		$rid = (int)$id;
		if ($uid <= 0 || $rid <= 0 || !InfrastructureGuard::isReady()) {
			return $this->response->withStringBody(json_encode(['ok' => false]));
		}
		try {
			$T = TableRegistry::get('PortalInternalNotifications');
			$T->updateAll(
				['is_read' => 1, 'modified' => date('Y-m-d H:i:s')],
				['id' => $rid, 'user_id' => $uid]
			);
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false]));
		}

		return $this->response->withStringBody(json_encode(['ok' => true]));
	}

	public function markAllRead() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$this->response = $this->response->withType('json');
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0 || !InfrastructureGuard::isReady()) {
			return $this->response->withStringBody(json_encode(['ok' => false]));
		}
		try {
			TableRegistry::get('PortalInternalNotifications')->updateAll(
				['is_read' => 1, 'modified' => date('Y-m-d H:i:s')],
				['user_id' => $uid, 'is_read' => 0]
			);
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false]));
		}

		return $this->response->withStringBody(json_encode(['ok' => true]));
	}
}
