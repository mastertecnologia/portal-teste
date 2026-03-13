<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Security;

class TicketcomentariosTable extends Table {
    public function initialize(array $config) {
        $this->belongsTo('Tickets')->setForeignKey('idticket')->setDependent(true);
        $this->belongsTo('Users')->setForeignKey('idautor')->setDependent(true);
    }
}
