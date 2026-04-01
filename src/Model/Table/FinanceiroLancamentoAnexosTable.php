<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FinanceiroLancamentoAnexosTable extends Table {

	public function initialize(array $config) {
		$this->setTable('financeiro_lancamento_anexos');
		$this->belongsTo('FinanceiroLancamentos', ['foreignKey' => 'idlancamento']);
		$this->belongsTo('Users', ['foreignKey' => 'iduser']);
	}
}
