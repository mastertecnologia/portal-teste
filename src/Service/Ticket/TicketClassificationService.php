<?php
namespace App\Service\Ticket;

/**
 * Classificação por regras (matriz impacto × urgência). Preparado para providers externos (IA) no futuro.
 */
class TicketClassificationService {

	public const PRIORIDADE_P1 = 'P1';
	public const PRIORIDADE_P2 = 'P2';
	public const PRIORIDADE_P3 = 'P3';
	public const PRIORIDADE_P4 = 'P4';

	/**
	 * @param string|null $impacto baixo|medio|alto|critico
	 * @param string|null $urgencia baixa|media|alta|imediata
	 */
	public static function prioridadeFromImpactoUrgencia(?string $impacto, ?string $urgencia): string {
		$i = self::normalizeImpacto($impacto);
		$u = self::normalizeUrgencia($urgencia);

		if ($i === 'critico' && $u === 'imediata') {
			return self::PRIORIDADE_P1;
		}
		if ($i === 'alto' && $u === 'alta') {
			return self::PRIORIDADE_P2;
		}
		if ($i === 'medio' && $u === 'media') {
			return self::PRIORIDADE_P3;
		}
		if ($i === 'baixo' && $u === 'baixa') {
			return self::PRIORIDADE_P4;
		}

		$score = self::scoreImpacto($i) + self::scoreUrgencia($u);
		if ($score >= 7) {
			return self::PRIORIDADE_P1;
		}
		if ($score >= 5) {
			return self::PRIORIDADE_P2;
		}
		if ($score >= 3) {
			return self::PRIORIDADE_P3;
		}

		return self::PRIORIDADE_P4;
	}

	/**
	 * Deriva impacto/urgência a partir da severidade legada do formulário (baixa|media|alta|urgente).
	 *
	 * @return array{0:string,1:string} [impacto, urgencia]
	 */
	public static function impactoUrgenciaFromSeveridade(?string $severidade): array {
		$s = strtolower(trim((string)$severidade));
		switch ($s) {
			case 'baixa':
				return ['baixo', 'baixa'];
			case 'alta':
				return ['alto', 'alta'];
			case 'urgente':
				return ['critico', 'imediata'];
			case 'media':
			default:
				return ['medio', 'media'];
		}
	}

	protected static function normalizeImpacto(?string $v): string {
		$v = strtolower(trim((string)$v));
		if (in_array($v, ['baixo', 'medio', 'alto', 'critico'], true)) {
			return $v;
		}

		return 'medio';
	}

	protected static function normalizeUrgencia(?string $v): string {
		$v = strtolower(trim((string)$v));
		if (in_array($v, ['baixa', 'media', 'alta', 'imediata'], true)) {
			return $v;
		}

		return 'media';
	}

	protected static function scoreImpacto(string $i): int {
		$map = ['baixo' => 1, 'medio' => 2, 'alto' => 3, 'critico' => 4];

		return $map[$i] ?? 2;
	}

	protected static function scoreUrgencia(string $u): int {
		$map = ['baixa' => 1, 'media' => 2, 'alta' => 3, 'imediata' => 4];

		return $map[$u] ?? 2;
	}
}
