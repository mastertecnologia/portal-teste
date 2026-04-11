<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroCentroCusto extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'codigo' => true,
        'descricao' => true,
        'ativo' => true,
    ];
}
