<?php
namespace App\Service;

use Cake\ORM\TableRegistry;

class RbacPermissionMatrixService {

	public function build(array $filters = []): array {
		$permTbl = TableRegistry::get('RbacPermissions');
		$roleTbl = TableRegistry::get('RbacRoles');
		$linkTbl = TableRegistry::get('RbacRolesPermissions');
		$userRoleTbl = TableRegistry::get('RbacUsersRoles');

		$qPerm = $permTbl->find()->order(['module' => 'ASC', 'sort_order' => 'ASC', 'code' => 'ASC']);
		$fModule = trim((string)($filters['module'] ?? ''));
		$fCtrl = trim((string)($filters['controller'] ?? ''));
		$fAction = trim((string)($filters['action'] ?? ''));
		$page = max(1, (int)($filters['page'] ?? 1));
		$limit = max(1, min(100, (int)($filters['limit'] ?? 100)));
		$forExport = !empty($filters['for_export']);
		if ($fModule !== '') {
			$qPerm->where(['module' => $fModule]);
		}
		if ($fCtrl !== '') {
			$qPerm->where(['controller' => $fCtrl]);
		}
		if ($fAction !== '') {
			$qPerm->where(['action LIKE' => '%' . $fAction . '%']);
		}
		$totalPermissions = (int)(clone $qPerm)->count();
		if (!$forExport) {
			$qPerm->limit($limit)->offset(($page - 1) * $limit);
		}
		$permissions = $qPerm->all()->toList();
		$roles = $roleTbl->find()->where(['active' => true])->order(['sort_order' => 'ASC', 'name' => 'ASC'])->all()->toList();
		$roleFilter = (int)($filters['role_id'] ?? 0);
		if ($roleFilter > 0) {
			$roles = array_values(array_filter($roles, function ($r) use ($roleFilter) {
				return (int)$r->id === $roleFilter;
			}));
		}
		$roleIds = array_map('intval', array_column($roles, 'id'));
		$permIds = array_map('intval', array_column($permissions, 'id'));
		$linkMap = [];
		if ($roleIds !== [] && $permIds !== []) {
			foreach ($linkTbl->find()->where(['role_id IN' => $roleIds, 'permission_id IN' => $permIds])->all() as $lnk) {
				$linkMap[(int)$lnk->role_id][(int)$lnk->permission_id] = true;
			}
		}
		$usersByRole = [];
		if ($roleIds !== []) {
			foreach ($userRoleTbl->find()->select(['role_id', 'user_id'])->where(['role_id IN' => $roleIds])->all() as $ur) {
				if (!isset($usersByRole[(int)$ur->role_id])) {
					$usersByRole[(int)$ur->role_id] = [];
				}
				$usersByRole[(int)$ur->role_id][(int)$ur->user_id] = true;
			}
		}
		$orphanPerms = [];
		$orphanCount = 0;
		foreach ($permissions as $p) {
			$has = false;
			foreach ($roleIds as $rid) {
				if (!empty($linkMap[$rid][(int)$p->id])) {
					$has = true;
					break;
				}
			}
			if (!$has) {
				$orphanPerms[] = $p;
				$orphanCount++;
			}
		}
		$rolesWithoutUsers = [];
		foreach ($roles as $r) {
			if (empty($usersByRole[(int)$r->id])) {
				$rolesWithoutUsers[] = $r;
			}
		}

		return [
			'permissions' => $permissions,
			'roles' => $roles,
			'link_map' => $linkMap,
			'users_by_role_count' => array_map('count', $usersByRole),
			'orphan_permissions' => $orphanPerms,
			'roles_without_users' => $rolesWithoutUsers,
			'filters' => ['module' => $fModule, 'controller' => $fCtrl, 'action' => $fAction, 'role_id' => $roleFilter],
			'pagination' => [
				'page' => $page,
				'limit' => $limit,
				'total' => $totalPermissions,
				'total_pages' => (int)max(1, ceil($totalPermissions / $limit)),
			],
			'orphan_count_page' => $orphanCount,
		];
	}
}

