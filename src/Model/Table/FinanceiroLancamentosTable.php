<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FinanceiroLancamentosTable extends Table {

	public function initialize(array $config) {
		$this->setTable('financeiro_lancamentos');
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('idautor')->setDependent(false);
		$this->belongsTo('Faturamento')->setForeignKey('idfaturamento')->setDependent(false);
		$this->belongsTo('FinanceiroPlanoContas')->setForeignKey('plano_conta_id')->setDependent(false);
		$this->belongsTo('FinanceiroCentrosCusto')->setForeignKey('centro_custo_id')->setDependent(false);
		$this->hasMany('FinanceiroLancamentoAnexos', ['foreignKey' => 'idlancamento'])->setDependent(true);
	}
}
