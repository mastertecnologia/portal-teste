<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasImpostosTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_impostos');
        $this->setDisplayField('imposto');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotasItens', ['foreignKey' => 'fiscal_nota_item_id']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('imposto', 'Tipo de imposto é obrigatório')
            ->inList('imposto', ['ICMS', 'ICMS_ST', 'IPI', 'PIS', 'COFINS', 'ISS', 'IRPJ', 'CSLL', 'II', 'DIFAL'])
            ->decimal('base_calculo')
            ->decimal('aliquota')
            ->decimal('valor');
    }
}
