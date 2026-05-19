<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacAccessRequestService {
	public const SESSION_ACCESS_REQUEST_CAPTURE = 'RbacAccessRequestCapture';

	/**
	 * Cria (ou reaproveita) pedido pendente para o mesmo user/controller/action.
	 *
	 * @param array<string,mixed> $capture
	 * @param array<string,mixed> $diag
	 * @param string|null $requesterMessage
	 * @return array{created:bool,reused:bool,row:\Cake\Datasource\EntityInterface|null}
	 */
	public function createOrReuseFromCapture(int $userId, array $capture, array $diag, ?string $requesterMessage = null): array {
		$reqTbl = TableRegistry::get('RbacAccessRequests');
		$conn = $reqTbl->getConnection();

		return $conn->transactional(function () use ($reqTbl, $conn, $userId, $capture, $diag, $requesterMessage) {
			try {
				$conn->execute('SELECT pg_advisory_xact_lock(:k)', ['k' => 910000 + $userId]);
			} catch (\Throwable $e) {
				// Sem lock advisory, segue com transação.
			}
			$limitCheck = $this->checkRateLimit($userId);
			if (!$limitCheck['ok']) {
				return ['created' => false, 'reused' => false, 'row' => null, 'rate_limited' => true];
			}
			$supportCode = substr((string)($capture['support_code'] ?? ''), 0, 40);
			$controller = substr(strtolower((string)($capture['controller'] ?? '')), 0, 80);
			$action = substr(strtolower((string)($capture['action'] ?? '')), 0, 80);
			$prefix = substr(strtolower((string)($capture['prefix'] ?? '')), 0, 80);
			$plugin = substr(strtolower((string)($capture['plugin'] ?? '')), 0, 80);
			$reason = substr((string)($capture['reason'] ?? ''), 0, 64);
			$pending = $reqTbl->find()
				->where([
					'user_id' => $userId,
					'controller' => $controller,
					'action' => $action,
					'status IN' => ['pending_manager', 'manager_approved', 'pending_admin'],
				])
				->order(['id' => 'DESC'])
				->first();
			if ($pending) {
				$this->syncApprovalInbox($pending);

				return ['created' => false, 'reused' => true, 'row' => $pending, 'rate_limited' => false];
			}
			$reqPerm = $diag['required_permission_codes'] ?? ($diag['missing_permission_codes'] ?? []);
			$suggestRoleIds = array_map('intval', array_column((array)($diag['roles_that_would_grant'] ?? []), 'role_id'));
			$abacCtx = [
				'abac' => $diag['abac_evaluated'] ?? ($diag['abac'] ?? []),
				'deny_reason' => $diag['deny_reason'] ?? null,
				'suggestions' => $diag['suggestions'] ?? [],
			];
			$row = $reqTbl->newEntity([
				'support_code' => $supportCode,
				'user_id' => $userId,
				'controller' => $controller,
				'action' => $action,
				'plugin' => $plugin,
				'prefix' => $prefix,
				'reason' => $reason,
				'requested_permission_codes' => $this->_json($reqPerm),
				'suggested_role_ids' => $this->_json(array_values(array_unique($suggestRoleIds))),
				'abac_context' => $this->_json($abacCtx),
				'status' => 'pending_manager',
				'requester_message' => $requesterMessage !== null ? trim($requesterMessage) : null,
			]);
			$saved = $reqTbl->save($row);
			if ($saved) {
				$this->syncApprovalInbox($row);
			}

			return ['created' => (bool)$saved, 'reused' => false, 'row' => $saved ?: null, 'rate_limited' => false];
		});
	}

	/**
	 * Piloto approval_requests: espelha pedido RBAC na fila unificada (ignora falhas).
	 *
	 * @param \Cake\Datasource\EntityInterface $rbacRow
	 */
	public function syncApprovalInbox($rbacRow): void {
		try {
			(new ApprovalRequestSyncService())->syncFromRbacAccessRequest($rbacRow);
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @return array{ok:bool,count:int,limit:int}
	 */
	public function checkRateLimit(int $userId): array {
		$rb = (array)Configure::read('Rbac');
		$diag = isset($rb['diagnostics']) && is_array($rb['diagnostics']) ? $rb['diagnostics'] : [];
		$limit = isset($diag['access_request_rate_limit_per_hour']) ? (int)$diag['access_request_rate_limit_per_hour'] : 5;
		if ($limit <= 0) {
			$limit = 5;
		}
		$since = FrozenTime::now()->subHour(1);
		$count = (int)TableRegistry::get('RbacAccessRequests')->find()
			->where(['user_id' => $userId, 'created >=' => $since])
			->count();

		return ['ok' => $count < $limit, 'count' => $count, 'limit' => $limit];
	}

	public function logAudit(array $payload): void {
		try {
			if (isset($payload['metadata_json']) && is_string($payload['metadata_json']) && $payload['metadata_json'] !== '') {
				$raw = json_decode($payload['metadata_json'], true);
				if (is_array($raw)) {
					$payload['metadata_json'] = $this->_json($this->sanitizeAuditMetadata($raw));
				}
			}
			$tbl = TableRegistry::get('RbacChangeAuditLogs');
			$row = $tbl->newEntity($payload);
			$tbl->save($row);
		} catch (\Throwable $e) {
			// auditoria não pode quebrar fluxo
		}
	}

	private function _json($v): ?string {
		$j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $j === false ? null : $j;
	}

	public function sanitizeAuditMetadata(array $data): array {
		$denyKeys = ['authorization', 'cookie', 'token', 'password', 'senha', 'secret', 'payload'];
		$out = [];
		foreach ($data as $k => $v) {
			$key = strtolower((string)$k);
			$block = false;
			foreach ($denyKeys as $d) {
				if (strpos($key, $d) !== false) {
					$block = true;
					break;
				}
			}
			if ($block) {
				continue;
			}
			if (is_array($v)) {
				$out[$k] = $this->sanitizeAuditMetadata($v);
				continue;
			}
			if (is_string($v)) {
				$out[$k] = $this->sanitizeText($v);
				continue;
			}
			$out[$k] = $v;
		}

		return $out;
	}

	public function sanitizeText(string $text, int $limit = 500): string {
		$text = trim($text);
		$text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?: '';

		return mb_substr($text, 0, $limit);
	}
}

