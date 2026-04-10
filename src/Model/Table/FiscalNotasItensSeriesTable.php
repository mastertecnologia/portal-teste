<?php
namespace App\Model\Table;

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasItensSeriesTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_itens_series');
        $this->setDisplayField('numero_serie');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotasItens', [
            'foreignKey' => 'fiscal_nota_item_id',
            'propertyName' => 'fiscal_nota_item',
        ]);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('numero_serie', 'Informe o número de série')
            ->maxLength('numero_serie', 120);
    }

    /**
     * Linhas de série com nota e item (controle / busca).
     */
    public function findControlePorEmpresa($idempresa, array $filters = []) {
        $query = $this->find()
            ->matching('FiscalNotasItens.FiscalNotas', function ($q) use ($idempresa, $filters) {
                $q = $q->where(['FiscalNotas.idempresa' => $idempresa]);
                if (isset($filters['tipo_operacao']) && $filters['tipo_operacao'] !== '' && $filters['tipo_operacao'] !== null) {
                    $q->where(['FiscalNotas.tipo_operacao' => (int)$filters['tipo_operacao']]);
                }
                if (!empty($filters['data_inicio'])) {
                    $q->where(['FiscalNotas.data_emissao >=' => $filters['data_inicio']]);
                }
                if (!empty($filters['data_fim'])) {
                    $q->where(['FiscalNotas.data_emissao <=' => $filters['data_fim'] . ' 23:59:59']);
                }
                return $q;
            })
            ->contain([
                'FiscalNotasItens' => [
                    'fields' => ['id', 'fiscal_nota_id', 'numero_item', 'codigo_produto', 'descricao', 'ncm', 'quantidade'],
                    'FiscalNotas' => [
                        'fields' => ['id', 'idempresa', 'modelo', 'serie', 'numero', 'chave_acesso', 'data_emissao', 'status', 'tipo_operacao', 'valor_total'],
                        'Clientes' => ['fields' => ['id', 'razaosocial', 'nome']],
                    ],
                ],
            ]);

        if (!empty($filters['numero_serie'])) {
            $query->where(FiscalSqlConditions::caseInsensitiveLike(
                $this->getConnection(),
                'FiscalNotasItensSeries.numero_serie',
                '%' . $filters['numero_serie'] . '%'
            ));
        }
        if (!empty($filters['codigo_produto'])) {
            $conn = $this->getConnection();
            $codPat = '%' . $filters['codigo_produto'] . '%';
            $query->innerJoinWith('FiscalNotasItens', function ($q) use ($conn, $codPat) {
                return $q->where(FiscalSqlConditions::caseInsensitiveLike(
                    $conn,
                    'FiscalNotasItens.codigo_produto',
                    $codPat
                ));
            });
        }

        return $query
            ->distinct(true)
            ->order(['FiscalNotasItensSeries.modified' => 'DESC', 'FiscalNotasItensSeries.id' => 'DESC']);
    }
}
