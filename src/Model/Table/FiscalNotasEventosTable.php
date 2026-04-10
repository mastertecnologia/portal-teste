<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasEventosTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_eventos');
        $this->setDisplayField('tipo_evento');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('FiscalNotas', ['foreignKey' => 'fiscal_nota_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
    }

    public function validationDefault(Validator $validator) {
        $validator
            ->notEmpty('tipo_evento', 'Tipo de evento é obrigatório')
            ->inList('tipo_evento', ['cancelamento', 'carta_correcao', 'inutilizacao', 'manifestacao'])
            ->allowEmptyString('motivo', function ($context) {
                if (($context['data']['tipo_evento'] ?? '') !== 'manifestacao') {
                    return false;
                }

                return !in_array((string)($context['data']['codigo_evento'] ?? ''), ['210220', '210240'], true);
            })
            ->add('motivo', 'manifest_just_min', [
                'rule' => function ($value, $context) {
                    if (($context['data']['tipo_evento'] ?? '') !== 'manifestacao') {
                        return true;
                    }
                    if (!in_array((string)($context['data']['codigo_evento'] ?? ''), ['210220', '210240'], true)) {
                        return true;
                    }

                    return strlen(trim((string)$value)) >= 15;
                },
                'message' => 'Para desconhecimento ou operação não realizada, use justificativa com pelo menos 15 caracteres.',
            ]);

        return $validator;
    }

    /**
     * Próximo número de sequência para evento de uma nota.
     */
    public function proximaSequencia($fiscalNotaId) {
        $max = $this->find()
            ->where(['fiscal_nota_id' => $fiscalNotaId])
            ->select(['max_seq' => $this->find()->func()->max('sequencia')])
            ->disableHydration()
            ->first();
        return ($max['max_seq'] ?? 0) + 1;
    }

    /**
     * Sequência por tipo de evento SEFAZ (ex.: 210200) — exigido na manifestação do destinatário.
     *
     * @param int|string $fiscalNotaId
     * @param string $codigoEvento ex.: 210200
     */
    public function proximaSequenciaPorCodigoEvento($fiscalNotaId, $codigoEvento) {
        $max = $this->find()
            ->where([
                'fiscal_nota_id' => $fiscalNotaId,
                'codigo_evento' => (string)$codigoEvento,
            ])
            ->select(['max_seq' => $this->find()->func()->max('sequencia')])
            ->disableHydration()
            ->first();

        return (int)($max['max_seq'] ?? 0) + 1;
    }
}
