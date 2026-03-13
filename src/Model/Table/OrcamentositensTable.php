<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrcamentositensTable extends Table {
    public function initialize(array $config) {
        $this->setTable('orcamentosnovositens');
        $this->table('orcamentosnovositens');
        $this->belongsTo('Orcamentos')->setForeignKey('idorcamento')->setDependent(false);
    }
}