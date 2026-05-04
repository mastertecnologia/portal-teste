<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produtos');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->hasMany('LaudosProdutoImagens', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoImagens.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('LaudosProdutoPecas', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoPecas.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('LaudosProdutoServicos', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoServicos.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('parecer_id')->notEmptyString('parecer_id')
            ->scalar('nome')->maxLength('nome', 200)->allowEmptyString('nome')
            ->scalar('tipo')->maxLength('tipo', 50)->allowEmptyString('tipo')
            ->inList('recomendacao', ['repair', 'replace', 'partial']);
        return $validator;
    }
}
