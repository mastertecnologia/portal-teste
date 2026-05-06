<?php
declare(strict_types=1);

namespace App\Utility\Ticket;

/**
 * Normalização para KPI operacional quando tickets.prioridade mistura P1–P4 com rótulos semânticos legados.
 */
class TicketPriorityKpi {

	public const RANK_ORDER = ['P1' => 1, 'P2' => 2, 'P3' => 3, 'P4' => 4];

	/**
	 * @return string|null P1|P2|P3|P4 quando reconhecível, senão null
	 */
	public static function mapToPxBucket(?string $prioridade): ?string {
		$p = self::normalized((string)$prioridade);
		if ($p === '') {
			return null;
		}
		if ($p === 'p1' || $p === '1') {
			return 'P1';
		}
		if ($p === 'p2' || $p === '2') {
			return 'P2';
		}
		if ($p === 'p3' || $p === '3') {
			return 'P3';
		}
		if ($p === 'p4' || $p === '4') {
			return 'P4';
		}
		switch ($p) {
			case 'critica':
			case 'critico':
				return 'P1';
			case 'alta':
				return 'P2';
			case 'media':
			case 'medio':
				return 'P3';
			case 'baixa':
			case 'baixo':
				return 'P4';
		}

		return null;
	}

	/**
	 * Condições OR para usar em Finder (tickets com prioridade contando como P1 nos KPI).
	 *
	 * @return array<string,mixed>[]
	 */
	public static function p1MatchOrConditions(string $prioridadeAlias = 'Tickets.prioridade'): array {
		$variants = [
			'P1',
			'p1',
			'critica',
			'Critica',
			'CRÍTICA',
			'crítica',
			'critico',
			'Critico',
		];
		$normalized = [];
		foreach ($variants as $v) {
			$normalized[] = [$prioridadeAlias => $v];
		}

		return ['OR' => $normalized];
	}

	protected static function normalized(string $s): string {
		$s = trim(mb_strtolower($s, 'UTF-8'));

		return str_replace(
			['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
			['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
			$s
		);
	}
}
