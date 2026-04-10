<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNaturezaOperacao extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'codigo' => true,
        'descricao' => true,
        'tipo' => true,
        'cfop_padrao' => true,
        'gera_financeiro' => true,
        'ativo' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
    ];
}
