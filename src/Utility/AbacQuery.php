<?php
namespace App\Utility;

use Cake\Core\Configure;
use Cake\ORM\Query;

/**
 * Aplica predicados de isolamento (empresa / cliente / own) em queries ORM.
 */
class AbacQuery {

	/**
	 * @param \Cake\ORM\Query $query
	 * @param array $user Auth->user() ou []
	 * @param \Cake\Controller\Controller $controller
	 * @param string $tableKey chave em Configure Abac.tables
	 * @param string|null $alias alias SQL (default do mapa)
	 * @return \Cake\ORM\Query
	 */
	public static function apply(Query $query, array $user, $controller, $tableKey, $alias = null) {
		$cfg = Configure::read('Abac');
		if (empty($cfg['enabled'])) {
			return $query;
		}
		$tables = isset($cfg['tables'][$tableKey]) ? $cfg['tables'][$tableKey] : null;
		if ($tables === null || !is_array($tables)) {
			return $query;
		}
		$alias = $alias !== null && $alias !== '' ? $alias : (isset($tables['alias']) ? $tables['alias'] : $tableKey);
		$scope = self::resolveScope($user, $controller, $tables);
		if ($scope === null) {
			return $query;
		}

		return self::applyScope($query, $user, $tables, $alias, $scope);
	}

	/**
	 * @param array $user
	 * @param \Cake\Controller\Controller $controller
	 * @param array $tableMap
	 * @return string|null empresa|cliente|own
	 */
	public static function resolveScope(array $user, $controller, array $tableMap) {
		$role = isset($user['role']) ? (int) $user['role'] : 0;

		if ($role === 1) {
			if (!empty($tableMap['cliente_row_id']) || !empty($tableMap['cliente_column'])) {
				return 'cliente';
			}
			if (!empty($tableMap['empresa_column'])) {
				return 'empresa';
			}

			return null;
		}

		$fromRbac = null;
		if (is_object($controller) && isset($controller->rbacAbacScope)) {
			$fromRbac = trim((string) $controller->rbacAbacScope);
		}
		if ($fromRbac === 'empresa' || $fromRbac === 'cliente' || $fromRbac === 'own') {
			return $fromRbac;
		}

		if (!empty($tableMap['empresa_column'])) {
			return 'empresa';
		}

		return null;
	}

	/**
	 * @param \Cake\ORM\Query $query
	 * @param array $user
	 * @param array $tableMap
	 * @param string $alias
	 * @param string $scope
	 * @return \Cake\ORM\Query
	 */
	protected static function applyScope(Query $query, array $user, array $tableMap, $alias, $scope) {
		if ($scope === 'empresa') {
			$col = isset($tableMap['empresa_column']) ? $tableMap['empresa_column'] : null;
			if ($col === null || $col === '') {
				return $query;
			}
			$eid = isset($user['idempresa']) ? $user['idempresa'] : null;
			if ($eid === null || $eid === '') {
				return $query->where('1=0');
			}

			return $query->where([$alias . '.' . $col => $eid]);
		}

		if ($scope === 'cliente') {
			$cid = isset($user['idcliente']) ? $user['idcliente'] : null;
			if ($cid === null || $cid === '') {
				return $query->where('1=0');
			}
			if (!empty($tableMap['cliente_row_id'])) {
				return $query->where([$alias . '.id' => $cid]);
			}
			$cc = isset($tableMap['cliente_column']) ? $tableMap['cliente_column'] : null;
			if ($cc === null || $cc === '') {
				return $query;
			}

			return $query->where([$alias . '.' . $cc => $cid]);
		}

		if ($scope === 'own') {
			$uid = isset($user['id']) ? (int) $user['id'] : 0;
			if ($uid < 1) {
				return $query->where('1=0');
			}
			$uc = isset($tableMap['user_id_column']) ? $tableMap['user_id_column'] : 'id';

			return $query->where([$alias . '.' . $uc => $uid]);
		}

		return $query;
	}
}
