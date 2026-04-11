<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroRecorrentesTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('financeiro_recorrentes');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
        $this->belongsTo('FinanceiroPlanoContas', ['foreignKey' => 'plano_conta_id']);
        $this->belongsTo('FinanceiroCentrosCusto', ['foreignKey' => 'centro_custo_id']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('descricao', 'Descrição obrigatória')
            ->inList('tipo', ['receita', 'despesa'])
            ->decimal('valor')
            ->inList('frequencia', ['semanal', 'quinzenal', 'mensal', 'trimestral', 'anual'])
            ->range('dia_vencimento', [1, 28], 'Dia deve ser entre 1 e 28');
    }
}
