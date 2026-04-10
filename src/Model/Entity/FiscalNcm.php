<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNcm extends Entity {

    protected $_accessible = [
        'id' => false,
        'codigo' => true,
        'descricao' => true,
        'aliquota_ipi' => true,
        'ex_tipi' => true,
    ];
}
