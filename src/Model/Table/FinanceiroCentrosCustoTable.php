<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class FinanceiroCentrosCustoTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('financeiro_centros_custo');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas', ['foreignKey' => 'idempresa']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('codigo', 'Código obrigatório')
            ->maxLength('codigo', 20)
            ->notEmpty('descricao', 'Descrição obrigatória');
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['idempresa', 'codigo'], 'Código já existe nesta empresa.'));
        return $rules;
    }

    public function listByEmpresa(int $idempresa): array {
        return $this->find('list', ['keyField' => 'id', 'valueField' => function ($row) {
            return $row->codigo . ' — ' . $row->descricao;
        }])
            ->where(['idempresa' => $idempresa, 'ativo' => true])
            ->order(['codigo' => 'ASC'])
            ->toArray();
    }
}
