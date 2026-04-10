<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalCfopTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_cfop');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('codigo', 'Código CFOP obrigatório')
            ->notEmpty('descricao', 'Descrição obrigatória')
            ->maxLength('codigo', 5)
            ->notEmpty('tipo', 'Informe se o CFOP é de entrada ou saída')
            ->inList('tipo', ['entrada', 'saida'], 'Tipo deve ser entrada ou saida');
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['codigo'], 'Este código CFOP já existe.'));
        return $rules;
    }

    public function listByTipo($tipo = 'saida') {
        return $this->find('list', [
                'keyField' => 'codigo',
                'valueField' => function ($e) {
                    return $e->codigo . ' — ' . $e->descricao;
                },
            ])
            ->where(['tipo' => $tipo])
            ->order(['codigo' => 'ASC'])
            ->toArray();
    }
}
