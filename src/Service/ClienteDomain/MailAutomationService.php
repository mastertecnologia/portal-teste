<?php
namespace App\Service\ClienteDomain;

use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * Envio síncrono com registro em portal_mail_automation_logs (base para fila futura).
 */
class MailAutomationService {

	public static function notifyUsersIfEnabled(
		array $userIds,
		string $eventType,
		string $subject,
		string $htmlBody
	): void {
		if (!InfrastructureGuard::isReady() || empty($userIds)) {
			return;
		}
		$ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
		if (empty($ids)) {
			return;
		}
		$wantsEmail = self::_batchWantsEmail($ids, $eventType);
		$emails = self::_batchEmailsForUsers($ids);
		foreach ($ids as $uid) {
			if ($uid <= 0 || empty($wantsEmail[$uid])) {
				continue;
			}
			$to = $emails[$uid] ?? '';
			if ($to === '') {
				continue;
			}
			self::_sendAndLog($eventType, $to, $subject, $htmlBody);
		}
	}

	/**
	 * E-mail habilitado em lote. Sem preferência salva => false (opt-in, igual ao legado).
	 *
	 * @param int[] $userIds
	 * @return array<int,bool>
	 */
	protected static function _batchWantsEmail(array $userIds, string $eventType): array {
		$out = [];
		foreach ($userIds as $id) {
			$id = (int)$id;
			if ($id > 0) {
				$out[$id] = false;
			}
		}
		if (empty($out)) {
			return [];
		}
		try {
			$Prefs = TableRegistry::get('PortalNotificationPreferences');
			$rows = $Prefs->find()
				->select(['user_id', 'send_email'])
				->where(['user_id IN' => array_keys($out), 'event_type' => $eventType])
				->enableHydration(false)
				->toArray();
			foreach ($rows as $r) {
				$uid = (int)($r['user_id'] ?? 0);
				if ($uid > 0) {
					$out[$uid] = (int)($r['send_email'] ?? 0) === 1;
				}
			}
		} catch (\Throwable $e) {
			return $out;
		}

		return $out;
	}

	/**
	 * @param int[] $userIds
	 * @return array<int,string>
	 */
	protected static function _batchEmailsForUsers(array $userIds): array {
		$out = [];
		try {
			$Users = TableRegistry::get('Users');
			$rows = $Users->find()
				->select(['id', 'email'])
				->where(['id IN' => $userIds])
				->enableHydration(false)
				->toArray();
			foreach ($rows as $r) {
				$id = (int)($r['id'] ?? 0);
				if ($id > 0) {
					$out[$id] = trim((string)($r['email'] ?? ''));
				}
			}
		} catch (\Throwable $e) {
			return $out;
		}

		return $out;
	}

	protected static function _userWantsEmail(int $userId, string $eventType): bool {
		$m = self::_batchWantsEmail([$userId], $eventType);

		return !empty($m[(int)$userId]);
	}

	protected static function _emailForUser(int $userId): string {
		$m = self::_batchEmailsForUsers([(int)$userId]);

		return $m[(int)$userId] ?? '';
	}

	protected static function _sendAndLog(string $eventType, string $to, string $subject, string $htmlBody): void {
		$Logs = TableRegistry::get('PortalMailAutomationLogs');
		$log = $Logs->newEntity([
			'event_type' => $eventType,
			'recipient' => $to,
			'subject' => $subject,
			'status' => 'pending',
			'created' => date('Y-m-d H:i:s'),
		]);
		if (!$Logs->save($log) || empty($log->id)) {
			return;
		}

		try {
			$email = new Email('default');
			$email->to($to)
				->subject($subject)
				->emailFormat('html')
				->send($htmlBody);
			$log->status = 'sent';
			$log->sent_at = date('Y-m-d H:i:s');
			$Logs->save($log);
		} catch (\Throwable $e) {
			$log->status = 'failed';
			$log->error_message = $e->getMessage();
			$Logs->save($log);
			\Cake\Log\Log::error('MailAutomationService: ' . $e->getMessage());
		}
	}

	public static function buildHtmlBody(string $title, string $message): string {
		$safeTitle = h($title);
		$safeMsg = nl2br(h($message));

		return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:system-ui,sans-serif">'
			. '<h2 style="color:#1d9e75">' . $safeTitle . '</h2>'
			. '<p>' . $safeMsg . '</p>'
			. '<hr><p style="font-size:12px;color:#666">PGM Soluções — notificação automática do portal.</p>'
			. '</body></html>';
	}
}
