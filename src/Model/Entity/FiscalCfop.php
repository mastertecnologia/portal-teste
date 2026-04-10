<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalCfop extends Entity {

    protected $_accessible = [
        'id' => false,
        'codigo' => true,
        'descricao' => true,
        'tipo' => true,
        'aplicacao' => true,
    ];
}
