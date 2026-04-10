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
     * Regime normal (CRT 3) com Lucro presumido ou Lucro real definidos na configuração.
     */
    public static function empresaRegimeNormalProntaParaNfe(array $configEmpresa): bool {
        $rt = (int)($configEmpresa['regime_tributario'] ?? 0);
        if ($rt !== 3) {
            return true;
        }
        $enq = $configEmpresa['regime_normal_enquadramento'] ?? null;

        return in_array((int)$enq, [self::ENQUADRAMENTO_PRESUMIDO, self::ENQUADRAMENTO_REAL], true);
    }

    /**
     * Variáveis para o elemento Template/Element/Fiscal/regime_context.ctp.
     *
     * @return array{fiscalRegimeLabel: string, fiscalConfigRegimeIncomplete: bool, fiscalRegimeNormalEnquadLabel: string|null}
     */
    public static function viewContextFromEmpresaConfig(array $configEmpresa): array {
        $regime = (int)($configEmpresa['regime_tributario'] ?? 3);
        $regimesMap = Configure::read('Fiscal.regimes') ?: [];
        $fiscalRegimeLabel = $regimesMap[$regime] ?? ('Regime #' . $regime);
        $enqRaw = $configEmpresa['regime_normal_enquadramento'] ?? null;
        $fiscalConfigRegimeIncomplete = ($regime === 3 && !self::empresaRegimeNormalProntaParaNfe($configEmpresa));
        $enqMap = Configure::read('Fiscal.regime_normal_enquadramento') ?: [];
        $fiscalRegimeNormalEnquadLabel = null;
        if ($regime === 3 && !$fiscalConfigRegimeIncomplete) {
            $fiscalRegimeNormalEnquadLabel = $enqMap[(int)$enqRaw] ?? null;
        }

        return compact(
            'fiscalRegimeLabel',
            'fiscalConfigRegimeIncomplete',
            'fiscalRegimeNormalEnquadLabel'
        );
    }

    /**
     * Texto do checklist de homologação (painel fiscal) quando CRT 3 sem enquadramento.
     */
    public static function mensagemChecklistHomologacaoRegimeNormalIncompleto(): string {
        return 'Regime Normal (CRT 3) sem enquadramento Lucro presumido / Lucro real — preencha em Configuração fiscal para PIS/COFINS de referência corretos.';
    }

    /**
     * Mensagem de validação ao emitir NF-e/NFC-e com regime normal incompleto.
     */
    public static function mensagemBloqueioEmissaoRegimeNormalIncompleto(): string {
        return 'Regime Normal (CRT 3): defina Lucro presumido ou Lucro real em Configuração fiscal antes de emitir NF-e/NFC-e.';
    }

    /**
     * Flag de estudo para IBS/CBS (LC 214/2025) — não altera XML até NT/Convênio vigente.
     */
    public static function reformaTributariaEstudoIbscbsAtivo(): bool {
        $v = Configure::read('Fiscal.reforma_tributaria.habilitar_estudo_ibscbs');

        return $v === true || $v === 1 || $v === '1';
    }
}
