<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalDfeRecebido extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'nsu_doc' => true,
        'schema' => true,
        'chave_acesso' => true,
        'tipo_documento' => true,
        'conteudo_hash' => true,
        'xml_conteudo' => true,
        'status' => true,
        'fiscal_nota_id' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
        'fiscal_nota' => true,
    ];
}
