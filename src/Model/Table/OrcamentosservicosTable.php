<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrcamentosservicosTable extends Table {
    public function initialize(array $config) {
        $this->setTable('orcamentosnovosdesservicos');
        $this->table('orcamentosnovosdesservicos');
        $this->belongsTo('Orcamentos')->setForeignKey('idorcamento')->setDependent(false);
    }
}