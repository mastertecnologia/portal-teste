<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FinanceiroLancamentosTable extends Table {

	public function initialize(array $config) {
		$this->setTable('financeiro_lancamentos');
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('idautor')->setDependent(false);
		$this->belongsTo('Faturamento')->setForeignKey('idfaturamento')->setDependent(false);
	}
}
