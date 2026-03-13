<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdemmovsTable extends Table {
    public function initialize(array $config) {
        $this->belongsTo('Ordensservicos')->setForeignKey('idordem')->setDependent(false);
        $this->belongsTo('Users')->setForeignKey('iduser')->setDependent(false);
    }
}