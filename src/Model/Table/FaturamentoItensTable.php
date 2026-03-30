<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class FaturamentoItensTable extends Table {

	public function initialize(array $config) {
		$this->setTable('faturamento_itens');
		$this->belongsTo('Faturamento')->setForeignKey('idfaturamento')->setDependent(false);
	}
}
