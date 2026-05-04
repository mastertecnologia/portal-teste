<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosEmpresasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_empresas');
        $this->setDisplayField('razao_social');
        $this->addBehavior('Timestamp');

        $this->hasMany('LaudosCatalogoPecas', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosCatalogoServicos', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosTemplates', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosPareceres', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('razao_social')->maxLength('razao_social', 200)->notEmptyString('razao_social')
            ->scalar('cnpj')->maxLength('cnpj', 18)->notEmptyString('cnpj')
            ->email('email', false)->allowEmptyString('email');
        return $validator;
    }
}
