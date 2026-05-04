<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoServicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_servicos');
        $this->setDisplayField('descricao');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
        $this->belongsTo('LaudosCatalogoServicos', ['foreignKey' => 'catalogo_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('descricao')->maxLength('descricao', 300)->notEmptyString('descricao')
            ->numeric('horas')
            ->numeric('valor_hora');
        return $validator;
    }
}
