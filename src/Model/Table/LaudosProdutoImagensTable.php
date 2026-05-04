<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoImagensTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_imagens');
        $this->setDisplayField('nome_original');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('file_path')->maxLength('file_path', 500)->notEmptyString('file_path');
        return $validator;
    }
}
