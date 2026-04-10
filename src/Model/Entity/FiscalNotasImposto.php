<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasImposto extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_item_id' => true,
        'imposto' => true,
        'base_calculo' => true,
        'aliquota' => true,
        'valor' => true,
        'reducao_base' => true,
        'mva' => true,
        'valor_retido' => true,
        'created' => true,
        'modified' => true,
        'fiscal_notas_item' => true,
    ];
}
