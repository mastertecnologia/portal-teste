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
		foreach ($userIds as $uid) {
			$uid = (int)$uid;
			if ($uid <= 0 || !self::_userWantsEmail($uid, $eventType)) {
				continue;
			}
			$to = self::_emailForUser($uid);
			if ($to === '') {
				continue;
			}
			self::_sendAndLog($eventType, $to, $subject, $htmlBody);
		}
	}

	protected static function _userWantsEmail(int $userId, string $eventType): bool {
		try {
			$Prefs = TableRegistry::get('PortalNotificationPreferences');
			$p = $Prefs->find()
				->where(['user_id' => $userId, 'event_type' => $eventType])
				->first();
			if ($p === null) {
				return false;
			}

			return (int)$p->send_email === 1;
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected static function _emailForUser(int $userId): string {
		try {
			$u = TableRegistry::get('Users')->get($userId, ['fields' => ['email', 'username']]);

			return trim((string)($u->email ?? ''));
		} catch (\Throwable $e) {
			return '';
		}
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
