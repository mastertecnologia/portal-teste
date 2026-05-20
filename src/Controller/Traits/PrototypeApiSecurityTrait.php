<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Utility\ErpPrototypeAccess;
use App\Service\PrototypeApiUsageService;
use Cake\Cache\Cache;
use Cake\Log\Log;

/**
 * Helpers reutilizáveis para endpoints AJAX dos controllers *PrototypeController*.
 *
 * - rate limit por usuário (padrão 60 req/min)
 * - guard que recusa usuários portal (role!=0) e inativos
 * - log de tentativas suspeitas
 */
trait PrototypeApiSecurityTrait {

	/**
	 * Verifica se o usuário pode acessar um endpoint AJAX de escrita.
	 * Retorna null se OK, ou Response JSON 4xx se bloqueado.
	 *
	 * @return \Cake\Http\Response|null
	 */
	protected function guardApiEquipe() {
		$user = (array)$this->Auth->user();
		$uid = (int)($user['id'] ?? 0);
		if ($uid <= 0) {
			return $this->_apiJsonError(401, 'auth_required');
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			Log::warning(sprintf(
				'PrototypeApi: tentativa bloqueada (role!=0) user=%d ip=%s rota=%s',
				$uid,
				(string)$this->request->clientIp(),
				(string)$this->request->getRequestTarget()
			));

			return $this->_apiJsonError(403, 'team_only');
		}
		if ((int)($user['inativo'] ?? 0) === 1) {
			return $this->_apiJsonError(403, 'user_inactive');
		}

		$deny = $this->_guardApiRbacWrite($user);
		if ($deny !== null) {
			return $deny;
		}

		return $this->_apiRateLimit($uid, 60, 60);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	protected function _guardApiRbacWrite(array $user) {
		$controller = (string)$this->request->getParam('controller');
		$action = (string)$this->request->getParam('action');
		if (ErpPrototypeAccess::allowsApiWrite($user, $controller, $action)) {
			return null;
		}
		Log::warning(sprintf(
			'PrototypeApi: RBAC write negado user=%d rota=%s',
			(int)($user['id'] ?? 0),
			(string)$this->request->getRequestTarget()
		));

		return $this->_apiJsonError(403, 'permission_denied');
	}

	/**
	 * Guard para endpoints GET JSON (leitura / poll).
	 *
	 * @return \Cake\Http\Response|null
	 */
	protected function guardApiEquipeGet() {
		$user = (array)$this->Auth->user();
		$uid = (int)($user['id'] ?? 0);
		if ($uid <= 0) {
			return $this->_apiJsonError(401, 'auth_required');
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			Log::warning(sprintf(
				'PrototypeApi: GET bloqueado (role!=0) user=%d ip=%s rota=%s',
				$uid,
				(string)$this->request->clientIp(),
				(string)$this->request->getRequestTarget()
			));

			return $this->_apiJsonError(403, 'team_only');
		}
		if ((int)($user['inativo'] ?? 0) === 1) {
			return $this->_apiJsonError(403, 'user_inactive');
		}

		$deny = $this->_guardApiRbacRead($user);
		if ($deny !== null) {
			return $deny;
		}

		return $this->_apiRateLimit($uid, 120, 60);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	protected function _guardApiRbacRead(array $user) {
		$controller = (string)$this->request->getParam('controller');
		$action = (string)$this->request->getParam('action');
		if (ErpPrototypeAccess::allows($user, $controller, $action)) {
			return null;
		}
		Log::warning(sprintf(
			'PrototypeApi: RBAC read negado user=%d rota=%s',
			(int)($user['id'] ?? 0),
			(string)$this->request->getRequestTarget()
		));

		return $this->_apiJsonError(403, 'permission_denied');
	}

	/**
	 * @return \Cake\Http\Response|null retorna response 429 se exceder
	 */
	protected function _apiRateLimit(int $uid, int $limitPerWindow, int $windowSeconds) {
		$key = 'pgm_api_rl_' . $uid . '_' . (int)floor(time() / $windowSeconds);
		try {
			$count = (int)Cache::read($key, 'default');
		} catch (\Throwable $e) {
			return null;
		}
		if ($count >= $limitPerWindow) {
			Log::warning(sprintf('PrototypeApi: rate limit user=%d count=%d', $uid, $count));

			return $this->_apiJsonError(429, 'rate_limited');
		}
		try {
			Cache::write($key, $count + 1, 'default');
		} catch (\Throwable $e) {
		}

		$this->_trackPrototypeApiHit();

		return null;
	}

	/**
	 * Contabiliza chamada (GET ou POST) para painel de estatísticas.
	 */
	protected function _trackPrototypeApiHit(?string $endpoint = null): void {
		if ($endpoint === null) {
			$endpoint = (string)$this->request->getParam('controller') . '.' . (string)$this->request->getParam('action');
		}
		(new PrototypeApiUsageService())->hit($endpoint);
	}

	protected function _apiJsonError(int $status, string $code) {
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json')->withStatus($status);

		return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $code]));
	}
}
