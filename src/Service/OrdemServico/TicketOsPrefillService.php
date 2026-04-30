<?php
namespace App\Service\OrdemServico;

/**
 * Pré-preenchimento operacional de OS a partir de ticket (sem defaults fiscais/faturamento).
 */
class TicketOsPrefillService {

	/**
	 * Rótulo de situação do ticket (legado + estados estendidos).
	 */
	public static function situacaoTicketLabel(int $situacao): string {
		if (\defined('C_TicketSituacoesFuncionario') && isset(\C_TicketSituacoesFuncionario[$situacao])) {
			return (string)\C_TicketSituacoesFuncionario[$situacao];
		}

		return 'Situação ' . $situacao;
	}

	public static function formatMinutosRegistrados(int $minutos): string {
		$minutos = max(0, $minutos);
		$h = intdiv($minutos, 60);
		$m = $minutos % 60;

		return sprintf('%d:%02d', $h, $m);
	}

	/**
	 * Mapeia prioridade do ticket para chave de C_OrdensPrioridade (0–3).
	 */
	public static function mapPrioridadeTicketParaOs($prioridadeTicket): int {
		if ($prioridadeTicket === null || $prioridadeTicket === '') {
			return \defined('C_OrdensPrioridadeNormal') ? (int)\C_OrdensPrioridadeNormal : 1;
		}
		if (is_numeric($prioridadeTicket)) {
			$p = (int)$prioridadeTicket;
			if (\defined('C_OrdensPrioridade') && isset(\C_OrdensPrioridade[$p])) {
				return $p;
			}
		}
		$s = mb_strtolower(trim((string)$prioridadeTicket));
		$map = [
			'baixa' => 0,
			'normal' => 1,
			'alta' => 2,
			'urgente' => 3,
			'urgência' => 3,
			'urgencia' => 3,
		];
		if (isset($map[$s])) {
			return $map[$s];
		}

		return \defined('C_OrdensPrioridadeNormal') ? (int)\C_OrdensPrioridadeNormal : 1;
	}

	public static function trunc(string $text, int $maxLen): string {
		$t = trim($text);
		if ($t === '') {
			return '';
		}
		if (mb_strlen($t) <= $maxLen) {
			return $t;
		}

		return mb_substr($t, 0, max(1, $maxLen - 1)) . '…';
	}

	/**
	 * Nome de exibição do cliente (PF/PJ) para painel somente leitura.
	 *
	 * @param object|null $cliente
	 */
	public static function clienteDisplayName($cliente): string {
		if ($cliente === null) {
			return '';
		}
		$tipo = (int)($cliente->tipo ?? 0);
		if (\defined('C_ClientesTipoJuridica') && $tipo === (int)\C_ClientesTipoJuridica) {
			return trim((string)($cliente->razaosocial ?? ''));
		}

		return trim((string)($cliente->nome ?? ''));
	}

	/**
	 * Texto de laudo a partir do último relatório técnico (causa + conclusão).
	 *
	 * @param object|null $rep
	 */
	public static function laudoFromTechnicalReport($rep): string {
		if ($rep === null) {
			return '';
		}

		return trim(trim((string)($rep->causa_provavel ?? '')) . ' ' . trim((string)($rep->conclusao_tecnica ?? '')));
	}

	/**
	 * Observação interna operacional: relatório de atendimento + laudo (sem comentários do ticket).
	 *
	 * @param object|null $technicalReport
	 */
	public static function buildOperationalObservacao(?string $descricaoAtendimento, $technicalReport, int $maxLen = 200): string {
		$parts = [];
		$da = trim((string)$descricaoAtendimento);
		if ($da !== '') {
			$parts[] = 'Relatório de atendimento: ' . $da;
		}
		$laudo = self::laudoFromTechnicalReport($technicalReport);
		if ($laudo !== '') {
			$parts[] = 'Laudo técnico: ' . $laudo;
		}
		if ($parts === []) {
			return '';
		}

		return self::trunc(implode(' | ', $parts), $maxLen);
	}

	/**
	 * Rótulos de ativos vinculados ao ticket (somente leitura no painel).
	 *
	 * @param iterable $ticketAssetRows entidades TicketAsset com Asset carregado
	 * @return string[]
	 */
	public static function labelsAtivosTicket(iterable $ticketAssetRows): array {
		$out = [];
		foreach ($ticketAssetRows as $row) {
			$asset = $row->asset ?? null;
			if ($asset === null) {
				continue;
			}
			$bits = array_filter([
				trim((string)($asset->patrimonio ?? '')),
				trim((string)($asset->identificador ?? '')),
				trim((string)($asset->descricao ?? '')),
			]);
			$label = $bits !== [] ? implode(' — ', $bits) : ('Ativo #' . (int)($asset->id ?? 0));
			if ($label !== '' && $label !== 'Ativo #0') {
				$out[] = $label;
			}
		}

		return $out;
	}
}
