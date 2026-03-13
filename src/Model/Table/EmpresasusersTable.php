<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Security;

class EmpresasusersTable extends Table{

	public function initialize(array $config){
		$this->belongsTo('Empresas')->setForeignKey('idempresa')->setDependent(true);
		$this->belongsTo('Users')->setForeignKey('iduser')->setDependent(true);
	}

	public function selectAllRelacoes() {
		return $this->find('all', ['fields' => ['id', 'idempresa', 'iduser']])->toArray();
	}
		
	public function findByIdempresa($idempresa) {
		return $this->find('all', ['fields' => ['id', 'idempresa', 'iduser']])->where(['idempresa' => $idempresa])->toArray();
	}
		
	public function findByIduser($iduser) {
		return $this->find('all', ['fields' => ['id', 'idempresa', 'iduser']])->where(['iduser' => $iduser])->toArray();
	}
}
