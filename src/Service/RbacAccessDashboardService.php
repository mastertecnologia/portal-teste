<?php
namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacAccessDashboardService {

	/**
	 * @param array{days?:int,company_id?:int,status?:string} $filters
	 * @return array<string,mixed>
	 */
	public function build(array $filters = []): array {
		$days = isset($filters['days']) ? (int)$filters['days'] : 30;
		if ($days <= 0) {
			$days = 30;
		}
		$since = FrozenTime::now()->subDays($days);
		$companyId = isset($filters['company_id']) ? (int)$filters['company_id'] : 0;
		$statusRaw = isset($filters['status']) ? trim((string)$filters['status']) : '';
		$statusList = $statusRaw !== '' ? array_values(array_filter(array_map('trim', explode(',', $statusRaw)))) : [];

		$out = [
			'period_days' => $days,
			'company_id' => $companyId,
			'status_filter' => $statusRaw,
			'pending_manager' => 0,
			'pending_admin' => 0,
			'admin_approved_waiting_grant' => 0,
			'created_last_window' => 0,
			'granted_last_window' => 0,
			'rejected_last_window' => 0,
			'active_grants' => 0,
			'grants_expiring_7d' => 0,
			'critical_active_grants' => 0,
			'expired_still_active_anomaly' => 0,
			'avg_hours_created_to_manager' => null,
			'avg_hours_manager_to_admin' => null,
			'avg_hours_admin_to_grant' => null,
			'top_permissions' => [],
			'top_modules' => [],
			'top_users' => [],
			'top_roles' => [],
			'recent_audit' => [],
			'_error' => null,
		];

		try {
			$req = TableRegistry::get('RbacAccessRequests');
			$qBase = $req->find();
			if ($companyId > 0) {
				$qBase->innerJoin(['U' => 'users'], ['U.id = RbacAccessRequests.user_id', 'U.idempresa' => $companyId]);
			}
			if ($statusList !== []) {
				$qBase->where(['RbacAccessRequests.status IN' => $statusList]);
			}

			$countWhere = function (array $where) use ($req, $companyId, $statusList) {
				$q = $req->find()->where($where);
				if ($companyId > 0) {
					$q->innerJoin(['U' => 'users'], ['U.id = RbacAccessRequests.user_id', 'U.idempresa' => $companyId]);
				}
				if ($statusList !== []) {
					$q->where(['RbacAccessRequests.status IN' => $statusList]);
				}

				return (int)$q->count();
			};

			$out['pending_manager'] = $countWhere(['RbacAccessRequests.status' => 'pending_manager']);
			$out['pending_admin'] = $countWhere(['RbacAccessRequests.status IN' => ['pending_admin', 'manager_approved']]);
			$out['admin_approved_waiting_grant'] = $countWhere(['RbacAccessRequests.status' => 'admin_approved']);

			$qWin = $req->find()->where(['RbacAccessRequests.created >=' => $since]);
			if ($companyId > 0) {
				$qWin->innerJoin(['U2' => 'users'], ['U2.id = RbacAccessRequests.user_id', 'U2.idempresa' => $companyId]);
			}
			if ($statusList !== []) {
				$qWin->where(['RbacAccessRequests.status IN' => $statusList]);
			}
			$out['created_last_window'] = (int)$qWin->count();

			$qg = $req->find()->where(['RbacAccessRequests.modified >=' => $since, 'RbacAccessRequests.status' => 'granted']);
			if ($companyId > 0) {
				$qg->innerJoin(['U3' => 'users'], ['U3.id = RbacAccessRequests.user_id', 'U3.idempresa' => $companyId]);
			}
			$out['granted_last_window'] = (int)$qg->count();

			$qr = $req->find()->where([
				'RbacAccessRequests.modified >=' => $since,
				'RbacAccessRequests.status IN' => ['manager_rejected', 'admin_rejected'],
			]);
			if ($companyId > 0) {
				$qr->innerJoin(['U4' => 'users'], ['U4.id = RbacAccessRequests.user_id', 'U4.idempresa' => $companyId]);
			}
			$out['rejected_last_window'] = (int)$qr->count();

			$this->_fillLatenciesAndTops($out, $req, $since, $companyId);
		} catch (\Throwable $e) {
			$out['_error'] = substr($e->getMessage(), 0, 240);
		}

		try {
			$gTbl = TableRegistry::get('RbacAccessGrants');

			$h = FrozenTime::now();


			$t7 = FrozenTime::now()->addDays(7);


			if ($companyId <= 0) {


				$out['active_grants'] = (int)$gTbl->find()->where(['status' => 'active'])->count();


				$out['grants_expiring_7d'] = (int)$gTbl->find()


					->where(['status' => 'active', 'expires_at <=' => $t7, 'expires_at >=' => $h])


					->count();


				$out['expired_still_active_anomaly'] = (int)$gTbl->find()


					->where(['status' => 'active', 'expires_at IS NOT' => null, 'expires_at <' => $h])


					->count();


			} else {


				$b = $gTbl->find()->innerJoin(['U5' => 'users'], ['U5.id = RbacAccessGrants.user_id', 'U5.idempresa' => $companyId]);


				$out['active_grants'] = (int)$b->where(['RbacAccessGrants.status' => 'active'])->count();


				$out['grants_expiring_7d'] = (int)$gTbl->find()


					->innerJoin(['U6' => 'users'], ['U6.id = RbacAccessGrants.user_id', 'U6.idempresa' => $companyId])


					->where(['RbacAccessGrants.status' => 'active', 'RbacAccessGrants.expires_at <=' => $t7, 'RbacAccessGrants.expires_at >=' => $h])


					->count();


				$out['expired_still_active_anomaly'] = (int)$gTbl->find()


					->innerJoin(['U7' => 'users'], ['U7.id = RbacAccessGrants.user_id', 'U7.idempresa' => $companyId])


					->where(['RbacAccessGrants.status' => 'active', 'RbacAccessGrants.expires_at IS NOT' => null, 'RbacAccessGrants.expires_at <' => $h])


					->count();


			}


			$out['critical_active_grants'] = $this->countCriticalActiveGrantsIterate($gTbl, $companyId);


		} catch (\Throwable $e2) {



		}








		try {



			$log = TableRegistry::get('RbacChangeAuditLogs')->find()


				->order(['created' => 'DESC'])




				->limit(15)




				->all();


			foreach ($log as $r) {


				$out['recent_audit'][] = [


					'id' => (int)$r->id,


					'action_type' => (string)$r->action_type,




					'created' => $r->created ? $r->created->format('Y-m-d H:i') : '',






					'request_id' => $r->access_request_id ?? null,




				];

			}




		} catch (\Throwable $eL) {


		}




		return $out;





	}








	protected function countCriticalActiveGrantsIterate($gTbl, int $companyId): int {


		$n = 0;





		try {


			$q = $gTbl->find()->where(['RbacAccessGrants.status' => 'active']);


			if ($companyId > 0) {


				$q->innerJoin(['Ux' => 'users'], ['Ux.id = RbacAccessGrants.user_id', 'Ux.idempresa' => $companyId]);


			}


			foreach ($q->all() as $g) {


				$reqId = (int)$g->access_request_id;


				$row = TableRegistry::get('RbacAccessRequests')->find()->where(['id' => $reqId])->first();


				if (!$row) {


					continue;


				}


				$codes = json_decode((string)$row->requested_permission_codes, true);


				if (!is_array($codes)) {


					$codes = [];


				}


				if ($this->codesLookCritical($codes)) {


					$n++;


				}


			}


		} catch (\Throwable $e) {


		}


		return $n;


	}








	protected function codesLookCritical(array $codes): bool {


		foreach ($codes as $c) {


			$c = strtolower((string)$c);


			if ($c === 'senhas.view' || $c === 'bancosenhas.view' || strpos($c, 'permissoes.') === 0) {


				return true;


			}


		}


		if ($codes === []) {


			return false;


		}


		try {


			return TableRegistry::get('RbacPermissions')->find()->where(['code IN' => $codes, 'criticality' => 'critical'])->count() > 0;


		} catch (\Throwable $e) {


			return false;


		}


	}








	protected function _fillLatenciesAndTops(array &$out, $req, FrozenTime $since, int $companyId): void {


		$rows = $req->find()


			->where(['RbacAccessRequests.created >=' => $since, 'RbacAccessRequests.status' => 'granted']);


		if ($companyId > 0) {


			$rows->innerJoin(['Uy' => 'users'], ['Uy.id = RbacAccessRequests.user_id', 'Uy.idempresa' => $companyId]);


		}


		$s1 = [];


		$s2 = [];


		$s3 = [];


		$permCnt = [];


		$modCnt = [];


		$userCnt = [];


		$roleCnt = [];


		foreach ($rows->all() as $r) {


			$cr = $r->created ? FrozenTime::instance($r->created) : null;


			$mr = $r->manager_reviewed_at ? FrozenTime::instance($r->manager_reviewed_at) : null;


			$ar = $r->admin_reviewed_at ? FrozenTime::instance($r->admin_reviewed_at) : null;


			if ($cr && $mr) {


				$s1[] = max(0, ($mr->getTimestamp() - $cr->getTimestamp()) / 3600);


			}


			if ($mr && $ar) {


				$s2[] = max(0, ($ar->getTimestamp() - $mr->getTimestamp()) / 3600);


			}


			$grantAt = null;


			try {


				$g = TableRegistry::get('RbacAccessGrants')->find()->where(['access_request_id' => (int)$r->id])->first();


				if ($g && $g->granted_at) {


					$grantAt = FrozenTime::instance($g->granted_at);


				}


			} catch (\Throwable $e) {


			}


			if ($ar && $grantAt) {


				$s3[] = max(0, ($grantAt->getTimestamp() - $ar->getTimestamp()) / 3600);


			}


			$pc = json_decode((string)$r->requested_permission_codes, true);


			if (is_array($pc)) {


				foreach ($pc as $code) {


					$code = (string)$code;


					if ($code === '') {


						continue;


					}


					$permCnt[$code] = ($permCnt[$code] ?? 0) + 1;


					$mod = explode('.', $code, 2)[0] ?? '_';


					$modCnt[$mod] = ($modCnt[$mod] ?? 0) + 1;


				}


			}


			$uid = (int)$r->user_id;


			$userCnt[$uid] = ($userCnt[$uid] ?? 0) + 1;


			$sr = json_decode((string)$r->suggested_role_ids, true);


			if (is_array($sr)) {


				foreach ($sr as $rid) {


					$rid = (int)$rid;


					if ($rid <= 0) {


						continue;


					}


					$roleCnt[$rid] = ($roleCnt[$rid] ?? 0) + 1;


				}


			}


		}


		$avg = function (array $a) {


			if ($a === []) {


				return null;


			}


			return round(array_sum($a) / count($a), 2);


		};


		$out['avg_hours_created_to_manager'] = $avg($s1);


		$out['avg_hours_manager_to_admin'] = $avg($s2);


		$out['avg_hours_admin_to_grant'] = $avg($s3);


		arsort($permCnt);


		arsort($modCnt);


		arsort($userCnt);


		arsort($roleCnt);


		$out['top_permissions'] = array_slice($permCnt, 0, 12, true);


		$out['top_modules'] = array_slice($modCnt, 0, 10, true);


		$out['top_users'] = array_slice($userCnt, 0, 10, true);


		$out['top_roles'] = array_slice($roleCnt, 0, 10, true);


	}








	/** @param array{days?:int,company_id?:int,status?:string} $filters */


	public function csvResumo(array $filters): string {


		$d = $this->build($filters);


		$lines = ['k,v'];


		foreach ($d as $k => $v) {


			if (is_scalar($v) || $v === null) {


				$lines[] = $this->csvEsc((string)$k) . ',' . $this->csvEsc($v === null ? '' : (string)$v);


			}


		}


		return implode("\n", $lines) . "\n";


	}








	public function csvPendentes(array $filters): string {


		$days = isset($filters['days']) ? (int)$filters['days'] : 30;


		$companyId = isset($filters['company_id']) ? (int)$filters['company_id'] : 0;


		$req = TableRegistry::get('RbacAccessRequests');


		$q = $req->find()->where(['status IN' => ['pending_manager', 'manager_approved', 'pending_admin', 'admin_approved']])->order(['id' => 'DESC'])->limit(5000);


		if ($companyId > 0) {


			$q->innerJoin(['Uv' => 'users'], ['Uv.id = RbacAccessRequests.user_id', 'Uv.idempresa' => $companyId]);


		}


		$h = ['id', 'user_id', 'status', 'controller', 'action', 'created'];


		$rows = [$this->csvLine($h)];


		foreach ($q->all() as $r) {


			$rows[] = $this->csvLine([


				(string)(int)$r->id,


				(string)(int)$r->user_id,


				(string)$r->status,


				(string)$r->controller,


				(string)$r->action,


				$r->created ? $r->created->format('c') : '',


			]);


		}


		return implode("\n", $rows) . "\n";


	}








	public function csvGrantsAtivos(array $filters): string {


		$companyId = isset($filters['company_id']) ? (int)$filters['company_id'] : 0;


		$gTbl = TableRegistry::get('RbacAccessGrants');


		$q = $gTbl->find()->where(['status' => 'active'])->order(['id' => 'DESC'])->limit(8000);


		if ($companyId > 0) {


			$q->innerJoin(['Uw' => 'users'], ['Uw.id = RbacAccessGrants.user_id', 'Uw.idempresa' => $companyId]);


		}


		$rows = [$this->csvLine(['id', 'user_id', 'role_id', 'access_request_id', 'expires_at', 'applied_role_assignment'])];

		foreach ($q->all() as $g) {


			$rows[] = $this->csvLine([


				(string)(int)$g->id,


				(string)(int)$g->user_id,


				(string)(int)$g->role_id,


				(string)(int)$g->access_request_id,


				$g->expires_at ? FrozenTime::instance($g->expires_at)->format('c') : '',


				!empty($g->applied_role_assignment) ? '1' : '0',


			]);


		}


		return implode("\n", $rows) . "\n";


	}








	protected function csvEsc(string $s): string {


		return '"' . str_replace('"', '""', $s) . '"';


	}








	/** @param array<int,string> $cells */


	protected function csvLine(array $cells): string {


		$o = [];


		foreach ($cells as $c) {


			$o[] = $this->csvEsc((string)$c);


		}


		return implode(',', $o);


	}


}
