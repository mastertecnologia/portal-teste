<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalEmpresasConfig extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'regime_tributario' => true,
        'regime_normal_enquadramento' => true,
        'ambiente' => true,
        'serie_nfe' => true,
        'prox_numero_nfe' => true,
        'serie_nfse' => true,
        'prox_numero_nfse' => true,
        'serie_nfce' => true,
        'prox_numero_nfce' => true,
        'inscricao_estadual' => true,
        'inscricao_municipal' => true,
        'cnae_fiscal' => true,
        'codigo_municipio_ibge' => true,
        'uf' => true,
        'csc_id' => true,
        'csc_token' => true,
        'aliquota_simples' => true,
        'certificado_id' => true,
        'dfe_ult_nsu' => true,
        'nfse_provedor' => true,
        'nfse_usuario' => true,
        'nfse_senha' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'fiscal_certificado' => true,
    ];

    protected $_hidden = ['nfse_senha', 'csc_token'];
}
