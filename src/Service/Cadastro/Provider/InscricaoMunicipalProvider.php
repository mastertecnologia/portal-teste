<?php
namespace App\Service\Cadastro\Provider;

/**
 * Inscrição Municipal (IM).
 * Stub: município sem integração por padrão; pode ser estendido com NFS-e/prefeitura.
 */
class InscricaoMunicipalProvider
{
    /**
     * Consulta IM por CNPJ + município + UF.
     * Retorno: ['numero' => string|null, 'situacao' => string] ou null se não implementado.
     */
    public function consultar(string $cnpj, string $municipio, string $uf): ?array
    {
        // NAO_IMPLEMENTADO: sem integração municipal no momento
        return null;
    }
}
