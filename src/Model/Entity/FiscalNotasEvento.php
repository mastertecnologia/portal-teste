<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalNotasEvento extends Entity {

    protected $_accessible = [
        'id' => false,
        'fiscal_nota_id' => true,
        'tipo_evento' => true,
        'sequencia' => true,
        'codigo_evento' => true,
        'motivo' => true,
        'protocolo' => true,
        'data_evento' => true,
        'status' => true,
        'codigo_retorno' => true,
        'mensagem_retorno' => true,
        'user_id' => true,
        'created' => true,
        'modified' => true,
        'fiscal_nota' => true,
        'user' => true,
    ];
}
