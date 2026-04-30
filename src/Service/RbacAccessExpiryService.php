<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * Expiração e lembretes de rbac_access_grants (cron shells).
 */
class RbacAccessExpiryService {

	protected function rbacAccessGrantsExist(): bool {
		try {

			return in_array('rbac_access_grants', TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {

			return false;
		}
	}

	protected function expiryConfig(): array {
		$cfg = Configure::read('Rbac.access_expiration');

		return is_array($cfg) ? $cfg + [
			'enabled' => !empty($cfg['enabled']),
			'notify_before_days' => [7, 1],
			'auto_revoke_enabled' => false,
		] : ['enabled' => false, 'notify_before_days' => [], 'auto_revoke_enabled' => false];
	}

	/** @return array{sent:int,skipped:int,dry_run:bool,errors:int} */
	public function notifyExpiringSoon(bool $dryRun = false): array {
		$out = ['sent' => 0, 'skipped' => 0, 'dry_run' => $dryRun, 'errors' => 0];
		$cfg = $this->expiryConfig();
		if (!$cfg['enabled']) {
			return $out;
		}
		if (!$this->rbacAccessGrantsExist()) {
			return $out;
		}

		$before = isset($cfg['notify_before_days']) && is_array($cfg['notify_before_days'])
			? array_values(array_unique(array_map('intval', $cfg['notify_before_days']))) : [];

		$before = array_filter($before, fn($d) => $d > 0);

		sort($before);
		if ($before === []) {

			return $out;

		}

		$today = FrozenTime::now()->startOfDay();

		$gTbl = TableRegistry::get('RbacAccessGrants');
		$now = FrozenTime::now();

		$rows = $gTbl->find()
			->where([
				'status' => 'active',
				'expires_at IS NOT' => null,
				'expires_at >' => $now,
			])->all();

		$svcNotif = new RbacAccessNotificationService();

		foreach ($rows as $gr) {

			try {
				$expDay = FrozenTime::instance($gr->expires_at)->startOfDay();
				if ($expDay <= $today) {

					continue;

				}
				$daysLeft = (int)floor(($expDay->getTimestamp() - $today->getTimestamp()) / 86400);
				if ($daysLeft <= 0) {

					continue;

				}
				if (!in_array((int)$daysLeft, $before, true)) {

					continue;

				}

				$sentJson = (string)($gr->expiry_notifications_sent_json ?? '');
				$map = [];
				if ($sentJson !== '') {
					$dec = json_decode($sentJson, true);
					$map = is_array($dec) ? $dec : [];

				}
				$tierKey = (string)$daysLeft;
				if (!empty($map[$tierKey])) {

					$out['skipped']++;

					continue;

				}

				if ($dryRun) {
					$out['skipped']++;

					continue;

				}

				try {


					$ok = $svcNotif->notifyEvent('access_expiring_soon', [
						'target_user_id' => (int)$gr->user_id,
						'access_request_id' => (int)$gr->access_request_id,
						'dedup_key' => 'grant_exp_notify:' . (int)$gr->id . ':' . $tierKey,
						'routing' => [
							'grant_id' => (string)(int)$gr->id,
							'dias_restantes' => (string)$daysLeft,
							'expira_em' => FrozenTime::instance($gr->expires_at)->format('Y-m-d H:i'),
							'request_id' => (string)(int)$gr->access_request_id,
						],
					]);

					if ($ok) {
						$map[$tierKey] = FrozenTime::now()->format('c');


						$gr->expiry_notifications_sent_json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


						$gTbl->save($gr);



						$out['sent']++;
					}




					else {



						$out['errors']++;



					}



				} catch (\Throwable $e) {

					$out['errors']++;

				}
			} catch (\Throwable $e2) {

				$out['errors']++;

			}

		}

		return $out;
	}

	/** @return array{expired:int,revoked:int,skipped_duplicate_role:int,dry_run:bool,errors:int} */
	public function expireGrants(bool $dryRun = false): array {

		$out = ['expired' => 0, 'revoked' => 0, 'skipped_duplicate_role' => 0, 'dry_run' => $dryRun, 'errors' => 0];
		if (!$this->rbacAccessGrantsExist()) {

			return $out;

		}
		$expireCfg = Configure::read('Rbac.access_expiration');
		$expireCfg = is_array($expireCfg) ? $expireCfg : [];
		if (empty($expireCfg['enabled'])) {
			return $out;
		}

		$autoRevoke = !empty($expireCfg['auto_revoke_enabled']);

		$svcReq = new RbacAccessRequestService();
		$gTbl = TableRegistry::get('RbacAccessGrants');
		$urTbl = TableRegistry::get('RbacUsersRoles');
		$now = FrozenTime::now();

		$due = $gTbl->find()
			->where([
				'status' => 'active',
				'expires_at IS NOT' => null,
				'expires_at <=' => $now,
			])
			->all();

		foreach ($due as $gr) {

			try {

				if ((string)$gr->status !== 'active') {

					continue;

				}

				if ($dryRun) {
					if ($autoRevoke && !empty($gr->applied_role_assignment)) {
						$otherActive = (int)$gTbl->find()
							->where([
								'user_id' => (int)$gr->user_id,
								'role_id' => (int)$gr->role_id,
								'status' => 'active',
								'id !=' => (int)$gr->id,
							])
							->count();
						if ($otherActive > 0) {
							$out['skipped_duplicate_role']++;
						} else {

							$out['revoked']++;

						}
					}
					$out['expired']++;

					continue;

				}

				$grantId = (int)$gr->id;

				$uid = (int)$gr->user_id;

				$rid = (int)$gr->role_id;

				$reqId = (int)$gr->access_request_id;

				$actor = max(1, (int)$gr->granted_by);

				$expMetaBase = ['grant_id' => $grantId, 'expires_at' => FrozenTime::instance($gr->expires_at)->format('c')];

				$markedExpired = function () use ($gr, $gTbl, $now) {
					$gr->status = 'expired';
					$gr->revoked_at = $now;

					return (bool)$gTbl->save($gr);
				};

				if ($autoRevoke && !empty($gr->applied_role_assignment)) {

					$otherActive = (int)$gTbl->find()
						->where([
							'user_id' => $uid,
							'role_id' => $rid,
							'status' => 'active',
							'id !=' => $grantId,
						])
						->count();
					if ($otherActive > 0) {

						$svcReq->logAudit([
							'actor_user_id' => $actor,
							'target_user_id' => $uid,
							'access_request_id' => $reqId,
							'action_type' => 'access_expired',
							'metadata_json' => json_encode($expMetaBase + ['auto_revoke_skipped_other_active_grant' => true], JSON_UNESCAPED_UNICODE),

							'ip' => null,

							'user_agent' => 'rbac-access-expire',

							'created' => FrozenTime::now(),
						]);
						if ($markedExpired()) {

							$out['expired']++;

							$out['skipped_duplicate_role']++;

						}

						continue;

					}
					$svcReq->logAudit([
						'actor_user_id' => $actor,
						'target_user_id' => $uid,
						'access_request_id' => $reqId,
						'action_type' => 'access_expired',
						'metadata_json' => json_encode($expMetaBase + ['auto_revoke_pending' => true], JSON_UNESCAPED_UNICODE),
						'ip' => null,
						'user_agent' => 'rbac-access-expire',

						'created' => FrozenTime::now(),

					]);
					$urTbl->deleteAll(['user_id' => $uid, 'role_id' => $rid]);
					$svcReq->logAudit([
						'actor_user_id' => $actor,
						'target_user_id' => $uid,
						'access_request_id' => $reqId,
						'action_type' => 'access_revoked',
						'metadata_json' => json_encode([
							'grant_id' => $grantId,
							'reason' => 'expiry_auto_revoke',
							'role_id' => $rid,
						], JSON_UNESCAPED_UNICODE),
						'ip' => null,
						'user_agent' => 'rbac-access-expire',

						'created' => FrozenTime::now(),

					]);

					try {
						(new RbacAccessNotificationService())->notifyEvent('access_expired', [
							'target_user_id' => $uid,
							'access_request_id' => $reqId,
							'routing' => ['grant_id' => (string)$grantId],
						]);
					} catch (\Throwable $e) {
					}
					if ($markedExpired()) {
						$out['revoked']++;
						$out['expired']++;

					}
				} else {

					$svcReq->logAudit([
						'actor_user_id' => $actor,
						'target_user_id' => $uid,
						'access_request_id' => $reqId,
						'action_type' => 'access_expired',
						'metadata_json' => json_encode($expMetaBase + [
							'auto_revoke' => $autoRevoke,
							'applied_role_assignment' => (bool)$gr->applied_role_assignment,
						], JSON_UNESCAPED_UNICODE),

						'ip' => null,

						'user_agent' => 'rbac-access-expire',

						'created' => FrozenTime::now(),
					]);
					try {

						(new RbacAccessNotificationService())->notifyEvent('access_expired', [
							'target_user_id' => $uid,
							'access_request_id' => $reqId,
							'routing' => ['grant_id' => (string)$grantId],
						]);
					} catch (\Throwable $e) {
					}
					if ($markedExpired()) {
						$out['expired']++;

					}
				}
			} catch (\Throwable $e) {

				$out['errors']++;

			}

		}

		return $out;
	}
}
