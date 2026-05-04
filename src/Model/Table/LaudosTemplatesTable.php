<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosTemplatesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_templates');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->inList('tipo', ['diagnostico', 'conclusao', 'objetivo', 'documentacao'])
            ->scalar('nome')->maxLength('nome', 150)->notEmptyString('nome')
            ->scalar('conteudo')->notEmptyString('conteudo');
        return $validator;
    }

    /**
     * Retorna templates ativos de um tipo específico.
     */
    public function porTipo(int $empresaId, string $tipo): array
    {
        return $this->find()
            ->where(['empresa_id' => $empresaId, 'tipo' => $tipo, 'ativo' => true])
            ->order(['ordem' => 'ASC', 'nome' => 'ASC'])
            ->all()
            ->toArray();
    }
}
