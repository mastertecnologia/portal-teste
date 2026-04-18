<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FinanceiroRetornoArquivosTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('financeiro_retorno_arquivos');
        $this->setDisplayField('nome_arquivo_original');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas', [
            'foreignKey' => 'idempresa',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('FinanceiroBancos', [
            'foreignKey' => 'financeiro_banco_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'usuario_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('FinanceiroRemessas', [
            'foreignKey' => 'financeiro_remessa_id',
            'joinType' => 'LEFT',
        ]);

        $this->hasMany('FinanceiroRetornoItens', [
            'foreignKey' => 'financeiro_retorno_arquivo_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmptyString('id', null, 'create');

        $validator
            ->integer('idempresa', 'Empresa inválida.')
            ->requirePresence('idempresa', 'create')
            ->notEmptyString('idempresa', 'Empresa obrigatória.');

        $validator
            ->integer('financeiro_banco_id', 'Banco inválido.')
            ->allowEmptyString('financeiro_banco_id');

        $validator
            ->integer('usuario_id', 'Usuário inválido.')
            ->allowEmptyString('usuario_id');

        $validator
            ->integer('financeiro_remessa_id', 'Remessa inválida.')
            ->allowEmptyString('financeiro_remessa_id');

        $validator
            ->scalar('nome_arquivo_original')
            ->maxLength(
                'nome_arquivo_original',
                255,
                'Nome do arquivo original deve ter no máximo 255 caracteres.'
            )
            ->requirePresence('nome_arquivo_original', 'create')
            ->notEmptyString('nome_arquivo_original', 'Nome do arquivo original obrigatório.');

        $validator
            ->scalar('nome_arquivo_salvo')
            ->maxLength(
                'nome_arquivo_salvo',
                255,
                'Nome do arquivo salvo deve ter no máximo 255 caracteres.'
            )
            ->allowEmptyString('nome_arquivo_salvo');

        $validator
            ->scalar('caminho_arquivo')
            ->maxLength(
                'caminho_arquivo',
                255,
                'Caminho do arquivo deve ter no máximo 255 caracteres.'
            )
            ->allowEmptyString('caminho_arquivo');

        $validator
            ->scalar('layout_cnab')
            ->maxLength(
                'layout_cnab',
                10,
                'Layout CNAB deve ter no máximo 10 caracteres.'
            )
            ->requirePresence('layout_cnab', 'create')
            ->notEmptyString('layout_cnab', 'Layout CNAB obrigatório.');

        $validator->add('layout_cnab', 'dominio', [
            'rule' => function ($value) {
                return in_array((string)$value, ['240', '400'], true);
            },
            'message' => 'Layout CNAB deve ser 240 ou 400.',
        ]);

        $validator
            ->scalar('status_processamento')
            ->maxLength(
                'status_processamento',
                30,
                'Status de processamento deve ter no máximo 30 caracteres.'
            )
            ->requirePresence('status_processamento', 'create')
            ->notEmptyString('status_processamento', 'Status de processamento obrigatório.');

        $validator->add('status_processamento', 'dominio', [
            'rule' => function ($value) {
                return in_array((string)$value, [
                    'processado',
                    'processado_parcial',
                    'erro',
                    'rejeitado',
                ], true);
            },
            'message' => 'Status de processamento inválido.',
        ]);

        $validator
            ->scalar('observacoes')
            ->allowEmptyString('observacoes');

        $validator
            ->integer('processados', 'Quantidade processada inválida.')
            ->requirePresence('processados', 'create')
            ->notEmptyString('processados', 'Quantidade processada obrigatória.')
            ->greaterThanOrEqual('processados', 0, 'Quantidade processada não pode ser negativa.');

        $validator
            ->integer('baixados', 'Quantidade baixada inválida.')
            ->requirePresence('baixados', 'create')
            ->notEmptyString('baixados', 'Quantidade baixada obrigatória.')
            ->greaterThanOrEqual('baixados', 0, 'Quantidade baixada não pode ser negativa.');

        $validator
            ->integer('rejeitados', 'Quantidade rejeitada inválida.')
            ->requirePresence('rejeitados', 'create')
            ->notEmptyString('rejeitados', 'Quantidade rejeitada obrigatória.')
            ->greaterThanOrEqual('rejeitados', 0, 'Quantidade rejeitada não pode ser negativa.');

        $validator
            ->integer('ignorados', 'Quantidade ignorada inválida.')
            ->requirePresence('ignorados', 'create')
            ->notEmptyString('ignorados', 'Quantidade ignorada obrigatória.')
            ->greaterThanOrEqual('ignorados', 0, 'Quantidade ignorada não pode ser negativa.');

        $validator
            ->integer('erros', 'Quantidade de erros inválida.')
            ->requirePresence('erros', 'create')
            ->notEmptyString('erros', 'Quantidade de erros obrigatória.')
            ->greaterThanOrEqual('erros', 0, 'Quantidade de erros não pode ser negativa.');

        $validator
            ->dateTime('data_processamento', 'Data de processamento inválida.')
            ->requirePresence('data_processamento', 'create')
            ->notEmptyDateTime('data_processamento', 'Data de processamento obrigatória.');

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['idempresa'], 'Empresas'), [
            'errorField' => 'idempresa',
            'message' => 'Empresa informada não existe.',
        ]);

        $rules->add(
            function ($entity) {
                if (empty($entity->financeiro_banco_id)) {
                    return true;
                }

                return $this->FinanceiroBancos->exists([
                    'id' => (int)$entity->financeiro_banco_id,
                ]);
            },
            'bancoExiste',
            [
                'errorField' => 'financeiro_banco_id',
                'message' => 'Banco informado não existe.',
            ]
        );

        $rules->add(
            function ($entity) {
                if (empty($entity->usuario_id)) {
                    return true;
                }

                return $this->Users->exists([
                    'id' => (int)$entity->usuario_id,
                ]);
            },
            'usuarioExiste',
            [
                'errorField' => 'usuario_id',
                'message' => 'Usuário informado não existe.',
            ]
        );

        $rules->add(
            function ($entity) {
                if (empty($entity->financeiro_remessa_id)) {
                    return true;
                }

                return $this->FinanceiroRemessas->exists([
                    'id' => (int)$entity->financeiro_remessa_id,
                ]);
            },
            'remessaExiste',
            [
                'errorField' => 'financeiro_remessa_id',
                'message' => 'Remessa informada não existe.',
            ]
        );

        return $rules;
    }
}
