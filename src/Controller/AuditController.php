<?php
namespace App\Controller;

use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;

/**
 * API JSON: validação de senha de auditoria e registo em ticket_audit_logs.
 */
class AuditController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Users');
		$this->loadModel('Tickets');
		$this->loadModel('TicketAuditLogs');
	}

	/**
	 * POST /api/audit/validate
	 * Corpo JSON: user_id, ticket_id, old_time, new_time, reason, authKey (ou auth_key)
	 */
	public function apiValidate() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if ((int) $this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Apenas utilizadores de suporte.'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$authUid = (int) ($body['user_id'] ?? $body['userId'] ?? 0);
		$sessionUid = (int) $this->Auth->user('id');
		if ($authUid < 1 || $authUid !== $sessionUid) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Sessão inválida.'], 403);
		}
		$ticketId = (int) ($body['ticket_id'] ?? $body['ticketId'] ?? 0);
		$oldTime = $this->_normalizeHms($body['old_time'] ?? $body['oldTime'] ?? '');
		$newTime = $this->_normalizeHms($body['new_time'] ?? $body['newTime'] ?? '');
		$reason = isset($body['reason']) ? trim((string) $body['reason']) : '';
		$authKey = $body['authKey'] ?? $body['auth_key'] ?? '';
		$authKey = is_string($authKey) ? $authKey : (string) $authKey;
		if ($ticketId < 1 || strlen($oldTime) !== 8 || strlen($newTime) !== 8) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid', 'message' => 'Informe ticket_id, old_time e new_time (HH:MM:SS).'], 400);
		}
		if ($reason === '') {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid', 'message' => 'Informe o motivo.'], 400);
		}
		$q = $this->Tickets->find()->where(['Tickets.id' => $ticketId]);
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$ticket = $q->first();
		if (empty($ticket) || (int) $ticket->idempresa !== (int) $this->Auth->user('idempresa')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Ticket não encontrado ou sem acesso.'], 403);
		}
		try {
			$user = $this->Users->get($authUid);
		} catch (RecordNotFoundException $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Utilizador inválido.'], 403);
		}
		if ((int) $user->idempresa !== (int) $this->Auth->user('idempresa')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Utilizador inválido.'], 403);
		}
		$hash = $user->get('audit_password_hash');
		if ($hash === null || $hash === '') {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Senha de auditoria não definida.'], 403);
		}
		$hasher = new DefaultPasswordHasher();
		if ($authKey === '' || !$hasher->check($authKey, (string) $hash)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Chave inválida.'], 403);
		}
		$log = $this->TicketAuditLogs->newEntity([
			'ticket_id' => $ticketId,
			'user_id' => $authUid,
			'old_time' => $oldTime,
			'new_time' => $newTime,
			'reason' => $reason,
			'created' => FrozenTime::now(),
		]);
		try {
			$saved = $this->TicketAuditLogs->save($log);
		} catch (\Throwable $e) {
			$this->log('Audit save exception: ' . $e->getMessage(), 'error');
			$em = (string) $e->getMessage();
			$migrationHint = (
				stripos($em, 'ticket_audit') !== false
				|| stripos($em, 'Unknown column') !== false
				|| stripos($em, "doesn't exist") !== false
				|| stripos($em, 'audit_password_hash') !== false
			);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'server_error',
				'message' => $migrationHint
					? 'Base de dados desatualizada: execute as migrações (ticket_audit_logs e coluna users.audit_password_hash).'
					: 'Não foi possível gravar o registo. Tente novamente ou contacte o suporte.',
			], 503);
		}
		if (!$saved) {
			$this->log('Audit save: ' . json_encode($log->getErrors()), 'error');

			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível gravar o registo.'], 500);
		}

		return $this->jsonResponse(['ok' => true]);
	}

	/**
	 * @param string $s
	 */
	protected function _normalizeHms($s) {
		$s = trim((string) $s);
		if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/', $s, $m)) {
			$hh = (int) $m[1];
			$mm = (int) $m[2];
			$ss = (int) $m[3];
			if ($hh > 23 || $mm > 59 || $ss > 59) {
				return '';
			}
			$h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
			$i = str_pad($m[2], 2, '0', STR_PAD_LEFT);
			$sec = str_pad($m[3], 2, '0', STR_PAD_LEFT);

			return $h . ':' . $i . ':' . $sec;
		}

		return '';
	}

}
