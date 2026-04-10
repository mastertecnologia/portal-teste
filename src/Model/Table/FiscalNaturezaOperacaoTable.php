<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNaturezaOperacaoTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_natureza_operacao');
        $this->setDisplayField('descricao');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas')->setForeignKey('idempresa');
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('codigo', 'Código obrigatório')
            ->notEmpty('descricao', 'Descrição obrigatória')
            ->inList('tipo', ['entrada', 'saida'], 'Tipo deve ser entrada ou saida');
    }

    public function listByEmpresa($idempresa, $tipo = null) {
        $conditions = ['idempresa' => $idempresa, 'ativo' => true];
        if ($tipo) {
            $conditions['tipo'] = $tipo;
        }
        return $this->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
            ->where($conditions)
            ->order(['descricao' => 'ASC'])
            ->toArray();
    }
}
