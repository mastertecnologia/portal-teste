<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * Dual-write piloto: rbac_access_requests → approval_requests.
 */
class ApprovalRequestSyncService {

	public const SOURCE_RBAC = 'rbac_access';

	public function isEnabled(): bool {
		if (!filter_var(Configure::read('ApprovalRequests.dual_write_rbac', true), FILTER_VALIDATE_BOOLEAN)) {
			return false;
		}
		try {
			$schema = TableRegistry::getTableLocator()->get('ApprovalRequests')->getConnection()->getSchemaCollection();

			return in_array('approval_requests', $schema->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $rbacRow linha RbacAccessRequests
	 */
	public function syncFromRbacAccessRequest(EntityInterface $rbacRow): void {
		if (!$this->isEnabled()) {
			return;
		}
		$rbacId = (int)$rbacRow->get('id');
		if ($rbacId <= 0) {
			return;
		}
		$userId = (int)$rbacRow->get('user_id');
		$idempresa = $this->resolveUserEmpresa($userId);
		if ($idempresa <= 0) {
			return;
		}

		$status = $this->mapRbacStatus((string)$rbacRow->get('status'));
		$supportCode = (string)($rbacRow->get('support_code') ?? $rbacId);
		$perms = (string)($rbacRow->get('requested_permission_codes') ?? '');
		$summary = [
			'support_code' => $supportCode,
			'controller' => (string)$rbacRow->get('controller'),
			'action' => (string)$rbacRow->get('action'),
			'permissions' => $perms,
			'rbac_status' => (string)$rbacRow->get('status'),
		];

		$requestedAt = $rbacRow->get('created');
		if (!$requestedAt instanceof \DateTimeInterface) {
			$requestedAt = FrozenTime::now();
		}
		$slaDue = FrozenTime::instance($requestedAt)->addHours(24);

		$decidedBy = null;
		$decidedAt = null;
		$decisionNote = null;
		if ($status === 'approved') {
			$decidedBy = (int)($rbacRow->get('admin_reviewed_by') ?: $rbacRow->get('manager_reviewed_by') ?: 0) ?: null;
			$decidedAt = $rbacRow->get('admin_reviewed_at') ?? $rbacRow->get('manager_reviewed_at');
			$decisionNote = (string)($rbacRow->get('admin_response') ?? $rbacRow->get('manager_response') ?? '');
		} elseif ($status === 'rejected') {
			$decidedBy = (int)($rbacRow->get('admin_reviewed_by') ?: $rbacRow->get('manager_reviewed_by') ?: 0) ?: null;
			$decidedAt = $rbacRow->get('admin_reviewed_at') ?? $rbacRow->get('manager_reviewed_at');
			$decisionNote = (string)($rbacRow->get('admin_response') ?? $rbacRow->get('manager_response') ?? '');
		}

		$tbl = TableRegistry::getTableLocator()->get('ApprovalRequests');
		$existing = $tbl->find()
			->where([
				'source_type' => self::SOURCE_RBAC,
				'source_id' => $rbacId,
			])
			->first();

		$data = [
			'idempresa' => $idempresa,
			'source_type' => self::SOURCE_RBAC,
			'source_id' => $rbacId,
			'status' => $status,
			'title' => 'RBAC · ' . $supportCode,
			'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'requested_by' => $userId > 0 ? $userId : null,
			'requested_at' => $requestedAt,
			'assignee_role' => $this->mapRbacAssignee((string)$rbacRow->get('status')),
			'sla_due_at' => $status === 'pending' ? $slaDue : null,
			'decided_by' => $decidedBy,
			'decided_at' => $decidedAt,
			'decision_note' => $decisionNote !== '' ? $decisionNote : null,
		];

		if ($existing) {
			$tbl->patchEntity($existing, $data);
			$tbl->save($existing);
		} else {
			$row = $tbl->newEntity($data);
			$tbl->save($row);
		}
	}

	protected function resolveUserEmpresa(int $userId): int {
		if ($userId <= 0) {
			return 0;
		}
		try {
			$u = TableRegistry::getTableLocator()->get('Users')->find()
				->select(['idempresa'])
				->where(['id' => $userId])
				->first();

			return $u ? (int)$u->get('idempresa') : 0;
		} catch (\Throwable $e) {
			return 0;
		}
	}

	protected function mapRbacStatus(string $rbacStatus): string {
		if (in_array($rbacStatus, ['pending_manager', 'pending_admin', 'manager_approved'], true)) {
			return 'pending';
		}
		if (strpos($rbacStatus, 'reject') !== false || $rbacStatus === 'rejected') {
			return 'rejected';
		}
		if (strpos($rbacStatus, 'approv') !== false || $rbacStatus === 'granted' || $rbacStatus === 'admin_approved') {
			return 'approved';
		}

		return 'pending';
	}

	protected function mapRbacAssignee(string $rbacStatus): ?string {
		if ($rbacStatus === 'pending_manager') {
			return 'manager';
		}
		if (in_array($rbacStatus, ['pending_admin', 'manager_approved'], true)) {
			return 'admin';
		}

		return null;
	}
}
