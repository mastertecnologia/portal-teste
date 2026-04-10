<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalNotasXmlsTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_notas_xmls');
        $this->setDisplayField('tipo');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', ['modified' => false]);

        $this->belongsTo('FiscalNotas', ['foreignKey' => 'fiscal_nota_id']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('tipo', 'Tipo de XML é obrigatório')
            ->inList('tipo', ['autorizacao', 'cancelamento', 'inutilizacao', 'carta_correcao', 'evento', 'distribuicao', 'dfe_import']);
    }
}
