<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketsmovsTable extends Table
{
    public function initialize(array $config) {
        $this->belongsTo('tickets')->setForeignKey('idticket')->setDependent(false);
        $this->belongsTo('users')->setForeignKey('idusuario')->setDependent(false);
    }
}
