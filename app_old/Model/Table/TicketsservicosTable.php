<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketsservicosTable extends Table
{
    public function initialize(array $config) {
        $this->belongsTo('Tickets')->setForeignKey('id')->setDependent(true);
        $this->belongsTo('Servicos')->setForeignKey('id')->setDependent(true);
    }
}
