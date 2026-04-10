<?php
namespace App\Model\Table;

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class FiscalNotasTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas')->setForeignKey('idempresa');
        $this->belongsTo('Clientes')->setForeignKey('idcliente');
        $this->belongsTo('Users')->setForeignKey('user_id');
        $this->belongsTo('FiscalNaturezaOperacao', ['foreignKey' => 'natureza_operacao_id']);

        $this->hasMany('FiscalNotasItens', [
            'foreignKey' => 'fiscal_nota_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('FiscalNotasPagamentos', [
            'foreignKey' => 'fiscal_nota_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('FiscalNotasEventos', [
            'foreignKey' => 'fiscal_nota_id',
            'dependent' => true,
        ]);
        $this->hasMany('FiscalNotasXmls', [
            'foreignKey' => 'fiscal_nota_id',
            'dependent' => true,
        ]);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('idempresa', 'Empresa é obrigatória')
            ->notEmpty('modelo', 'Modelo do documento é obrigatório')
            ->inList('modelo', ['55', '65', 'NFSE'], 'Modelo inválido')
            ->notEmpty('natureza_operacao', 'Natureza da operação é obrigatória')
            ->allowEmpty('chave_acesso')
            ->decimal('valor_total');
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->existsIn(['idempresa'], 'Empresas'));
        return $rules;
    }

    /**
     * Busca notas por empresa com filtros.
     */
    public function findByEmpresa($idempresa, array $filters = []) {
        $query = $this->find()
            ->where(['FiscalNotas.idempresa' => $idempresa])
            ->contain([
                'Clientes' => ['fields' => ['id', 'razaosocial', 'cnpj', 'nome']],
                'Users' => ['fields' => ['id', 'name']],
            ])
            ->order(['FiscalNotas.created' => 'DESC']);

        if (!empty($filters['status'])) {
            $query->where(['FiscalNotas.status' => $filters['status']]);
        }
        if (!empty($filters['modelo'])) {
            $query->where(['FiscalNotas.modelo' => $filters['modelo']]);
        }
        if (!empty($filters['idcliente'])) {
            $query->where(['FiscalNotas.idcliente' => $filters['idcliente']]);
        }
        if (!empty($filters['data_inicio'])) {
            $query->where(['FiscalNotas.data_emissao >=' => $filters['data_inicio']]);
        }
        if (!empty($filters['data_fim'])) {
            $query->where(['FiscalNotas.data_emissao <=' => $filters['data_fim']]);
        }
        if (!empty($filters['numero'])) {
            $query->where(['FiscalNotas.numero' => $filters['numero']]);
        }
        if (isset($filters['tipo_operacao']) && $filters['tipo_operacao'] !== '' && $filters['tipo_operacao'] !== null) {
            $query->where(['FiscalNotas.tipo_operacao' => (int)$filters['tipo_operacao']]);
        }
        if (!empty($filters['numero_serie'])) {
            $serie = $filters['numero_serie'];
            $conn = $this->getConnection();
            $query->matching('FiscalNotasItens.FiscalNotasItensSeries', function ($q) use ($serie, $conn) {
                return $q->where(FiscalSqlConditions::caseInsensitiveLike(
                    $conn,
                    'FiscalNotasItensSeries.numero_serie',
                    '%' . $serie . '%'
                ));
            })
                ->distinct(true);
        }

        return $query;
    }

    /**
     * Totais por status (dashboard).
     */
    public function totaisPorStatus($idempresa) {
        return $this->find()
            ->select([
                'status' => 'FiscalNotas.status',
                'total' => $this->find()->func()->count('*'),
                'valor' => $this->find()->func()->sum('valor_total'),
            ])
            ->where(['FiscalNotas.idempresa' => $idempresa])
            ->group('FiscalNotas.status')
            ->disableHydration()
            ->toArray();
    }
}
