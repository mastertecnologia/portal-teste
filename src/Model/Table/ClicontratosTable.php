<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ClicontratosTable extends Table
{
    public function initialize(array $config) {
        // Coluna física é idcliente; o padrão Cake infere cliente_id e quebra o JOIN.
        $this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
    }
}
