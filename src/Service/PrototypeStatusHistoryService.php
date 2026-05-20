<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Grava transições de status dos protótipos (Orçamento, OS, Ticket, RBAC).
 * Falha silenciosa: se a tabela não existir ou der erro, só loga.
 */
class PrototypeStatusHistoryService {

	/**
	 * @param string $sourceType orcamento|os|ticket|rbac|fatura
	 * @param int    $sourceId
	 * @param string|int|null $from
	 * @param string|int      $to
	 * @param array<string,mixed> $user (do AuthComponent)
	 * @param string $note
	 * @param int|null $idempresa
	 */
	public function record(string $sourceType, int $sourceId, $from, $to, array $user = [], string $note = '', ?int $idempresa = null): void {
		if ($sourceId <= 0) {
			return;
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
			$entity = $tbl->newEntity([
				'idempresa' => $idempresa,
				'source_type' => $sourceType,
				'source_id' => $sourceId,
				'status_from' => $from !== null && $from !== '' ? (string)$from : null,
				'status_to' => (string)$to,
				'actor_user_id' => (int)($user['id'] ?? 0) ?: null,
				'actor_name' => trim((string)($user['name'] ?? $user['username'] ?? '')) ?: null,
				'actor_ip' => null,
				'note' => $note !== '' ? $note : null,
				'created' => date('Y-m-d H:i:s'),
			]);
			$tbl->save($entity);
		} catch (\Throwable $e) {
			Log::warning('PrototypeStatusHistory: ' . $e->getMessage());
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function fetch(string $sourceType, int $sourceId, int $limit = 30): array {
		if ($sourceId <= 0) {
			return [];
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
			$rows = $tbl->find()
				->where(['source_type' => $sourceType, 'source_id' => $sourceId])
				->order(['created' => 'DESC'])
				->limit($limit)
				->all();
			$out = [];
			foreach ($rows as $r) {
				$out[] = [
					'id' => (int)$r->get('id'),
					'from' => (string)($r->get('status_from') ?? ''),
					'to' => (string)$r->get('status_to'),
					'user_id' => (int)($r->get('actor_user_id') ?? 0),
					'user_name' => (string)($r->get('actor_name') ?? ''),
					'note' => (string)($r->get('note') ?? ''),
					'created' => $r->get('created'),
				];
			}

			return $out;
		} catch (\Throwable $e) {
			return [];
		}
	}
}
