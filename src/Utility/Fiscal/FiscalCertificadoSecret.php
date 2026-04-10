<?php
namespace App\Utility\Fiscal;

use App\Utility\VaultCrypto;

/**
 * Senha do certificado A1 em repouso (VaultCrypto / legado PGM) e bytes do PFX para o FiscalSigner.
 */
class FiscalCertificadoSecret {

    /**
     * @param string $plain
     * @param int|string $idempresa
     * @return string
     */
    public static function encryptForStorage($plain, $idempresa) {
        $plain = (string)$plain;
        if ($plain === '') {
            return '';
        }

        return VaultCrypto::encrypt($plain, $idempresa);
    }

    /**
     * @param string|null $stored
     * @param int|string $idempresa
     * @return string
     */
    public static function decryptForUse($stored, $idempresa) {
        if ($stored === null || $stored === '') {
            return '';
        }

        return VaultCrypto::decrypt((string)$stored, $idempresa);
    }

    /**
     * @param object $certificado entidade com arquivo_pfx (resource|string)
     * @return string
     */
    public static function pfxBytes($certificado) {
        $pfx = $certificado->arquivo_pfx ?? '';
        if (is_resource($pfx)) {
            rewind($pfx);

            return (string)stream_get_contents($pfx);
        }

        return (string)$pfx;
    }
}
