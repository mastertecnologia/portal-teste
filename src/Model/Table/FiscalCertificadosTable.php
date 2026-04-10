<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalCertificadosTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_certificados');
        $this->setDisplayField('nome');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas')->setForeignKey('idempresa');
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('nome', 'Nome do certificado é obrigatório')
            ->inList('tipo', ['A1', 'A3'], 'Tipo deve ser A1 ou A3')
            ->notEmpty('idempresa', 'Empresa é obrigatória');
    }

    /**
     * Retorna o certificado ativo da empresa.
     */
    public function getAtivo($idempresa) {
        return $this->find()
            ->where(['idempresa' => $idempresa, 'ativo' => true])
            ->first();
    }

    /**
     * Verifica se o certificado está dentro da validade.
     */
    public function isValido($id) {
        $cert = $this->get($id);
        if (!$cert || !$cert->validade_fim) {
            return false;
        }
        return $cert->validade_fim > new \DateTime();
    }
}
