<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosCatalogoPecasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_catalogo_pecas');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('nome')->maxLength('nome', 200)->notEmptyString('nome')
            ->numeric('preco_default');
        return $validator;
    }

    /**
     * Busca peças do catálogo com filtro de texto.
     */
    public function buscar(int $empresaId, ?string $q = null, int $limit = 50): array
    {
        $query = $this->find()
            ->where(['empresa_id' => $empresaId, 'ativo' => true])
            ->order(['categoria' => 'ASC', 'nome' => 'ASC'])
            ->limit($limit);

        if ($q) {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where([
                'OR' => [
                    'nome ILIKE' => $like,
                    'codigo ILIKE' => $like,
                ],
            ]);
        }

        return $query->all()->toArray();
    }
}
