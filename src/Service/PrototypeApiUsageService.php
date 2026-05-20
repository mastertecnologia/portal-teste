<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;

/**
 * Contadores em cache dos endpoints AJAX dos protótipos (janela rolante ~7 dias).
 */
class PrototypeApiUsageService {

	private const CACHE_KEY = 'pgm_prototype_api_stats_v1';

	/**
	 * Registra uma chamada (controller.action ou rótulo explícito).
	 */
	public function hit(string $endpoint): void {
		$endpoint = trim($endpoint);
		if ($endpoint === '') {
			return;
		}
		try {
			$data = Cache::read(self::CACHE_KEY, 'default');
			if (!is_array($data)) {
				$data = [];
			}
			$data[$endpoint] = (int)($data[$endpoint] ?? 0) + 1;
			$data['_updated'] = time();
			Cache::write(self::CACHE_KEY, $data, '+7 days');
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @return array<int,array{endpoint:string,count:int}>
	 */
	public function top(int $limit = 25): array {
		try {
			$data = Cache::read(self::CACHE_KEY, 'default');
		} catch (\Throwable $e) {
			return [];
		}
		if (!is_array($data)) {
			return [];
		}
		unset($data['_updated']);
		arsort($data, SORT_NUMERIC);
		$out = [];
		foreach (array_slice($data, 0, max(1, $limit), true) as $ep => $count) {
			$out[] = ['endpoint' => (string)$ep, 'count' => (int)$count];
		}

		return $out;
	}
}
