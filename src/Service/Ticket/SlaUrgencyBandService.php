<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Banda de urgência SLA para listagem técnica (uma única fonte da verdade no backend).
 */
class SlaUrgencyBandService {

	public const BAND_PAUSED = 'paused';
	public const BAND_VIOLATED = 'violated';
	public const BAND_CRITICAL = 'critical';
	public const BAND_ATTENTION = 'attention';
	public const BAND_OK = 'ok';
	public const BAND_UNKNOWN = 'unknown';

	/** Minutos até o limite para considerar "muito próximo" além do <10% restante. */
	protected const NEAR_DEADLINE_MINUTES = 15;

	/**
	 * Fragmento JSON-safe para a linha da grid (api-index / _ticketRowApiTecnico).
	 *
	 * @param string[] $cols Colunas reais do schema tickets
	 * @return array<string, mixed>
	 */
	public function buildListFragment(EntityInterface $ticket, array $cols): array {
		$out = [
			'sla_status' => null,
			'sla_percentual_consumido' => null,
			'sla_resolucao_pausado' => null,
			'data_limite_resolucao_iso' => null,
			'sla_remaining_minutes' => null,
			'sla_urgency_band' => self::BAND_UNKNOWN,
			'sla_tooltip' => '',
		];
		if (!in_array('sla_status', $cols, true)) {
			return $out;
		}

		$paused = $this->isResolutionPaused($ticket, $cols);
		$out['sla_resolucao_pausado'] = in_array('sla_resolucao_pausado', $cols, true)
			? (bool)$ticket->get('sla_resolucao_pausado')
			: null;

		$deadline = null;
		if (in_array('data_limite_resolucao', $cols, true)) {
			$deadline = $this->toTime($ticket->get('data_limite_resolucao'));
			if ($deadline !== null) {
				$out['data_limite_resolucao_iso'] = $deadline->format('c');
			}
		}

		$now = Time::now();
		$remainingMin = null;
		if ($deadline !== null) {
			$remainingMin = (int)floor(($deadline->getTimestamp() - $now->getTimestamp()) / 60);
			$out['sla_remaining_minutes'] = $remainingMin;
		}

		if ($paused) {
			$out['sla_urgency_band'] = self::BAND_PAUSED;
			$out['sla_status'] = $ticket->get('sla_status');
			$pctDb = in_array('sla_percentual_consumido', $cols, true) ? $ticket->get('sla_percentual_consumido') : null;
			$out['sla_percentual_consumido'] = $pctDb !== null && $pctDb !== '' ? round((float)$pctDb, 2) : null;
			$out['sla_tooltip'] = $this->tooltipPaused($out['sla_percentual_consumido']);

			return $out;
		}

		$recalc = new SlaRecalculationService($this->ticketsTableFromEntity($ticket));
		$state = $recalc->evaluateSlaState($ticket, $cols);
		$pct = (float)($state['pct'] ?? 0.0);
		$viol = !empty($state['violado']);
		$dbStatus = (string)($ticket->get('sla_status') ?? '');

		$out['sla_status'] = $viol ? 'violado' : (string)($state['status'] ?? $dbStatus);
		$out['sla_percentual_consumido'] = round($pct, 2);

		if ($viol || $dbStatus === 'violado') {
			$out['sla_urgency_band'] = self::BAND_VIOLATED;
			$out['sla_tooltip'] = $this->tooltipViolated($pct, $deadline);

			return $out;
		}

		$resM = in_array('sla_resolucao_minutos', $cols, true) ? (int)($ticket->get('sla_resolucao_minutos') ?? 0) : 0;
		$remainingPct = null;
		if ($remainingMin !== null && $resM > 0) {
			$remainingPct = ($remainingMin / $resM) * 100.0;
		} elseif ($remainingMin !== null && $pct <= 100.0001) {
			$remainingPct = max(0.0, 100.0 - $pct);
		}

		$criticalByConsume = $pct >= 90.0001;
		$criticalByRemainingPct = $remainingPct !== null && $remainingPct < 10.0 && $remainingPct >= 0;
		$criticalByWallClock = $remainingMin !== null && $remainingMin >= 0 && $remainingMin <= self::NEAR_DEADLINE_MINUTES;

		if ($criticalByConsume || $criticalByRemainingPct || $criticalByWallClock) {
			$out['sla_urgency_band'] = self::BAND_CRITICAL;
			$out['sla_tooltip'] = $this->tooltipCritical($pct, $remainingMin, $deadline);

			return $out;
		}

		// Usar status avaliado (recalc), não só a coluna persistida — pode estar defasada.
		if ((string)$out['sla_status'] === 'em_risco' || $pct >= 80.0) {
			$out['sla_urgency_band'] = self::BAND_ATTENTION;
			$out['sla_tooltip'] = $this->tooltipAttention($pct, $remainingMin, $deadline);

			return $out;
		}

		if ($deadline !== null || $resM > 0) {
			$out['sla_urgency_band'] = self::BAND_OK;
			$out['sla_tooltip'] = $this->tooltipOk($pct, $remainingMin, $deadline);

			return $out;
		}

		$out['sla_urgency_band'] = self::BAND_UNKNOWN;
		$out['sla_tooltip'] = 'SLA: dados insuficientes para calcular urgência.';

		return $out;
	}

	protected function isResolutionPaused(EntityInterface $ticket, array $cols): bool {
		$r = in_array('sla_resolucao_pausado', $cols, true) && (bool)$ticket->get('sla_resolucao_pausado');
		$a = in_array('sla_resposta_pausado', $cols, true) && (bool)$ticket->get('sla_resposta_pausado');

		return $r || $a;
	}

	/**
	 * @param mixed $v
	 */
	protected function toTime($v): ?Time {
		if ($v === null || $v === '') {
			return null;
		}
		if ($v instanceof Time) {
			return $v;
		}
		if ($v instanceof \DateTimeInterface) {
			return Time::createFromInterface($v);
		}
		try {
			return new Time($v);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function ticketsTableFromEntity(EntityInterface $ticket): \Cake\ORM\Table {
		$alias = $ticket->getSource();
		if (is_string($alias) && $alias !== '') {
			try {
				return TableRegistry::get($alias);
			} catch (\Throwable $e) {
			}
		}

		return TableRegistry::get('Tickets');
	}

	protected function tooltipPaused(?float $pct): string {
		$s = 'SLA pausado';
		if ($pct !== null) {
			$s .= sprintf(' (último consumo registrado: %s%%).', (string)$pct);
		} else {
			$s .= '.';
		}
		$s .= ' O contador de resolução não avança enquanto aguarda retorno do cliente.';

		return $s;
	}

	protected function tooltipViolated(float $pct, ?Time $deadline): string {
		$s = sprintf('SLA violado: %.1f%% do prazo consumido.', $pct);
		if ($deadline !== null) {
			$s .= ' Limite: ' . $deadline->i18nFormat("dd/MM/yyyy HH:mm");
		}

		return $s;
	}

	protected function tooltipCritical(float $pct, ?int $remainingMin, ?Time $deadline): string {
		$s = sprintf('SLA crítico: %.1f%% consumido.', $pct);
		if ($remainingMin !== null) {
			if ($remainingMin < 0) {
				$s .= ' Prazo ultrapassado.';
			} else {
				$s .= sprintf(' Restam ~%d min.', $remainingMin);
			}
		}
		if ($deadline !== null) {
			$s .= ' Limite: ' . $deadline->i18nFormat("dd/MM/yyyy HH:mm");
		}

		return $s;
	}

	protected function tooltipAttention(float $pct, ?int $remainingMin, ?Time $deadline): string {
		$s = sprintf('SLA em risco: %.1f%% consumido.', $pct);
		if ($remainingMin !== null && $remainingMin >= 0) {
			$s .= sprintf(' Restam ~%d min.', $remainingMin);
		}
		if ($deadline !== null) {
			$s .= ' Limite: ' . $deadline->i18nFormat("dd/MM/yyyy HH:mm");
		}

		return $s;
	}

	protected function tooltipOk(float $pct, ?int $remainingMin, ?Time $deadline): string {
		$s = sprintf('SLA dentro do esperado (%.1f%% consumido).', $pct);
		if ($remainingMin !== null && $remainingMin >= 0) {
			$s .= sprintf(' Restam ~%d min.', $remainingMin);
		}
		if ($deadline !== null) {
			$s .= ' Limite: ' . $deadline->i18nFormat("dd/MM/yyyy HH:mm");
		}

		return $s;
	}
}
