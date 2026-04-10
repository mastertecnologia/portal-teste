<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasXml extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_id' => true,
        'tipo' => true,
        'xml_envio' => true,
        'xml_retorno' => true,
        'created' => true,
        'fiscal_nota' => true,
    ];
}
