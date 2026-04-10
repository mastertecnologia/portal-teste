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
        'sped_contabilista_nome' => true,
        'sped_contabilista_cpf' => true,
        'sped_contabilista_crc' => true,
        'sped_contabilista_cnpj' => true,
        'sped_contabilista_cep' => true,
        'sped_contabilista_logradouro' => true,
        'sped_contabilista_numero' => true,
        'sped_contabilista_complemento' => true,
        'sped_contabilista_bairro' => true,
        'sped_contabilista_fone' => true,
        'sped_contabilista_fax' => true,
        'sped_contabilista_email' => true,
        'sped_contabilista_cod_municipio' => true,
        'sped_inventario_declarar' => true,
        'sped_inventario_dt_inv' => true,
        'sped_inventario_mot_inv' => true,
        'sped_inventario_itens_json' => true,
        'sped_e111_ajustes_json' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'fiscal_certificado' => true,
    ];

    protected $_hidden = ['nfse_senha', 'csc_token'];
}
