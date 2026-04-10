<?php
declare(strict_types=1);

namespace App\Utility\Fiscal\Nfse;

use Cake\Core\Configure;

/**
 * Resolve implementação NFS-e a partir de fiscal_empresas_config.nfse_provedor e do mapa em Configure Fiscal.nfse_emissor_map.
 * Valores desconhecidos ou vazio usam o emissor por defeito (normalmente NfseEmissorStub).
 */
class NfseEmissorResolver {

    /**
     * @param array<string, mixed> $configFiscal FiscalEmpresasConfig->toArray() ou equivalente
     */
    public static function forConfig(array $configFiscal): NfseEmissorInterface {
        $slug = strtolower(trim((string)($configFiscal['nfse_provedor'] ?? '')));
        $map = Configure::read('Fiscal.nfse_emissor_map');
        if (!is_array($map)) {
            $map = [];
        }
        $class = $map[$slug] ?? $map[''] ?? NfseEmissorStub::class;
        if (!is_string($class) || $class === '' || !class_exists($class)) {
            return new NfseEmissorStub();
        }
        try {
            $obj = new $class();
        } catch (\Throwable $e) {
            return new NfseEmissorStub();
        }
        if (!$obj instanceof NfseEmissorInterface) {
            return new NfseEmissorStub();
        }

        return $obj;
    }

    /**
     * @param array<string, mixed> $configFiscal
     */
    public static function isStubOnly(array $configFiscal): bool {
        return self::forConfig($configFiscal) instanceof NfseEmissorStub;
    }
}
