<?php
namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacApprovalWorkflowService {

	public function managerCanReview(array $actorUser, int $targetUserId): bool {
		if ((int)($actorUser['id'] ?? 0) <= 0) {
			return false;
		}
		if (!empty($actorUser['admin'])) {
			return true;
		}
		$target = TableRegistry::get('Users')->find()->where(['id' => $targetUserId])->first();
		if (!$target) {
			return false;
		}

		return (int)($actorUser['idempresa'] ?? 0) > 0
			&& (int)($actorUser['idempresa'] ?? 0) === (int)($target->idempresa ?? 0);
	}

	public function approveManager($requestEntity, array $actorUser, string $response): void {
		$requestEntity->status = 'manager_approved';
		$requestEntity->manager_reviewed_by = (int)$actorUser['id'];
		$requestEntity->manager_reviewed_at = FrozenTime::now();
		$requestEntity->manager_response = trim($response);
	}

	public function enqueueForAdmin($requestEntity): void {
		$requestEntity->status = 'pending_admin';
	}

	public function rejectManager($requestEntity, array $actorUser, string $response): void {
		$requestEntity->status = 'manager_rejected';
		$requestEntity->manager_reviewed_by = (int)$actorUser['id'];
		$requestEntity->manager_reviewed_at = FrozenTime::now();
		$requestEntity->manager_response = trim($response);
	}

	public function approveAdmin($requestEntity, array $actorUser, string $response): void {
		$requestEntity->status = 'admin_approved';
		$requestEntity->admin_reviewed_by = (int)$actorUser['id'];
		$requestEntity->admin_reviewed_at = FrozenTime::now();
		$requestEntity->admin_response = trim($response);
	}

	public function rejectAdmin($requestEntity, array $actorUser, string $response): void {
		$requestEntity->status = 'admin_rejected';
		$requestEntity->admin_reviewed_by = (int)$actorUser['id'];
		$requestEntity->admin_reviewed_at = FrozenTime::now();
		$requestEntity->admin_response = trim($response);
	}
}

