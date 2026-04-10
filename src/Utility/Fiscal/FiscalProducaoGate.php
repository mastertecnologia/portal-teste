<?php
namespace App\Utility\Fiscal;

/**
 * Regras puras: quando o ambiente SEFAZ é produção e se o POST traz confirmação explícita.
 */
final class FiscalProducaoGate {

    /**
     * Produção se ambiente global OU da empresa for 1 (SEFAZ produção).
     */
    public static function ambienteEhProducao(int $globalAmbiente, int $empresaAmbiente): bool {
        return $globalAmbiente === 1 || $empresaAmbiente === 1;
    }

    /**
     * Checkbox "confirmar_producao" enviado no pedido.
     *
     * @param array<string,mixed>|null $requestData Dados POST (ex.: $request->getData())
     */
    public static function confirmacaoProducaoMarcada(?array $requestData): bool {
        if ($requestData === null) {
            return false;
        }

        return !empty($requestData['confirmar_producao']);
    }
}
