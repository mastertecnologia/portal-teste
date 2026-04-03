<?php
namespace App\Service\Fiscal;

/**
 * Preenche colunas de tributos aproximados (Lei 12.741) a partir do breakdown IBPT já calculado.
 */
class ModuloIbptEscrita implements ModuloFiscalInterface {

	public function getId() {
		return 'ibpt_transparencia';
	}

	public function contribuir(array $context) {
		$b = isset($context['ibpt_breakdown']) ? $context['ibpt_breakdown'] : null;
		if (!is_array($b) || empty($b['totais'])) {
			return [];
		}
		$t = $b['totais'];

		return [
			'valor_trib_aprox_federal' => (float) ($t['nacional_valor'] ?? 0),
			'valor_trib_aprox_estadual' => (float) ($t['estadual_valor'] ?? 0),
			'valor_trib_aprox_municipal' => (float) ($t['municipal_valor'] ?? 0),
			'valor_trib_aprox_total' => (float) ($t['geral_valor'] ?? 0),
			'fonte_ibpt_versao' => isset($b['versao']) ? (string) $b['versao'] : null,
			'fonte_calculo' => 'IBPT_LEI12741',
		];
	}
}
