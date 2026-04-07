<?php
namespace App\Utility;

/**
 * Avaliação mínima de conditions_json para rbac_permission_policies (Fase 5 — base).
 * Formato suportado (extensível):
 *   { "all": [ { "path": "user.role", "eq": 0 }, { "path": "user.admin", "eq": true } ] }
 *   { "all": [ { "path": "request.prefix", "in": ["admin", ""] } ] }
 * path aponta para chaves em $context (mapa plano com pontos na chave).
 *
 * JSON vazio ou inválido: em modo estrito retorna false; se null/'' trata-se como «sem política» → true em matchesOrEmpty.
 */
class RbacPolicyConditions {

	/**
	 * Sem política (null ou só espaços) = coincide (não restringe).
	 */
	public static function matchesOrEmpty($conditionsJson, array $context): bool {
		if ($conditionsJson === null) {
			return true;
		}
		$s = trim((string)$conditionsJson);

		return $s === '' ? true : self::matches($s, $context);
	}

	public static function matches(string $conditionsJson, array $context): bool {
		$data = json_decode($conditionsJson, true);
		if (!is_array($data)) {
			return false;
		}
		if (!isset($data['all']) || !is_array($data['all'])) {
			return false;
		}
		foreach ($data['all'] as $rule) {
			if (!is_array($rule) || empty($rule['path'])) {
				return false;
			}
			$path = (string)$rule['path'];
			$actual = self::_contextGet($context, $path);
			if (array_key_exists('eq', $rule)) {
				if (!self::_looseEq($actual, $rule['eq'])) {
					return false;
				}
			} elseif (array_key_exists('in', $rule)) {
				$list = $rule['in'];
				if (!is_array($list) || !self::_inList($actual, $list)) {
					return false;
				}
			} else {
				return false;
			}
		}

		return true;
	}

	protected static function _contextGet(array $context, string $path) {
		return array_key_exists($path, $context) ? $context[$path] : null;
	}

	protected static function _looseEq($a, $b): bool {
		if ($a === $b) {
			return true;
		}
		if (is_numeric($a) && is_numeric($b)) {
			return (string)$a === (string)$b || (float)$a === (float)$b;
		}
		if (is_bool($a) || is_bool($b)) {
			return (bool)$a === (bool)$b;
		}

		return (string)$a === (string)$b;
	}

	protected static function _inList($actual, array $list): bool {
		foreach ($list as $item) {
			if (self::_looseEq($actual, $item)) {
				return true;
			}
		}

		return false;
	}
}
