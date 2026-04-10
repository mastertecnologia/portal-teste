<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasPagamento extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_id' => true,
        'forma_pagamento' => true,
        'valor' => true,
        'bandeira_cartao' => true,
        'cnpj_operadora' => true,
        'autorizacao' => true,
        'numero_parcelas' => true,
        'created' => true,
        'modified' => true,
        'fiscal_nota' => true,
    ];
}
