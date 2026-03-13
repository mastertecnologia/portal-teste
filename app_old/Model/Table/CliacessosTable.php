<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CliacessosTable extends Table
{
    public function validationDefault(Validator $validator)
    {
        return $validator->notEmpty('nome', 'O nome é obrigatório!');
    }
}
