<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasPagamentosTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_pagamentos');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotas', ['foreignKey' => 'fiscal_nota_id']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('forma_pagamento', 'Forma de pagamento é obrigatória')
            ->greaterThan('valor', 0, 'Valor deve ser maior que zero')
            ->decimal('valor');
    }
}
