<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosAnexosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_anexos');
        $this->setDisplayField('nome_original');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->belongsTo('CreatedBy', ['className' => 'Users', 'foreignKey' => 'created_by']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('parecer_id')->notEmptyString('parecer_id')
            ->scalar('nome_original')->maxLength('nome_original', 255)->notEmptyString('nome_original')
            ->scalar('file_path')->maxLength('file_path', 500)->notEmptyString('file_path');
        return $validator;
    }
}
