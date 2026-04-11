<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FinanceiroExtratoBancarioTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('financeiro_extrato_bancario');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FinanceiroLancamentos', ['foreignKey' => 'financeiro_lancamento_id']);
    }
}
