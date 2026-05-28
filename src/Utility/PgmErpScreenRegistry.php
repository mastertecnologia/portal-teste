<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Registry canônico das telas pg-* (mock pgm_erp_completo.html).
 */
class PgmErpScreenRegistry {

	/** @var array<string,mixed>|null */
	private static $cache;

	/**
	 * @return array<string,mixed>
	 */
	public static function load(): array {
		if (self::$cache !== null) {
			return self::$cache;
		}
		$path = dirname(__DIR__, 2) . '/config/pgm_erp_screens.json';
		if (!is_file($path)) {
			self::$cache = ['screens' => [], 'grid_api_endpoints' => []];

			return self::$cache;
		}
		$raw = file_get_contents($path);
		$data = is_string($raw) ? json_decode($raw, true) : null;
		self::$cache = is_array($data) ? $data : ['screens' => [], 'grid_api_endpoints' => []];

		return self::$cache;
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function screensById(): array {
		$out = [];
		foreach (self::load()['screens'] ?? [] as $row) {
			if (!empty($row['id'])) {
				$out[(string)$row['id']] = $row;
			}
		}

		return $out;
	}

	public static function find(string $pgId): ?array {
		$map = self::screensById();

		return $map[$pgId] ?? null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function byModule(string $module): array {
		$module = strtolower(trim($module));
		$rows = [];
		foreach (self::load()['screens'] ?? [] as $row) {
			if (strtolower((string)($row['module'] ?? '')) === $module) {
				$rows[] = $row;
			}
		}

		return $rows;
	}
}
