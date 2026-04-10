<?php
namespace App\Utility\Fiscal\Nfse;

/**
 * Implementação de referência: bloqueia emissão até existir conector municipal configurado.
 */
class NfseEmissorStub implements NfseEmissorInterface {

    /**
     * Mensagem única para validação (FiscalValidator) e flash no controller.
     */
    public static function mensagemBloqueioEmissaoPortal(): string {
        return 'NFS-e municipal: a emissão não usa o webservice NF-e da SEFAZ. '
            . 'Configure um conector do município (provedor) ou emita pela prefeitura até a integração estar disponível.';
    }

    public function emitir(array $nota, array $config, array $empresa): array {
        return [
            'success' => false,
            'mensagem' => self::mensagemBloqueioEmissaoPortal(),
        ];
    }
}
