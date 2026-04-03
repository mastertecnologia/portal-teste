<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FaturasTable extends Table {
	public function initialize(array $config) {
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('iduser')->setDependent(false);
		$this->hasMany('Faturasitens')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Faturasitensrel')->setForeignKey('id')->setDependent(false);
		$this->hasMany('FaturasOrdensServico')->setForeignKey('idfatura')->setDependent(false);
		$this->hasOne('FaturasEscritaFiscal')->setForeignKey('idfatura')->setDependent(false);
	}

	public function hash() {
		$hashUnico = false;
		while ($hashUnico !== true) {
			$hash = sequenciaAleatoria(15);
			$faturaExiste = $this->findByHash($hash)->first();
			if(empty($faturaExiste)) $hashUnico = true;
		}

		return $hash;
	}
}
