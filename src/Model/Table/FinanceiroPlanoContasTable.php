<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class FinanceiroPlanoContasTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('financeiro_plano_contas');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('ParentFinanceiroPlanoContas', [
            'className' => 'FinanceiroPlanoContas',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildFinanceiroPlanoContas', [
            'className' => 'FinanceiroPlanoContas',
            'foreignKey' => 'parent_id',
        ]);
        $this->belongsTo('Empresas', ['foreignKey' => 'idempresa']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('codigo', 'Código obrigatório')
            ->maxLength('codigo', 20)
            ->notEmpty('descricao', 'Descrição obrigatória')
            ->inList('tipo', ['receita', 'despesa'], 'Tipo deve ser receita ou despesa')
            ->inList('natureza', ['sintetica', 'analitica'], 'Natureza inválida');
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['idempresa', 'codigo'], 'Código já existe nesta empresa.'));
        return $rules;
    }

    /**
     * Retorna lista hierárquica para select (código — descrição).
     */
    public function listByEmpresa(int $idempresa, ?string $tipo = null, bool $apenasAnalitica = false): array {
        $conditions = ['idempresa' => $idempresa, 'ativo' => true];
        if ($tipo !== null) {
            $conditions['tipo'] = $tipo;
        }
        if ($apenasAnalitica) {
            $conditions['natureza'] = 'analitica';
        }

        $rows = $this->find()
            ->where($conditions)
            ->order(['codigo' => 'ASC'])
            ->toArray();

        $list = [];
        foreach ($rows as $r) {
            $prefix = $r->natureza === 'sintetica' ? '▸ ' : '  ';
            $list[$r->id] = $prefix . $r->codigo . ' — ' . $r->descricao;
        }
        return $list;
    }
}
