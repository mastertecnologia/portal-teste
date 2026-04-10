<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;

/**
 * Motor de cálculo tributário.
 *
 * Calcula ICMS, ICMS-ST, IPI, PIS, COFINS, ISS, IRRF, CSLL
 * respeitando o regime tributário da empresa (Simples Nacional, Lucro Presumido, Lucro Real).
 */
class FiscalCalculator {

    /** @var array Configuração fiscal da empresa (FiscalEmpresasConfig entity) */
    private $config;

    /** @var array Alíquotas carregadas do banco */
    private $aliquotas;

    public function __construct(array $config, array $aliquotas = []) {
        $this->config = $config;
        $this->aliquotas = $aliquotas;
    }

    /**
     * Calcula todos os impostos de um item da nota.
     *
     * @param array $item Dados do item (valor_total, ncm, cfop, cst_*, etc.)
     * @param array $aliquota Alíquotas aplicáveis (entity FiscalAliquota)
     * @return array Lista de impostos calculados
     */
    public function calcularImpostosItem(array $item, array $aliquota = []) {
        $impostos = [];
        $valorBase = (float)($item['valor_total'] ?? 0) - (float)($item['valor_desconto'] ?? 0);
        $regime = (int)($this->config['regime_tributario'] ?? 3);

        // ICMS
        $icms = $this->calcularICMS($valorBase, $item, $aliquota, $regime);
        if ($icms) {
            $impostos[] = $icms;
        }

        // ICMS-ST
        $icmsSt = $this->calcularICMSST($valorBase, $item, $aliquota, $icms);
        if ($icmsSt) {
            $impostos[] = $icmsSt;
        }

        // IPI (não se aplica a Simples Nacional em geral)
        if ($regime === 3) {
            $ipi = $this->calcularIPI($valorBase, $item, $aliquota);
            if ($ipi) {
                $impostos[] = $ipi;
            }
        }

        // PIS
        $pis = $this->calcularPIS($valorBase, $item, $aliquota, $regime);
        if ($pis) {
            $impostos[] = $pis;
        }

        // COFINS
        $cofins = $this->calcularCOFINS($valorBase, $item, $aliquota, $regime);
        if ($cofins) {
            $impostos[] = $cofins;
        }

        // ISS (para serviços / NFSe)
        $iss = $this->calcularISS($valorBase, $item, $aliquota);
        if ($iss) {
            $impostos[] = $iss;
        }

        return $impostos;
    }

    /**
     * Calcula os totais da nota a partir dos itens.
     *
     * @param array $itens Lista de itens com impostos já calculados
     * @return array Totais consolidados
     */
    public function calcularTotaisNota(array $itens) {
        $totais = [
            'valor_produtos' => 0,
            'valor_desconto' => 0,
            'valor_frete' => 0,
            'valor_seguro' => 0,
            'valor_outras_despesas' => 0,
            'valor_icms' => 0,
            'valor_icms_st' => 0,
            'valor_ipi' => 0,
            'valor_pis' => 0,
            'valor_cofins' => 0,
            'valor_iss' => 0,
            'valor_total_impostos' => 0,
            'valor_total' => 0,
        ];

        foreach ($itens as $item) {
            $totais['valor_produtos'] += (float)($item['valor_total'] ?? 0);
            $totais['valor_desconto'] += (float)($item['valor_desconto'] ?? 0);
            $totais['valor_frete'] += (float)($item['valor_frete'] ?? 0);
            $totais['valor_seguro'] += (float)($item['valor_seguro'] ?? 0);
            $totais['valor_outras_despesas'] += (float)($item['valor_outras_despesas'] ?? 0);

            if (!empty($item['impostos'])) {
                foreach ($item['impostos'] as $imp) {
                    $tipo = $imp['imposto'] ?? '';
                    $valor = (float)($imp['valor'] ?? 0);
                    switch ($tipo) {
                        case 'ICMS': $totais['valor_icms'] += $valor; break;
                        case 'ICMS_ST': $totais['valor_icms_st'] += $valor; break;
                        case 'IPI': $totais['valor_ipi'] += $valor; break;
                        case 'PIS': $totais['valor_pis'] += $valor; break;
                        case 'COFINS': $totais['valor_cofins'] += $valor; break;
                        case 'ISS': $totais['valor_iss'] += $valor; break;
                    }
                }
            }
        }

        $totais['valor_total_impostos'] = $totais['valor_icms'] + $totais['valor_icms_st']
            + $totais['valor_ipi'] + $totais['valor_pis'] + $totais['valor_cofins'] + $totais['valor_iss'];

        $totais['valor_total'] = $totais['valor_produtos']
            - $totais['valor_desconto']
            + $totais['valor_frete']
            + $totais['valor_seguro']
            + $totais['valor_outras_despesas']
            + $totais['valor_icms_st']
            + $totais['valor_ipi'];

        return $totais;
    }

    // ── Cálculos individuais ─────────────────────────────────────────

    private function calcularICMS($valorBase, array $item, array $aliquota, $regime) {
        $cst = $item['icms_cst'] ?? null;
        if (!$cst || in_array($cst, ['40', '41', '50', '300', '400', '500'])) {
            return null;
        }
        $aliq = (float)($aliquota['icms_aliquota'] ?? 0);
        $reducao = (float)($aliquota['icms_reducao'] ?? 0);
        if ($aliq <= 0) {
            return null;
        }
        $base = $valorBase;
        if ($reducao > 0) {
            $base = $valorBase * (1 - ($reducao / 100));
        }
        return [
            'imposto' => 'ICMS',
            'base_calculo' => round($base, 2),
            'aliquota' => $aliq,
            'valor' => round($base * ($aliq / 100), 2),
            'reducao_base' => $reducao,
        ];
    }

    private function calcularICMSST($valorBase, array $item, array $aliquota, $icms) {
        $cst = $item['icms_cst'] ?? null;
        if (!in_array($cst, ['10', '30', '70', '201', '202', '203'])) {
            return null;
        }
        $mva = (float)($aliquota['icms_st_mva'] ?? 0);
        $aliqInterna = (float)($aliquota['icms_aliquota'] ?? 0);
        if ($mva <= 0 || $aliqInterna <= 0) {
            return null;
        }
        $baseST = $valorBase * (1 + ($mva / 100));
        $icmsST = ($baseST * ($aliqInterna / 100)) - (float)($icms['valor'] ?? 0);
        if ($icmsST < 0) $icmsST = 0;
        return [
            'imposto' => 'ICMS_ST',
            'base_calculo' => round($baseST, 2),
            'aliquota' => $aliqInterna,
            'valor' => round($icmsST, 2),
            'mva' => $mva,
        ];
    }

    private function calcularIPI($valorBase, array $item, array $aliquota) {
        $cst = $item['ipi_cst'] ?? null;
        if (!$cst || !in_array($cst, ['00', '50'])) {
            return null;
        }
        $aliq = (float)($aliquota['ipi_aliquota'] ?? 0);
        if ($aliq <= 0) {
            return null;
        }
        return [
            'imposto' => 'IPI',
            'base_calculo' => round($valorBase, 2),
            'aliquota' => $aliq,
            'valor' => round($valorBase * ($aliq / 100), 2),
        ];
    }

    private function calcularPIS($valorBase, array $item, array $aliquota, $regime) {
        $cst = $item['pis_cst'] ?? null;
        if (!$cst || in_array($cst, ['04', '06', '07', '08', '09'])) {
            return null;
        }
        $defs = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita($this->config);
        $aliq = (float)($aliquota['pis_aliquota'] ?? $defs['pis']);
        return [
            'imposto' => 'PIS',
            'base_calculo' => round($valorBase, 2),
            'aliquota' => $aliq,
            'valor' => round($valorBase * ($aliq / 100), 2),
        ];
    }

    private function calcularCOFINS($valorBase, array $item, array $aliquota, $regime) {
        $cst = $item['cofins_cst'] ?? null;
        if (!$cst || in_array($cst, ['04', '06', '07', '08', '09'])) {
            return null;
        }
        $defs = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita($this->config);
        $aliq = (float)($aliquota['cofins_aliquota'] ?? $defs['cofins']);
        return [
            'imposto' => 'COFINS',
            'base_calculo' => round($valorBase, 2),
            'aliquota' => $aliq,
            'valor' => round($valorBase * ($aliq / 100), 2),
        ];
    }

    private function calcularISS($valorBase, array $item, array $aliquota) {
        $aliq = (float)($aliquota['iss_aliquota'] ?? 0);
        if ($aliq <= 0) {
            return null;
        }
        return [
            'imposto' => 'ISS',
            'base_calculo' => round($valorBase, 2),
            'aliquota' => $aliq,
            'valor' => round($valorBase * ($aliq / 100), 2),
        ];
    }

    // ── Utilitários ──────────────────────────────────────────────────

    /**
     * Calcula o dígito verificador do módulo 11 (usado na chave de acesso NFe).
     */
    public static function modulo11($chave) {
        $soma = 0;
        $peso = 2;
        for ($i = strlen($chave) - 1; $i >= 0; $i--) {
            $soma += (int)$chave[$i] * $peso;
            $peso = ($peso >= 9) ? 2 : $peso + 1;
        }
        $resto = $soma % 11;
        $dv = 11 - $resto;
        if ($dv >= 10) {
            $dv = 0;
        }
        return $dv;
    }

    /**
     * Gera a chave de acesso da NFe (44 dígitos).
     */
    public static function gerarChaveAcesso($cuf, $aamm, $cnpj, $mod, $serie, $numero, $tpEmis, $codigo) {
        $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
        $chave = str_pad($cuf, 2, '0', STR_PAD_LEFT)
            . $aamm
            . str_pad($cnpjLimpo, 14, '0', STR_PAD_LEFT)
            . str_pad($mod, 2, '0', STR_PAD_LEFT)
            . str_pad($serie, 3, '0', STR_PAD_LEFT)
            . str_pad($numero, 9, '0', STR_PAD_LEFT)
            . str_pad($tpEmis, 1, '0', STR_PAD_LEFT)
            . str_pad($codigo, 8, '0', STR_PAD_LEFT);
        $dv = self::modulo11($chave);
        return $chave . $dv;
    }
}
