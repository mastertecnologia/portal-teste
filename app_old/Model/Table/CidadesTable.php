<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CidadesTable extends Table
{
    public function validationDefault(Validator $validator) {
    }

	public function initialize(array $config) {
        $this->setdisplayField('nome');
		$this->belongsTo('Estados')->setForeignKey('idestado');
    }

    public function selectAllByEstado($idestado) {
        return $this->find('all', ['fields' => ['id', 'nome']])->where(['idestado' => $idestado])->toArray();
    }
}
