<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrcamentosmovsTable extends Table {
    public function initialize(array $config) {
        $this->setTable('orcamentosnovosdesmovs');
        $this->table('orcamentosnovosdesmovs');
        $this->belongsTo('Orcamentos')->setForeignKey('idorcamento')->setDependent(false);
        $this->belongsTo('Users')->setForeignKey('idusuario')->setDependent(false);
    }
}