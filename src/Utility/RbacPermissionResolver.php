<?php
namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Expande IDs de rbac_permissions usando rbac_permission_legacy_aliases (legacy_code → canonical_code).
 * Quem tem um papel só com permissão legada (ex.: clientes.manage) passa a considerar também as linhas
 * canónicas correspondentes na mesma checagem de rota — útil quando a matriz mistura macro e atómicas.
 */
class RbacPermissionResolver {

	/**
	 * @param int[] $permissionIds
	 * @return int[]
	 */
	public static function expandPermissionIds(array $permissionIds) {
		$permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds), static function ($v) {
			return $v > 0;
		})));
		if ($permissionIds === []) {
			return [];
		}
		try {
			$permissionsTable = TableRegistry::get('RbacPermissions');
			$conn = $permissionsTable->getConnection();
			if (!self::_hasAliasTable($conn)) {
				return $permissionIds;
			}
			$codes = $permissionsTable->find()
				->select(['code'])
				->where(['id IN' => $permissionIds])
				->extract('code')
				->toList();
			$codes = array_values(array_unique(array_filter(array_map('strval', $codes))));
			if ($codes === []) {
				return $permissionIds;
			}
			$placeholders = implode(',', array_fill(0, count($codes), '?'));
			$sql = 'SELECT DISTINCT canonical_code FROM rbac_permission_legacy_aliases WHERE legacy_code IN (' . $placeholders . ')';
			$stmt = $conn->execute($sql, $codes);
			$canonicalCodes = [];
			while ($row = $stmt->fetch('assoc')) {
				if (!empty($row['canonical_code'])) {
					$canonicalCodes[] = $row['canonical_code'];
				}
			}
			$canonicalCodes = array_values(array_unique($canonicalCodes));
			if ($canonicalCodes === []) {
				return $permissionIds;
			}
			$extraIds = $permissionsTable->find()
				->select(['id'])
				->where(['code IN' => $canonicalCodes])
				->extract('id')
				->toList();
			$extraIds = array_values(array_unique(array_map('intval', $extraIds)));

			return array_values(array_unique(array_merge($permissionIds, $extraIds)));
		} catch (\Exception $e) {
			return $permissionIds;
		}
	}

	/**
	 * Código de permissão aparece como legacy em rbac_permission_legacy_aliases.
	 *
	 * @param \Cake\Database\Connection $conn
	 */
	public static function isLegacyBundleCode($code, $conn) {
		$code = trim((string)$code);
		if ($code === '' || !self::_hasAliasTable($conn)) {
			return false;
		}
		try {
			$row = $conn->execute(
				'SELECT 1 AS x FROM rbac_permission_legacy_aliases WHERE legacy_code = ? LIMIT 1',
				[$code]
			)->fetch('assoc');

			return !empty($row);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @param \Cake\Database\Connection $conn
	 */
	protected static function _hasAliasTable($conn) {
		try {
			return in_array('rbac_permission_legacy_aliases', $conn->getSchemaCollection()->listTables(), true);
		} catch (\Exception $e) {
			return false;
		}
	}
}
