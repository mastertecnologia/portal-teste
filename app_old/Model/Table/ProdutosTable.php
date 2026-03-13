<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProdutosTable extends Table
{
    public function initialize(array $config) {
        $this->belongsTo('Empresas')->setForeignKey('idempresa');
    }
}
