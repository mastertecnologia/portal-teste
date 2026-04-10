<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;

/**
 * Regimes da NF-e (CRT) e referências PIS/COFINS quando não há alíquota explícita em fiscal_aliquotas.
 *
 * - regime_tributario 1 e 2: Simples Nacional / excesso de sublimite (CRT 1 ou 2 no XML).
 * - regime_tributario 3: Regime Normal — CRT permanece 3; Lucro Presumido vs Lucro Real afeta apenas
 *   as alíquotas-padrão de PIS/COFINS sobre receita (CST típicos cumulativos vs não cumulativos).
 * Contador deve validar CST/base em cada operação.
 */
class FiscalRegimeHelper {

    public const ENQUADRAMENTO_PRESUMIDO = 1;
    public const ENQUADRAMENTO_REAL = 2;

    /**
     * @param array $configEmpresa Linha fiscal_empresas_config como array
     * @return array{pis: float, cofins: float}
     */
    public static function pisCofinsAliquotasPadraoReceita(array $configEmpresa): array {
        $rt = (int)($configEmpresa['regime_tributario'] ?? 3);
        if ($rt === 3) {
            $enq = (int)($configEmpresa['regime_normal_enquadramento'] ?? self::ENQUADRAMENTO_REAL);
            if ($enq === self::ENQUADRAMENTO_PRESUMIDO) {
                return ['pis' => 0.65, 'cofins' => 3.00];
            }

            return ['pis' => 1.65, 'cofins' => 7.60];
        }

        return ['pis' => 0.65, 'cofins' => 3.00];
    }

    /**
     * Flag de estudo para IBS/CBS (LC 214/2025) — não altera XML até NT/Convênio vigente.
     */
    public static function reformaTributariaEstudoIbscbsAtivo(): bool {
        $v = Configure::read('Fiscal.reforma_tributaria.habilitar_estudo_ibscbs');

        return $v === true || $v === 1 || $v === '1';
    }
}
