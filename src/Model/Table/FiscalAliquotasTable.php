<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalAliquotasTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_aliquotas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas')->setForeignKey('idempresa');
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('uf_origem', 'UF origem obrigatória')
            ->notEmpty('uf_destino', 'UF destino obrigatória')
            ->maxLength('uf_origem', 2)
            ->maxLength('uf_destino', 2);
    }

    /**
     * Busca a alíquota para a combinação UF origem/destino e NCM.
     * Prioridade: regra específica por NCM > regra genérica da UF.
     */
    public function getAliquota($idempresa, $ufOrigem, $ufDestino, $ncm = null) {
        // Tenta regra específica por NCM
        if ($ncm) {
            $especifica = $this->find()
                ->where([
                    'idempresa' => $idempresa,
                    'uf_origem' => $ufOrigem,
                    'uf_destino' => $ufDestino,
                    'ncm_codigo' => $ncm,
                ])
                ->first();
            if ($especifica) {
                return $especifica;
            }
        }
        // Regra genérica (sem NCM)
        return $this->find()
            ->where([
                'idempresa' => $idempresa,
                'uf_origem' => $ufOrigem,
                'uf_destino' => $ufDestino,
                'ncm_codigo IS' => null,
            ])
            ->first();
    }
}
