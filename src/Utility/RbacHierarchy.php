<?php
namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Anti-escalação por hierarchy_level em rbac_roles (Fase 3).
 * Nível numérico maior = mais privilegiado; operador só atribui papéis com nível <= ao máximo dos seus papéis efetivos.
 * Administrador legado (users.admin) ignora o teto.
 * Papéis de sistema (seed / migration 20260419230000): super_admin 10000, admin_equipe 8000, operacao/financeiro 5000, leitura 500, cliente_portal 100.
 */
class RbacHierarchy {

	/**
	 * Teto de nível para atribuição: null = sem limite (admin legado).
	 */
	public static function operatorAssignHierarchyCap($legacyAdmin, int $operatorUserId): ?int {
		if (!empty($legacyAdmin)) {
			return null;
		}
		if ($operatorUserId <= 0) {
			return 0;
		}
		try {
			$roleIds = RbacUserRolesResolver::effectiveRoleIds($operatorUserId);
			if ($roleIds === []) {
				return 0;
			}
			$rows = TableRegistry::get('RbacRoles')->find()
				->select(['id', 'hierarchy_level'])
				->where(['id IN' => $roleIds])
				->all();
			$max = 0;
			foreach ($rows as $r) {
				$lvl = (int)($r->hierarchy_level ?? 0);
				if ($lvl > $max) {
					$max = $lvl;
				}
			}

			return $max;
		} catch (\Exception $e) {
			return 0;
		}
	}

	/**
	 * Papéis exibidos na matriz de checkboxes: até ao teto OU já vinculados ao alvo (read-only alto mantém-se visível).
	 *
	 * @param \App\Model\Entity\RbacRole[]|\Traversable|array $allRoles
	 * @return \App\Model\Entity\RbacRole[]
	 */
	public static function rolesVisibleForAssign(?int $cap, array $existingRoleIdsOnTarget, $allRoles): array {
		$existingSet = array_fill_keys(array_map('intval', $existingRoleIdsOnTarget), true);
		$out = [];
		foreach ($allRoles as $r) {
			$rid = (int)$r->id;
			$lvl = (int)($r->hierarchy_level ?? 0);
			if ($cap === null || $lvl <= $cap || isset($existingSet[$rid])) {
				$out[] = $r;
			}
		}

		return $out;
	}

	/**
	 * Monta lista final de role_id ao gravar: admin → só o pedido; com teto → preserva papéis acima do teto já existentes + pedido filtrado.
	 *
	 * @param int[] $existingRoleIds
	 * @param int[] $requestedRoleIds
	 * @param array<int,int> $roleIdToLevel
	 * @return array{0: int[], 1: int[]} finalIds, strippedRequestedIds (pedidos ignorados por nível)
	 */
	public static function finalizeRoleIdsForSave(?int $cap, array $existingRoleIds, array $requestedRoleIds, array $roleIdToLevel): array {
		$existingRoleIds = array_values(array_unique(array_filter(array_map('intval', $existingRoleIds), static function ($v) {
			return $v > 0;
		})));
		$requestedRoleIds = array_values(array_unique(array_filter(array_map('intval', $requestedRoleIds), static function ($v) {
			return $v > 0;
		})));

		if ($cap === null) {
			return [$requestedRoleIds, []];
		}

		$highPreserve = [];
		foreach ($existingRoleIds as $rid) {
			$lvl = $roleIdToLevel[$rid] ?? 0;
			if ($lvl > $cap) {
				$highPreserve[] = $rid;
			}
		}

		$stripped = [];
		$allowedReq = [];
		foreach ($requestedRoleIds as $rid) {
			$lvl = $roleIdToLevel[$rid] ?? 0;
			if ($lvl > $cap) {
				$stripped[] = $rid;
			} else {
				$allowedReq[] = $rid;
			}
		}

		$final = array_values(array_unique(array_merge($highPreserve, $allowedReq)));

		return [$final, $stripped];
	}
}
