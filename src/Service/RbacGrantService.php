<?php
namespace App\Service;

use App\Utility\RbacChecker;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

class RbacGrantService {

	protected function rbacAccessGrantsTableExists(): bool {
		try {
			$t = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();

			return in_array('rbac_access_grants', $t, true);
		} catch (\Throwable $e) {

			return false;
		}
	}

	public function previewAssignExistingRole(int $accessRequestId, int $roleId, array $actorUser): array {
		$ctx = $this->_validateBase($accessRequestId, $roleId, $actorUser);
		$rolePermCodes = $this->_rolePermissionCodes($roleId);
		$required = $ctx['required_codes'];
		$missingInRole = array_values(array_diff($required, $rolePermCodes));
		$isCritical = !empty($ctx['is_critical']);

		return [
			'ok' => $missingInRole === [],
			'request' => $ctx['request']->toArray(),
			'role' => $ctx['role']->toArray(),
			'required_codes' => $required,
			'role_permission_codes' => $rolePermCodes,
			'missing_in_role' => $missingInRole,
			'is_critical' => $isCritical,
			'impact' => $missingInRole === []
				? 'O papel cobre as permissões solicitadas para a rota.'
				: 'O papel NÃO cobre todos os códigos requeridos.',
		];
	}

	public function executeAssignExistingRole(int $accessRequestId, int $roleId, array $actorUser, string $justification): array {
		$justification = trim($justification);
		$justification = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $justification) ?: '';
		$justification = mb_substr($justification, 0, 500);
		if ($justification === '') {
			throw new \InvalidArgumentException('Justificativa obrigatória.');
		}
		$preview = $this->previewAssignExistingRole($accessRequestId, $roleId, $actorUser);
		if (empty($preview['ok'])) {
			throw new \RuntimeException('Role escolhido não cobre as permissões necessárias.');
		}

		$reqTbl = TableRegistry::get('RbacAccessRequests');
		$tblUr = TableRegistry::get('RbacUsersRoles');
		$conn = $reqTbl->getConnection();
		$isCritical = !empty($preview['is_critical']);
		$actorId = (int)($actorUser['id'] ?? 0);

		$out = $conn->transactional(function () use ($reqTbl, $tblUr, $accessRequestId, $roleId, $justification, $isCritical, $actorId) {
			$request = $reqTbl->find()->where(['id' => $accessRequestId])->epilog('FOR UPDATE')->first();
			if (!$request) {
				throw new \RuntimeException('Pedido não encontrado.');
			}
			if ((string)$request->status === 'granted') {
				return ['ok' => true, 'request_id' => $accessRequestId, 'role_id' => $roleId, 'user_id' => (int)$request->user_id, 'applied' => false, 'already_granted' => true];
			}
			if ((string)$request->status !== 'admin_approved') {
				throw new \RuntimeException('Pedido precisa estar em admin_approved.');
			}
			$userId = (int)$request->user_id;
			$hadRoleBefore = (bool)$tblUr->find()->where(['user_id' => $userId, 'role_id' => $roleId])->first();
			if (!$hadRoleBefore) {
				$row = $tblUr->newEntity(['user_id' => $userId, 'role_id' => $roleId]);
				if (!$tblUr->save($row)) {
					throw new \RuntimeException('Falha ao aplicar papel no usuário.');
				}
			}
			$appliedRoleAssignment = !$hadRoleBefore;
			$request->status = 'granted';
			$request->justification = $justification;
			$request->admin_response = trim((string)$request->admin_response . "\n" . 'Grant automático: role_id=' . $roleId . '.');
			if (!$reqTbl->save($request)) {
				throw new \RuntimeException('Falha ao finalizar grant no pedido.');
			}

			try {
				if ($this->rbacAccessGrantsTableExists()) {
					$gTbl = TableRegistry::get('RbacAccessGrants');
					$dup = $gTbl->find()->where(['access_request_id' => $accessRequestId])->first();
					if (!$dup) {
						$expiresAt = $this->_computeExpiresAt($isCritical);
						$grant = $gTbl->newEntity([
							'access_request_id' => $accessRequestId,
							'user_id' => $userId,
							'role_id' => $roleId,
							'granted_by' => $actorId,
							'granted_at' => FrozenTime::now(),
							'expires_at' => $expiresAt,
							'applied_role_assignment' => $appliedRoleAssignment,
							'status' => 'active',
						]);
						$gTbl->save($grant);
					}
				}
			} catch (\Throwable $e) {
			}

			return ['ok' => true, 'request_id' => $accessRequestId, 'role_id' => $roleId, 'user_id' => $userId, 'applied' => true, 'already_granted' => false];
		});

		return $out;
	}

	private function _validateBase(int $accessRequestId, int $roleId, array $actorUser): array {
		$cfg = (array)Configure::read('Rbac');
		$diag = isset($cfg['diagnostics']) && is_array($cfg['diagnostics']) ? $cfg['diagnostics'] : [];
		if (empty($diag['allow_automatic_grant'])) {
			throw new \RuntimeException('Liberação automática está desativada por configuração.');
		}
		$actorId = (int)($actorUser['id'] ?? 0);
		if ($actorId <= 0 || !RbacChecker::userHasPermissionCode($actorId, 'rbac.requests.grant')) {
			throw new \RuntimeException('Sem permissão para executar grant automático.');
		}
		$request = TableRegistry::get('RbacAccessRequests')->find()->where(['id' => $accessRequestId])->first();
		if (!$request) {
			throw new \RuntimeException('Pedido não encontrado.');
		}
		if ((string)$request->status !== 'admin_approved') {
			throw new \RuntimeException('Pedido precisa estar em admin_approved.');
		}
		$role = TableRegistry::get('RbacRoles')->find()->where(['id' => $roleId])->first();
		if (!$role) {
			throw new \RuntimeException('Role não encontrado.');
		}
		if ((string)($role->slug ?? '') === 'super_admin' && empty($diag['allow_auto_grant_super_admin'])) {
			throw new \RuntimeException('Grant automático para super_admin está bloqueado.');
		}
		$required = json_decode((string)($request->requested_permission_codes ?? '[]'), true);
		if (!is_array($required)) {
			$required = [];
		}
		$required = array_values(array_unique(array_filter(array_map('strval', $required))));
		$isCritical = $this->_hasCriticalCode($required);
		if ($isCritical && empty($diag['allow_auto_grant_critical'])) {
			throw new \RuntimeException('Grant automático para permissões críticas está bloqueado.');
		}

		return ['request' => $request, 'role' => $role, 'required_codes' => $required, 'is_critical' => $isCritical];
	}

	private function _rolePermissionCodes(int $roleId): array {
		$permIds = TableRegistry::get('RbacRolesPermissions')->find()
			->select(['permission_id'])
			->where(['role_id' => $roleId])
			->extract('permission_id')
			->toList();
		$permIds = array_values(array_unique(array_map('intval', $permIds)));
		if ($permIds === []) {
			return [];
		}

		return array_values(array_unique(array_filter(array_map('strval',
			TableRegistry::get('RbacPermissions')->find()->where(['id IN' => $permIds])->extract('code')->toList()
		))));
	}

	private function _hasCriticalCode(array $codes): bool {
		foreach ($codes as $c) {
			$c = strtolower((string)$c);
			if ($c === 'senhas.view' || $c === 'bancosenhas.view' || strpos($c, 'permissoes.') === 0) {
				return true;
			}
		}
		if ($codes !== []) {
			try {
				$n = TableRegistry::get('RbacPermissions')->find()
					->where(['code IN' => $codes, 'criticality' => 'critical'])
					->count();

				return $n > 0;
			} catch (\Throwable $e) {

				return false;
			}
		}

		return false;
	}

	private function _computeExpiresAt(bool $isCritical): ?FrozenTime {
		$cfg = Configure::read('Rbac.access_expiration');
		if (!is_array($cfg) || empty($cfg['enabled'])) {

			return null;
		}

		$d = $isCritical ? (int)($cfg['critical_default_days'] ?? 7) : (int)($cfg['default_days'] ?? 90);
		if ($d <= 0) {
			$d = $isCritical ? 7 : 90;
		}

		return FrozenTime::now()->addDays($d);
	}
}

