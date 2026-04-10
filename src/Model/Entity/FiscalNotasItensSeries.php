<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasItensSeries extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_item_id' => true,
        'numero_serie' => true,
        'created' => true,
        'modified' => true,
        'fiscal_nota_item' => true,
    ];
}
