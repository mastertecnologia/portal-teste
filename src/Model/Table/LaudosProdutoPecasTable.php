<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoPecasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_pecas');
        $this->setDisplayField('nome');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
        $this->belongsTo('LaudosCatalogoPecas', ['foreignKey' => 'catalogo_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('nome')->maxLength('nome', 200)->notEmptyString('nome')
            ->numeric('quantidade')
            ->numeric('preco_unitario');
        return $validator;
    }
}
