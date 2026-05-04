<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;

class LaudosPareceresTable extends Table
{
    public const STATUSES = [
        'rascunho' => 'Rascunho',
        'em_analise' => 'Em análise',
        'aprovado' => 'Aprovado',
        'concluido' => 'Concluído',
        'enviado' => 'Enviado',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_pareceres');
        $this->setDisplayField('numero');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', [
            'foreignKey' => 'empresa_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Tecnico', [
            'className' => 'Users',
            'foreignKey' => 'tecnico_user_id',
        ]);

        $this->belongsTo('CreatedByUser', [
            'className' => 'Users',
            'foreignKey' => 'created_by',
        ]);

        $this->belongsTo('Clientes', [
            'foreignKey' => 'requester_client_id',
        ]);

        $this->hasMany('LaudosProdutos', [
            'foreignKey' => 'parecer_id',
            'sort' => ['LaudosProdutos.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasMany('LaudosAnexos', [
            'foreignKey' => 'parecer_id',
            'dependent' => true,
        ]);

        $this->hasMany('LaudosHistorico', [
            'foreignKey' => 'parecer_id',
            'sort' => ['LaudosHistorico.created' => 'DESC'],
            'dependent' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('empresa_id')
            ->notEmptyString('empresa_id');

        $validator
            ->scalar('numero')
            ->maxLength('numero', 20)
            ->requirePresence('numero', 'create')
            ->notEmptyString('numero');

        $validator
            ->scalar('titulo')
            ->maxLength('titulo', 200)
            ->notEmptyString('titulo');

        $validator
            ->scalar('public_hash')
            ->maxLength('public_hash', 20)
            ->requirePresence('public_hash', 'create');

        $validator
            ->inList('status', array_keys(self::STATUSES))
            ->notEmptyString('status');

        $validator
            ->email('requester_email', false)
            ->allowEmptyString('requester_email');

        $validator
            ->date('data_emissao')
            ->allowEmptyDate('data_emissao');

        $validator
            ->numeric('estimated_new_equipment')
            ->allowEmptyString('estimated_new_equipment');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['public_hash']), ['errorField' => 'public_hash']);
        return $rules;
    }

    /**
     * Gera próximo número usando função PostgreSQL atomic.
     */
    public function gerarProximoNumero(int $empresaId): string
    {
        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute('SELECT laudos_proximo_numero(?) AS numero', [$empresaId]);
        $row = $stmt->fetch('assoc');
        return $row['numero'];
    }

    /**
     * Gera hash público para validação via QR Code.
     */
    public function gerarHashPublico(): string
    {
        do {
            $hash = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
            $exists = $this->find()->where(['public_hash' => $hash])->count();
        } while ($exists > 0);
        return $hash;
    }

    /**
     * Aplica filtros para listagem.
     */
    public function findFiltered(Query $query, array $options = []): Query
    {
        $query->contain(['LaudosEmpresas', 'Tecnico']);
        $query->where(['LaudosPareceres.deleted IS' => null]);

        if (!empty($options['status']) && $options['status'] !== 'all') {
            $query->where(['LaudosPareceres.status' => $options['status']]);
        }

        if (!empty($options['q'])) {
            $q = '%' . str_replace(' ', '%', $options['q']) . '%';
            $query->where([
                'OR' => [
                    'LaudosPareceres.titulo ILIKE' => $q,
                    'LaudosPareceres.numero ILIKE' => $q,
                    'LaudosPareceres.requester_company_name ILIKE' => $q,
                    'LaudosPareceres.requester_cnpj ILIKE' => $q,
                ],
            ]);
        }

        if (!empty($options['empresa_id'])) {
            $query->where(['LaudosPareceres.empresa_id' => $options['empresa_id']]);
        }

        if (!empty($options['tecnico_user_id'])) {
            $query->where(['LaudosPareceres.tecnico_user_id' => $options['tecnico_user_id']]);
        }

        return $query->order(['LaudosPareceres.modified' => 'DESC']);
    }

    /**
     * Carrega parecer completo com todos os relacionamentos.
     */
    public function getCompleto(int $id): ?\Cake\Datasource\EntityInterface
    {
        return $this->find()
            ->where(['LaudosPareceres.id' => $id, 'LaudosPareceres.deleted IS' => null])
            ->contain([
                'LaudosEmpresas',
                'Tecnico',
                'Clientes',
                'LaudosProdutos' => function ($q) {
                    return $q->contain([
                        'LaudosProdutoImagens' => function ($qi) {
                            return $qi->order(['LaudosProdutoImagens.ordem' => 'ASC']);
                        },
                        'LaudosProdutoPecas' => function ($qp) {
                            return $qp->order(['LaudosProdutoPecas.ordem' => 'ASC']);
                        },
                        'LaudosProdutoServicos' => function ($qs) {
                            return $qs->order(['LaudosProdutoServicos.ordem' => 'ASC']);
                        },
                    ])->order(['LaudosProdutos.ordem' => 'ASC']);
                },
                'LaudosAnexos',
                'LaudosHistorico' => function ($q) {
                    return $q->limit(50);
                },
            ])
            ->first();
    }

    /**
     * Calcula totais de um parecer.
     */
    public function calcularTotais(int $parecerId): array
    {
        $fallback = [
            'total_pecas' => 0,
            'total_servicos' => 0,
            'total_geral' => 0,
            'total_novo' => 0,
            'percentual_reparo' => null,
        ];
        try {
            $conn = ConnectionManager::get('default');
            $stmt = $conn->execute(
                'SELECT total_pecas, total_servicos, total_geral, total_novo, percentual_reparo
                 FROM laudos_totais_view WHERE parecer_id = ?',
                [$parecerId]
            );
            $row = $stmt->fetch('assoc');
        } catch (\Throwable $e) {
            $this->log('LaudosPareceres::calcularTotais: ' . $e->getMessage(), 'warning');

            return $fallback;
        }

        return $row ?: $fallback;
    }

    /**
     * Soft delete.
     */
    public function softDelete(int $id, int $userId): bool
    {
        $parecer = $this->get($id);
        $parecer->deleted = new \DateTime();
        $parecer->deleted_by = $userId;
        return (bool)$this->save($parecer);
    }
}
