<?php
namespace App\Service\Fiscal;

use Cake\ORM\TableRegistry;

/**
 * Persiste snapshot de escrita fiscal (tabela faturas_escrita_fiscal) a partir da locação + módulos.
 */
class EscritaFiscalService {

	/**
	 * @param object $fatura Entidade com id, valor, desconto, outrosgastos já normalizados como no fluxo de impressão.
	 * @param object $empresa
	 * @param array $carrinho Faturasitens
	 * @param int $idempresa
	 * @param array|null $ibptBreakdown
	 * @return \App\Model\Entity\FaturasEscritaFiscal|null
	 */
	public function upsertSnapshot($fatura, $empresa, array $carrinho, $idempresa, $ibptBreakdown = null) {
		if (empty($fatura->id)) {
			return null;
		}
		$Table = TableRegistry::get('FaturasEscritaFiscal');
		$row = $Table->find()->where(['idfatura' => (int) $fatura->id, 'idempresa' => (int) $idempresa])->first();
		if (!$row) {
			$row = $Table->newEntity();
			$row->idfatura = (int) $fatura->id;
			$row->idempresa = (int) $idempresa;
		}

		$somaItens = 0.0;
		foreach ($carrinho as $it) {
			$somaItens += (float) $it->valortotal;
		}

		$row->valor_produtos = round($somaItens, 2);
		$row->valor_total_nota = round((float) $fatura->valor, 2);
		$row->valor_desconto = round((float) ($fatura->desconto ?? 0), 2);
		$row->valor_servicos = 0;
		$row->valor_frete = round((float) ($fatura->outrosgastos ?? 0), 2);

		$orch = new OrquestradorFiscal();
		$context = [
			'fatura' => $fatura,
			'empresa' => $empresa,
			'carrinho' => $carrinho,
			'idempresa' => $idempresa,
			'ibpt_breakdown' => $ibptBreakdown,
		];
		$patch = $orch->compor($context);
		$cols = $Table->schema()->columns();
		foreach ($patch as $field => $val) {
			if (in_array($field, $cols, true)) {
				$row->set($field, $val);
			}
		}

		$row->payload_modulos = json_encode(['modulos' => $orch->getModuloIds(), 'gerado_em' => date('c')], JSON_UNESCAPED_UNICODE);

		if ($Table->save($row) === false) {
			return null;
		}

		return $row;
	}
}
