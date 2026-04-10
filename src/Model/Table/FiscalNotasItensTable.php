<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasItensTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_itens');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotas', ['foreignKey' => 'fiscal_nota_id']);
        $this->hasMany('FiscalNotasImpostos', [
            'foreignKey' => 'fiscal_nota_item_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('FiscalNotasItensSeries', [
            'foreignKey' => 'fiscal_nota_item_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('descricao', 'Descrição do item é obrigatória')
            ->notEmpty('cfop', 'CFOP é obrigatório')
            ->notEmpty('unidade', 'Unidade é obrigatória')
            ->greaterThan('quantidade', 0, 'Quantidade deve ser maior que zero')
            ->greaterThan('valor_unitario', 0, 'Valor unitário deve ser maior que zero')
            ->decimal('valor_total');
    }
}
