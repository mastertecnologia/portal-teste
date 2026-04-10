<?php
namespace App\Model\Table;

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNcmTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_ncm');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('codigo', 'Código NCM obrigatório')
            ->notEmpty('descricao', 'Descrição obrigatória')
            ->maxLength('codigo', 10);
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['codigo'], 'Este código NCM já existe.'));
        return $rules;
    }

    public function buscar($termo) {
        $or = [
            'codigo LIKE' => "%{$termo}%",
        ];
        $or = array_merge(
            $or,
            FiscalSqlConditions::caseInsensitiveLike($this->getConnection(), 'descricao', "%{$termo}%")
        );

        return $this->find()
            ->where(['OR' => $or])
            ->limit(20)
            ->toArray();
    }
}
