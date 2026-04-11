<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroPlanoContas extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'parent_id' => true,
        'codigo' => true,
        'descricao' => true,
        'tipo' => true,
        'natureza' => true,
        'ativo' => true,
    ];
}
