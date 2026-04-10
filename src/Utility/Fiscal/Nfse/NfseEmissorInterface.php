<?php
namespace App\Utility\Fiscal\Nfse;

/**
 * Contrato para emissão/cancelamento de NFS-e municipal (por prefeitura/provedor).
 * Implementações concretas podem usar webservices ABRASF, GINFES, etc.
 */
interface NfseEmissorInterface {

    /**
     * @param array $nota Dados da nota (FiscalNota + relações)
     * @param array $config FiscalEmpresasConfig + credenciais necessárias
     * @param array $empresa Cadastro da empresa emitente
     * @return array{success:bool, protocolo?:string, mensagem?:string}
     */
    public function emitir(array $nota, array $config, array $empresa): array;
}
