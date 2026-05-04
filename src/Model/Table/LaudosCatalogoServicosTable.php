<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosCatalogoServicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_catalogo_servicos');
        $this->setDisplayField('descricao');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('descricao')->maxLength('descricao', 300)->notEmptyString('descricao')
            ->numeric('valor_hora_default');
        return $validator;
    }

    /**
     * Busca serviços do catálogo com filtro de texto.
     */
    public function buscar(int $empresaId, ?string $q = null, int $limit = 50): array
    {
        $query = $this->find()
            ->where(['empresa_id' => $empresaId, 'ativo' => true])
            ->order(['categoria' => 'ASC', 'descricao' => 'ASC'])
            ->limit($limit);

        if ($q) {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where(['descricao ILIKE' => $like]);
        }

        return $query->all()->toArray();
    }
}
