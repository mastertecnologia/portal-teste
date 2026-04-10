<?php
namespace App\Utility\Fiscal;

/**
 * Recalcula totais da nota e valor_total dos itens (mesma lógica que FiscalNotasController::recalcularNota).
 */
class FiscalNotaRecalculo {

    /**
     * @param \App\Model\Entity\FiscalNota|\Cake\Datasource\EntityInterface $nota
     * @param \App\Model\Table\FiscalEmpresasConfigTable $fiscalEmpresasConfigTable
     * @param \App\Model\Table\FiscalAliquotasTable $fiscalAliquotasTable
     * @return \App\Model\Entity\FiscalNota|\Cake\Datasource\EntityInterface
     */
    public static function aplicar($nota, int $idempresa, $fiscalEmpresasConfigTable, $fiscalAliquotasTable) {
        $configFiscal = $fiscalEmpresasConfigTable->getOrCreate($idempresa);
        $calculator = new FiscalCalculator($configFiscal->toArray());

        $itens = $nota->fiscal_notas_itens ?? [];
        $itensCalculados = [];

        foreach ($itens as &$item) {
            $itemArr = is_array($item) ? $item : $item->toArray();
            $itemArr['valor_total'] = (float)($itemArr['quantidade'] ?? 0) * (float)($itemArr['valor_unitario'] ?? 0);

            $aliquota = $fiscalAliquotasTable->getAliquota(
                $idempresa,
                $configFiscal->uf ?? 'SP',
                $configFiscal->uf ?? 'SP',
                $itemArr['ncm'] ?? null
            );
            $aliqArr = $aliquota ? $aliquota->toArray() : [];
            $impostos = $calculator->calcularImpostosItem($itemArr, $aliqArr);
            $itemArr['impostos'] = $impostos;
            $itensCalculados[] = $itemArr;

            if (!is_array($item)) {
                $item->valor_total = $itemArr['valor_total'];
            }
        }
        unset($item);

        $totais = $calculator->calcularTotaisNota($itensCalculados);
        foreach ($totais as $k => $v) {
            $nota->{$k} = $v;
        }

        return $nota;
    }
}
