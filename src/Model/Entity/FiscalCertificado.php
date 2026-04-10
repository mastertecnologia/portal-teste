<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalCertificado extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'nome' => true,
        'tipo' => true,
        'arquivo_pfx' => true,
        'senha_hash' => true,
        'serial_number' => true,
        'cn_subject' => true,
        'cnpj_certificado' => true,
        'validade_inicio' => true,
        'validade_fim' => true,
        'ativo' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
    ];

    protected $_hidden = ['arquivo_pfx', 'senha_hash'];
}
