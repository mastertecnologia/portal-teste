<?php
namespace App\Service;

use App\Utility\RbacChecker;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * IAM: email + Slack, retry, dedup, rate limit, corpo simples (usuário) vs técnico (equipa interna).
 */
class RbacAccessNotificationService {

	private function audit(string $ok, string $ch, array $ctx): void {
		try {

			$actorId = isset($ctx['actor_user_id']) ? (int)$ctx['actor_user_id'] : 1;
			if ($actorId < 1) {

				$actorId = 1;

			}
			(new RbacAccessRequestService())->logAudit([
				'actor_user_id' => $actorId,
				'target_user_id' => isset($ctx['target_user_id']) ? (int)$ctx['target_user_id'] : null,
				'access_request_id' => !empty($ctx['access_request_id']) ? (int)$ctx['access_request_id'] : null,
				'action_type' => $ok === 'ok' ? 'notification_sent' : 'notification_failed',
				'metadata_json' => json_encode([
					'channel' => $ch,
					'event' => (string)($ctx['iam_event'] ?? ($ctx['event'] ?? '')),
					'detail' => substr((string)($ctx['audit_detail'] ?? ''), 0, 200),
				], JSON_UNESCAPED_UNICODE),
				'ip' => null,
				'user_agent' => 'rbac-notification',
				'created' => date('Y-m-d H:i:s'),
			]);


		} catch (\Throwable $e) {


		}
	}




	public function notificationsConfig(): array {
		$r = Configure::read('Rbac.notifications');

		return is_array($r)
			? $r + [
				'enabled' => !empty($r['enabled']),
				'email_enabled' => !empty($r['email_enabled']),
				'slack_enabled' => !empty($r['slack_enabled']),
				'max_retries' => isset($r['max_retries']) ? max(1, (int)$r['max_retries']) : 3,
				'max_notifications_per_minute' => isset($r['max_notifications_per_minute']) ? max(1, (int)$r['max_notifications_per_minute']) : 60,
				'dedupe_ttl_seconds' => isset($r['dedupe_ttl_seconds']) ? max(3600, (int)$r['dedupe_ttl_seconds']) : 604800,
			]
			: [
				'enabled' => false,
				'email_enabled' => false,
				'slack_enabled' => false,
				'slack_webhook_url' => null,
				'from_email' => 'no-reply@pgm.inf.br',
				'admin_notify_emails' => [],
				'max_retries' => 3,
				'max_notifications_per_minute' => 60,
				'dedupe_ttl_seconds' => 604800,
			];
	}




	/**
	 * @return bool true se pelo menos um envio (e-mail ou Slack) teve êxito
	 */
	public function notifyEvent(string $event, array $ctx, bool $slackPrivileged = true): bool {

		$cfg = $this->notificationsConfig();
		if (empty($cfg['enabled'])) {

			return false;

		}
		$ctx['iam_event'] = $event;

		try {

			$dkey = isset($ctx['dedup_key']) ? trim((string)$ctx['dedup_key']) : '';

			if ($dkey !== '') {

				try {
					$h = hash('sha256', $event . '|' . $dkey);

					if (Cache::read('rbac_nt_dd_' . $h) !== null) {

						return false;

					}
				} catch (\Throwable $e) {


				}
			}


			if (!$this->rateConsume((int)$cfg['max_notifications_per_minute'])) {

				return false;

			}



			$dispatchedOk = false;

			foreach ($this->recipientListUserSimple($event, $ctx, $cfg) as $to) {


				if ($this->sendOneMail($cfg, $to, $this->subjectFor($event), $this->body($event, $ctx, false), $ctx)) {


					$dispatchedOk = true;


				}



			}

			foreach ($this->recipientListAdminDetailed($event, $ctx, $cfg) as $to) {

				if ($this->sendOneMail($cfg, $to, $this->subjectFor($event), $this->body($event, $ctx, true), $ctx)) {

					$dispatchedOk = true;



				}




			}

			if ($slackPrivileged && !empty($cfg['slack_enabled']) && !empty($cfg['slack_webhook_url'])) {


				if ($this->slackPostSilent($event, $ctx, (string)$cfg['slack_webhook_url'])) {


					$dispatchedOk = true;


				}




			}

			if ($dkey !== '' && $dispatchedOk) {


				$h2 = hash('sha256', $event . '|' . $dkey);

				try {
					$ttlSec = max(120, min((int)$cfg['dedupe_ttl_seconds'], 86400 * 31));
					Cache::write('rbac_nt_dd_' . $h2, '1', '+' . $ttlSec . ' seconds');
				} catch (\Throwable $e3) {
				}
			}

			return $dispatchedOk;
		} catch (\Throwable $eOuter) {

			$b = $ctx;



			if (!isset($b['actor_user_id'])) {


				$b['actor_user_id'] = 1;


			}




			$b['audit_detail'] = $eOuter->getMessage();



			$this->audit('fail', 'pipe', $b);

			return false;
		}
	}




	private function rateConsume(int $maxPerMinute): bool {


		if ($maxPerMinute <= 0) {


			return true;
		}




		try {

			$b = 'rbac_nt_rl_' . gmdate('YmdHi');


			$cur = Cache::read($b);





			if ($cur !== null && (int)$cur >= $maxPerMinute) {


				return false;
			}




			if ($cur === false || $cur === null) {


				Cache::write($b, 1, '+120 seconds');



				return true;


			}




			$p = Cache::increment($b);



			return $p !== false && (int)$p <= $maxPerMinute;



		} catch (\Throwable $e) {


			return true;
		}
	}




	private function sendOneMail(array $cfg, string $to, string $subject, string $mailBody, array $auditCtx): bool {


		if ($to === '' || empty($cfg['email_enabled'])) {


			return false;


		}
		$max = max(1, (int)$cfg['max_retries']);





		for ($i = 0; $i < $max; $i++) {


			try {


				$m = new Email('default');


				$fe = trim((string)($cfg['from_email'] ?? ''));

				if ($fe !== '') {

					$m->setFrom($fe);

				}
				$m->setTo($to);





				$m->setSubject($subject);

				$m->send($mailBody);


				$row = array_merge($auditCtx, ['audit_detail' => $to, 'actor_user_id' => $auditCtx['actor_user_id'] ?? 1]);
				$this->audit('ok', 'email', $row);





				return true;







			} catch (\Throwable $e) {


				if ($i + 1 >= $max) {

					$row = array_merge($auditCtx, ['audit_detail' => substr($e->getMessage(), 0, 200), 'actor_user_id' => $auditCtx['actor_user_id'] ?? 1]);
					$this->audit('fail', 'email', $row);





					return false;



				}





				usleep(180000);







			}




		}
		return false;
	}
	private function subjectFor(string $event): string {


		$subj = [


			'access_request_created' => '[IAM] Pedido criado',


			'manager_approved' => '[IAM] Manager aprovou',



			'manager_rejected' => '[IAM] Manager rejeitou',





			'admin_approved' => '[IAM] Admin aprovou',






			'admin_rejected' => '[IAM] Admin rejeitou',






			'access_granted' => '[IAM] Grant aplicado',




			'access_expiring_soon' => '[IAM] Acesso a vencer',




			'access_expired' => '[IAM] Acesso expirado',




		];




		return $subj[$event] ?? '[IAM]';





	}




	private function slackPostSilent(string $event, array $ctx, string $url): bool {


		$h = strtolower((string)parse_url(trim($url), PHP_URL_HOST));




		if ($h !== 'hooks.slack.com') {


			return false;



		}




		if (strpos(ltrim(trim($url)), 'http') !== 0) {



			return false;







		}
		$id = !empty($ctx['access_request_id']) ? '#' . (int)$ctx['access_request_id'] : '';


		$text = mb_substr('[iam] ' . $event . ($id !== '' ? ' ' . $id : ''), 0, 400);




		$body = json_encode(['text' => $text], JSON_UNESCAPED_UNICODE);





		if ($body === false) {


			return false;


		}




		for ($i = 0; $i < 3; $i++) {


			try {


				$c = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $body, 'timeout' => 8]]);
				if (@file_get_contents(trim($url), false, $c) !== false) {


					$r = array_merge($ctx, ['audit_detail' => 'ok', 'actor_user_id' => $ctx['actor_user_id'] ?? 1, 'iam_event' => $event]);





					$this->audit('ok', 'slack', $r);



					return true;



				}



			}



			catch (\Throwable $ignored) {


			}






			usleep(150000);





		}




		return false;


	}




	private function userMail(int $uid): string {


		if ($uid <= 0) {


			return '';

		}




		try {



			$u = TableRegistry::get('Users')->find()->select(['username', 'email'])->where(['id' => $uid])->first();



			if (!$u) {


				return '';

			}




			if (!empty($u->email)) {


				return strtolower(trim((string)$u->email));


			}




			$x = strtolower(trim((string)$u->username));





			return strpos($x, '@') !== false ? $x : '';




		}




		catch (\Throwable $e) {



			return '';



		}
	}




	private function adminExtra(array $cfg): array {


		$r = [];

		foreach ((array)($cfg['admin_notify_emails'] ?? []) as $e) {


			$x = strtolower(trim((string)$e));


			if ($x !== '') {


				$r[] = $x;



			}
		}




		return $r;





	}






	private function mgrMails(int $targetUserId): array {


		$list = [];

		try {






			$row = TableRegistry::get('Users')->find()->select(['idempresa'])->where(['id' => $targetUserId])->first();

			$emp = $row ? (int)$row->idempresa : 0;







			foreach (TableRegistry::get('Users')->find()->select(['id'])->where(['role' => 0, 'idempresa' => $emp])->limit(800)->all() as $uu) {


				if (!RbacChecker::userHasPermissionCode((int)$uu->id, 'rbac.requests.approve_manager')) {


					continue;



				}
				$m = $this->userMail((int)$uu->id);







				if ($m !== '') {


					$list[] = $m;



				}



			}




		}




		catch (\Throwable $e2) {


		}




		return array_values(array_unique(array_filter($list)));




	}




	private function admMails(): array {



		$list = [];





		try {



			foreach (TableRegistry::get('Users')->find()->select(['id'])->where(['role' => 0])->limit(4000)->all() as $uu) {


				$id = (int)$uu->id;



				if (!(RbacChecker::userHasPermissionCode($id, 'rbac.requests.approve_admin') || RbacChecker::userHasPermissionCode($id, 'rbac.requests.grant'))) {



					continue;

				}




				$m = $this->userMail($id);





				if ($m !== '') {


					$list[] = $m;





				}



			}




		}




		catch (\Throwable $e4) {


		}




		return array_values(array_unique(array_filter($list)));

	}




	private function recipientMerged(string $event, array $ctx, array $cfg): array {


		return array_values(array_unique(array_filter($this->resolveEmails($event, $ctx, $cfg, true))));


	}




	private function recipientListUserSimple(string $event, array $ctx, array $cfg): array {
		if (!empty($ctx['admins_only'])) {

			return [];


		}

		$t = isset($ctx['target_user_id']) ? (int)$ctx['target_user_id'] : 0;



		switch ($event) {


			case 'manager_rejected':
			case 'admin_rejected':
			case 'admin_approved':
			case 'access_granted':
			case 'access_expiring_soon':
			case 'access_expired':


				$m = $this->userMail($t);





				return $m !== '' ? [$m] : [];

			default:


				return [];





		}
	}




	private function recipientListAdminDetailed(string $event, array $ctx, array $cfg): array {


		$b = $this->adminExtra($cfg);





		$t = isset($ctx['target_user_id']) ? (int)$ctx['target_user_id'] : 0;





		switch ($event) {


			case 'access_request_created':
				return array_values(array_unique(array_filter(array_merge($b, $this->mgrMails($t)))));





			case 'manager_approved':


				return array_values(array_unique(array_filter(array_merge($b, $this->admMails()))));


			case 'manager_rejected':
			case 'admin_rejected':


				return array_values(array_unique(array_filter($b)));


			case 'admin_approved':
			case 'access_granted':
			case 'access_expiring_soon':
			case 'access_expired':


				return array_values(array_unique(array_filter(array_merge($b, $this->admMails()))));


			default:

				return [];

		}
	}




	private function resolveEmails(string $event, array $ctx, array $cfg, bool $priv): array {


		$extra = $this->adminExtra($cfg);



		$tid = isset($ctx['target_user_id']) ? (int)$ctx['target_user_id'] : 0;





		if (!$priv) {


			$m = $this->userMail($tid);





			return $m !== '' ? [$m] : [];

		}




		$adminsOnly = !empty($ctx['admins_only']);

		$out = $extra;







		switch ($event) {


			case 'access_request_created':


				$out = array_merge($out, $this->mgrMails($tid));


				break;





			case 'manager_approved':




				$out = array_merge($out, $this->admMails());





				break;




			case 'manager_rejected':
			case 'admin_rejected':




				if ($tid > 0) {


					$m2 = $this->userMail($tid);





					if ($m2 !== '') {


						$out[] = $m2;





					}



				}




				break;



			case 'admin_approved':
			case 'access_granted':
			case 'access_expiring_soon':
			case 'access_expired':




				if (!$adminsOnly && $tid > 0) {


					$m2 = $this->userMail($tid);





					if ($m2 !== '') {


						$out[] = $m2;





					}






				}








				$out = array_merge($out, $this->admMails());





				break;




			default:


				break;



		}








		return array_values(array_unique(array_filter($out)));





	}




	private function body(string $event, array $ctx, bool $privileged): string {


		if (!$privileged) {


			$o = [];

			$o[] = 'Atualização sobre seu pedido de acesso (IAM).';


			if (!empty($ctx['access_request_id'])) {


				$o[] = 'Pedido #' . (int)$ctx['access_request_id'];



			}








			$o[] = 'Consulte "Meus pedidos de acesso" no portal para detalhes.';







			return implode("\n", $o);





		}






		$o = [];

		$o[] = 'IAM — ' . $event;







		if (!empty($ctx['access_request_id'])) {



			$o[] = 'Pedido #' . (int)$ctx['access_request_id'];





		}






		if (!empty($ctx['diagnostic_teaser'])) {



			$o[] = (string)$ctx['diagnostic_teaser'];





		}




		foreach ((array)($ctx['routing'] ?? []) as $kk => $vv) {






			$o[] = $kk . ': ' . $vv;





		}






		return implode("\n", $o);



	}
}
