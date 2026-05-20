<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Envia notificações Web Push para usuários inscritos.
 *
 * Requer `composer require minishlink/web-push` no servidor. Sem a lib, faz
 * fallback "dry-run" (log) sem falhar. VAPID keys em config WebPush.*.
 *
 * Payload sugerido (JSON):
 *   ['title' => 'Novo ticket', 'body' => '#1234 Acme', 'url' => '/portal/...', 'tag' => 'ticket-1234']
 */
class WebPushSenderService {

	/**
	 * Envia para todas as inscrições ativas de um usuário.
	 *
	 * @param int $userId
	 * @param array<string,mixed> $payload
	 * @return array{ok:bool,sent:int,dry:int,errors:int,driver:string}
	 */
	public function sendToUser(int $userId, array $payload): array {
		if ($userId <= 0) {
			return ['ok' => false, 'sent' => 0, 'dry' => 0, 'errors' => 0, 'driver' => 'noop'];
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('WebPushSubscriptions');
			$subs = $tbl->find()
				->where(['user_id' => $userId, 'inativo' => 0])
				->all()
				->toArray();
		} catch (\Throwable $e) {
			Log::warning('WebPushSender: ' . $e->getMessage());

			return ['ok' => false, 'sent' => 0, 'dry' => 0, 'errors' => 1, 'driver' => 'error'];
		}

		if ($subs === []) {
			return ['ok' => true, 'sent' => 0, 'dry' => 0, 'errors' => 0, 'driver' => 'no_subs'];
		}

		return $this->sendToSubscriptions($subs, $payload);
	}

	/**
	 * Envia para uma lista de subscriptions.
	 *
	 * @param iterable<int,\Cake\Datasource\EntityInterface|array<string,mixed>> $subs
	 * @param array<string,mixed> $payload
	 * @return array{ok:bool,sent:int,dry:int,errors:int,driver:string}
	 */
	public function sendToSubscriptions(iterable $subs, array $payload): array {
		$publicKey = trim((string)Configure::read('WebPush.public_key', ''));
		$privateKey = trim((string)Configure::read('WebPush.private_key', ''));
		$libAvailable = class_exists('\\Minishlink\\WebPush\\WebPush');
		$driver = $libAvailable && $publicKey !== '' && $privateKey !== '' ? 'minishlink' : 'dry';

		$sent = 0;
		$dry = 0;
		$errors = 0;
		$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($driver === 'dry') {
			foreach ($subs as $sub) {
				$endpoint = $this->endpointOf($sub);
				if ($endpoint === '') {
					$errors++;
					continue;
				}
				Log::info('WebPush DRY: ' . substr($endpoint, 0, 60) . '... payload=' . $payloadJson);
				$this->updateLastSeen($sub);
				$dry++;
			}

			return ['ok' => true, 'sent' => 0, 'dry' => $dry, 'errors' => $errors, 'driver' => $driver];
		}

		// Driver real (minishlink/web-push)
		try {
			$auth = [
				'VAPID' => [
					'subject' => (string)Configure::read('App.fullBaseUrl', 'mailto:noreply@pgm.inf.br'),
					'publicKey' => $publicKey,
					'privateKey' => $privateKey,
				],
			];
			$webPushClass = '\\Minishlink\\WebPush\\WebPush';
			$subscriptionClass = '\\Minishlink\\WebPush\\Subscription';
			/** @var object $webPush */
			$webPush = new $webPushClass($auth);
			foreach ($subs as $sub) {
				$endpoint = $this->endpointOf($sub);
				if ($endpoint === '') {
					$errors++;
					continue;
				}
				$subscription = $subscriptionClass::create([
					'endpoint' => $endpoint,
					'publicKey' => $this->p256dhOf($sub),
					'authToken' => $this->authOf($sub),
					'contentEncoding' => 'aesgcm',
				]);
				$webPush->queueNotification($subscription, $payloadJson);
			}
			foreach ($webPush->flush() as $report) {
				if (method_exists($report, 'isSuccess') && $report->isSuccess()) {
					$sent++;
				} else {
					$errors++;
					$reason = method_exists($report, 'getReason') ? (string)$report->getReason() : 'unknown';
					Log::warning('WebPush failed: ' . $reason);
				}
			}
		} catch (\Throwable $e) {
			Log::error('WebPush exception: ' . $e->getMessage());

			return ['ok' => false, 'sent' => $sent, 'dry' => 0, 'errors' => $errors + 1, 'driver' => $driver];
		}

		return ['ok' => $errors === 0, 'sent' => $sent, 'dry' => 0, 'errors' => $errors, 'driver' => $driver];
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $sub
	 */
	protected function endpointOf($sub): string {
		if (is_array($sub)) {
			return (string)($sub['endpoint'] ?? '');
		}

		return (string)$sub->get('endpoint');
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $sub
	 */
	protected function p256dhOf($sub): string {
		return is_array($sub) ? (string)($sub['p256dh'] ?? '') : (string)$sub->get('p256dh');
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $sub
	 */
	protected function authOf($sub): string {
		return is_array($sub) ? (string)($sub['auth'] ?? '') : (string)$sub->get('auth');
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string,mixed> $sub
	 */
	protected function updateLastSeen($sub): void {
		if (is_array($sub)) {
			return;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('WebPushSubscriptions');
			$sub->set('last_seen_at', date('Y-m-d H:i:s'));
			$tbl->save($sub);
		} catch (\Throwable $e) {
		}
	}
}
